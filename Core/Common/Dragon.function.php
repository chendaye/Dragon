<?php
// +----------------------------------------------------------------------
// | DragonPHP [ DO IT NOW ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://chen.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: chendaye <chendaye666@gmail.com>
// +----------------------------------------------------------------------
// | One letter one dream!
// +----------------------------------------------------------------------

/**
 * 打印一个变量
 * @param mixed $var  变量值
 * @param bool $exit  是否中断
 */
function E($var, $exit=false){
    if($var === true){
        $var = '(BOOL)TRUE';
    }elseif ($var === false){
        $var = '(BOOL)FALSE';
    }elseif(is_null($var)){
        $var = 'NULL';
    }
    echo "<pre style='position:relative; z-Index: 1000; padding: 10px; border-radius: 5px;background: #F5F5F5;
                border: 1px solid #aaa; font-size: 20px; line-height: 18px; opacity: 0.9;'>"
        .print_r($var, true).'</pre>';
    if($exit) exit;
}

function filtertest($value){
    if(is_array($value)){
        $value = '数组';
    }elseif (is_object($value)){
        $value = '对象';
    }else{
        $value = '字符串';
    }
    return $value;
}
?>