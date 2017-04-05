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
use Observer\Provider;

/**
 * 初始化请求注册表
 * Class RequestHelper
 * @package Core\Lib\Registry
 */
class ObserveHelper extends RegistryHelper
{
    private static $instance;

    /**
     * 单例构造方法
     * RequestHelper constructor.
     */
    private function __construct(){}

    /**
     * 获取单例实例
     * @return RequestHelper
     */
    public static function instance()
    {
        if(!isset(self::$instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 初始化注册表信息
     */
    public function init()
    {
        // TODO: Implement init() method.
        $thing = Provider::Thing();
        //注册所有事件
        foreach ($thing as $value){
            //是否已经注册
            $event = ObserveRegistry::getEvent($value);
            if(is_null($event)){
                //若没有注册，注册
                $this->registryOption($value);    //调用方法注册
            }
        }
    }

    /**
     * 在注册表中注册事件
     * @param array|string $event
     * @return  void
     */
    protected function registryOption($event)
    {
        // TODO: Implement registryOption() method.
        ObserveRegistry::setRequest($event); //在注册表中注册事件
    }
}
?>