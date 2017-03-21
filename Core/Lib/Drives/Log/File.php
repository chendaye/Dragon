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

namespace Core\Lib\Drives\Log;
use Core\Lib\Conf;

class File{
    public $path;

    /**
     * 初始化路径
     * File constructor.
     */
    public function __construct()
    {
        $this->path = Conf::get('config','OPTION')['log_path'];
    }

    /**
     * @param $msg  mixed 日志内容
     * @param string $name  日志文件名
     * @return int 日志大小
     */
    public function log($msg, $name='log'){
        //检查路径是否存在,一个小时建一个目录
        $path = $this->path.'/'.date("YmdH");
        if(!is_dir($path)){
            mkdir($path, 0777);
        }
        $msg = date("Y-m-d H:i:s").json_encode($msg).PHP_EOL;   //加上换行
        //把日志内容写入文件
        $file_path = $path.'/'.$name.'.php';
        return file_put_contents($file_path, $msg, FILE_APPEND);    //以追加的形式写入
    }

    public function save($log){

    }
}
?>