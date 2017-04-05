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
use Core\Lib\Registry\RequestRegistry;

/**
 * 命令解析器，解析请求获取对应的命令类实例
 * Class CommandResolver
 * @package Core\Lib
 */
class CommandResolver
{
    private static $cmd;

    /**
     * 初始化命令
     * CommondResolver constructor.
     */
    public function __construct()
    {
        if(!isset(self::$cmd)){
            //获取Command对象的反射方法，然后可以调用反射的各种方法，增加安全性
            self::$cmd = new \ReflectionClass('\Core\Lib\Command');
        }
    }

    /**
     * 根据请求信息获取命令对象
     * @param Request $request
     * @return array
     */
    public function getCommand(Request $request)
    {
        //命令参数
        $cmd = $request->getProperty('REQUEST_URI');
        DragonException::error($cmd,"URL 参数：REQUEST_URI 错误！");
        //从请求参数中解析出对应的命令操作
        $this->parse_url($cmd);
        //命令
        $command = $request->getProperty('controller');
        //命令操作
        $action = $request->getProperty('action');
        $cmd_class = CommandFactory::getCommand($command);
        $cmd_class = new \ReflectionClass($cmd_class);  //通过反射检查类型
        //todo:检查类型，反射
        if($cmd_class->isSubclassOf(self::$cmd)){
            return $cmd_class->newInstance();   //满足要求返回一个实例
        }else{
            $request->addFeedback(" {$cmd} 不是一个命令"); //不满足给出提示
        }
    }

    /**
     * 解析URL
     * @param $cmd static REQUEST_URI
     * @return array 控制器 方法
     */
    public function parse_url($cmd)
    {
        //命令只能有字符串组成
        DragonException::error(preg_match('/^(\/\w+(\/)?)+(\?(\w+=\w+&?)+)?$/', $cmd),"命令:{$cmd} 包含非法字符！");
        //todo:命令类的实例
        $request = RequestRegistry::getRequest();
        //todo:默认控制器，方法
        $controller = Conf::get('route','CONTROLLER');
        $action = Conf::get('route','ACTION');
        if(isset($cmd) && $cmd !== '/'){
            $param_status = strpos($cmd, '?');
            //todo:问号参数形式，问号后面的参数会自动解析
            if($param_status){
                $parse_arr = explode('?', $cmd);
                if(isset($parse_arr[0])){
                    $path_arr = explode('/', ltrim($parse_arr[0], '/'));
                    $path_arr = array_slice($path_arr, 1);  //去掉框架目录
                    //todo:控制器
                    if(!empty($path_arr[0])) {
                        $controller = $path_arr[0];
                    }
                    //todo:方法
                    if(!empty($path_arr[1])) {
                        $action = $path_arr[1];
                    }
                }else{
                    DragonException::throw_exception("地址错误");
                }
            }else{  //todo:斜杠引用参数形式
                $parse_arr = explode('/', ltrim($cmd, '/'));
                $parse_arr = array_slice($parse_arr, 1);  //去掉框架目录
                if(!empty($parse_arr[0])) {$controller = $parse_arr[0];};
                if(!empty($parse_arr[1])) {$action = $parse_arr[1];}
                //把剩余部分转化为get参数
                $count = count($parse_arr)-1;
                unset($parse_arr[0]);
                unset($parse_arr[1]);
                $i = 2;
                while($i < $count){
                    $j = $i + 1;
                    if(isset($parse_arr[$j])) {
                        $_GET[$parse_arr[$i]] = $parse_arr[$j];
                        //解析的参数同同时存入请求类中
                        $request->setProperty($parse_arr[$i], $parse_arr[$j]);
                    }
                    $i += 2;
                }
            }
            $controller = ucfirst(strtolower($controller)); //控制器首字母大写
            $action = lcfirst(strtolower($action)); //方法首字母小写
            //记录访问信息
            Log::log('controller:'.$controller.';action:'.$action, 'visit_info');
            //todo:解析出控制器和操作，并保存在信息数组中
            $request->setProperty('controller', $controller);
            $request->setProperty('action', $action);
        }else{
            DragonException::throw_exception("地址解析错误");
        }
    }
}
?>