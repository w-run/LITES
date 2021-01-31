<?php

namespace app\topic;


use core\lib\Data;
use core\lib\Date;

class Topic
{


    private $rows = array("tid", "content", "uid", "time", "area_id", "tag_id", "image", "is_top", "top_time", "state");


    public function get_list($show_factor, $p = 0, $s = 10, $order = "if( unix_timestamp(top_time)-unix_timestamp(NOW())>0 and is_top=1,0,1),time desc")
    {
        $start = 0;
        if ($p > 0) {
            $start = $p * $s;
        }
        $dao = new Data("(SELECT tid,count(rid) AS review_count FROM  review GROUP BY tid ) r RIGHT JOIN topic ON r.tid = topic.tid");
        $rows = $this->rows;
        for ($i = 0; $i < count($rows); $i++) {
            $show_factor = str_replace($rows[$i], "topic." . $rows[$i], $show_factor);
            $rows[$i] = "topic." . $rows[$i] . " AS " . $rows[$i];
        }
        $rows[1] = "LEFT(topic.content,256) AS content";
        array_push($rows, "IFNULL(user.nickname,user.usr) as nickname", "usr", "IFNULL(avatar,'temp/avatar.png') as avatar", "tag.name as tag", "review_count");
        return $dao->get_union("user,tag", "topic.uid = user.uid AND topic.tag_id = tag.tag_id", $show_factor, $rows, "$start,$s", $order);
    }


    public function get_list_search($keywords, $area_id, $p = 0, $s = 10)
    {
        $k_arr = explode(" ", trim($keywords));
        $i = 0;
        $order_arr = [];
        foreach ($k_arr as $item) {
            $order_arr[$i++] = "LOCATE('$item',topic.content)";
        }
        $order_res = "IF(" . implode(" and ", $order_arr) . "!=0,1,0) DESC, " . implode(" + ", $order_arr) . " ASC";
        $k_res = "content REGEXP '" . implode("|", $k_arr) . "'";
        $factor = "state = 0 AND (area_id = '$area_id' OR area_id = 0) AND $k_res";
        $order = $order_res . ", topic.time DESC";
        return $this->get_list($factor, $p, $s, $order);
    }



    public function get_content($tid)
    {
        $dao = new Data("(SELECT tid,count(rid) AS review_count FROM  review GROUP BY tid ) r RIGHT JOIN topic ON r.tid = topic.tid");
        $show_factor = "tid=$tid";
        $rows = $this->rows;
        for ($i = 0; $i < count($rows); $i++) {
            $show_factor = str_replace($rows[$i], "topic." . $rows[$i], $show_factor);
            $rows[$i] = "topic." . $rows[$i] . " AS " . $rows[$i];
        }
        array_push($rows,  "IFNULL(user.nickname,user.usr) as nickname", "usr", "IFNULL(avatar,'temp/avatar.png') as avatar", "tag.name as tag", "review_count");
        $res = $dao->get_union("user,tag", "topic.uid = user.uid AND topic.tag_id = tag.tag_id", $show_factor, $rows);
        if (count($res) == 0)
            return null;
        return $res[0];
    }


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
        $dao = new Data("topic");
        $res = $dao->add($attr, $values);
        return $res == 1;
    }


    public function del($tid, $uid=null)
    {
        $dao = new Data("topic");
        $show_factor = "tid=$tid";
        if($uid!=null)
            $show_factor .= " and uid=$uid";
        $res = $dao->edit($show_factor, ["state" => -1]);
        return $res == 1;
    }

    public function edit($tid, $data, $factor = "")
    {
        $dao = new Data("topic");
        $res = $dao->edit("tid = $tid" . $factor, $data);
        return $res == 1;
    }

    public function count($show_factor)
    {
        $dao = new Data("topic");
        return $dao->count($show_factor);
    }

    public function top($tid, $time)
    {
        $dao = new Data("topic");
        return $dao->edit("tid=$tid", ['is_top' => 1, 'top_time' => $time]);
    }
}
