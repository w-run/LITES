<?php

namespace app\user;


use core\lib\Data;
use core\lib\Date;
use core\lib\Session;
use core\lib\Token;

class User
{
    private $rows = array("uid", "usr", "pwd", "IFNULL(avatar,'temp/avatar.png') as avatar", "IFNULL(nickname,usr) as nickname",
        "phone", "gender", "birth", "ugid", "area_id", "wechat_openid", "wechat_unionid",
        "last_area", "last_time", "reg_area", "reg_time", "state");

    public function add($data)
    {
        $attr = array();
        $values = array();
        $index = 0;
        foreach ($data as $key => $value) {
            $attr[$index] = $key;
            $values[$index] = $value;
            $index++;
        }
        $time = Date::cur_time();
        $area_id = array_key_exists("area_id", $data) ? $data['area_id'] : "0";
        array_push($attr, "ugid", "last_time", "reg_time", "last_area", "reg_area");
        array_push($values, 1, $time, $time, $area_id, $area_id);
        $dao = new Data("user");
        $res = $dao->add($attr, $values);
        return $res == 1;
    }


    public function get($uid)
    {
        $dao = new Data("user");
        $show_factor = "uid=$uid";
        $res = $dao->get($show_factor, $this->rows);
        if (count($res) == 0)
            return null;
        return $res[0];
    }


    public function get_list($where = null, $rows = null)
    {
        $dao = new Data("user");
        $res = $dao->get($where, $rows == null ? $this->rows : $rows, null, "uid ASC");
        return $res;
    }


    public function get_list_search($keywords, $p = 0, $s = 10)
    {
        $start = 0;
        if ($p > 0) {
            $start = $p * $s;
        }
        $k_arr = explode(" ", trim($keywords));
        $i = 0;
        $order_arr = [];
        foreach ($k_arr as $item) {
            $order_arr[$i++] = "LOCATE('$item',IFNULL(nickname,usr))";
        }
        $order_res = "IF(" . implode(" and ", $order_arr) . "!=0,1,0) DESC, ";
        $order_res .= implode(" + ", $order_arr) . " ASC";
        $k_res = "IFNULL(nickname,usr) REGEXP '" . implode("|", $k_arr) . "'";
        $factor = "$k_res";
        $order = $order_res . ", uid DESC";
        $rows = $this->rows;
        $dao = new Data("user");
        $res = $dao->get($factor, $rows, "$start,$s", $order);
        return $res;
    }


    public function getBy($attr, $data)
    {
        $dao = new Data("user");
        $show_factor = "$attr='$data'";
        $res = $dao->get($show_factor, $this->rows);
        if (count($res) == 0)
            return null;
        return $res[0];
    }


    public function edit($uid, $data, $self = true)
    {
        $dao = new Data("user");
        $res = $dao->edit("uid=$uid", $data);
        if ($res == 1 && $self) $this->login_refresh();
        return $res == 1;
    }


    public function login_time_refresh($uid)
    {
        $dao = new Data("user");
        $res = $dao->edit("uid=$uid", array("last_time" => Date::cur_time()));
        return $res == 1;
    }

    public function login_refresh()
    {
        $dao = new Data("user");
        $res = $this->get(Session::get("login_user")['uid']);
        $this->set_login($res);
    }


    public function set_login($u)
    {
        session_destroy();

        $sid = substr(md5($u['uid'] . Token::key), 0, 24) . str_pad($u['uid'], 8, "0", STR_PAD_LEFT);
        session_id($sid);
        session_start();

        $u['last_time'] = Date::cur_time();
        Session::set("login_time", time());
        Session::set("login_user", $u);
        $token = Token::en_token($sid);
        Session::set("token", $token);
        Session::denied();
        $this->login_time_refresh($u['uid']);
        return $token;
    }


    public function get_login()
    {
        $res = Session::get("login_user");
        if ($res == null) {
            $res = array();
            foreach ($this->rows as $key)
                switch ($key) {
                    case 'ugid':
                        $res[$key] = 0;
                        break;
                    case 'IFNULL(avatar,\'temp/avatar.png\') as avatar':
                        $res['avatar'] = 'temp/avatar.png';
                        break;
                    case 'IFNULL(nickname,usr) as nickname':
                        $res['nickname'] = null;
                        break;
                    default:
                        $res[$key] = null;
                }
        }
        return $res;
    }


    public static function is_login()
    {
        return (Session::get("login_user")['uid'] != null);
    }


    public function exit_login()
    {
        Session::del("login_user");
    }

}