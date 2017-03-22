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

namespace Core;  //与目录结构保持一致
use Core\Lib\DragonException;

/**
 * 核心基础类
 * Class Dragon
 * @package Dragon
 */
class Base{
    static private $classMap = []; //储存实例化过的类，避免重复实例化，提高性能

    /**
     * 自动加载框架类文件 核心类文件  控制器 数据层
     * @param $class
     * @return bool
     * @throws \Exception
     */
    static public function autoLoad($class){
        //已经实例化就不再重复实例化
        if(!empty(self::$classMap[$class])){
            return true;
        }
        //根据命名空间判断类文件的位置
        $class = str_replace("\\", '/', ltrim($class, '\\'));
        $dir = explode('/', $class);
        switch ($dir[0]){
            case 'Core':
                $classPath = DRAGON_CORE.$class.EXT;
                break;
            case 'Controller':
                $classPath = CONTROLLER.end($dir).CTRL_EXT;
                break;
            case 'Model':
                $classPath = MODEL.end($dir).MOD_EXT;
                break;
            case 'Command';
                $classPath = COMMAND.end($dir).CMD_EXT;
                break;
            case 'Observer':
                if($dir[1] == 'Event'){
                    $ext = OBS_EVENT_EXT;
                }elseif ($dir[1] == 'Listen'){
                    $ext = OBS_LISTEN_EXT;
                }else{
                    $ext = OBS_EXT;
                }
                if(isset($dir[2])){
                    $classPath = OBSERVER.$dir[1].'/'.$dir[2].$ext;
                }else{
                    $classPath = OBSERVER.end($dir).$ext;
                }
                break;
            default:
                throw new \Exception("未知的命名空间:{$class}！");
        }
        //加载文件
        if(is_file($classPath)){
            include ($classPath);
            self::$classMap[$class] = $class;   //实例过的对象放在静态属性中
        }else{
            throw new \Exception("类文件:".$classPath."不存在");
        }
    }


    /**
     * 自动加载
     */
    static public function registerAutoloader()
    {
        spl_autoload_register("\\Core\\"."Base::autoLoad");
    }
    /**
     * 递归加载目录下所有PHP文件
     * @param string $path
     * @throws DragonException
     */
    static public function  requireFile($path = COM){
        if(is_file($path)){
            if(substr($path, strrpos($path, '.')) == '.php' && preg_match('/\.php$/', $path)){
                require_once ($path);
            }
        }
        if(is_dir($path)){
            $obj = dir($path);
            while($file = $obj->read()){
                if($file != '.' && $file != '..'){
                    //递归调用
                    self::requireFile($path.'/'.$file);
                }
            }
        }
    }
}
?>