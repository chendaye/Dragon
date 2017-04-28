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
 * 解析init配置文件
 * Class Ini
 * @package Core\Lib\Drives\Config
 */
class Ini implements Drives{
    /**
     * 解析init配置文件
     * @param $content
     * @return array
     */
    public function resolve($content)
    {
        if (is_file($content)) {
            return parse_ini_file($content, true);
        } else {
            return parse_ini_string($content, true);
        }
    }
}
?>