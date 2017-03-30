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

    static public function init(array $config = []){
        if(empty($config)) $config = Conf::get('SESSION');  //去取session配置
        Log::log('[SESSION] INIT '.var_export($config), 'info');    //记录日志信息

        $start = false;
        //session跨页传送
        if(isset($config['USE_TRANS_SID'])){
            ini_set('session.use_trans_sid', $config['USE_TRANS_SID']?1:0);
        }
        //自动启动session;PHP_SESSION_ACTIVE 如果会话被启用，并且存在一个
        if(!empty($config['AUTO_START']) && session_start() != PHP_SESSION_ACTIVE){
            ini_set('session.auto_start', 0);
            $start = true;
        }
        //范围前缀
        if(isset($config['PREFIX']) && !empty($config['PREFIX'])) self::$prefix = $config['PREFIX'];

        //设置session_id
        if(isset($config['VAR_SESSION_ID']) && $_REQUEST[$config['VAR_SESSION_ID']]){
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
        if (isset($config['USE_COOKIES']))  ini_set('session.use_cookies', $config['use_cookies'] ? 1 : 0);

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
           session_start();
           self::$init = true;
       }else{
           self::$init = false;
       }
    }

    /**
     * session启动或者初始化
     */
    static public function start(){
        if(self::$init == null){
            self::init();   //初始化
        }elseif(self::$init === false){
            session_start(); //重新启动
            self::$init = true;
        }
    }

    static public function set($name, $value = '', $prefix = null){
        //检查是否启动,初始化
        if(self::$init === null) self::start();
        //范围前缀
        $prefix = !is_null($prefix)?$prefix:self::$prefix;
    }

}
?>