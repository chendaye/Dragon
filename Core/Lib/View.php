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
 * 视图类，为命令加载相应的视图
 * Class View
 * @package Core\Lib
 */
class View
{
    /**
     * 加载视图
     * @param string $path 视图路径
     * @throws DragonException
     */
    static public function view($data,$path = '')
    {
        //视图路径信息
        $info = self::viewPath($path);
        //加载视图文件
        //$tpl = [$view_path, VIEW.'Public'];
        $loader = new \Twig_Loader_Filesystem($info['path']);  //模板目录
        $loader->addPath(VIEW.'Public');    //添加目录，模板搜索目录
        $twig = new \Twig_Environment($loader, array(   //模板设置 注意www所属用户组
            'cache' => TPL,
            'debug' => DEBUG
        ));
        $template = $twig->load("{$info['file']}.html");  //加载模板
        echo $template->render($data);  //载入数据
    }

    /**
     * 获取视图文件位置
     * @param $path
     * @return array 视图文件位置信息
     * @throws DragonException
     */
    static private function viewPath($path)
    {
        if(!empty($path)){
            //解析指定视图路径
            $visit = explode('/', $path);
            if(is_array($visit) && count($visit) == 2){
                $controller = $visit[0];
                $action = $visit[1];
            }else{
                throw new DragonException("视图文件路径错误！");
            }
        }else{
            $request = RequestRegistry::getRequest();
            $controller = $request->getProperty('controller');
            $action = $request->getProperty('action');
        }
        $view_path = VIEW.$controller.'/';
        DragonException::error(is_dir($view_path),"路径{$view_path} 不存在！");
        return [
            'path' => $view_path,
            'file' => $action
        ];
    }
}
?>