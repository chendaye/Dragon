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
use Core\Base;
use Core\Lib\Registry\ApplicationHelper;
use Core\Lib\Registry\ObserveHelper;
use Core\Lib\Registry\RequestHelper;
use Core\Lib\Registry\RequestRegistry;

/**
 * 前端控制器启动类
 * Class Dragon
 * @package Core\Lib
 */
class Dragon
{
    
    //TODO:test
    static public function Test()
    {
        //todo:test
        Conf::cfgFile('Config.php', '');
        Test::test();
        exit;
    }

    /**
     * 单例
     * Controller constructor.
     */
    private function __construct(){}

    /**
     * 请求分发引擎，完成相应请求的准备工作
     */
    public static function engine()
    {

        $instance = new Dragon();   //单例
        $instance->init();
        $instance->distributeRequest();
    }

    /**
     * 初始化注册表数据，将数据放入注册表中
     * 使用委托，委托其他类实际完成初始化的任务
     */
    public function init()
    {
        //设置时区
        ini_set('date.timezone','Asia/Shanghai');
        //date_default_timezone_set('Asia/Shanghai');
        //初始化应用注册表
        $applicationHelper = ApplicationHelper::instance();
        $applicationHelper->init();
        //初始化请求注册表
        $requestHelper = RequestHelper::instance();
        $requestHelper->init();
        //初始化事件注册表，注册所有事件
        $observeHelper = ObserveHelper::instance();
        $observeHelper->init();
        //启动日志类
        Log::init();    //初始化
        //加载框架公共函数
        Base::requireFile();
    }

    /**
     * 分发请求
     * Request->获取请求信息 >>>>>  CommandResolver -> 解析请求信息获取对应的命令对象
     * Command->执行具体的操作
     */
    public function distributeRequest()
    {
        //todo:请求对象实例
        $request = RequestRegistry::getRequest();
        //todo:解析器实例, 命令类工厂，解析命令参数，返回对应命令
        $cmd_resolver = new CommandResolver();
        //todo:根据请求获取命令对象,命令类的内部再实际调用业务逻辑类，处理业务逻辑
        $cmd = $cmd_resolver->getCommand($request);
        //todo；命令对象调用具体业务类的方法
        $cmd->execute($request);   //调用业务逻辑类
    }
}
?>