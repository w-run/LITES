<?php


namespace core\sdk\quark;


use core\lib\Web;

class Hots
{
    public function weibo()
    {
        $url = "https://quark.sm.cn/api/rest?method=newstoplist.weibo";
        $res = Web::send($url, "GET");
        return json_decode($res, true);
    }

    public function zhihu()
    {
        $url = "https://quark.sm.cn/api/rest?method=newstoplist.zhihu";
        $res = Web::send($url, "GET");
        return json_decode($res, true);
    }
}