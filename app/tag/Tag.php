<?php

namespace app\tag;


use core\lib\Data;

class Tag
{
    private $rows = array("tag_id", "name", "belong");


    public function list($belong)
    {
        $factor = "tag.belong = $belong";
        if($belong==0)
            $factor.=" or tag.belong IS NULL";
        $dao = new Data("(SELECT belong,count(belong) AS sub_count FROM tag GROUP BY belong ) r RIGHT JOIN tag ON r.belong = tag.tag_id");
        return $dao->get($factor,array("tag_id", "name","tag.belong as belong","IFNULL(sub_count,0) as sub_count"));
    }

}