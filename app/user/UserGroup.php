<?php

namespace app\user;


use app\log\Log;
use app\vip\Vip;
use core\lib\Data;
use core\lib\Request;
use core\lib\Session;

class UserGroup
{
    public static function auth($api = null, $handle = null)
    {
        $ugid = self::get_ugid();
        $dao = new Data("usergroup");
        $factor = null;
        if ($api !== null)
            $factor = "api = '$api'";
        if ($handle !== null)
            $factor .= " AND handle = '$handle'";
        $res = $dao->get($factor, ['api', 'handle', "`" . $ugid . "`"]);
        $authArr = [];
        foreach ($res as $i => $item) {
            $authArr[$item['api'] . "_" . $item['handle']] = $item[$ugid] == "1";
        }
        if ($handle !== null)
            if (count($authArr) == 1)
                return $authArr["$api" . "_$handle"];
            else
                return true;
        return $authArr;
    }

    public static function get_ugid()
    {
        return (new User())->get_login()['ugid'];
    }
}