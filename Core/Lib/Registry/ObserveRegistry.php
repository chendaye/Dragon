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
use Core\Lib\DragonException;

/**
 * 监听事件注册表
 * Class ObserveRegistry
 * @package Core\Lib\Registry
 */
class ObserveRegistry extends Registry {
    static private $instance;
    private $event = []; //保存事件对象，键值就是事件名（类名+命名空间）

    /**
     * 单例
     * ObserveRegistry constructor.
     */
    private function __construct(){}

    /**
     * 创建单例
     * @return ObserveRegistry
     */
    static public function instance(){
        if(!isset(self::$instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }
    /**
     * 监听事件的实例
     * @param string $key   数据键值
     * @return mixed    请求对象的实例
     */
    protected function get($key)
    {
        // TODO: Implement get() method.
        if(!isset($this->event[$key])) return false;
        return $this->event[$key];
    }

    /**
     * 保存监听事件对象
     * @param string $key  数据键值
     * @param mixed $val    对象值
     * @return bool 是否保存成功
     */
    protected function set($key, $val)
    {
        // TODO: Implement set() method.
        if(isset($this->event[$key])) return false;
        $this->event[$key] = $val;    //保存对象
        return true;
    }

    /**
     * 静态方法获取监听事件对象
     * @param $event string 事件名
     * @return mixed|null
     */
    public static function getEvent($event){
        if(!self::instance()->get($event)) return null;
        return self::instance()->get($event);
    }

    /**
     * 注册监听事件
     * @param $event string 监听事件名
     * @return bool
     */
    public static function setRequest($event){
        DragonException::error(class_exists($event),"事件{$event} 不存在！");
        DragonException::insCheck($event,'Core\Lib\Observe\Event');
        if(self::instance()->get($event)) return false;
        self::instance()->set($event,new $event()); //保存请求对象
    }
}
?>