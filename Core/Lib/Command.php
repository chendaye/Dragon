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
 * 抽象的命令基类
 * Class Commond
 * @package Core\Lib
 */
abstract class Command
{
    /**
     * 定义为final，则任何子类都不能覆盖父类的构造方法
     * Commond constructor.
     */
    public final function __construct(){}

    /**
     * 分发请求；具体的操作移到doExecute()方法中实现，子类中可以有不同的具体实现
     * @param Request $request
     */
    public function execute(Request $request)
    {
        $this->doExecute($request);
    }

    /**
     * 抽象方法，在子类中具体实现
     * @param Request $request
     * @return mixed
     */
    abstract public function doExecute(Request $request);
}
?>