<?php

namespace app\tag;


use core\lib\BaseAPI;

class API extends BaseAPI
{
    public function list()
    {
        $tag_id = $this->getForm_opt("belong", 0);
        $tag = new Tag();
        $res = $tag->list($tag_id);
        $this->callback($res);
    }

}