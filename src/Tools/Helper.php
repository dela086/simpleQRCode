<?php
namespace Simple\QrCode\Tools;

use Simple\QrCode\Exception\InvalidException;
trait Helper
{
    /**
     * 十六进制转换成RGB颜色值
     *
     * @param $color
     * @return array
     * @throws InvalidException
     */
    // #ff00ff -> array(255,0,255) or #f0f -> array(255,0,255)
    public static function hex2rgb($color) {
        $color = str_replace('#','',$color);
        $s = strlen($color) / 3;
        $rgb[]=hexdec(str_repeat(substr($color,0,$s),2/$s));
        $rgb[]=hexdec(str_repeat(substr($color,$s,$s),2/$s));
        $rgb[]=hexdec(str_repeat(substr($color,2*$s,$s),2/$s));
        return $rgb;
    }
}
