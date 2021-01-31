<?php

namespace app\file;


use core\lib\BaseAPI;
use core\lib\File;
use core\lib\Request;

class API extends BaseAPI
{
    public function upload()
    {
        $file = $_FILES['file'];
        $from = $this->getForm_opt("type", "file");
        $callback = [];
        if ($from == "image") {
            $callback['url'] = File::upload($file);
        } else {
            $callback['url']  = File::upload(
                $file,
                "res/file",
                ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', '7z', 'txt', 'jpg', 'png', 'gif', 'mp3', 'wav', 'mp4', 'avi', 'flv'],
                5120
            );
        }
        $this->callback($callback);
    }
}