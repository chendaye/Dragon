<?php
namespace Command;
use Core\Lib\ControllerFactory;
use Core\Lib\Command;
use Core\Lib\Request;
use Core\Lib\View;

/**
 * 具体的命令类，调用具体的业务逻辑处理类
 * 并且加载相应的视图
 * Class Login
 * @package Command
 */
class Login extends Command {

    /**
     * 调用具体的业务逻辑类来处理请求
     * @param Request $request
     * @return null
     */
    public function doExecute(Request $request)
    {
        $request->addFeedback('welcome');
        $instance = ControllerFactory::ctrl();
        $data = $instance->login();
        //$data = ControllerFactory::action();
        View::view($data);
    }  
}
?>