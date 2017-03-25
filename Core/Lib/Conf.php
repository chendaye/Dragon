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
class Conf{
    //配置参数
    static private $config = [];

    //参数作用范围，配置数组的键名
    static private $range = 'sys';

    /**
     * 设置配置参数的作用域
     * @param string $range 作用域
     */
    static public function setRange($range){
        self::$range = $range;
        if(!isset(self::$config[$range])){
            self::$config[$range] = [];
        }
    }

    /**
     * 解析xml json init 配置内容
     * @param $conf
     * @param string $type
     * @param string $name
     * @param string $range
     * @return mixed|void
     */
    static public function analysis($name = '', $conf, $type = '',  $range = ''){
        $range = !empty($range)?$range:self::$range;    //作用范围
        if(!$type){
            $type = pathinfo($conf, PATHINFO_EXTENSION);    //获取配置文件扩展名
        }
        $driver = 'Core\\Lib\\Drives\\Config\\'.ucwords($type);
        $action = strtolower($type);
        $instance = new $driver();
        return self::set($name, $instance->$action($conf), $range);
    }
    /**
     * @param string $name 配置名
     * @param null $value  配置值
     * @param string $range  配置范围
     * @return mixed|void  返回配置值
     */
    static public function set($name, $value = null, $range){
        $range = $range?:self::$range;
        if(!isset(self::$config[$range])){
            self::$config[$range] = []; //配置数组
        }
        //单个设置
        if(is_string($value) || is_numeric($value) || is_bool($value)){
            if(!strpos($name, '.')){
                self::$config[$range][strtolower($name)] = $value;
            }else{
                $name = explode('.', $name);
                self::$config[$range][$name[0]][$name[1]] = $value;     //支持二级数组，名称用点号分割
            }
            return;
        }
        //批量设置
        if(is_array($value)){
            if(!empty($name)){
                if(isset(self::$config[$range][$name])){
                    self::$config[$range][$name] = array_merge(self::$config[$range][$name], $value);   //覆盖
                }else{
                    self::$config[$range][$name] = $value;  //设置
                }
                return self::$config[$range][$name];
            }
        }
        return self::$config[$range];   //配置值为空，直接返回范围配置数组
    }
}
?>