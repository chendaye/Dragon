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

namespace Core\Lib\Driver\Log;

/**
 * 日志驱动接口
 * Interface Driver
 * @package Core\Lib\Driver\Config
 */
interface Driver{
    /**
     * 保存日志内容内容
     * @param array $content 日志内容
     * @return mixed
     */
    public function save(array $content);

    /**
     * 获取运行时信息
     * @return mixed
     */
    public function request_info();
}
?>