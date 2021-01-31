<?php


namespace app\article;


use core\lib\Data;

class Article
{

    private $rows = array("aid", "title", "content", "uid", "time", "state");


    public function list($show_factor, $p = 0, $s = 10)
    {
        $start = 0;
        if ($p > 0) {
            $start = $p * $s;
        }
        $dao = new Data("article");
        $rows = $this->rows;
        for ($i = 0; $i < count($rows); $i++) {
            $show_factor = str_replace($rows[$i], "article." . $rows[$i], $show_factor);
            $rows[$i] = "article." . $rows[$i] . " AS " . $rows[$i];
        }
        array_push($rows, "IFNULL(user.nickname,user.usr) as nickname", "usr", "IFNULL(avatar,'temp/avatar.png') as avatar");
        $res = $dao->get_union("user", "article.uid = user.uid", $show_factor, $rows, "$start,$s");
        return $res;
    }

    public function read($aid)
    {
        $show_factor = "aid=$aid AND state = 0";
        $res = $this->list($show_factor, 0, 1);
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
        $dao = new Data("article");
        $res = $dao->add($attr, $values);
        return $res == 1;
    }

    public function del($aid, $uid = null)
    {
        $dao = new Data("article");
        $show_factor = "aid=$aid";
        if ($uid != null)
            $show_factor .= " and uid=$uid";
        $res = $dao->edit($show_factor, ["state" => -1]);
        return $res == 1;
    }

    public function edit($aid, $data, $factor = "")
    {
        $dao = new Data("article");
        $res = $dao->edit("aid = $aid" . $factor, $data);
        return $res == 1;
    }
}