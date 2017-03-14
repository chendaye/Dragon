<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2017 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace Core\Lib;
/**
 * PSR-0 \namespace\package\Class_Name => /path/to/project/lib/vendor/namespace/package/Class/Name.php
 * PSR-4 \Aura\Web\Response\Status => /path/to/aura-web/src/Response/Status.php
 * Class Load
 * @package Core\Lib
 */
class Load
{
    static protected  $instance = [];
    // 类名映射
    static protected  $map = [];
    // 命名空间别名
    static protected  $alias = [];
    // PSR-4
    static private  $prefix_lengths_psr4 = [];
    static private  $prefix_dirs_psr4    = [];  //命名空间
    static private  $fallback_dirs_psr4  = [];   //命名空间上级目录
    // PSR-0
    static private  $prefixes_psr0     = [];    //命名空间
    static private  $fallback_dirs_psr0 = [];    //命名空间上级目录
    // 自动加载的文件
    static private  $loaded = [];
    /**
     * 注册自动加载机制
     * @param string $autoload
     */
    public static function register($autoload = '')
    {
        //加载公共函数
        self::requireFile();

        // 注册系统自动加载
        spl_autoload_register($autoload ?: 'Core\\Lib\\Load::autoload', true, true);

        // 注册框架核心命名空间映射
        self::addNamespace([
            'Core'    => CORE,
            'Core\\Lib' => LIB,
            'Core\\Lib\\Db'   => LIB. 'Db' . SP,
            'Core\\Lib\\Db\\Connector'   => LIB. 'Db' . SP.'Connector'.SP,
            'Core\\Lib\\Drives\\Log'   => LIB. 'Drives' . SP.'Log'.SP,
            'Core\\Lib\\Observe'   => LIB. 'Observe' . SP,
            'Core\\Lib\\Registry'   => LIB. 'Registry' . SP,
        ]);

        // 加载类库映射文件
        if (is_file(RUNTIME . 'classmap' . EXT)) {
            self::addClassMap(self::insulate_include(RUNTIME. 'classmap' . EXT));
        }

        // Composer自动加载支持
        if (is_dir(VENDOR . 'composer')) {
            self::Composer();
        }

        // 自动加载extend目录
        self::$fallback_dirs_psr4[] = rtrim(EXTEND, SP);
    }
    /**
     *  自动加载
     * @param $class
     * @return bool
     */
    static public function autoload($class)
    {
        //根据类名查找文件
        if ($file = self::findFile($class)) {
            // Win环境严格区分大小写
            if (IS_WIN && pathinfo($file, PATHINFO_FILENAME) != pathinfo(realpath($file), PATHINFO_FILENAME)) return false;
            //加载文件
            if(is_file($file)) self::insulate_require($file);
            return true;
        }
    }
    /**
     * 查找文件
     * @param $class string 类名
     * @return bool
     */
    static private  function findFile($class)
    {
        if (!empty(self::$map[$class])) return self::$map[$class];  //如果类名有指定的文件路径，直接返回该路径；类名直接对应文件路径
        $psr4_path = strtr($class, '\\', SP) . EXT; //命名空间转化为路径形式
        $index = $class[0];     //命名空间的第一个字母作为，二级数组的以为索引， self::$prefix_lengths_psr4[$prefix[0]][$prefix] = $length;
        if (isset(self::$prefix_lengths_psr4[$index])) {
            //$index 对应一个父空间
            foreach (self::$prefix_lengths_psr4[$index] as $prefix => $length) {
                //strpos()函数查找字符串在另一字符串中第一次出现的位置
                if (strpos($class, $prefix) === 0) {
                    foreach (self::$prefix_dirs_psr4[$prefix] as $dir) {
                        //substr()函数返回字符串的一部分
                        if (is_file($file = $dir . SP . substr($psr4_path, $length))) {
                            return $file;
                        }
                    }
                }
            }
        }
        //查找 PSR-4 命名空间上级目录
        foreach (self::$fallback_dirs_psr4 as $dir) {
            //命名空间拼上级目录得到完整的文件路径
            if (is_file($file = $dir . SP . $psr4_path)) {
                return $file;
            }
        }
        // 查找 PSR-0,'_'在PRS0中是目录分隔符,strrpos — 计算指定字符串在目标字符串中最后一次出现的位置
        if (false !== $pos = strrpos($class, '\\')) {
            // 如果命名空间中有‘\’,找到目录部分的位置； \namespace\package\Class_Name -> /namespace/package/Class_Name -> /namespace/package/Class/Name
            $psr0_path = substr($psr4_path, 0, $pos + 1). strtr(substr($psr4_path, $pos + 1), '_', SP);
        } else {
            // PEAR-like class name
            $psr0_path = strtr($class, '_', SP) . EXT;
        }
        //通过拼接目录前缀和命名空间表示的目录，得到文件路径
        if (isset(self::$prefixes_psr0[$index])) {
            foreach (self::$prefixes_psr0[$index] as $prefix => $dirs) {
                if (strpos($class, $prefix) === 0) {
                    foreach ($dirs as $dir) {
                        if (is_file($file = $dir . SP . $psr0_path)) {
                            return $file;
                        }
                    }
                }
            }
        }
        //查找 PSR-0 上级目录路径
        foreach (self::$fallback_dirs_psr0 as $dir) {
            if (is_file($file = $dir . SP . $psr0_path)) {
                return $file;
            }
        }
        //没找到文件，返回FALSE
        return self::$map[$class] = false;
    }
    /**
     * 框架目录映射
     * 注册类与文件路径的映射关系  类 => 路径
     * @param array|string $class
     * @param string $map
     */
    static public function addClassMap($class, $map = '')
    {
        if (is_array($class)) {
            self::$map = array_merge(self::$map, $class);
        } else {
            self::$map[$class] = $map;
        }
    }
    /**注册命名空间  命名空间 => 路径
     * @param $namespace
     * @param string $path
     */
    public static function addNamespace($namespace, $path = '')
    {
        if (is_array($namespace)) {
            // 'Core\\Lib' => LIB,
            foreach ($namespace as $prefix => $paths) {
                self::addPsr4($prefix . '\\', rtrim($paths, SP), true);
            }
        } else {
            self::addPsr4($namespace . '\\', rtrim($path, SP), true);
        }
    }
    /**
     * 添加Psr4空间
     * @param $prefix
     * @param $paths
     * @param bool $prepend
     */
    private static function addPsr4($prefix, $paths, $prepend = false)
    {
        if (!$prefix) {
            //注册根命名空间的路径
            if ($prepend) {
                self::$fallback_dirs_psr4 = array_merge((array) $paths,self::$fallback_dirs_psr4 );
            } else {
                self::$fallback_dirs_psr4 = array_merge( self::$fallback_dirs_psr4,(array) $paths);
            }
        } elseif (!isset(self::$prefix_dirs_psr4[$prefix])) {
            // 给新的命名空间注册路径
            $length = strlen($prefix);
            if ($prefix[$length - 1] !== '\\' ) {   //字符串隐式转化为数组
                throw new \InvalidArgumentException(" PSR-4 命名空间必须以反斜杠结尾！");
            }
            self::$prefix_lengths_psr4[$prefix[0]][$prefix] = $length;  //$prefix[0]='\'
            self::$prefix_dirs_psr4[$prefix] = (array) $paths;  //命名空间对应目录
        } elseif ($prepend) {
            // 对已经存在的命名空间指定路径
            self::$prefix_dirs_psr4[$prefix] = array_merge( (array) $paths,self::$prefix_dirs_psr4[$prefix]);
        } else {
            // 给已经存在的目录追加路径
            self::$prefix_dirs_psr4[$prefix] = array_merge( self::$prefix_dirs_psr4[$prefix],(array) $paths);
        }
    }
    /**
     * 添加Ps0空间
     * @param $prefix array  命名空间前缀
     * @param $paths  array 路径
     * @param bool $prepend
     */
    private static function addPsr0($prefix, $paths, $prepend = false)
    {
        //命名空间为空
        if (!$prefix) {
            if ($prepend) {
                //psr0数组覆盖合并 path
                self::$fallback_dirs_psr0 = array_merge((array) $paths,self::$fallback_dirs_psr0 );
            } else {
                //path覆盖合并psr0数组
                self::$fallback_dirs_psr0 = array_merge( self::$fallback_dirs_psr0,(array) $paths);
            }
            return;
        }
        $first = $prefix[0];    // '\'
        //命名空间存在
        if (!isset(self::$prefixes_psr0[$first][$prefix])) {
            self::$prefixes_psr0[$first][$prefix] = (array) $paths; //命名空间和路径对应
            return;
        }
        if ($prepend) {
            self::$prefixes_psr0[$first][$prefix] = array_merge((array) $paths, self::$prefixes_psr0[$first][$prefix] );    //命名空间对应路径
        } else {
            self::$prefixes_psr0[$first][$prefix] = array_merge(self::$prefixes_psr0[$first][$prefix],(array) $paths );
        }
    }

    /**
     * 注册composer自动延迟加载
     */
    private static function Composer()
    {
        //'Twig_' => array($vendorDir . '/twig/twig/lib'),
        if (is_file(VENDOR . 'composer/autoload_namespaces.php')) {
            $map = require VENDOR . 'composer/autoload_namespaces.php';
            foreach ($map as $namespace => $path) {
                self::addPsr0($namespace, $path);   //注册psr0命名空间映射
            }
        }
        // 'Doctrine\\Instantiator\\' => array($vendorDir . '/doctrine/instantiator/src/Doctrine/Instantiator'),
        if (is_file(VENDOR . 'composer/autoload_psr4.php')) {
            $map = require VENDOR . 'composer/autoload_psr4.php';
            foreach ($map as $namespace => $path) {
                self::addPsr4($namespace, $path);   //注册psr4命名空间映射
            }
        }
        //'File_Iterator' => $vendorDir . '/phpunit/php-file-iterator/File/Iterator.php',
        if (is_file(VENDOR . 'composer/autoload_classmap.php')) {
            $classMap = require VENDOR . 'composer/autoload_classmap.php';
            if ($classMap) {
                self::addClassMap($classMap);   //注册路径映射
            }
        }
        //'0e6d7bf4a5811bfa5cf40c5ccd6fae6a' => $vendorDir . '/symfony/polyfill-mbstring/bootstrap.php',
        if (is_file(VENDOR . 'composer/autoload_files.php')) {
            $includeFiles = require VENDOR . 'composer/autoload_files.php';
            foreach ($includeFiles as $fileIdentifier => $file) {
                if (empty(self::$loaded[$fileIdentifier])) {
                    self::insulate_include($file);  //直接引入文件
                    self::$loaded[$fileIdentifier] = true;
                }
            }
        }
    }

    /**
     * 导入所需的类库 同java的Import 本函数有缓存功能
     * @param string $class   类库命名空间字符串
     * @param string $baseUrl 起始路径
     * @param string $ext     导入的文件扩展名
     * @return boolean
     */
    public static function import($class, $baseUrl = '', $ext = EXT)
    {
        static $_file = [];
        $key          = $class . $baseUrl;
        $class        = str_replace(['.', '#'], [SP, '.'], $class);
        if (isset($_file[$key])) {
            return true;
        }
        if (empty($baseUrl)) {
            list($name, $class) = explode(SP, $class, 2);
            if (isset(self::$prefix_dirs_psr4[$name . '\\'])) {
                // 注册的命名空间
                $baseUrl = self::$prefix_dirs_psr4[$name . '\\'];
            } elseif ('@' == $name) {
                //加载当前模块应用类库
                $baseUrl = App::$modulePath;
            } elseif (is_dir(EXTEND_PATH . $name)) {
                $baseUrl = EXTEND_PATH . $name . SP;
            } else {
                // 加载其它模块的类库
                $baseUrl = APP_PATH . $name . SP;
            }
        } elseif (substr($baseUrl, -1) != SP) {
            $baseUrl .= SP;
        }
        // 如果类存在 则导入类库文件
        if (is_array($baseUrl)) {
            foreach ($baseUrl as $path) {
                $filename = $path . SP . $class . $ext;
                if (is_file($filename)) {
                    break;
                }
            }
        } else {
            $filename = $baseUrl . $class . $ext;
        }
        if (!empty($filename) && is_file($filename)) {
            // 开启调试模式Win环境严格区分大小写
            if (IS_WIN && pathinfo($filename, PATHINFO_FILENAME) != pathinfo(realpath($filename), PATHINFO_FILENAME)) {
                return false;
            }
            self::insulate_include($filename);
            $_file[$key] = true;
            return true;
        }
        return false;
    }
    /**
     * 实例化（分层）模型
     * @param string $name         Model名称
     * @param string $layer        业务层名称
     * @param bool   $appenSPuffix 是否添加类名后缀
     * @param string $common       公共模块名
     * @return Object
     * @throws ClassNotFoundException
     */
    public static function model($name = '', $layer = 'model', $appenSPuffix = false, $common = 'common')
    {
        $guid = $name . $layer;
        if (isset(self::$instance[$guid])) {
            return self::$instance[$guid];
        }
        if (strpos($name, '\\')) {
            $class = $name;
        } else {
            if (strpos($name, '/')) {
                list($module, $name) = explode('/', $name, 2);
            } else {
                $module = Request::instance()->module();
            }
            $class = self::parseClass($module, $layer, $name, $appenSPuffix);
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
     * @param bool   $appenSPuffix 是否添加类名后缀
     * @param string $empty        空控制器名称
     * @return Object|false
     * @throws ClassNotFoundException
     */
    public static function controller($name, $layer = 'controller', $appenSPuffix = false, $empty = '')
    {
        if (strpos($name, '\\')) {
            $class = $name;
        } else {
            if (strpos($name, '/')) {
                list($module, $name) = explode('/', $name);
            } else {
                $module = Request::instance()->module();
            }
            $class = self::parseClass($module, $layer, $name, $appenSPuffix);
        }
        if (class_exists($class)) {
            return App::invokeClass($class);
        } elseif ($empty && class_exists($emptyClass = self::parseClass($module, $layer, $empty, $appenSPuffix))) {
            return new $emptyClass(Request::instance());
        }
    }
    /**
     * 实例化验证类 格式：[模块名/]验证器名
     * @param string $name         资源地址
     * @param string $layer        验证层名称
     * @param bool   $appenSPuffix 是否添加类名后缀
     * @param string $common       公共模块名
     * @return Object|false
     * @throws ClassNotFoundException
     */
    public static function validate($name = '', $layer = 'validate', $appenSPuffix = false, $common = 'common')
    {
        $name = $name ?: Config::get('default_validate');
        if (empty($name)) {
            return new Validate;
        }
        $guid = $name . $layer;
        if (isset(self::$instance[$guid])) {
            return self::$instance[$guid];
        }
        if (strpos($name, '\\')) {
            $class = $name;
        } else {
            if (strpos($name, '/')) {
                list($module, $name) = explode('/', $name);
            } else {
                $module = Request::instance()->module();
            }
            $class = self::parseClass($module, $layer, $name, $appenSPuffix);
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
     * @return \think\db\Connection
     */
    public static function db($config = [], $name = false)
    {
        return Db::connect($config, $name);
    }
    /**
     * 远程调用模块的操作方法 参数格式 [模块/控制器/]操作
     * @param string       $url          调用地址
     * @param string|array $vars         调用参数 支持字符串和数组
     * @param string       $layer        要调用的控制层名称
     * @param bool         $appenSPuffix 是否添加类名后缀
     * @return mixed
     */
    public static function action($url, $vars = [], $layer = 'controller', $appenSPuffix = false)
    {
        $info   = pathinfo($url);
        $action = $info['basename'];
        $module = '.' != $info['dirname'] ? $info['dirname'] : Request::instance()->controller();
        $class  = self::controller($module, $layer, $appenSPuffix);
        if ($class) {
            if (is_scalar($vars)) {
                if (strpos($vars, '=')) {
                    parse_str($vars, $vars);
                } else {
                    $vars = [$vars];
                }
            }
            return App::invokeMethod([$class, $action . Config::get('action_suffix')], $vars);
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
    public static function parseName($name, $type = 0, $ucfirst = true)
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
     * @param bool   $appenSPuffix
     * @return string
     */
    public static function parseClass($module, $layer, $name, $appenSPuffix = false)
    {
        $name  = str_replace(['/', '.'], '\\', $name);
        $array = explode('\\', $name);
        $class = self::parseName(array_pop($array), 1) . (App::$suffix || $appenSPuffix ? ucfirst($layer) : '');
        $path  = $array ? implode('\\', $array) . '\\' : '';
        return App::$namespace . '\\' . ($module ? $module . '\\' : '') . $layer . '\\' . $path . $class;
    }
    /**
     * 初始化类的实例
     * @return void
     */
    public static function clearInstance()
    {
        self::$instance = [];
    }
    /**
     * include作用范围隔离
     * @param $file
     * @return mixed
     */
    static public function insulate_include($file)
    {
        return include $file;
    }
    /**
     * require作用范围隔离
     * @param $file
     * @return mixed
     */
    static public function insulate_require($file)
    {
        return require $file;
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

    /**
     * 测试
     */
    static public function test(){
        E([
            self::$fallback_dirs_psr0,
            self::$fallback_dirs_psr4,
            self::$prefix_dirs_psr4,
            self::$prefix_lengths_psr4
        ]);
    }
}