<?php
/*  
 *  @file UserAdmin.php
 *  @project LITES_Example
 *  @author W/Run
 *  @version 2021-01-24
 */

namespace app\admin;

use app\topic\Topic;
use core\lib\Date;

class TopicAdmin extends DaoAdmin
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
        $dao = new Topic();
        $data = $dao->get_list($where,$p,$s);
        for ($i = 0;$i<count($data);$i++){
            $data[$i]['content'] = mb_substr($data[$i]['content'] ,0,32);
//            $data[$i]['time'] = Date::time_h($data[$i]['time']);
            $data[$i]['state'] = $this->state_code[$data[$i]['state']];
        }
        $this->callback = $this->callback_list([
            'tid'=>"ID",
            'uid'=>"用户",
            'content'=>"内容",
            'time'=>"时间",
            'state'=>"状态",
        ],[
            'del'=>'删除',
            'info'=>'查看'
        ],$data,"tid");
    }

    public function deled()
    {
        $where = $this->getData('where',"state = -1");
        $p = $this->getData('p',0);
        $s = $this->getData('s',100);
        $dao = new Topic();
        $data = $dao->get_list($where,$p,$s);
        for ($i = 0;$i<count($data);$i++){
            $data[$i]['content'] = mb_substr($data[$i]['content'] ,0,32);
//            $data[$i]['time'] = Date::time_h($data[$i]['time']);
            $data[$i]['state'] = $this->state_code[$data[$i]['state']];
        }
        $this->callback = $this->callback_list([
            'tid'=>"ID",
            'uid'=>"用户",
            'content'=>"内容",
            'time'=>"时间",
            'state'=>"状态",
        ],[
            'undel'=>'恢复'
        ],$data,"tid");
    }

    public function del()
    {
        $id = $this->getData('id');
        $data = $this->getData('data');
        $dao = new Topic();
        $res = $dao->del($id);
        $this->callback = $this->callback_result($res,"handle error","reload");
    }
    public function undel()
    {
        $id = $this->getData('id');
        $dao = new Topic();
        $res = $dao->edit($id,[
            'state'=>0
        ]);
        $this->callback = $this->callback_result($res,"handle error","reload");
    }

    public function edit()
    {
        $id = $this->getData('id');
        $data = $this->getData('data');
        $dao = new Topic();
        $res = $dao->edit($id,$data);
        $this->callback = $this->callback_result($res,"handle error","reload");
    }

    public function info()
    {
        $field = [
            Form::field('content','textarea','内容'),
            Form::field('image','textarea','图片'),
            Form::field('time','input','时间')
        ];
        $dao = new Topic();
        $id = $this->getData('id',null);
        $data = $dao->get_content($id);
        $this->callback = $this->callback_form($field,$data,$id);
    }
}