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

namespace Core\Lib\Drives\Config;

/**
 * 配置解析驱动接口
 * Interface Drives
 * @package Core\Lib\Drives\Config
 */
interface Drives{
    /**
     * 解析配置内容
     * @param  $content
     * @return mixed
     */
    public function resolve($content);
}
?>