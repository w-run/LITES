<?php

namespace app\topic;


use app\user\User;
use app\user\UserGroup;
use app\vip\Vip;
use core\lib\BaseAPI;
use core\lib\Date;
use core\lib\Session;

class API extends BaseAPI
{


    private static function format_data($data)
    {
        for ($i = 0; $i < count($data); $i++) {
            if ($data[$i]['review_count'] == null)
                $data[$i]['review_count'] = 0;
            $data[$i]['time'] = Date::time_h($data[$i]['time']);
            $data[$i]['image'] = json_decode($data[$i]['image'], true);
            $data[$i]['is_top'] = ($data[$i]['is_top'] == "1" && Date::time_diff_str($data[$i]['top_time']) < 0) ? 1 : 0;
            if ($data[$i]['is_top'] == 1)
                $data[$i]['top_remain'] = 0 - Date::time_diff_str($data[$i]['top_time']);
            unset($data[$i]['top_time'], $data[$i]['tag_id'], $data[$i]['state']);
        }
        return $data;
    }


    public function list()
    {
        $area_id = $this->getForm_opt("area_id");
        $tag_id = $this->getForm_opt("tag_id");
        $uid = $this->getForm_opt("uid");
        $p = $this->getForm_opt("p", "0");
        $s = $this->getForm_opt("s", "10");
        $where = $this->getForm_opt("where", "1");
        $where = str_replace("\'", "'", $where);
        $topic = new Topic();
        $factor = "$where and state = 0 and ";

        if ($area_id != null && $tag_id != null)
            $factor .= "(area_id = $area_id OR area_id = 0) and tag_id = $tag_id";
        else if ($area_id != null)
            $factor .= "(area_id = $area_id OR area_id = 0)";
        else if ($uid != null)
            $factor .= "uid = $uid";
        else
            $this->callback_error("param error");

        $res = $topic->get_list($factor, $p, $s);

        $data = [
            "count" => $topic->count($factor),
            "list" => self::format_data($res)
        ];
        $this->callback($data);
    }

    public function read()
    {
        $form = $this->getForm("tid");
        $tid = $form['tid'];
        $topic = new Topic();
        $res = $topic->get_content($tid);
        if ($res == null)
            $this->callback_error("topic not found");
        else
            $this->callback(self::format_data([$res])[0]);
    }

    public function add()
    {
        $form = $this->getForm("content area_id");
        $content = $form['content'];
        $area_id = $form['area_id'];
        $tag_id = $this->getForm_opt("tag_id", 0);
        $image = $this->getForm_opt("image", "[]");
        $uid = Session::get("login_user")['uid'];
        $data = array(
            "uid" => $uid,
            "content" => $content,
            "is_top" => 0,
            "tag_id" => $tag_id,
            "image" => $image,
            "area_id" => $area_id,
            "time" => Date::cur_time()
        );
        $topic = new Topic();
        if ($uid == null)
            $this->callback_error("user not login");

        else if ($topic->add($data))
            $this->callback(
                ["tid" => self::format_data($topic->get_list("uid=$uid", 0, 1))[0]['tid']]
            );
        else
            $this->callback_error("post error");
    }

    public function del()
    {
        $uid = $this->getUid();
        $form = $this->getForm("tid");
        $tid = $form['tid'];
        $topic = new Topic();
        if (UserGroup::get_ugid() == 9)
            $res = $topic->del($tid);
        else
            $res = $topic->del($tid, $uid);
        if ($res)
            $this->callback();
        else
            $this->callback_error("delete fail");
    }

    public function count()
    {
        $form = $this->getForm("uid");
        $uid = $form['uid'];
        $topic = new Topic();
        $res = $topic->count("uid=$uid and state = 0");
        $this->callback(intval($res));
    }

    public function top()
    {
        $form = $this->getForm("tid");
        $tid = $form['tid'];
        $top_time = $this->getForm_opt("top_time", Date::cur_time());
        $topic = new Topic();
        $res = $topic->top($tid, $top_time);
        if ($res)
            $this->callback();
        else
            $this->callback_error("submit error");

    }


}
