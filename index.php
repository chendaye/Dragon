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
<<<<<<< HEAD
// 应用入口文件
// 检测PHP环境
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.4.0 !');
=======
// 应用入口文件
// 检测PHP环境
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.4.0 !');
>>>>>>> 182e2b620ce0b915f25570c1a229789e5e3203e5
// 开启调试模式
define('DEBUG',True);
//目录分隔符
define('SP', DIRECTORY_SEPARATOR);
// 定义应用目录
define('APP', __DIR__.SP.'App'.SP);
//框架资源目录
define('PUB', __DIR__.SP.'Public'.SP);
// 引入DragonPHP入口文件
require '.'.SP.'Core'.SP.'DragonGo.php';
?>