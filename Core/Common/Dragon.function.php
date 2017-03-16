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
 * @param $var mixed 变量
 */
function E($var, $exit=false){
    if(is_bool($var)){
        var_dump($var);
    }elseif(is_null($var)){
        var_dump(NULL);
    }else{
        echo "<pre style='position:relative; z-Index: 1000; padding: 10px; border-radius: 5px;background: #F5F5F5;
                border: 1px solid #aaa; font-size: 20px; line-height: 18px; opacity: 0.9;'>"
            .print_r($var, true).'</pre>';
    }
    if($exit) exit;
}


?>