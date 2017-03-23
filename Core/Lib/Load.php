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
    // PSR-4
    static private  $space_lengths_psr4 = [];   //以首字母为索引，保存所有空间的长度，反斜杠结尾
    static private  $space_dirs_psr4    = [];  //每一个命名空间，对应一个或者多个具体目录，如此，一个空间可以匹配一组目录
    static private  $dirs_psr4  = [];   //直接目录
    // PSR-0
    static private  $space_psr0     = [];    //以首字母为索引，保存所有命名空间及其对应的若干目录
    static private  $dirs_psr0 = [];    //直接目录
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
        // 注册框架核心命名空间映射，长度 路径
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
        self::$dirs_psr4[] = rtrim(EXTEND, SP);
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
     *
     * $space_dirs_psr4  命名空间=>路径
     * [Core\Lib\Db\Connector\] => Array（[0] => /var/www/Dragon/Core/Lib/Db/Connector）
     * $space_lengths_psr4   一个字母（目录）的命名空间  命名空间=>长度
     *  [W] => Array（ [Whoops\] => 7，[Webmozart\Assert\] => 17）
     *
     * $dirs_psr4
     *
     * 1, Core\Lib\Log -> Core/Lib/Log.php
     * 2, Core\Lib\Log -> 以 C 开头的命名空间数组  -> 遍历数组，查找C开头的数组中有没有，与之匹配的命名空间，获取其长度
     * 3,如果以 C 开头的命名空间数组中有此命名空间，则去查找此命名空间对应得真实路径； 用长度截取类文件名Log.php；拼接得到最终文件名
     */
    static private  function findFile($class)
    {
        //命名空间直接映射目录
        if (!empty(self::$map[$class])) return self::$map[$class];
        //类文件名
        $psr4_path = strtr($class, '\\', SP) . EXT;
        //空间首字母长度数组
        $index = $class[0];
        $len = self::$space_lengths_psr4[$index];
        if (isset($len)) {
            //$index 对应一个父空间
            foreach ($len as $space => $length) {
                //查找长度数组中有没有与当前空间匹配的$class 截取 0-$length
                if (strpos($class, $space) === 0) {
                    foreach (self::$space_dirs_psr4[$space] as $dir) {
                        //截除长度数组中第一个匹配到的空间，并拼接上该空间对应得路径，
                        //一个空间就代表着多个具体的目录，把类名中的空间直接替换成对应的目录，得到完整的文件目录
                        if (is_file($file = $dir . SP . substr($psr4_path, $length))) {
                            return $file;
                        }
                    }
                }
            }
        }
        //查找直接注册的目录
        foreach (self::$dirs_psr4 as $dir) {
            if (is_file($file = $dir . SP . $psr4_path)) {
                return $file;
            }
        }
        // 反斜杠最后出现的位置
        if (false !== $pos = strrpos($class, '\\')) {
            // \namespace\package\Class_Name -> /namespace/package/Class_Name -> /namespace/package/Class/Name
            $psr0_path = substr($psr4_path, 0, $pos + 1). strtr(substr($psr4_path, $pos + 1), '_', SP);
        } else {
            //没有反斜杠，直接把'-'替换为反斜杠
            $psr0_path = strtr($class, '_', SP) . EXT;
        }
        //通过拼接目录前缀和命名空间表示的目录，得到文件路径
        $prs0_space = self::$space_psr0[$index];    //prs0 首字母空间数组
        if (isset($prs0_space)) {
            foreach ($prs0_space as $space => $dirs) {  //一个空间下面有多个目录
                if (strpos($class, $space) === 0) {
                    foreach ($dirs as $dir) {
                        if (is_file($file = $dir . SP . $psr0_path)) {
                            return $file;
                        }
                    }
                }
            }
        }
        //查找 PSR-0 直接映射目录
        foreach (self::$dirs_psr0 as $dir) {
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
    /**
     * 注册命名空间
     * 要么接受一个数组，要么两个字符串([ 'Command'=>COMMAND.SP.'Back'.SP,],'')  ('Command',COMMAND.SP.'Front'.SP)
     * @param array|string $namespace  可以是命名空间 也可以是  命名空间 => 目录 形式的数组
     * @param string $path  命名空间对应的数组
     */
    public static function addNamespace($namespace, $path = '')
    {
        if (is_array($namespace)) {
            foreach ($namespace as $space => $catalog) {
                self::addPsr4($space . '\\', rtrim($catalog, SP), true);
            }
        } else {
            self::addPsr4($namespace . '\\', rtrim($path, SP), true);
        }
    }
    /**
     * 添加Psr4空间
     * @param $space string|array 命名空间
     * @param $paths    string  命名空间路径
     * @param bool $prepend     覆盖方式
     * @throws \Exception
     */
    private static function addPsr4($space, $paths, $prepend = false)
    {
        //若给定的空间参数为空，把路径追加到路径数组中，注册路径
        if(empty($space)){
            if($prepend){
                self::$dirs_psr4 = array_merge((array)$paths, self::$dirs_psr4);
            }else{
                self::$dirs_psr4 = array_merge(self::$dirs_psr4, (array)$paths);
            }
            return;
        }
        //如果给定的空间参数不为空，但是空间映射数组中没有注册，注册
        if(!isset(self::$space_dirs_psr4[$space])){
            $length = strlen($space);   //长度
            $first_str = $space[0];     //首字母
            $end_str = $space[$length-1];   //尾字母
            if($end_str != '\\') throw new \Exception('命名空间必须以反斜杠结尾！');
            self::$space_lengths_psr4[$first_str][$space] = $length;    //长度数组
            self::$space_dirs_psr4[$space] = (array)$paths;     //空间->路径映射数组
            return;
        }
        //如果空间参数不为空，且已经在空间映射数组中注册，合并
        if($prepend){
            self::$space_dirs_psr4[$space] = array_merge( (array) $paths,self::$space_dirs_psr4[$space]);
        }else{
            self::$space_dirs_psr4[$space] = array_merge( self::$space_dirs_psr4[$space],(array) $paths);
        }
    }
    /**
     * 添加Ps0空间
     * @param $space array  命名空间前缀
     * @param $paths  array 路径
     * @param bool $prepend
     */
    private static function addPsr0($space, $paths, $prepend = false)
    {
        //若给定的空间参数为空，把路径追加到路径数组中，注册路径
        if (!$space) {
            if ($prepend) {
                self::$dirs_psr0 = array_merge((array) $paths,self::$dirs_psr0 );
            } else {
                self::$dirs_psr0 = array_merge( self::$dirs_psr0,(array) $paths);
            }
            return;
        }
        //如果给定的空间参数不为空，但是空间映射数组中没有注册，注册
        $first_str = $space[0];
        if (!isset(self::$space_psr0[$first_str][$space])) {
            self::$space_psr0[$first_str][$space] = (array) $paths; //命名空间和路径对应
            return;
        }
        //如果空间参数不为空，且已经在空间映射数组中注册，合并
        if ($prepend) {
            self::$space_psr0[$first_str][$space] = array_merge((array) $paths, self::$space_psr0[$first_str][$space] );    //命名空间对应路径
        } else {
            self::$space_psr0[$first_str][$space] = array_merge(self::$space_psr0[$first_str][$space],(array) $paths );
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
            if (isset(self::$space_dirs_psr4[$name . '\\'])) {
                // 注册的命名空间
                $baseUrl = self::$space_dirs_psr4[$name . '\\'];
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

    static public function builed($path){
        $dirtree = self::dirTree($path);
        if(empty($dirtree)){
            throw new \Exception("{$path} 是空目录！");
        }
       return self::makePath($dirtree);
    }

    /**
     * 前一层的输出作为后一层的输入
     * 递归是普通的函数调用
     * @param $path
     * @param $catlog
     * @return array
     */
    static private function makePath($catlog, $path = ''){
        $content = [];
        foreach ($catlog as $key => $val){
            if(!is_numeric($key)){
                $new_path = $path.$key.SP;
                $ret = self::makePath($val,$new_path);     //获取键名拼接目录
                $content[$new_path] = $ret;
            }
        }
        return $content;
    }
    /**
     * 获取目录结构
     * @param string $path  目标目录
     * @return array   目录结构
     */
    static public function dirTree($path) {
        $handle = opendir($path);
        $construct = [];
        //循环遍历目录下的项目
        while (false !== ($file = readdir($handle))) {
            if (($file=='.')||($file=='..')){

            }else if (is_dir($path.$file)) {
                try {
                    $dirtmparr=self::dirTree($path.$file.'/');  //是目录就递归（调用函数获取目录的结构）
                } catch (\Exception $e) {
                    $dirtmparr=null;
                };
                $construct[$file]=$dirtmparr;   //把结果放入数组
            }else{
                array_push($construct, $file);  //是文件直接压如数组
            }
        }
        return $construct;
    }
    /**
     * 测试
     */
    static public function test(){
        E([
            '$dirs_psr0'=>self::$dirs_psr0,
            '$space_psr0'=>self::$space_psr0,
            '$dirs_psr4'=>self::$dirs_psr4,
            '$space_dirs_psr4'=>self::$space_dirs_psr4,
            '$space_lengths_psr4'=>self::$space_lengths_psr4,
        ]);
    }
}