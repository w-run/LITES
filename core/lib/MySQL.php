<?php

namespace core\lib;


class MySQL
{

    private $con = null;

    public static function str_escape($str)
    {
        $str = str_replace("\\", "\\\\", $str);
        $str = str_replace("\"", "\\\"", $str);
        $str = str_replace("'", "\\'", $str);
        $str = str_replace("\n", "\\n", $str);
        $str = str_replace("\r", "\\r", $str);
        return $str;
    }


    public function __construct()
    {
        $config = Config::get('mysql');
        $this->con = new \mysqli(
            $config['host'],
            $config['usr'],
            $config['pwd'],
            $config['lib'],
            $config['port']
        );

        return;
    }


    public function close()
    {
        $this->con->close();
        return;
    }


    public function query($sql = "")
    {
        $sql .= ";";
        $this->con->query("SET NAMES utf8mb4");

        $res = $this->con->query($sql);
        if ($this->con->error != "")
            throw new Exception($this->con->error . ":" . $sql);
        return $res;
    }


    public function query_res()
    {
        return mysqli_affected_rows($this->con);
    }
}