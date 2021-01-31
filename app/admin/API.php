<?php


namespace app\admin;


use app\user\User;
use app\user\UserGroup;
use core\lib\BaseAPI;
use core\lib\Date;
use core\lib\MFA;
use core\lib\Session;

class API extends BaseAPI
{

    public function custom_init()
    {
        if (in_array($this->handle, ['login', "logout"]))
            return;
        $this->is_login();
        $o = $this->opera();
        switch ($this->handle) {
            case "user":
                $a = new UserAdmin($o);
                break;
            case "topic":
                $a = new TopicAdmin($o);
                break;
            case "review":
                $a = new ReviewAdmin($o);
                break;
            case "article":
                $a = new ArticleAdmin($o);
                break;
            case "site":
                $a = new SiteAdmin($o);
                break;
            case "status":
                $a = new StatusAdmin($o);
                break;
            default:
                $this->callback_error("param error");
        }
        $res = $a->exec();
        if ($res['state'])
            $this->callback($res['callback']);
        else
            $this->callback_error($res['errMsg']);
    }

    public function is_login()
    {
        $last = Session::get("admin_login");
        if ($last == "" || Date::time_diff($last) > 600)
            $this->callback_error("admin not login");
        Session::set("admin_login", time());
    }

    public function opera()
    {
        $form = $this->getForm("o");
        try {
            $form['data'] = json_decode($this->getForm_opt("data", "{}"), true);
        } catch (\Exception $e) {
            $this->callback_error("param error");
        }
        return [
            "handle" => $form['o'],
            "data" => $form['data']
        ];
    }

    public function login()
    {
        $u = (new User())->get($this->getUid());

        $last = Session::get("admin_login");
        if ($last == "" || Date::time_diff($last) > 600) {
            $data = $this->getForm_opt("data", "{}");
            $pwd = $this->getData(json_decode($data, true), 'pwd');
            if ($pwd == '')
                $this->callback_error("admin not login");
            $mfa = new MFA();
            $secret = $mfa->get_secret(session_id());
            if ($u['pwd'] != $pwd && !$mfa->verify($secret, $pwd))
                $this->callback_error("password error");
        }
        $list = UserGroup::auth("admin");
        $res = [];
        foreach ($list as $k => $v)
            $res[str_replace("admin_", "", $k)] = $v;
        unset($res['login']);
        Session::set("admin_login", time());
        $this->callback([
            'type' => 'nav',
            'data' => $res
        ]);
    }

    public function logout()
    {
        $this->is_login();
        Session::del("admin_login");
        $this->callback();
    }

    public function getData($data, $optional = "", $default = null)
    {
        if (array_key_exists($optional, $data) && $data[$optional] != "")
            return $data[$optional];
        else
            return $default;
    }

}