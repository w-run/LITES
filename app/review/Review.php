<?php

namespace app\review;


use core\lib\Data;

class Review
{

    private $rows = array("rid", "tid", "uid", "content", "time", "state");




    public function get($show_factor, $p, $s)
    {
        $start = 0;
        if ($p > 0) {
            $start = $p * $s;
        }
        $dao = new Data("review");
        $rows = $this->rows;
        for ($i = 0; $i < count($rows); $i++) {
            $show_factor = str_replace($rows[$i], "review." . $rows[$i], $show_factor);
            $rows[$i] = "review." . $rows[$i] . " AS " . $rows[$i];
        }
        array_push($rows, "usr", "IFNULL(user.nickname,user.usr) as nickname", "IFNULL(avatar,'_temp/avatar.png') as avatar");
        return $dao->get_union("user", "review.uid = user.uid", $show_factor, $rows, "$start,$s", "time desc");
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
        $dao = new Data("review");
        $res = $dao->add($attr, $values);
        return $res == 1;
    }


    public function del($rid)
    {
        $dao = new Data("review");
        $show_factor = "rid=$rid";
        $res = $dao->edit($show_factor, ["state" => -1]);
        return $res == 1;
    }

    public function edit($rid, $data, $factor = "")
    {
        $dao = new Data("review");
        $res = $dao->edit("rid = $rid" . $factor, $data);
        return $res == 1;
    }


    public function count($show_factor)
    {
        $dao = new Data("review");
        return $dao->count($show_factor);
    }
}
