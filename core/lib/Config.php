<?php

namespace core\lib;


class Config
{
    public static function system($key)
    {
        $config = File::getJson(CONFIG_FILE);
        return $config['system'][$key];
    }

    public static function get($key)
    {
        $config = File::getJson(CONFIG_FILE);
        return $config[$key];
    }
}