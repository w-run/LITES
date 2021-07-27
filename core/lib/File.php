<?php

namespace core\lib;


class File
{
    protected static $ban_ext = ["exe"];

    public static function get($file, $header = [], $cache = "0")
    {
        $w = 0;
        if (strstr($file, "@")) {
            $part = explode("@", $file);
            $file = $part[0];
            $w = $part[1];
        }
        $type = substr(strrchr($file, '.'), 1);
        if (file_exists($file)) {
            if (in_array($type, self::$ban_ext))
                Error::callback_error(ERR_FORBIDDEN);
            $mime = self::get_mime($file);
            $header = array_merge([
                "Content-type" => $mime . ";charset=utf-8"
            ], $header);
            if (!in_array($type, ['php', 'json']))
                $header['Content-Length'] = filesize($file);

            if ($w == 0)
                Response::print(file_get_contents($file), $header, $cache);
            else {
                $res = Image::thumb($file, $w, $w);
                Response::print($res['content'], [
                    "Content-type" => $res['mime'] . ";charset=utf-8",
                    "Content-Length" => $res['size']
                ], $cache);
            }
        } else {
            Error::notfound();
        }
    }

    public static function get_mime($file)
    {
        $type = substr(strrchr($file, '.'), 1);
        switch ($type) {
            case "css":
                $mime = "text/css";
                break;
            case "apk":
                $mime = "application/vnd.android.package-archive";
                break;
            default:
                $mime = mime_content_type($file);
        }
        $mime = str_replace("text/plain", "text/html", $mime);
        return $mime;
    }


    public static function getJson($filename)
    {
        if (is_file($filename))
            return json_decode(file_get_contents($filename), true);
        else
            Error::notfound();
    }

    public static function upload($file, $base_path = "res/img", $allow = ['jpg', 'png', 'gif'], $maxsize = 2048, $randName = true)
    {
        $maxsize *= 1024;
        if ($file['error'] != 0)
            switch ($file['error']) {
                case 1:
                case 2:
                    Error::callback_error(ERR_FILE_SIZE);
                    break;
                case 3:
                    Error::callback_error(ERR_FILE_INCOMPLETE);
                    break;
                case 4:
                    Error::callback_error(ERR_FILE_NULL);
                    break;
                default:
                    Error::callback_error(ERR_FILE_ERROR);
            }

        if ($file['size'] > $maxsize)
            Error::callback_error(ERR_FILE_SIZE);

        $type = substr(strrchr($file['name'], '.'), 1);
        if (!in_array($type, $allow))
            Error::callback_error(ERR_FILE_DENIED, [$type, $allow]);

        if ($randName)
            $new = date('dHis') . rand(1000, 9999) . "_" . session_id();
        else
            $new = session_id();
        $new .= strrchr($file['name'], '.');
        $new_name = $new;
        $path = $base_path . "/" . Date::cur_time("Y-m");
        if (!file_exists($path)) {
            $stat = mkdir($path, 0777, true);
        }
        $target = $path . '/' . $new_name;
        $result = move_uploaded_file($file['tmp_name'], $target);

        if ($result) {
            if ($type == 'gif')
                Image::gif2jpg($target);
            return $target;
        } else {
            Error::callback_error(ERR_FILE_ERROR);
        }
    }

    public static function save($remoteFile, $path = "res/img", $randName = true)
    {
        if ($randName)
            $name = date('dHis') . rand(1000, 9999) . "_" . session_id();
        else
            $name = session_id();
        $type = strrchr(strrchr($remoteFile, '/'), '.');
        $type = $type == "" ? "png" : $type;
        $path = $path . "/" . Date::cur_time("Y-m");
        $target = $path . '/' . $name . $type;
        if (!file_exists($path)) {
            $stat = mkdir($path, 0777, true);
        }
        $content = file_get_contents($remoteFile);
        $file = fopen($target, "w");

        fwrite($file, $content);
        fclose($file);
        return $target;
    }

    public static function write($file, $content)
    {
        $file = fopen($file, "w");
        fwrite($file, $content);
        fclose($file);
    }


}