<?php
namespace Controller;
use Core\Lib\Controller;
use Core\Lib\Debug;
use Core\Lib\Observe\Event;
use Core\Lib\Registry\ObserveRegistry;

/**
 * 处理登录的业务逻辑类
 * Class LoginController
 * @package Controller
 */
class Login extends Controller {
    /**
     * 登录
     */
    public function login(){
        Debug::dump(Debug::fileMsg(true));
        E(Debug::fileMsg(true), true);
        E('公共函数',true);
        //todo:触发事件
        $o = ObserveRegistry::instance();
        //todo:除了初始化在事件中加入监听器外，还可以在初始化之后单独添加删除，因为持有的是同一个事件的实例
        $thing = $o::getEvent('Observer\Event\Login');
        $thing->detach(new \Observer\Listen\Usr());
        $thing->attach(new \Observer\Listen\Dbs());
        $thing->detach(new \Observer\Listen\Login());
        //todo:触发事件
        dump($thing);exit;
        Event::Go('Observer\Event\Login');
        $this->assign('datsa',$_GET['dragon']);
        $this->assign('data',$this->diao());
        return $this->assign;
    }

   public function diao(){
       return '龙神就是叼,怎么说，问题不大！';
   }
}
?>