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
=======
>>>>>>> 182e2b620ce0b915f25570c1a229789e5e3203e5
//框架运行起始时间
define('DRAGON_START_TIME', microtime(true));
//起始内存使用情况
define('DRAGON_START_MEMORY', memory_get_usage());
<<<<<<< HEAD
=======
>>>>>>> 182e2b620ce0b915f25570c1a229789e5e3203e5
//获取框架根核心目录
define('CORE', __DIR__.SP);
//框架跟目录
define('DRAGON', dirname($_SERVER['SCRIPT_FILENAME']).SP);
//框架类文件目录
define('LIB', CORE.'Lib'.SP);
//框架公共函数
define('COM', CORE.'Common'.SP);
//Composer类库目录
define('VENDOR', DRAGON.'vendor'.SP);
//扩展类库目录
define('EXTEND', DRAGON.'Extend'.SP);
<<<<<<< HEAD
=======
>>>>>>> 182e2b620ce0b915f25570c1a229789e5e3203e5
//业务逻辑类目录
define('CONTROLLER', APP.'Module'.SP.'Controller'.SP);
//命令类目录
define('COMMAND', APP.'Module'.SP.'Command'.SP);
//观察者目录
define('OBSERVER', APP.'Module'.SP.'Observer'.SP);
//事件目录
define('EVENT', OBSERVER.'Event'.SP );
//监听器目录
define('LISTEN', OBSERVER.'Listen'.SP );
//数据层
define('MODEL',APP.'Module'.SP.'Model'.SP);
//视图目录
define('VIEW', APP.'Module'.SP.'View'.SP);
//运行时目录
define('RUNTIME', APP.'Runtime'.SP);
//配置
define('CONFIG', APP.'Config'.SP);
//模板缓存目录
define('TPL', APP.'Runtime'.SP.'TemplatesCache'.SP);
<<<<<<< HEAD
// 环境检测
define('IS_CML', php_sapi_name() == 'cli' ? true : false);
define('IS_WIN', strpos(php_uname() , 'WIN') !== false);
//常量
const EXT = '.php';   //类文件后缀
//框架基础类
require LIB."Load.php";
//自动加载
\Core\Lib\Load::register();
//错误调试
\Core\Lib\Error::registry();
$t = require CONFIG.'Map.php';
\Core\Lib\Load::addNamespace($t);
=======
// 环境检测
define('IS_CML', php_sapi_name() == 'cli' ? true : false);
define('IS_WIN', strpos(php_uname() , 'WIN') !== false);
//常量
const EXT = '.php';   //类文件后缀
//框架基础类
require LIB."Load.php";
//自动加载
\Core\Lib\Load::register();
//错误调试
\Core\Lib\Error::registry();
$t = require CONFIG.'Map.php';
\Core\Lib\Load::addNamespace($t);
>>>>>>> 182e2b620ce0b915f25570c1a229789e5e3203e5
\Core\Lib\Load::addNamespace([
    'Command'=>COMMAND.SP.'Back'.SP,
]);
\Core\Lib\Load::addNamespace('Command',COMMAND.SP.'Front'.SP);
<<<<<<< HEAD
\Core\Lib\Load::test();
=======
//\Core\Lib\Load::test();
E(dirtree(COMMAND));
>>>>>>> 182e2b620ce0b915f25570c1a229789e5e3203e5
exit;
//启动框架
\Core\Lib\Dragon::engine();
?>