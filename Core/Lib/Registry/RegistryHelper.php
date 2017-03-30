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

namespace Core\Lib\Registry;
abstract class RegistryHelper{
    /**
     * 初始化注册信息
     * @return mixed
     */
    abstract public function init();

    /**
     * 获取系统信息，并在注册表中注册
     * @param array $option  配置信息
     * @param bool $flash
     * @return mixed
     */
    abstract  protected function registryOption(array $option = [], $flash = false);

}
?>