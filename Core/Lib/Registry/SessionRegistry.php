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

namespace Core\Lib\Registry;
use Controller\Index;

class SessionRegistry extends Registry{
    private static $instance;

    /**
     * 单例构造方法
     * SessionRegistry constructor.
     */
    private function __construct(){
        session_start();    //开启session
    }

    /**
     * 单例实例
     * @return SessionRegistry
     */
    public static function instance(){
        if(!isset(self::$instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 获取会话信息session
     * @param string $key
     * @return null
     */
    protected function get($key)
    {
        if(isset($_SESSION[__CLASS__][$key])){  //__CLASS__ 当前类名
            return $_SESSION[__CLASS__][$key];  //会话信息
        }
        return null;
    }

    /**
     * 设置保存会话信息
     * @param string $key
     * @param mixed $val
     * @return null
     */
    protected function set($key, $val)
    {
        $_SESSION[__CLASS__][$key] = $val;  //保存会话信息
    }

    /**
     * Index 类的会话信息
     * @return null
     */
    public static function getIndex(){
        return self::instance()->get('index');    //子类的功能
    }

    /**
     * 设置Index 类的会话信息
     * @param Index $index
     */
    public static function setIndex(Index $index){
        self::instance()->set('index',$index);
    }

}
?>