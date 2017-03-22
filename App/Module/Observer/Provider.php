<?php
namespace Observer;
use Core\Lib\DragonException;

/**
 * 用户自定义监听事件
 * Class Provider
 * @package Observer
 */
class Provider{
    //注册监听事件
    static private $observe = [
        'Observer\Event\Login' => [
            'Observer\Listen\Login',
            'Observer\Listen\Usr',
        ],
    ];

    /**
     * 获取用户自定义添加的监听事件
     * @param $key
     * @return mixed
     */
    static public function Get($key){
        DragonException::error(!empty(self::$observe[$key]),"监听事件{$key}未注册！");
        return self::$observe[$key];
    }

    /**
     * 获取所有的注册事件
     * @return array|null
     */
    static public function Thing(){
        if(empty(self::$observe)){
            return null;    //未注册任何事件
        }
        return array_keys(self::$observe);  //返回注册事件的数组
    }
}
?>