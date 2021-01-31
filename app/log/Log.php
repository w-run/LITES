<?php

namespace app\log;


use core\lib\Data;
use core\lib\Date;
use core\lib\Request;
use core\lib\Session;
use core\lib\Token;

class Log
{

    private static $rows = array("session_id", "uid", "api", "handle","url", "errCode", "errMsg","time","rand", "ip");


    public static function add($callback,$api,$handle,$ip)
    {
        if(in_array($api,[
            "test","",null
        ]))
            return;
        $dao = new Data("log");
        $data = [
            Token::de_token(Session::get("token")), 
            Session::get("login_user")['uid'],      
            $api,               
            $handle, 
            $_SERVER['REQUEST_URI'],
            $callback['code'],
            $callback['errMsg'],
            Date::cur_time(),
            rand(100000000,999999999)."",
            $ip
        ];
        $res = $dao->add(self::$rows,$data);
        return $res==1;
    }


    public static function list($p=0, $s=100,$where = null)
    {
        $start = 0;
        if ($p > 0) {
            $start = $p * $s;
        }

        $dao = new Data("log");
        $rows = self::$rows;
        return $dao->get($where, $rows,$s==-1?null:"$start,$s","time desc");
    }


    public static function count($where = null)
    {

        $dao = new Data("log");
        return $dao->get($where, ["count(1)"])[0]['count(1)'];
    }



}