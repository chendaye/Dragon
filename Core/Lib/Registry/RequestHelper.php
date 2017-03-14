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
 * 初始化请求注册表
 * Class RequestHelper
 * @package Core\Lib\Registry
 */
class RequestHelper extends RegistryHelper {
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
    public static function instance(){
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
        //是否已经注册
        $request = RequestRegistry::getRequest();
        if(is_null($request)){
            //若没有注册，注册
            $this->registryOption('');    //调用方法注册
        }
    }
    /**
     * 在请求注册表中注册请求对象
     * @param array|string $var
     * @return mixed
     */
    protected function registryOption($var)
    {
        // TODO: Implement registryOption() method.
        RequestRegistry::setRequest(new Request()); //在注册表中注册
    }
}
?>