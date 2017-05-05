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

namespace Core\Lib\Observe;
use Core\Lib\Exception\DragonException;
use Core\Lib\Registry\ObserveRegistry;
use Observer\Provider;

/**
 * 提供事件的相关信息
 * Class Event
 * @package Core\Lib\Observe
 */
class Event implements Observabel
{
    protected $listen;  //监听器

    /**
     * 事件初始化监听器
     * Event constructor.
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * 事件监听器初始化
     */
    protected function init()
    {
        $listen_event = Provider::Get(get_class($this));
        //注册用户已经定义的监听器
        foreach ($listen_event as $listen){
            $this->attach(new $listen);
        }
    }

    /**
     * 在事件中注册观察者
     * @param Observe $observe
     * @return bool
     */
    public function attach(Observe $observe)
    {
        // TODO: Implement attach() method.
        $classname = get_class($observe);
        //观察者不存在就注册
        if(empty($this->listen[$classname])){
            $this->listen[$classname] = $observe;
        }else{
            return false;
        }
    }

    /**
     * 从事件中删除观察者
     * @param Observe $observe
     * @return bool
     */
    public function detach(Observe $observe)
    {
        // TODO: Implement detach() method.
        $classname = get_class($observe);
        //观察者不存在返回FALSE
        if(empty($this->listen[$classname])){
            return false;
        }
        $new_listen = [];
        foreach ($this->listen as $k => $v){
            if($v != $observe){
                $new_listen[$k] = $v;
            }
        }
        $this->listen = $new_listen;
    }

    /**
     * 事件的所有观察者响应事件的发生
     */
    public function notify()
    {
        // TODO: Implement notify() method.
        //监听数组为空抛出异常
        if(empty($this->listen)){
            $ref = new \ReflectionClass($this);
            $event = $ref->getName();
            throw new \Exception("事件".$event."没有被监听！");
        }
        foreach ($this->listen as $listen){
            $listen->execute($this);    //观察者持有一个 ，事件相关的对象实例，用来获取事件信息
        }
    }

    /**
     * 触发事件
     * @param  $event string 要触发的事件
     * @return  void
     */
    static public function Go($event)
    {
        DragonException::error(class_exists($event),"事件{$event} 不存在！");
        $reg = ObserveRegistry::instance();    //获取事件注册表单例
        $event_obj = $reg::getEvent($event);    //获取要触发的事件
        $event_obj->notify();
    }
}
?>