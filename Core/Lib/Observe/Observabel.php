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
/**
 * 主体功能类接口，定义注册，删除，通知观察者的基本功能
 * Interface Observabel
 * @package Core\Lib\Observe
 */
interface Observabel {
    /**
     *注册观察者
     * @param Observe $observe
     * @return mixed
     */
    public function attach(Observe $observe);

    /**
     * 删除观察者
     * @param Observe $observe
     * @return mixed
     */
    public function detach(Observe $observe);

    /**
     * 通知观察者
     * @return mixed
     */
    public function notify();
}
?>