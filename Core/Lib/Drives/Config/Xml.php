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
 * xml解析
 * Class Xml
 * @package Core\Lib\Drives\Config
 */
class Xml{
    /**
     * 解析xml内容
     * @param $content
     * @return array
     */
    public function xml($content)
    {
        if (is_file($content)) {
            $msg= simplexml_load_file($content);
        } else {
            $msg = simplexml_load_string($content);
        }
        $result = (array) $msg; //把结果转化为数组
        foreach ($result as $key => $val) {
            if (is_object($val)) {
                $result[$key] = (array) $val;   //对象转化为数组
            }
        }
        return $result;
    }
}
?>