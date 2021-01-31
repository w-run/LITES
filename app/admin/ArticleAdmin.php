<?php
/*  
 *  @file UserAdmin.php
 *  @project LITES_Example
 *  @author W/Run
 *  @version 2021-01-24
 */

namespace app\admin;

use app\article\Article;

class ArticleAdmin extends DaoAdmin
{


    protected $menu_desc = [
        'list'=>"列表",
        'add' => "新增话题",
        'deled' => '回收站'
    ];


    public function list()
    {
        $where = $this->getData('where',"state IN (0,1)");
        $p = $this->getData('p',0);
        $s = $this->getData('s',100);
        $dao = new Article();
        $data = $dao->list($where,$p,$s);
        for ($i = 0;$i<count($data);$i++){
            $data[$i]['content'] = mb_substr($data[$i]['content'] ,0,32);
//            $data[$i]['time'] = Date::time_h($data[$i]['time']);
            $data[$i]['state'] = $this->state_code[$data[$i]['state']];
        }
        $this->callback = $this->callback_list([
            'aid'=>"ID",
            'uid'=>"用户",
            'title'=>"标题",
            'time'=>"时间",
            'state'=>"状态",
        ],[
            'del'=>'删除',
            'info'=>'查看'
        ],$data,"aid");
    }

    public function deled()
    {
        $where = $this->getData('where',"state = -1");
        $p = $this->getData('p',0);
        $s = $this->getData('s',100);
        $dao = new Article();
        $data = $dao->list($where,$p,$s);
        for ($i = 0;$i<count($data);$i++){
            $data[$i]['content'] = mb_substr($data[$i]['content'] ,0,32);
//            $data[$i]['time'] = Date::time_h($data[$i]['time']);
            $data[$i]['state'] = $this->state_code[$data[$i]['state']];
        }
        $this->callback = $this->callback_list([
            'aid'=>"ID",
            'uid'=>"用户",
            'title'=>"标题",
            'time'=>"时间",
            'state'=>"状态",
        ],[
            'undel'=>'恢复'
        ],$data,"aid");
    }

    public function del()
    {
        $id = $this->getData('id');
        $data = $this->getData('data');
        $dao = new Article();
        $res = $dao->del($id);
        $this->callback = $this->callback_result($res,"handle error","reload");
    }
    public function undel()
    {
        $id = $this->getData('id');
        $dao = new Article();
        $res = $dao->edit($id,[
            'state'=>0
        ]);
        $this->callback = $this->callback_result($res,"handle error","reload");
    }

    public function edit()
    {
        $id = $this->getData('id');
        $data = $this->getData('data');
        $dao = new Article();
        $res = $dao->edit($id,$data);
        $this->callback = $this->callback_result($res,"handle error","reload");
    }

    public function info()
    {
        $field = [
            Form::field('title','input','标题'),
            Form::field('content','textarea','内容'),
            Form::field('time','input','时间')
        ];
        $dao = new Article();
        $id = $this->getData('id',null);
        $data = $dao->read($id);
        $this->callback = $this->callback_form($field,$data,$id);
    }
}