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

use Prophecy\Exception\Doubler\ClassNotFoundException;

class Session{
    //作用范围前缀
    static protected $prefix = '';

    //记录初始化状态
    static protected $init = null;

    /**
     * 设置作用域前缀
     * @param $prefix
     * @return string
     */
    static public function scope($prefix){
        if(!empty($prefix) || $prefix === null) self::$prefix = $prefix;
        return self::$prefix;
    }

    /**
     * session配置初始化
     * @param array $config
     */
    static public function init(array $config = []){
        if(empty($config)) $config = Conf::get('SESSION');  //去取session配置
        Log::log('[SESSION] INIT '.var_export($config, true), 'info');    //记录日志信息

        //默认session已经开启
        $start = false;
        //session跨页传送
        if(isset($config['USE_TRANS_SID'])){
            ini_set('session.use_trans_sid', $config['USE_TRANS_SID']?1:0);
        }
        //自动启动session;PHP_SESSION_ACTIVE 如果会话被启用，并且存在一个
        if(!empty($config['AUTO_START']) && session_status() != PHP_SESSION_ACTIVE){
            ini_set('session.auto_start', 0);   //开启回话要调用session_start()
            $start = true;  //session没有开启
        }
        //范围前缀
        if(isset($config['PREFIX']) && !empty($config['PREFIX'])) self::$prefix = $config['PREFIX'];

        //设置session_id
        if(isset($config['VAR_SESSION_ID']) && !empty($_REQUEST[$config['VAR_SESSION_ID']])){
            session_id($_REQUEST[$config['VAR_SESSION_ID']]);   //获取并设置网页请求中的session_id
        }elseif(isset($config['ID']) && !empty($config['ID'])){
            session_id($config['ID']);  //指定的session_id
        }

        //session名
        if(isset($config['NAME']) && !empty($config['NAME'])) session_name($config['NAME']);

        //session存放路径
        if(isset($config['PATH']) && !empty($config['PATH'])) session_save_path($config['PATH']);

        //session跨域，不同服务器公用客户端session
        if(isset($config['DOMAIN']) && !empty($config['DOMAIN'])) ini_set('session.cookie_domain', $config['DOMAIN']);

        //存储时间
        if(isset($config['EXPIRE']) && !empty($config['EXPIRE'])) {
            ini_set('session.gc_maxlifetime', $config['EXPIRE']);   //Session数据在服务器端储存的时间
            ini_set('session.cookie_lifetime', $config['EXPIRE']);  //SessionID在客户端Cookie储存的时间，默认是0
        }

        //是否用coolie保存session
        if (isset($config['USE_COOKIES']))  ini_set('session.use_cookies', $config['USE_COOKIES'] ? 1 : 0);

        //指定会话页面所使用的缓冲控制方法
        if (isset($config['CACHE_LIMITER']) && !empty($config['CACHE_LIMITER'])) session_cache_limiter($config['CACHE_LIMITER']);

        //缓存的到期时间,默认180
        if (isset($config['CACHE_EXPIRE']) && !empty($config['CACHE_EXPIRE'])) session_cache_expire($config['CACHE_EXPIRE']);

        //驱动
       if(!empty($config['TYPE'])){
           $drive = '';
           if(strpos($config['TYPE'], '\\') !== false) $drive = $config['TYPE'];
           $drive = '\\Core\\Lib\\Drives\\Session\\'.ucwords($config['TYPE']);
           //检查驱动、用户自定义会话存储函数
           if(!class_exists($drive) && !session_set_save_handler(new $drive($config))){
               throw new ClassNotFoundException('session handler 错误'.$drive, $drive);
           }
       }

       //启动session，记录初始化状态
       if($start){
           //session成功初始化，并开启
           session_start();
           self::$init = true;
       }else{
           //session已经开启，初始化失败
           self::$init = false;
       }
    }

    /**
     * session启动或者初始化
     */
    static public function start(){
        if(self::$init === null){
            self::init();   //初始化
        }elseif(self::$init === false){
            if(session_status() != PHP_SESSION_ACTIVE) session_start(); //重新启动
            self::$init = true;
        }
    }

    /**
     * 设置session
     * @param $name
     * @param string $value
     * @param null $prefix
     */
    static public function set($name, $value = '', $prefix = null){
        //初始化
        self::start();
        //范围前缀
        $prefix = !is_null($prefix)?$prefix:self::$prefix;
        //二维数组赋值支持
        if(strpos($name, '.')){
            list($one_level, $two_level) = explode('.', $name);
            if($prefix){
                $_SESSION[$prefix][$one_level][$two_level] = $value;
            }else{
                $_SESSION[$one_level][$two_level] = $value;
            }
        }else{
            if($prefix) {
                $_SESSION[$prefix][$name] = $value;
            }else{
                $_SESSION[$name] = $value;
            }
        }
    }

    /**
     * 获取session
     * @param string $name  名称
     * @param null $prefix  作用范围
     * @return array  返回值
     */
    static public function get($name = '', $prefix = null){
        //session初始化
        self::start();
        //作用范围
        $prefix = is_null($prefix)?self::$prefix:$prefix;
        if(empty($name)){
            //返回全部session
            $content = !empty($prefix)?(!empty($_SESSION[$prefix])?$_SESSION[$prefix]:[]):$_SESSION;
        }else{
            if($prefix){
                if(strpos($name, '.')){
                    list($one_level, $two_level) = explode('.', $name);
                    $content = isset($_SESSION[$prefix][$one_level][$two_level])?$_SESSION[$prefix][$one_level][$two_level]:null;
                }else{
                    $content = isset($_SESSION[$prefix][$name])?$_SESSION[$prefix][$name]:null;
                }
            }else{
                if(strpos($name, '.')){
                    list($one_level, $two_level) = explode('.', $name);
                    $content = isset($_SESSION[$one_level][$two_level])?$_SESSION[$one_level][$two_level]:null;
                }else{
                    $content = isset($_SESSION[$name])?$_SESSION[$name]:null;
                }
            }
        }
        return $content;
    }

    /**
     * 获取并删除
     * @param string $name  session名
     * @param null $prefix  作用范围
     * @return array|null  返回值
     */
    static public function obtain($name = '', $prefix = null){
        $content = self::get($name, $prefix);
        if($content){
            self::delete($name, $prefix);
            return $content;
        }else{
            return null;
        }
    }

    /**
     * 删除session，支持数组递归删除
     * @param string|array $name 名称
     * @param null $prefix  作用范围
     */
    static public function delete($name, $prefix = null){
        //初始化
        self::start();
        $prefix = is_null($prefix)?self::$prefix:$prefix;
        //支持数组批量删除
        if(is_array($name)){
            foreach ($name as $val){
                self::delete($val, $prefix);    //递归删除
            }
        }elseif(strpos($name, '.')){
            list($one_level, $two_level) = explode('.', $name);
            if($prefix){
                unset($_SESSION[$prefix][$one_level][$two_level]);
            }else{
                unset($_SESSION[$one_level][$two_level]);
            }
        }else{
            if($prefix){
                unset($_SESSION[$prefix][$name]);
            }else{
                unset($_SESSION[$name]);
            }
        }
    }

    /**
     * 判断指定的session是否存在
     * @param string $name  名称
     * @param null $prefix  范围前缀
     * @return bool
     */
    static public function exist($name, $prefix = null){
        //初始化
        self::start();
        //范围
        $prefix = is_null($prefix)?self::$prefix:$prefix;
        if(strpos($name, '.')){
            list($one_level, $two_level) = explode('.', $name);
            return $prefix?isset($_SESSION[$prefix][$one_level][$two_level]):isset($_SESSION[$one_level][$two_level]);
        }else{
            return $prefix?isset($_SESSION[$prefix][$name]):isset($_SESSION[$name]);
        }
    }

    /**
     * 设置下一次请求有效
     * @param string $name session名
     * @param mixed $value  session值
     * @param null $prefix  作用范围
     */
    static public function flash($name, $value, $prefix = null){
        $prefix = is_null($prefix)?self::$prefix:$prefix;
        self::set($name, $value, $prefix);
        if(!self::exist('__flash__.__time__')){
            self::set('__flash__.__time__', $_SERVER['REQUEST_TIME_FLOAT'], $prefix);   //REQUEST_TIME_FLOAT请求开始时的时间戳
        }
        self::push('__flash__', $name, $prefix);
    }

    static public function flush(){

    }
    /**
     * 添加一个数据到session数组
     * @param string $key session数组名
     * @param mixed $value  要添加的值
     * @param string $prefix  作用范围
     */
    static public function push($key, $value, $prefix = null){
        $prefix = is_null($prefix)?self::$prefix:$prefix;
        $result = self::get($key, $prefix);  //取出数组
        if(is_null($result)){
            $result = [];
        }
        $result[] = $value;  //追加数据
        self::set($key, $result, $prefix);  //重新赋值
    }

}
?>











