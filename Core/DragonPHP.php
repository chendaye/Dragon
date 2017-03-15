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

/**
 * 框架引导文件
 * 定义常量
 * 加载函数库
 * 启动框架
 */

define('DRAGON_START_TIME', microtime(true));   //框架运行起始时间
define('DRAGON_START_MEMORY', memory_get_usage());  //起始内存使用情况
define('SP', DIRECTORY_SEPARATOR);    //目录分隔符
define('DRAGON', __DIR__.SP);    //获取框架根目录,含项目名
define('DRAGON_CORE', dirname($_SERVER['SCRIPT_FILENAME']).SP);

define('CONTROLLER', APP.'Module/Controller/'); //业务逻辑类目录
define('COMMAND', APP.'Module/Command/'); //命令类目录
define('OBSERVER', APP.'Module/Observer/'); //观察者目录
define('MODEL',APP.'Module/Model/');    //数据层
define('VIEW', APP.'Module/View/');     //视图目录
define('RUNTIME', APP.'Runtime/');  //运行时目录
define('TPL', APP.'Runtime/TemplatesCache');  //模板缓存目录


define('LIB', DRAGON.'Lib/');     //框架类文件目录
define('DIRVES', DRAGON.'Lib/Dirves');     //框架驱动目录
define('C_COM', DRAGON.'Common/');  //框架公共函数

// 环境检测
define('IS_CML', php_sapi_name() == 'cli' ? true : false);
define('IS_WIN', strpos(php_uname() , 'WIN') !== false);


//常量
const EXT = '.php';   //类文件后缀
const CTRL_EXT = '.ctrl.php';   //控制器后缀
const MOD_EXT = '.mod.php';   //数据模型后缀
const CMD_EXT = '.cmd.php';   //命令后缀
const OBS_EXT = '.php';   //观察者后缀
const OBS_EVENT_EXT = '.event.php';   //事件后缀
const OBS_LISTEN_EXT = '.listen.php';   //事件后缀

//composer自动加载
require '../vendor/autoload.php';

//错误调试
if(DEBUG){
    ini_set('display_errors', 'on');
    //Whoops错误处理  详细用法见Github
    $whoops = new  Whoops\Run();
    $handler = new Whoops\Handler\PrettyPageHandler();
    $handler->setPageTitle("Dragon error");//设置报错页面的title
    $whoops->pushHandler($handler);
    if (Whoops\Util\Misc::isAjaxRequest()) {//设置处理ajax报错的信息
        $whoops->pushHandler(new \Whoops\Handler\JsonResponseHandler());
    }
    $whoops->register();
}else{
    ini_set('display_errors', 'off');
}

//框架基础类
require DRAGON."Base.php";

//自动加载
\Core\Base::registerAutoloader();

//启动框架
\Core\Lib\Dragon::engine();

?>