<?php

namespace core\lib;



class Auth
{

    public static function api($api, $handle)
    {
        // do sth...
    }

    public static function request()
    {
        $token = Request::getParam("token");
        if ($token == "") {
            session_start();
            $sid = session_id();
            $i = intval(substr($sid, 16, 16));
            if ($i > 0)
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