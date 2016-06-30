<?php

/**
 * Created by PhpStorm.
 * User: xiezj
 * Date: 2016/6/30
 * Time: 21:46
 */
namespace ZaneXie\JSON5;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'json5.php';

class JSON5
{
    public static function encode($obj)
    {
        return json5_encode($obj);
    }

    public static function decode($obj)
    {
        return json5_decode($obj);
    }
}