<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
function randstr($length = 8){
    // 密码字符集，可任意添加你需要的字符
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ( $i = 0; $i < $length; $i++ )
    {
        // 这里提供两种字符获取方式
        // 第一种是使用 substr 截取$chars中的任意一位字符；
        // 第二种是取字符数组 $chars 的任意元素
        // $password .= substr($chars, mt_rand(0, strlen($chars) – 1), 1);
        $password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
    }
    return $password;
}

function timetostr($start_time,$end_time){

    $value['class_day'] = date('m月d日',$start_time);
    $value['class_week'] = getTimeWeek($start_time);
    $value['class_start_time'] = date('H:i',$start_time);
    $value['class_end_time'] = date('H:i',$end_time);
    $value['class_real_time'] = $value['class_day'].' '.$value['class_week'].' '.$value['class_start_time'].'~'.$value['class_end_time'];
    return $value;
}

function getTimeWeek($time, $i = 0) {
    $weekarray = array("日","一", "二", "三", "四", "五", "六");
    $oneD = 24 * 60 * 60;
    return "星期" . $weekarray[date("w", $time + $oneD * $i)];
}

function getUrl(){
    return is_ssl().$_SERVER['HTTP_HOST'].DS;
}
function is_ssl() {
    if(isset($_SERVER['HTTPS']) && ('1' == $_SERVER['HTTPS'] || 'on' == strtolower($_SERVER['HTTPS']))){
        return 'https://';
    }elseif(isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'] )) {
        return 'http://';
    }
    return 'http://';
}

function canbook(){
    return true;
}


/**

 * @param $data  要加密的字符串

 * @param $key   密钥

 * @return string

 */

function encrypt($data, $key = 'encrypt')
{
    $key = md5($key);
    $x = 0;
    $len = strlen($data);
    $l = strlen($key);
    $char = '';
    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) {
            $x = 0;
        }
        $char .= $key{$x};
        $x++;
    }
    $str = '';
    for ($i = 0; $i < $len; $i++) {
        $str .= chr(ord($data{$i}) + (ord($char{$i})) % 256);
    }
    return base64_encode($str);
}

/**

 * @param $data    要解密的字符串

 * @param $key     密钥

 * @return string

 */

function decrypt($data, $key = 'encrypt')
{
    $key = md5($key);
    $x = 0;
    $data = base64_decode($data);
    $len = strlen($data);
    $l = strlen($key);
    $char = '';
    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) {
            $x = 0;
        }
        $char .= substr($key, $x, 1);
        $x++;
    }
    $str = '';
    for ($i = 0; $i < $len; $i++) {
        if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
        } else {
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
        }
    }
    return $str;
}