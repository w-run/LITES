<?php

namespace core\lib;

use core\sdk\qrcode\QRcode;

class Image
{

    public static function thumb($source, $width = 0, $height = 0)
    {
        $imgExt = strtolower(str_replace(".", "", strrchr($source, '.')));
        if ($imgExt == 'jpg') $imgExt = 'jpeg';
        $img = $source;
        $info = getimagesize($img);
        $fun = "imagecreatefrom{$imgExt}";
        $srcimg = $fun($img);
        $quality = 100;
        if ($imgExt == 'png') $quality = 9;
        $getImgInfo = "image{$imgExt}";
        $src_w = $info[0];
        $src_h = $info[1];
        if ($width * $height == 0) {
            $width = $src_w;
            $height = $src_h;
        }
        $image = imagecreatetruecolor($width, $height);
        imagecopyresampled($image, $srcimg, 0, 0, 0, 0, $width, $height, $src_w, $src_h);
        $temp = tmpfile();
        $path = stream_get_meta_data($temp)['uri'];
        $getImgInfo($image, $path, $quality);
        $size = filesize($path);
        $s = fread($temp, $size);
        $mime = "image/" . $imgExt . ";charset=UTF-8";
        fclose($temp);
        imagedestroy($srcimg);
        imagedestroy($image);
        return [
            "mime" => $mime,
            "content" => $s,
            "size" => $size,
            "ext" => $imgExt
        ];
    }

    public static function resize($source, $maxWidth, $maxHeight)
    {
        $img_info = getimagesize($source);
        $src_width = $img_info[0];
        $src_height = $img_info[1];
        $dst_width = $src_width;
        $dst_height = $src_height;
        if ($dst_height > $dst_width) {
            if ($dst_width > $maxWidth) {
                $dst_width = $maxWidth;
                $dst_height = $src_height / $src_width * $maxWidth;
            }
            if ($dst_height > $maxHeight) {
                $dst_height = $maxHeight;
                $dst_width = $src_width / $src_height * $maxHeight;
            }
        } else if ($dst_height < $dst_width) {
            if ($dst_height > $maxWidth) {
                $dst_height = $maxWidth;
                $dst_width = $src_width / $src_height * $maxWidth;
            }
            if ($dst_width > $maxHeight) {
                $dst_width = $maxHeight;
                $dst_height = $src_height / $src_width * $maxHeight;
            }
        } else {
            $dst_width = $dst_height = $maxWidth;
        }
        return self::thumb($source, $dst_width, $dst_height);
    }

    public static function gif2jpg($source)
    {
        $output = str_replace('.gif', '.gif.jpg', $source);
        $image = imagecreatefromgif($source);
        imagejpeg($image, $output);
        imagedestroy($image);
    }

    public static function thumb_forWx($source)
    {
        $res = self::resize($source, 750, 1334);
        $name = "temp/img/" . time() . rand(1000, 9999) . "." . ($res['ext'] == 'jpeg' ? 'jpg' : $res['ext']);
        $file = fopen($name, "w");

        fwrite($file, $res['content']);
        fclose($file);
        return L::basePath() . "/" . $name;
    }

    public static function qrcode($text, $level = "M", $width = 500, $height = 500)
    {
        $level = ['L' => 0, 'M' => 1, 'Q' => 2, 'H' => 3][strtoupper($level)];
        $name = date('dHis') . rand(1000, 9999) . "_" . session_id() . ".png";
        $path = "temp/img/";
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        QRcode::png($text, $path . $name, $level, 4, 1);
        $res = self::resize($path . "/" . $name, $width, $height);
        unlink($path . "/" . $name);
        return $res;
    }


}