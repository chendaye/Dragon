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
 *观察者接口
 * Interface Observe
 * @package Core\Lib\Observe
 */
interface Observe
{
    /**
     * 持有一个功能主体的实例
     * @param Event $event
     * @return mixed
     */
    public function execute(Event $event);
}
?>