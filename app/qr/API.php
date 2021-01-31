<?php


namespace app\qr;


use core\lib\BaseAPI;
use core\lib\Image;
use core\lib\Response;

class API extends BaseAPI
{
    public function get()
    {
        $text = $this->getForm('text')['text'];
        $res = Image::qrcode($text);
        Response::print($res['content'], [
            'Content-type' => $res['mime']
        ], "5m");
    }
}