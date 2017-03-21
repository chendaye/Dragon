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

/**
 * 获取框架配置信息
 * Class Conf
 * @package Core\Lib
 */
class Conf{
    //配置参数
    static private $config = [];

    //参数作用范围，配置数组的键名
    static private $range = 'SYS';

    /**
     * 获取配置
     * @param null|string $name  配置名
     * @param string $range     配置作用范围
     * @return mixed|null       配置值
     */
    static public function get($name = NULL, $range = ''){
        $range = $range?:self::$range;
        if($name === NULL) return isset(self::$config[$range])?self::$config[$range]:NULL;  //不指定配置名称获取所有配置
        $config = self::exist($name, $range);
        return $config;
    }

    /**
     * 加载配置文件
     * @param string $filename  配置文件名
     * @param string $name  配置名
     * @param string $range  配置作用范围
     * @return mixed|void  获取的配置
     */
    static public function cfgFile($filename, $name = '', $range = ''){
        $range = $range?:self::$range;
        if(!isset(self::$config[$range])) self::$config[$range] = []; //配置数组
        //读取配置文件内容
        if(is_file(CONFIG.$filename)){
            $type = pathinfo($filename, PATHINFO_EXTENSION);
            if($type == 'php'){
                $configure = Load::insulate_require(CONFIG.$filename);
            }elseif($type == 'yaml' && function_exists('yaml_parse_file')){
                $configure = yaml_parse_file(CONFIG.$filename);
            }else{
                $configure = self::analysis(CONFIG.$filename);
            }
            return self::set($name, $configure, $range);    //缓存配置信息
        }
    }

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
     * @return mixed|void
     */
    static public function analysis($conf, $type = ''){
        if(!$type){
            $type = pathinfo($conf, PATHINFO_EXTENSION);    //获取配置文件扩展名
        }
        $driver = 'Core\\Lib\\Drives\\Config\\'.ucwords($type);
        $instance = new $driver();
        return $instance->resolve($conf);
    }

    /**
     * @param string $name 配置名
     * @param null $value  配置值
     * @param string $range  配置范围
     * @return mixed|void  返回配置值
     */
    static public function set($name, $value = null, $range){
        $range = $range?:self::$range;
        if(!isset(self::$config[$range])) self::$config[$range] = []; //配置数组
        if(is_array($value)) (new Collection())->keyToCase($value);  //配置键名转化为大写
        if(empty($name)) {
            self::$config[$range] = $value;
            return;
        }
        $name = strtoupper($name);  //配置名转化成大写
        //单个设置
        if(is_string($value) || is_numeric($value) || is_bool($value)){
            if(!strpos($name, '.')){
                self::$config[$range][$name] = $value;
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

    /**
     * 检查配置是否存在
     * @param $name
     * @param string $range
     * @return null
     */
    static public function exist($name, $range = ''){
        $range = $range?:self::$range;
        $name = strtoupper($name);
        //是否存在
        if(strpos($name, '.')){
            $name = explode('.', $name);
            if(isset(self::$config[$range][$name[0]][$name[1]])) return self::$config[$range][$name[0]][$name[1]];
        }else{
            if(isset(self::$config[$range][$name])) return self::$config[$range][$name];
        }
        return NULL;
    }

    /**
     * 重置配置
     * @param string|bool $range true：重置全部参数
     */
    static public function reset($range = ''){
        $range = $range?:self::$range;
        if($range === true){
            self::$config = []; //重置全部配置
        }else{
            if(isset(self::$config[$range])) self::$config[$range] = [];
        }
    }
}
?>