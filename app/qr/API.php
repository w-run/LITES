<?php
/*  
 *  @file API.php
 *  @project LITES_Example
 *  @author W/Run
 *  @version 2021-01-27
 */

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
        Response::print($res['content'],[
            'Content-type'=>$res['mime']
        ],"5m");
    }
}