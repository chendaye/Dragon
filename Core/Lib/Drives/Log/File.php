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
class File implements Drives {
    //默认配置参数
    private $configure = [
        'TIME_FORMAT' => 'c',   //ISO-8601 标准的日期（例如 2013-05-05T16:34:42+00:00）
        'SIZE'        => 1024*2048,
        'PATH'    => LOG,
        'APART_LEVEL'       => []
    ];
    /**
     * 初始化配置信息
     * File constructor.
     * @param array $config
     */
    public function __construct($config = []){
        if(is_array($config)){
            $this->configure = array_merge($this->configure, $config);  //配置文件优先级更高
        }
    }
    public function save(array $content){
        $now = date($this->configure['TIME_FORMAT']);
        $log_path = $this->configure['PATH'].date('Ym').SP.date('d').'.log'; //以月为单位创建文件夹，天为单位创建日志文件
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
}
?>