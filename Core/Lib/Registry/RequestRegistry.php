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
use Core\Lib\Request;

/**
 * 请求级别的注册表，保存请求对象
 * Class RequestRegistry
 * @package Core\Lib\Registry
 */
class RequestRegistry extends Registry{
    private $request = [];  //保存请求对象
    private static $instance;

    /**
     * 私有的构造方法，实现单例模式
     * RequestRegistry constructor.
     */
    private function __construct(){}

    /**
     * 实例化自身，
     * @return RequestRegistry
     */
    public static function instance(){
        if(!isset(self::$instance)){
            self::$instance = new self();
        }
        return self::$instance; //获取单例对象
    }

    /**
     * 静态方法获取请求对象
     * @return bool
     */
    static public function getRequest(){
        //请求对象
        $instance = self::instance()->get('request');
        if(!$instance) return null;
        return $instance;
    }

    /**
     * 储存请求对象，便于集中管理
     * @param Request $request  路由对象，按理请求对象更合理，之后再处理
     * @return bool
     */
    static public function setRequest(Request $request){
        //请求对象
        $instance = self::instance()->get('request');
        if($instance) return false;
        self::instance()->set('request',$request); //保存请求对象
    }

    /**
     * 内部方法
     * 获取请求对象的实例
     * @param string $key   数据键值
     * @return mixed    请求对象的实例
     */
    protected function get($key){
        if(!isset($this->request[$key])) return false;
        return $this->request[$key];
    }

    /**
     * 内部方法
     * 保存请求对象
     * @param string $key  数据键值
     * @param mixed $val    对象值
     * @return bool 是否保存成功
     */
    protected function set($key, $val){
        if(isset($this->request[$key])) return false;
        $this->request[$key] = $val;    //保存对象
        return true;
    }
}
?>