<?php


namespace app\admin;


use app\user\User;

class DaoAdmin
{
    protected $handle = "";
    protected $data = "";
    protected $state = true;
    protected $errMsg = "param error";
    protected $menu_desc = [];
    protected $callback = [
        'type' => "data",
        'data' => []
    ];
    protected $state_code = ['0' => '-', '-1' => "x", '1' => '?'];

    public function __construct($o)
    {
        $this->handle = $o['handle'];
        $this->data = $o['data'];
    }

    public function exec()
    {
        $h = $this->handle;
        if ($h == 'get') {
            $m = get_class_methods($this);
            $a = [];
            $d = [];
            foreach ($m as $v)
                if (!in_array($v, ['exec', 'getData', 'callback_list', '__construct'])) {
                    array_push($a, $v);
                    if (array_key_exists($v, $this->menu_desc))
                        $d[$v] = $this->menu_desc[$v];
                }
            return [
                'state' => $this->state,
                'callback' => ['type' => 'menu', 'data' => json_encode($d)]
            ];
        }
        if (!method_exists($this, $h))
            return [
                'state' => false,
                'errMsg' => $this->errMsg
            ];
        call_user_func([$this, $h]);
        return [
            'state' => $this->state,
            'callback' => $this->callback
        ];
    }


    public function getData($optional = "", $default = null)
    {
        if (array_key_exists($optional, $this->data) && $this->data[$optional] != "")
            return $this->data[$optional];
        else
            return $default;
    }

    protected function callback_list($title, $handle, $data, $id)
    {
        $callback = [];
        $callback['type'] = 'list';
        $callback['title'] = json_encode($title);
        $callback['handle'] = json_encode($handle);
        $callback['data'] = json_encode($data);
        $callback['id'] = $id;
        return $callback;
    }

    protected function callback_form($field, $data, $id = null)
    {
        $callback = [];
        $callback['type'] = 'form';
        $callback['field'] = json_encode($field);
        $callback['data'] = json_encode($data);
        $callback['id'] = $id;
        return $callback;
    }

    public function callback_result($state = true, $errMsg = "handle error", $handle = "reload")
    {
        $callback = [];
        $callback['type'] = 'result';
        $callback['state'] = $state;
        $callback['errMsg'] = $errMsg;
        $callback['handle'] = $handle;
        return $callback;
    }

}