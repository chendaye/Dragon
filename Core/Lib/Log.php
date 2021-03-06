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

class Log{
    const LOG = 'LOG';
    const ERROR = 'ERROR';
    const INFO = 'INFO';
    const SQL = 'SQL';
    const NOTICE = 'NOTICE';
    const ALERT = 'ALERT';
    static protected $info = []; //日志信息
    static protected $config = [];  //配置信息
    static protected $type = ['log', 'error', 'info', 'sql', 'notice', 'alert'];    //日志类型
    static protected $drive;   //日志驱动
    static protected $power;    //日志授权

    /**
     * 日志驱动
     * @param array $config
     */
    static public function init($config = []){
        $logtype = isset($config['type'])?$config['type']:'File';   //默认日志写入文件
        $drive = 'Core\Lib\Drives\Log\\'.ucwords($logtype);   //驱动类
        self::$config = $config;
        unset($config['type']);
        DragonException::error(class_exists($drive), "类{$drive}不存在！");
        self::$drive = new $drive();   //日志驱动
        //记录日志驱动初始化信息
        static::log('[ LOG ] INIT'.$logtype, 'info');
        return self::$drive;
    }

    /**
     * 获取日志
     * @param string $type
     * @return array|mixed
     */
    static public function getLog($type = ''){
        return $type?self::$info['$type']:self::$info;
    }

    /**
     * 记录日志信息
     * @param $msg
     * @param string $type
     */
    static public function log($msg, $type = 'log'){
        self::$info[$type] = $msg;
    }

    /**
     * 清空日志信息
     */
    static public function clear(){
        self::$info = [];
    }

    /**
     * 当前日志记录的授权 power
     * @param $power
     */
    static public function power($power){
        self::$power = $power;
    }

    /**
     * 检查日志的写入权限
     * @param $config
     * @return bool
     */
    static public function check($config){
        if(self::$power && !empty($config['allow']) && !in_array(self::$power, $config['allow'])){
            return false;   //禁止写入
        }
        return true;
    }
    static public function save(){
        if(empty(self::$info)) return false;    //日志信息是否为空
        if(is_null(self::$drive)) self::$drive = self::init(Conf::get('config', 'LOG'));

    }
}
?>