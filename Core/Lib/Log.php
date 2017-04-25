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
use Core\Lib\Exception\ClassNotFoundException;


class Log
{
    const LOG = 'log';
    const ERROR = 'error';
    const INFO = 'info';
    const SQL = 'sql';
    const NOTICE = 'notice';
    const ALERT = 'alert';
    static protected $info = []; //日志信息
    static protected $config = [];  //配置信息
    static protected $level = ['log', 'error', 'info', 'sql', 'notice', 'alert'];    //日志类型
    static protected $drive;   //日志驱动
    static protected $power;    //日志授权

    /**
     * 初始化日志，获取日志配置，实例化对应日志驱动，保存日志配置
     * @param array $config
     */
    static public function init($config = [])
    {
        $logtype = isset($config['TYPE'])?$config['TYPE']:'File';   //默认日志写入文件
        $drive = 'Core\Lib\Drives\Log\\'.ucwords($logtype);   //驱动类
        self::$config = $config;
        unset($config['TYPE']);
        if(!class_exists($drive))throw new ClassNotFoundException("类{$drive}不存在！", $drive);
        self::$drive = new $drive($config);   //日志驱动
        //记录日志驱动初始化信息
        static::log('[ LOG ] INIT'.$logtype, 'info');
        return self::$drive;
    }

    /**
     * 获取日志
     * @param string $level
     * @return array|mixed
     */
    static public function getLog($level = '')
    {
        return $level?self::$info[$level]:self::$info;
    }

    /**
     * 记录日志信息
     * @param $msg
     * @param string $level
     */
    static public function log($msg, $level = 'log')
    {
        self::$info[$level] = $msg;
    }

    /**
     * 清空日志信息
     */
    static public function clear()
    {
        self::$info = [];
    }

    /**
     * 当前日志记录的授权 power
     * @param $power
     */
    static public function power($power)
    {
        self::$power = $power;
    }

    /**
     * 检查日志的写入权限,通过客户端的IP确定是否有写入日志的权限
     * @param $config
     * @return bool
     */
    static public function check($config)
    {
        if(self::$power && !empty($config['KEY']) && !in_array(self::$power, $config['KEY'])){
            return false;   //禁止写入
        }
        return true;
    }

    /**
     * 写入日志
     * @return bool
     */
    static public function save()
    {
        //日志信息是否为空
       if(empty(self::$info)) return false;
        //初始化日志配置
        if(is_null(self::$drive))  self::init(Conf::get('LOG'));
        //检查日志写入权限
        if(!self::check(self::$config)) return false;
        //获取日志的等级
        if(empty(self::$config['LEVEL'])){
            $log = self::$info; //没有限制记录的日志等级，写入全部日志
        }else{
            $log = [];
            foreach (self::$config['LEVEL'] as $level){
                if(isset(self::$info[$level])){
                    $log[$level] = self::$info[$level];     //记录相应等级的日志
                }
            }
        }
        $result = self::$drive->save($log);
        if($result) self::$info = [];   //日志写入后，清空缓存数据
        return $result;
    }

    /**
     * 动态手动写入日志
     * @param mixed $msg  记录的内容
     * @param string $level  写入日志等级
     * @param bool $force  是否强制写入日志
     * @return bool
     */
    static public function write($msg, $level = 'log', $force = false)
    {
        //初始化日志配置
        if(is_null(self::$drive))  self::init(Conf::get('LOG'));
        $content = [];
        if(empty(self::$config['LEVEL']) || $force == true){
            $content[$level][] = $msg;   //如果没有限制写入日志的等级，或者强制写入
        }elseif(in_array($level, self::$config['LEVEL'])){
            $content[$level][] = $msg;
        }else{
            return false;   //要写入的日志等级不再限制之列
        }
        $result = self::$drive->save($content);
        return $result;
    }

    /**
     * 调用不存在的静态方法是自动调用，记录日志
     * @param $name
     * @param $arguments
     * @return mixed
     */
    static public function __callStatic($name, $arguments)
    {
        //记录错误调用的信息
        if(in_array($name, self::$level)) return call_user_func_array('\\Core\\Log::log', [
            'static_method' => $name,
            'method_param'  => $arguments
        ]);
    }
}
?>