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

namespace Core\Lib;
class LogSelf{
    static $class;

    /**
     * 初始化获取log类实例
     */
    static public function init(){
        //获取驱动类型 实例，并且保存在静态属性中
        $dirves = Conf::get('config','LOG');
        $classname = '\\Core\Lib\Drives\Log\\'.$dirves;
        $log = new $classname();
        self::$class = $log;
    }

    /**
     * 启用指定的日志方法
     * @param $msg
     * @param $name
     */
    static public function log($msg, $name){
        self::$class->log($msg,$name);
    }
}
?>