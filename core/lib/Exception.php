<?php


namespace core\lib;


class Exception extends \Exception
{

    private $callback = [];

    public function __construct($message, $code = 0)
    {


        parent::__construct($message, $code);
    }

    public function __toString()
    {
        $basedir = str_replace("/", "\\", L::basePath()) . "\\";
        $f = str_replace($basedir, "", $this->getFile());

        if (strpos($f, "app\\") > -1)
            $error = ERR_API_ERROR;
        else if (strpos($f, "core\\lib\\MySQL") > -1)
            $error = ERR_DATABASE_ERROR;
        else if (strpos($f, "core\\sdk\\") > -1)
            $error = ERR_SDK_ERROR;
        else if (strpos($f, "core\\plugin\\") > -1)
            $error = ERR_PLUGIN_ERROR;
        else
            $error = ERR_SERVER;

        $this->callback = [
            'code' => $error,
            'message' => $this->getMessage(),
            'file' => str_replace("\\", "/", $f),
            'line' => $this->getLine()
        ];
        Error::callback_error($this->callback['code'], $this->callback);
    }


}