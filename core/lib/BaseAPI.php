<?php

namespace core\lib;


use app\user\User;
use app\user\UserGroup;


class BaseAPI
{

    protected $request = array();
    private $quote = false;
    public $res = null;
    protected $api = "";
    protected $handle = "";

    public function __construct($func = "", $request = array())
    {

        if (count($request) == 0) {
            $this->request = $_REQUEST;
        } else {
            $this->request = $request;
            $this->form = $request['form'];
        }
        $this->api = $this->request['url'][1];
        $this->handle = str_replace("/", "_", Request::url_cut($this->request['url'], 2));
        Auth::api($this->api, $this->handle);
        $this->init($func);
    }


    protected function custom_init()
    {
    }


    private function init($func)
    {
        $this->custom_init();

        $isAjax = 1 || (L_DEBUG == "on") || $this->request['isAjax'];
        if ($isAjax && $func != "") {
            if (method_exists($this, $func))
                $this->$func();
            else
                Error::callback_error(ERR_METHOD_DENIED);
        } else if ($this->request['isPost'])
            $this->post();
        else
            $this->get();
    }


    protected function get()
    {
        Error::callback_error(ERR_METHOD_DENIED);
    }

    protected function post()
    {
        Error::callback_error(ERR_METHOD_DENIED);
    }


    protected function callback($data = "", $header = array())
    {
        $res = array(
            "code" => ERR_OK,
            "errMsg" => "ok"
        );
        if ($this->quote) {
            $this->res = $data;
            return;
        }
        if (is_array($data) && count($data) != 0) {
            $res["data"] = Response::sort_data($data);
        }
        if (is_string($data) && $data != "")
            $res["data"] = $data;
        Response::print($data, $header);
    }


    protected function callback_error($errMsg, $data = "")
    {
        $res = array(
            "code" => ERR_API_ERROR,
            "api" => $this->api,
            "handle" => $this->handle,
            "errMsg" => $errMsg
        );
        if (is_array($data) && count($data) != 0) {
            $res["data"] = Response::sort_data($data);
        }
        if (is_string($data) && $data != "")
            $res["data"] = $data;
        Response::print($data, [
            "errCode" => ERR_API_ERROR,
            "errMsg" => $errMsg,
            "errApi" => $this->api,
            "errHandle" => $this->handle
        ]);
    }


    protected function getForm($require = "")
    {
        $form = array();
        $missed = array();

        $require = explode(" ", trim(str_replace("  ", " ", $require)));
        foreach ($require as $item)
            if (array_key_exists($item, $this->form) && $this->form[$item] != "")
                $form = $form + array($item => $this->form[$item]);
            else {
                array_push($missed, $item);
            }
        if (count($missed) > 0) {
            $this->callback_error("missing param", $missed);
        }
        return $form;
    }


    protected function getForm_opt($optional = "", $default = null)
    {
        if (array_key_exists($optional, $this->form) && $this->form[$optional] != "")
            return $this->form[$optional];
        else
            return $default;
    }

}
