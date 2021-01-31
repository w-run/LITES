<?php


namespace app\admin;


use app\user\User;

class UserAdmin extends DaoAdmin
{

    protected $menu_desc = [
        'add' => "添加用户",
        'list' => "列表",
        'deled' => "封禁列表"
    ];


    public function del()
    {
        $id = $this->getData('id');
        $data = $this->getData('data');
        $dao = new User();
        $res = $dao->edit($id, [
            'state' => -1
        ], false);
        $this->callback = $this->callback_result($res, "handle error", "reload");
    }

    public function undel()
    {
        $id = $this->getData('id');
        $dao = new User();
        $res = $dao->edit($id, [
            'state' => 0
        ], false);
        $this->callback = $this->callback_result($res, "handle error", "reload");
    }

    public function list()
    {
        $dao = new User();
        $where = $this->getData('where', 'state = 0');
        $data = $dao->get_list($where);
        for ($i = 0; $i < count($data); $i++) {
            $data[$i]['state'] = $this->state_code[$data[$i]['state']];
        }
        $this->callback = $this->callback_list([
            'uid' => 'ID',
            'usr' => '用户名',
            'nickname' => '昵称',
            'birth' => '生日',
            'state' => '状态'
        ], [
            'del' => '封禁',
            'profile' => '查看'
        ], $data, "uid");
    }


    public function profile()
    {
        $field = [
            Form::field('usr', 'input', '用户名'),
            Form::field('nickname', 'input', '昵称'),
            Form::field('birth', 'input', '生日', [
                'type' => 'date'
            ]),
            Form::field('gender', 'input', '性别')
        ];
        $dao = new User();
        $id = $this->getData('id', null);
        $data = $dao->get($id);
        $this->callback = $this->callback_form($field, $data, $id);
    }

    public function edit()
    {
        $id = $this->getData('id');
        $data = $this->getData('data');
        $dao = new User();
        $res = $dao->edit($id, $data, false);
        $this->callback = $this->callback_result($res, "handle error", "reload");
    }

    public function add()
    {
        $data = $this->data['data'];
        $s = count($data) != 0;
        if ($s) {
            if (!array_key_exists("usr", $data) || !array_key_exists("pwd", $data)) {
                $this->callback = $this->callback_result(false, "用户名、密码为必填项");
                return;
            }
            $dao = new User();
            $res = $dao->add($data);
            $this->callback = $this->callback_result($res);
        } else {
            $field = [
                Form::field('usr', 'input', '用户名*'),
                Form::field('pwd', 'input', '密码*', [
                    'type' => 'password'
                ]),
                Form::field('nickname', 'input', '昵称'),
                Form::field('birth', 'input', '生日', [
                    'type' => 'date'
                ])
            ];
            $this->callback = $this->callback_form($field, null);
        }
    }

    public function deled()
    {
        $dao = new User();
        $where = $this->getData('where', 'state = -1');
        $data = $dao->get_list($where);
        for ($i = 0; $i < count($data); $i++) {
            $data[$i]['state'] = $this->state_code[$data[$i]['state']];
        }
        $this->callback = $this->callback_list([
            'uid' => 'ID',
            'usr' => '用户名',
            'nickname' => '昵称',
            'birth' => '生日',
            'state' => '状态'
        ], [
            'undel' => '恢复'
        ], $data, "uid");
    }
}