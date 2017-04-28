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
 * 两个抽象方法，定义核心的储存获取操作
 * Class Registry
 * @package Core\Lib
 */
abstract class Registry
{
    /**
     * 获取数据,大致起着全局变量的作用
     * @param $key string
     * @return mixed
     */
    abstract protected function get($key);

   /**
     *设置数据
     * @param $key string
     * @param $val mixed
     * @return mixed
     */
    abstract protected function set($key, $val);
}
?>