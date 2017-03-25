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
 * Cookie
 * Class Cookie
 * @package Core\Lib
 */
class Cookie{
    //配置
    static protected $configure = [
        'PREFIX' => '',  //前缀
        'EXPIRE' => 0,
        'PATH'   => '/',
        'DOMAIN' => '',
        'SECURE' => false,
        'HTTPONLY' => '',
        'SETCOOKIE' => true,
    ];

    static protected $init = null;

    /**
     * 初始化
     * @param array $config
     */
    static public function init(array $config = []){
        if(empty($config)) $config = Conf::get('COOKIE');
        self::$configure = array_merge(self::$configure, $config);
        if(self::$configure['HTTPONLY']) ini_set('session.cookie_httponly', 1);
        self::$init = true;
    }

    /**
     * 设置或获取cookie作用域
     * @param string $prefix
     * @return mixed
     */
    static public function prefix($prefix = ''){
        if(empty($prefix)) return self::$configure['PREFIX'];
        self::$configure['PREFIX'] = $prefix;
    }

    /**
     * 获取cookie
     * @param $name
     * @param null $prefix
     * @return mixed|null|string
     */
    static public function get($name, $prefix = null){
        is_null(self::$init) && self::init();
        $prefix = is_null($prefix)?self::$configure['PREFIX']:$prefix;
        $name = $prefix.'_'.$name;
        if(isset($_COOKIE[$name])){
            $value = $_COOKIE[$name];
            //json数组先解码
            if(strpos($value, 'dragon:') === 0){
                $value = substr($value, 7);
                $value = json_decode($value, true);
                $value = (array)$value;
                // 对数组中的每个成员递归地应用用户函数
                array_walk_recursive($value, 'self::jsonFormat', 'decode');
            }
            return $value;
        }else{
            return null;
        }
    }

    /**
     * 设置session
     * @param $name
     * @param $data
     * @param null $option
     */
    static public function set($name, $data, $option = null){
        is_null(self::$init) && self::init();
        //参数设置,覆盖默认参数,当次有效
        if(!is_null($option)){
            if(is_numeric($option)){
                $option = ['EXPIRE' => $option];    //过期时间
            }elseif (is_string($option)){
                parse_str($option, $option);
            }
            $configure = array_merge(self::$configure, array_change_key_case($option, CASE_UPPER ));
        }else{
            $configure = self::$configure;
        }
        $prefix = $configure['PREFIX'];
        //cookie名
        $name = $prefix.'_'.$name;
        //设置cookie
        if(is_array($data)){
            //编码成URL字符串
            array_walk_recursive($data, 'self::jsonFormat', 'encode');
            //转化为json格式
            $data = 'dragon:'.json_encode($data);
        }
        //过期时间
        $expire = !empty($configure['EXPIRE'])?$_SERVER['REQUEST_TIME_FLOAT']+intval($configure['EXPIRE']):0;
        if($configure['SETCOOKIE']){
            //定义了 Cookie，会和 HTTP 头一起发送给客户端
            setcookie($name, $data, $expire, $configure['PATH'], $configure['DOMAIN'], $configure['SECURE'], $configure['HTTPONLY']);
        }
        $_COOKIE[$name] = $data;
    }

    /**
     * 判断cookie是否存在
     * @param $name
     * @param null $prefix
     * @return bool
     */
    static public function exist($name, $prefix = null){
        is_null(self::$init) && self::init();
        $prefix = is_null($prefix)?self::$configure['PREFIX']:$prefix;
        $name = $prefix.'_'.$name;
        return isset($_COOKIE[$name]);
    }

    /**
     * 清空cookie
     * @param null $prefix
     * @return bool|null
     */
    static public function clear($prefix = null){
        //cookie不存在啥也不干
        if(empty($_COOKIE)) return null;
        is_null(self::$init) && self::init();
        $prefix = is_null($prefix)?self::$configure['PREFIX']:$prefix;
        $configure = self::$configure;
        //前缀为空清空所有
        if(!$prefix) {
            $_COOKIE = [];
            return true;
        }
        foreach ($_COOKIE as $key => $value){
            //前缀
            if(strpos($key, $prefix) === 0){
                unset($_COOKIE[$key]);
                if($configure['SETCOOKIE']){
                    //清空发送的 cookie
                    setcookie($key, '', $_SERVER['REQUEST_TIME_FLOAT'] - 3600, $configure['PATH'], $configure['DOMAIN'], $configure['SECURE'], $configure['HTTPONLY']);
                }
            }
        }
        return true;
    }

    /**
     * @param $name
     * @param null $prefix
     */
    static public function delete($name, $prefix = null){
        is_null(self::$init) && self::init();
        $prefix = is_null($prefix)?self::$configure['PREFIX']:$prefix;
        $config = self::$configure;
        $name = $prefix.'_'.$name;
        if($config['SETCOOKIE']){
            //清空发送的 cookie
           setcookie($name, '', $_SERVER['REQUEST_TIME_FLOAT'] - 3600, $config['PATH'], $config['DOMAIN'], $config['SECURE'], $config['HTTPONLY']);
        }
        unset($_COOKIE[$name]);
    }

    /**
     * @param $val
     * @param string $type
     */
    static private function jsonFormat(&$val, $type = 'encode'){
        if(!empty($val && $val !== true)){
            if($type == 'encode'){
                //将字符串编码并将其用于 URL 的请求部分
                $val = urlencode($val);
            }elseif ($type == 'decode'){
                //解码URL编码的字符串
                $val = urldecode($val);
            }
        }
    }
}
?>