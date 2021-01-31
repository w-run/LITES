<?php

namespace app\user;

use app\vip\Vip;
use core\lib\BaseAPI;
use core\lib\Error;
use core\lib\File;
use core\lib\Request;
use core\lib\Session;
use core\sdk\sms\SMS;
use core\sdk\wxapp\WxApp;

class API extends BaseAPI
{



    public function fast_reg($data)
    {

        $usr = "btxy_" . rand(1000000, 9999999);
        $user = new User();
        $data['usr'] = $usr;


        $res = $user->add($data);
        if ($res) {
            $this->login_wxapp();
        } else
            $this->callback_error("fast reg error", $data);
    }


    public function reg()
    {
        $form = $this->getForm('usr pwd repwd');
        if($form['pwd']!=$form['repwd'])
            $this->callback_error('repwd error');
        if(strlen($form['usr'])<3 || strlen($form['usr'])>12)
            $this->callback_error("usr length error");
        if(strlen($form['pwd'])<6 || strlen($form['pwd'])>32)
            $this->callback_error("pwd length error");

        $user = new User();
        $res_exist = $user->getBy('usr',$form['usr']);
        if ($res_exist != null)
            $this->callback_error("usr already exist");
        $res = $user->add([
            'usr' => $form['usr'],
            'pwd' => $form['pwd']
        ]);
        if($res)
            $this->callback();
        else
            $this->callback_error("user reg error");
    }

    public function login()
    {
        $form = $this->getForm('usr pwd');
        $user = new User();
        $res = $user->getBy('usr',$form['usr']);
        if ($res != null) {
            if($res['state']==-1)
                $this->callback_error('user banned');
            if($res['pwd'] != $form['pwd'])
                $this->callback_error('password error');
            $res = $this->format_data($res);
            $this->callback($res, [
                'token' => $user->set_login($res)
            ]);
        }
        $this->callback_error('user not found');
    }

    public function login_wxapp()
    {
        $form = $this->getForm("session openid userInfo data iv area_id");
        $wa = new WxApp();
        $res = $wa->decrypt($form['session'], $form['data'], $form['iv']);

        if ($res['state']) {
            $phone = $res['data']['phoneNumber'];
        } else
            $this->callback_error($res['errMsg']);

        $area_id = $form['area_id'];
        $openid = $form['openid'];
        $userInfo = json_decode($form['userInfo'], true);
        $unionid = $this->getForm_opt("unionid", null);
        $avatar = File::save($userInfo['avatarUrl']);
        $user = new User();
        $res = $user->getBy("phone", $phone);
        if ($res != null) {

            $res = $this->format_data($res);
            $this->callback($res, [
                'token' => $user->set_login($res)
            ]);
        } else {

            $this->fast_reg([
                "phone" => $phone,
                "area_id" => $area_id,
                "wechat_openid" => $openid,
                "wechat_unionid" => $unionid,
                "avatar" => str_replace("res", "_res", $avatar),
                "nickname" => $userInfo['nickName'],
                "gender" => $userInfo['gender']
            ]);
        }
    }

    public function login_state()
    {
        $user = new User();
        $u = $user->get_login();
        $u['token'] = Session::get("token");
        if ($u['uid'] == null)
            $this->callback_error("user not login");
        else {
            $user->login_time_refresh($u['uid']);
            $this->callback($u);
        }
    }


    public function profile_get()
    {
        $user = new User();
        if ($this->getForm_opt("uid") != null) {
            $uid = $this->getForm_opt("uid");
        } else {
            $uid = $user->get_login()['uid'];
            if ($uid == null)
                $this->callback_error("user not login");
        }
        $res = $user->get($uid);
        if ($res == null)
            $this->callback_error("user not found");
        $res = $this->format_data($res);
        $res['token'] = Session::get("token");
        $this->callback($res);
    }


    public function profile_edit()
    {
        $form = $this->getForm("data");
        $user = new User();
        $uid = $user->get_login()['uid'];
        $data = json_decode($form['data'], true);
        if ($uid == null)
            $this->callback_error("user not login");


        else if ($user->edit($uid, $data))
            $this->callback();
        else
            $this->callback_error("edit error");
    }


    public function login_out()
    {
        $user = new User();
        if ($user->get_login()['uid'] != null) {
            $user->exit_login();
            $this->callback();
        } else
            $this->callback_error("user not login");
    }


    public function format_data($data)
    {
        $data['ugid'] = intval($data['ugid']);


        unset($data['reg_area'], $data['reg_time']);
        return $data;
    }
}
