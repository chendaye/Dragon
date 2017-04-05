<?php
namespace Controller;
use Core\Lib\Registry\SessionRegistry;
use Model\indexModel;
use Core\Dragon;

class Index extends Dragon
{
    /**
     * 测试控制器
     */
    public function index()
    {
        E($_SERVER['REQUEST_METHOD']);
        E($_SERVER);exit;
        $session = SessionRegistry::instance();
        $session::setIndex(new self());
        E($session::getIndex());
        //dump($_SESSION);
        header("location:http://www.dragon.com/Dragon/Index/viewTest");
    }

    /**
     * 测试数据层
     */
    public function test()
    {
        $db = new indexModel();
        $sql = 'select * from user';
        E(indexModel::getAll($sql));
        E($db->getUser());
    }

    /**
     * 测试视图
     */
    public function viewTest()
    {
        $data = '模板继承就是定义好公共的文件样式，然后其他页面继承公共部分，内容部分各自写,hahhahh！';
        E(DIRECTORY_SEPARATOR);
        E(__CLASS__);
        $this->assign('data', $data);
        $this->view();
    }
    public function temTest()
    {

    }
}
?>