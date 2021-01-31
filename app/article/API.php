<?php


namespace app\article;


use app\user\UserGroup;
use core\lib\BaseAPI;
use core\lib\Date;
use core\lib\Session;
use core\sdk\markdown\Parser;

class API extends BaseAPI
{

    public static function format_data($list)
    {
        $md = new Parser();
        foreach ($list as $i => $item) {
            $list[$i]['content'] = mb_substr(strip_tags($md->parse($item['content'])), 0, 150);
            $list[$i]['time'] = Date::time_h($item['time']);
        }
        return $list;
    }

    public function list()
    {
        $p = $this->getForm_opt("p", "0");
        $s = $this->getForm_opt("s", "10");
        $where = $this->getForm_opt("where", "state = 0");
        $article = new Article();
        $res = $article->list($where, $p, $s);
        $this->callback(self::format_data($res));
    }

    public function read()
    {
        $aid = $this->getForm("aid")['aid'];
        $article = new Article();
        $res = $article->read($aid);
        if ($res != null) {
            $md = new Parser();
            $res['content'] = $md->parse($res['content']);
            $this->callback($res);
        } else
            $this->callback_error("article not found");
    }


    public function del()
    {
        $uid = $this->getUid();
        $form = $this->getForm("aid");
        $aid = $form['aid'];
        $article = new Article();
        if (UserGroup::get_ugid() == 9)
            $res = $article->del($aid);
        else
            $res = $article->del($aid, $uid);
        if ($res)
            $this->callback();
        else
            $this->callback_error("delete fail");
    }

    public function add()
    {
        $form = $this->getForm("title content");
        $content = $form['content'];
        $content = str_replace("\\", "\\\\", $content);
        $content = str_replace("'", "\'", $content);
        $title = $form['title'];
        $uid = $this->getUid();
        $data = array(
            "uid" => $uid,
            "content" => $content,
            "title" => $title,
            "time" => Date::cur_time(),
            "state" => 0
        );
        $article = new Article();

        if ($article->add($data))
            $this->callback(
                ["aid" => self::format_data($article->list("uid=$uid", 0, 1))[0]['aid']]
            );
        else
            $this->callback_error("post error");
    }
}