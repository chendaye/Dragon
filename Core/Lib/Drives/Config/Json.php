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
 * 解析json
 * Class Json
 * @package Core\Lib\Drives\Config
 */
class Json{
    /**
     * 解析json
     * @param $content
     * @return mixed
     */
    public function json($content)
    {
        if (is_file($content)) $content = file_get_contents($content);
        $result = json_decode($content, true);
        return $result;
    }
}
?>