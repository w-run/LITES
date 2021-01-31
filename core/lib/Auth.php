<?php

namespace core\lib;


use app\user\UserGroup;

class Auth
{

    public static function api($api, $handle)
    {
        if (UserGroup::auth($api, $handle) == false)
            if (UserGroup::get_ugid() == 0)
                Response::print([], [
                    "errCode" => ERR_API_ERROR,
                    "errMsg" => "user not login",
                    "errApi" => $api,
                    "errHandle" => $handle
                ]);
            else
                Error::callback_error(ERR_AUTH_FAILED);
    }

    public static function request()
    {
        $token = Request::getParam("token");
        if ($token == "") {
            session_start();
            $sid = session_id();
            $i = intval(substr($sid,16,16));
            if($i>0)
                Session::denied();
            $token = Token::en_token($sid);
        } else {
            $sid = Token::de_token($token);
            session_id($sid);
            session_start();
        }
        Session::set("token", $token);
    }
}