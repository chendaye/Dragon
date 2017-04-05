<?php
namespace Core\Lib;
class Error{
    /**
     * 注册错误处理
     */
    static function registry()
    {
        if(DEBUG){
            ini_set('display_errors', 'on');
            //Whoops错误处理  详细用法见Github
            $whoops = new  \Whoops\Run();
            $handler = new \Whoops\Handler\PrettyPageHandler();
            $handler->setPageTitle("Dragon error");//设置报错页面的title
            $whoops->pushHandler($handler);
            if (\Whoops\Util\Misc::isAjaxRequest()) {//设置处理ajax报错的信息
                $whoops->pushHandler(new \Whoops\Handler\JsonResponseHandler());
            }
            $whoops->register();
        }else{
            ini_set('display_errors', 'off');
        }
    }
}
?>