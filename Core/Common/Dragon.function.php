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
function E($var, $exit = true)
{
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

/**
 * 查看加载的文件
 * @param $key string|array 匹配模式
 * @param bool $match  是否完全匹配
 */
function getInclude($key = null, $match = false){
    $files = [];
    $included_files = get_included_files();
    if(!empty($key) && is_string($key)){
        if(strpos($key, '/') === 0){
            foreach ($included_files as $filename) {
                $ret = preg_match($key, $filename);
                if($ret) $files[] = $filename;
            }
        }else{
            foreach ($included_files as $filename) {
                if($match){
                    if($key === $filename)$files[] = $filename;
                }else{
                    if(strpos($filename, $key)) $files[] = $filename;
                }
            }
        }
    }elseif (!empty($key) && is_array($key)){
        foreach ($included_files as $filename) {
            if($match){
                $status = true;
                foreach ($key as $val){
                    if(strpos($filename, $val) === false){
                        $status = false;
                        break;
                    }
                }
                if($status)$files[] = $filename;
            }else{
                $status = false;
                foreach ($key as $val){
                    if(strpos($filename, $val) !== false){
                        $status = true;
                        break;
                    }
                }
                if($status)$files[] = $filename;
            }
        }
    }else{
        $files = $included_files;
    }
    E($files, true);
}
?>