<?php

namespace app\review;


use app\vip\Vip;
use core\lib\BaseAPI;
use core\lib\Date;
use core\lib\Session;

class API extends BaseAPI
{


    public static function format_data($data)
    {
        for ($i = 0; $i < count($data); $i++) {
            if ($data[$i]['nickname'] == null)
                $data[$i]['nickname'] = $data[$i]['usr'];
            if ($data[$i]['avatar'] == null)
                $data[$i]['avatar'] = "temp/avatar.png";
            $data[$i]['time'] = Date::time_h($data[$i]['time']);
            unset($data[$i]['state']);
        }
        return $data;
    }

    public function list()
    {
        $tid = $this->getForm("tid")['tid'];
        $p = $this->getForm_opt("p", "0");
        $s = $this->getForm_opt("s", "10");
        $review = new Review();
        $factor = "tid=$tid";
        $res = $review->get($factor, $p, $s);
        $data = [
            "count" => $review->count($factor),
            "list" => self::format_data($res)
        ];
        $this->callback($data);
    }

    public function add()
    {
        $form = $this->getForm("tid content");

        $tid = $form['tid'];
        $content = $form['content'];
        $uid = Session::get("login_user")['uid'];
        $data = array(
            "tid" => $tid,
            "uid" => $uid,
            "content" => $content,
            "time" => Date::cur_time()
        );
        $review = new Review();
        if ($uid == null)
            $this->callback_error("user not login");
        else if ($review->add($data))
            $this->callback();
        else
            $this->callback_error("post error");
    }

    public function del()
    {
        $form = $this->getForm("rid");
        $rid = $form['rid'];
        $review = new Review();
        $r = $review->get("rid=$rid", 0, 1);
        if ($r != null && Session::get("login_user")['uid'] != $r[0]['uid'])
            $this->callback_error("can not delete");
        else if ($review->del($rid))
            $this->callback();
        else
            $this->callback_error("delete fail");
    }

    public function count()
    {
        $form = $this->getForm("uid");
        $uid = $form['uid'];
        $review = new Review();
        $res = $review->count("uid=$uid and state = 0");
        $this->callback(intval($res));
    }
}
