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
use Core\Lib\Registry\RequestRegistry;

/**
 * 业务逻辑工厂，获取请求对应的业务逻辑类实例；可直接执行业务逻辑类方法
 * Class ControllerFactory
 * @package Core\Lib
 */
class ControllerFactory{
    static private $ctrl;

    /**
     * 业务逻辑工厂
     * 用反射提高安全性
     * @return object
     */
    static public function ctrl(){
        $request = RequestRegistry::getRequest();
        $controller = $request->getProperty('controller');
        $classname = '\\Controller\\'.$controller;
        DragonException::error(class_exists($classname),"业务类{$classname} 不存在！"); //业务类不存在抛出错误
        DragonException::insCheck($classname,'\Core\Lib\Controller');
        $instance = new \ReflectionClass($classname);
        return $instance->newInstance();
    }

    /**
     * 根据请求直接调用默认的业务类，执行请求对应的方法
     */
    static public function action(){
        //从注册表中获取请求对象
        $request = RequestRegistry::getRequest();
        //从请求对象中获取请求方法
        $action = $request->getProperty('action');
        //用反射检查方法
        $instance = self::ctrl();
        $ref = new \ReflectionClass($instance);
        $fun = $ref->getMethod($action);
        DragonException::error($fun,"业务类".$ref->getName()."中，不存在".$fun->name."方法！");
        //执行方法
        return $instance->$action();
    }
}
?>