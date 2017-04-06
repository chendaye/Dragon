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

namespace Core\lib;

/**
 * 业务逻辑类基类
 * Class Controller
 * @package Core\lib
 */
class Controller
{
    public $assign = [];   //存储变量

    /**
     * 存储视图变量
     * @param $key
     * @param $value
     */
    protected function assign($key, $value)
    {
        $this->assign[$key] = $value;   //赋值
    }
}
?>