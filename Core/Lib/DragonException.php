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
/**
 * 异常类
 * Class DragonException
 * @package Dragon\Core\Lib
 */
class DragonException extends \Exception {
    /**
     * 自定义错误显示样式
     * @param $msg
     */
    static public function throw_exception($msg){
        echo '<div style="width:100%; background-color: crimson;color:black;font-size:20px; font-weight: 600;padding:20px 0px;">'.$msg.'</div>';
    }

    /**
     * 将异常抛出集中到方法中来
     * @param $expr mixed 判断的内容, 正确情况
     * @param $message  static  错误信息
     * @throws DragonException
     */
    static public function error($expr, $message){
        if(!$expr){
            throw new DragonException("$message");
        }
    }

    /**
     * 用反射检查类的类型
     * @param $check string  被检查的类名
     * @param $ischeck string  基准类的类名
     */
    static public function insCheck($check, $ischeck){
        $ref = new \ReflectionClass($check);
        $isref = new \ReflectionClass($ischeck);
        self::error($ref->isSubclassOf($isref),"{$check} 的类型错误，非{$ischeck} 的子类");

    }
}
?>