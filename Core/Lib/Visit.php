<?php

namespace Core\Lib;

use Core\Lib\Db\Db;
use Core\Lib\Exception\ClassNotFoundException;
use Core\Lib\Registry\RequestRegistry;

class Visit
{
    static protected $instance = [];

    /**
     * 实例化（分层）模型
     * @param string $name         Model名称
     * @param string $layer        业务层名称
     * @param bool   $appendSuffix 是否添加类名后缀
     * @param string $common       公共模块名
     * @return Object
     * @throws ClassNotFoundException
     */
    static public  function model($name = '', $layer = 'model', $appendSuffix = false, $common = 'common')
    {
        $guid = $name . $layer;
        if (isset(self::$instance[$guid])) {
            return self::$instance[$guid];
        }
        if (false !== strpos($name, '\\')) {
            $class  = $name;
            $module = RequestRegistry::getRequest()->module();
        } else {
            if (strpos($name, '/')) {
                list($module, $name) = explode('/', $name, 2);
            } else {
                $module = RequestRegistry::getRequest()->module();
            }
            $class = self::parseClass($module, $layer, $name, $appendSuffix);
        }
        if (class_exists($class)) {
            $model = new $class();
        } else {
            $class = str_replace('\\' . $module . '\\', '\\' . $common . '\\', $class);
            if (class_exists($class)) {
                $model = new $class();
            } else {
                throw new ClassNotFoundException('class not exists:' . $class, $class);
            }
        }
        self::$instance[$guid] = $model;
        return $model;
    }

    /**
     * 实例化（分层）控制器 格式：[模块名/]控制器名
     * @param string $name         资源地址
     * @param string $layer        控制层名称
     * @param bool   $appendSuffix 是否添加类名后缀
     * @param string $empty        空控制器名称
     * @return Object|false
     * @throws ClassNotFoundException
     */
    static public  function controller($name, $layer = 'controller', $appendSuffix = false, $empty = '')
    {
        if (false !== strpos($name, '\\')) {
            $class  = $name;
            $module = RequestRegistry::getRequest()->module();
        } else {
            if (strpos($name, '/')) {
                list($module, $name) = explode('/', $name);
            } else {
                $module = RequestRegistry::getRequest()->module();
            }
            $class = self::parseClass($module, $layer, $name, $appendSuffix);
        }
        if (class_exists($class)) {
            return Dispatch::invokeClass($class);
        } elseif ($empty && class_exists($emptyClass = self::parseClass($module, $layer, $empty, $appendSuffix))) {
            return new $emptyClass(RequestRegistry::getRequest());
        }
    }

    /**
     * 实例化验证类 格式：[模块名/]验证器名
     * @param string $name         资源地址
     * @param string $layer        验证层名称
     * @param bool   $appendSuffix 是否添加类名后缀
     * @param string $common       公共模块名
     * @return Object|false
     * @throws ClassNotFoundException
     */
    static public  function validate($name = '', $layer = 'validate', $appendSuffix = false, $common = 'common')
    {
        $name = $name ?: Conf::get('default_validate');
        if (empty($name)) {
            return new Validate;
        }
        $guid = $name . $layer;
        if (isset(self::$instance[$guid])) {
            return self::$instance[$guid];
        }
        if (false !== strpos($name, '\\')) {
            $class  = $name;
            $module = RequestRegistry::getRequest()->module();
        } else {
            if (strpos($name, '/')) {
                list($module, $name) = explode('/', $name);
            } else {
                $module = RequestRegistry::getRequest()->module();
            }
            $class = self::parseClass($module, $layer, $name, $appendSuffix);
        }
        if (class_exists($class)) {
            $validate = new $class;
        } else {
            $class = str_replace('\\' . $module . '\\', '\\' . $common . '\\', $class);
            if (class_exists($class)) {
                $validate = new $class;
            } else {
                throw new ClassNotFoundException('class not exists:' . $class, $class);
            }
        }
        self::$instance[$guid] = $validate;
        return $validate;
    }

    /**
     * 数据库初始化 并取得数据库类实例
     * @param mixed         $config 数据库配置
     * @param bool|string   $name 连接标识 true 强制重新连接
     * @return \Core\Lib\Db\Connection
     */
    static public  function db($config = [], $name = false)
    {
        return Db::connect($config, $name);
    }

    /**
     * 远程调用模块的操作方法 参数格式 [模块/控制器/]操作
     * @param string       $url          调用地址
     * @param string|array $vars         调用参数 支持字符串和数组
     * @param string       $layer        要调用的控制层名称
     * @param bool         $appendSuffix 是否添加类名后缀
     * @return mixed
     */
    static public  function action($url, $vars = [], $layer = 'controller', $appendSuffix = false)
    {
        $info   = pathinfo($url);
        $action = $info['basename'];
        $module = '.' != $info['dirname'] ? $info['dirname'] : RequestRegistry::getRequest()->controller();
        $class  = self::controller($module, $layer, $appendSuffix);
        if ($class) {
            if (is_scalar($vars)) {
                if (strpos($vars, '=')) {
                    parse_str($vars, $vars);
                } else {
                    $vars = [$vars];
                }
            }
            return Dispatch::invokeMethod([$class, $action . Conf::get('action_suffix')], $vars);
        }
    }

    /**
     * 字符串命名风格转换
     * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
     * @param string  $name 字符串
     * @param integer $type 转换类型
     * @param bool    $ucfirst 首字母是否大写（驼峰规则）
     * @return string
     */
    static public  function parseName($name, $type = 0, $ucfirst = true)
    {
        if ($type) {
            $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $name);
            return $ucfirst ? ucfirst($name) : lcfirst($name);
        } else {
            return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
        }
    }

    /**
     * 解析应用类的类名
     * @param string $module 模块名
     * @param string $layer  层名 controller model ...
     * @param string $name   类名
     * @param bool   $appendSuffix
     * @return string
     */
    static public  function parseClass($module, $layer, $name, $appendSuffix = false)
    {
        $name  = str_replace(['/', '.'], '\\', $name);
        $array = explode('\\', $name);
        $class = self::parseName(array_pop($array), 1) . (Dispatch::$suffix || $appendSuffix ? ucfirst($layer) : '');
        $path  = $array ? implode('\\', $array) . '\\' : '';
        return Dispatch::$namespace . '\\' . ($module ? $module . '\\' : '') . $layer . '\\' . $path . $class;
    }

    /**
     * 初始化类的实例
     * @return void
     */
    static public  function clearInstance()
    {
        self::$instance = [];
    }
}
?>