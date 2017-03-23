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

class Session{
    //作用范围前缀
    static protected $prefix = '';

    static protected $init = null;

    /**
     * 设置作用域前缀
     * @param $prefix
     * @return string
     */
    static public function scope($prefix){
        if(!empty($prefix) || $prefix === null) self::$prefix = $prefix;
        return self::$prefix;
    }
    static public function init(array $config){
        if(empty($config)) $config = Conf::get('SESSION');

    }
}
?>