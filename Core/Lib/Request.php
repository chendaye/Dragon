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
use Core\Lib\Exception\DragonException;
use Core\Lib\Exception\HttpResponseException;

/**
 * Class Request
 * @package Core\Lib
 */
class Request
{
    //对象实例
    protected $instance;

    //请求方法
    protected $method;

    //域名
    protected $domain;

    //url地址
    protected $url;

    //基础URL地址
    protected $baseUrl;

    //当前执行的文件
    protected $baseFile;

    //访问的root地址
    protected $root;

    //pathinfo
    protected $pathinfo;

    //path
    protected $path;

    //路由信息
    protected $routeInfo = [];

    //请求内容
    protected $content = null;

    //控制信息
    protected $dispatch = [];
    protected $module;
    protected $command;
    protected $controller;
    protected $language;

    //请求参数
    protected $param = [];
    protected $get   = [];
    protected $post  = [];
    protected $request = [];
    protected $route = [];
    protected $put = null;
    protected $session = [];
    protected $cookie = [];
    protected $file = [];
    protected $server = [];
    protected $env = [];
    protected $header = [];

    //资源类型
    protected $source = [
        'xml'  => 'application/xml,text/xml,application/x-xml',
        'json' => 'application/json,text/x-json,application/jsonrequest,text/json',
        'js'   => 'text/javascript,application/javascript,application/x-javascript',
        'css'  => 'text/css',
        'rss'  => 'application/rss+xml',
        'yaml' => 'application/x-yaml,text/yaml',
        'atom' => 'application/atom+xml',
        'pdf'  => 'application/pdf',
        'text' => 'text/plain',
        'png'  => 'image/png',
        'jpg'  => 'image/jpg,image/jpeg,image/pjpeg',
        'gif'  => 'image/gif',
        'csv'  => 'text/csv',
        'html' => 'text/html,application/xhtml+xml,*/*',
    ];

    //全局过滤规则
    protected $filter;

    //Hook注入扩展方法
    static protected $hook = [];

    //注入属性
    protected $bind = [];

    //php://input
    protected $input;

    //请求缓存
    protected $cache;

    //缓存检查
    protected $checkCache;

    /**
     * 参数初始化，获取请求数据
     * Request constructor.
     * @param array $options
     */
    public function __construct($options = [])
    {
        //初始化参数
        foreach ($options as $name => $item){
            //检查给出的 property 是否存在于指定的类中以及是否能在当前范围内访问
            if(property_exists($this, $name)){
                $this->$name = $item;
            }
        }
        //全局过滤
        if(is_null($this->filter)) $this->filter = Conf::get('DEFAULT_FILTER');
        //php://input 获取
        $this->input = file_get_contents("php://input");
    }

    /**
     * 可以动态注入当前Request对象的方法
     * 魔术方法，调用不类存在的方法时，指定调用某个方法
     * @param $name
     * @param $arguments
     * @throws DragonException
     * @return  mixed
     */
    public function __call($name, $arguments)
    {
        //检查扩展方法是否存在，注入的方法存入扩展数组中
        if(array_key_exists($name, self::$hook)){
            //当前请求对象加入参数数组中,放在开头
            array_unshift($arguments, $this);
            //返回扩展函数的值,参数值是顺序赋值的
            return call_user_func_array(self::$hook[$name], $arguments);
        }else{
            throw  new DragonException("方法不存在：".__CLASS__."->$name");
        }
    }

    /**
     * 方法注入。 当在类中调用不存在的  方法 method时， 会自动调用 method 对应的实际方法 callback
     * 注册Hook方法（钩子），支持单个和数组两种注册方式
     * @param $method
     * @param null $callback
     */
    static public function hook($method, $callback = null)
    {
        if(is_array($method)){
            //数组形式注册
            self::$hook = array_merge(self::$hook, $method);
        }else{
            //单个注册
            self::$hook[$method] = $callback;
        }
    }

    /**
     * 设置获取主机名
     * @param null $domain
     * @return null|string
     */
    public function domain($domain = null)
    {
        if(!is_null($domain)){
            $this->domain = $domain;
            return $this;
        }elseif (!$this->domain){
            $this->domain = $this->scheme().'://'.$this->host();
        }
        return $this->domain;
    }

    /**
     * 获取当前完整的URL 包括query_string
     * @param null $url
     * @return mixed
     */
    public function url($url = null)
    {
        if(!is_null($url) && $url !== true){
            $this->url = $url;
            return $this;
        }elseif(!$this->url){
            if(IS_CML){
                $this->url = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : '';
            }elseif (isset($_SERVER['HTTP_X_REWRITE_URL'])){
                $this->url = $_SERVER['HTTP_X_REWRITE_URL'];
            }elseif (isset($_SERVER['REQUEST_URI'])){
                $this->url = $_SERVER['REQUEST_URI'];
            }elseif (isset($_SERVER['ORIG_PATH_INFO'])){
                $this->url = $_SERVER['ORIG_PATH_INFO'] . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
            }else{
                $this->url = '';
            }
        }
        return ($url === true)? $this->domain() . $this->url:$this->url;
    }

    /**
     * 获取不含有query_string 的URL
     * @param null $url
     * @return mixed
     */
    public function baseUrl($url = null)
    {
        if(!is_null($url) && $url !== true){
            $this->baseUrl = $url;
            return $this;
        }elseif (!$this->baseUrl){
            $url_str = $this->url();
            //不含query_string 的url
            $this->baseUrl = strpos($url_str, '?')?strstr($url_str, '?', true):$url_str;
        }
        return ($url === true)?$this->domain().$this->baseUrl:$this->baseUrl;
    }

    /**
     * 获取当前执行文件 SCRIPT_NAME
     * @param null $file
     * @return mixed
     */
    public function baseFile($file = null)
    {
        if(!is_null($file) && $file !== true){
            $this->baseFile = $file;
            return $this;
        }elseif(!$this->baseFile){
            $url = '';
            if(!IS_CML){
                $script_name = basename($_SERVER['SCRIPT_FILENAME']);
                if($_SERVER['SCRIPT_NAME'] === $script_name){
                    $url = $_SERVER['SCRIPT_NAME'];
                }elseif ($_SERVER['PHP_SELF'] === $script_name){
                    $url = $_SERVER['PHP_SELF'];
                }elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $script_name){
                    $url = $_SERVER['ORIG_SCRIPT_NAME'];
                }elseif (($pos = strpos($_SERVER['PHP_SELF'], SP . $script_name)) !== false){
                    $url = substr($_SERVER['SCRIPT_NAME'], 0, $pos) .SP . $script_name;
                }elseif (isset($_SERVER['DOCUMENT_ROOT']) && strpos($_SERVER['SCRIPT_FILENAME'], $_SERVER['DOCUMENT_ROOT']) === 0){
                    $url = str_replace('\\', SP, str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME']));
                }
            }
            $this->baseFile = $url;
        }
        return ($file === true)?$this->domain().$this->baseFile:$this->baseFile;
    }

    /**
     * 获取URL访问的根地址
     * @param null $url
     * @return mixed
     */
    public function root($url = null)
    {
        if(!is_null($url) && $url !== true){
            $this->root = $url;
            return $this;
        }elseif(!$this->root){
            $file = $this->baseFile();  //可执行文件
            if($file && strpos($this->url(), $file) !== 0){
                $file = str_replace('\\', SP, dirname($file));
            }
            $this->root = rtrim($file, SP);
        }
        return ($url === true)?$this->domain().$this->root:$this->root;
    }

    /**
     * 获取当前请求URL的pathinfo信息(含后缀)
     * @return string
     */
    public function pathInfo()
    {
        if(!is_null($this->pathinfo)) return $this->pathinfo;
        //php cli运行模式
        if (IS_CML) $_SERVER['PATH_INFO'] = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : '';
        //分析PATH_INFO
        if (!isset($_SERVER['PATH_INFO'])) {
            $info = Conf::get('PATHINFO_FETCH');
            foreach ($info as $value) {
                if (!empty($_SERVER[$value])) {
                    $_SERVER['PATH_INFO'] = (strpos($_SERVER[$value], $_SERVER['SCRIPT_NAME']) === 0) ? substr($_SERVER[$value], strlen($_SERVER['SCRIPT_NAME'])) : $_SERVER[$value];
                    break;
                }
            }
        }
        $this->pathinfo = empty($_SERVER['PATH_INFO']) ? SP:SP.ltrim($_SERVER['PATH_INFO'], SP);
        return $this->pathinfo;
    }

    /**
     * 获取当前请求URL的pathinfo信息(不含后缀)
     * @return mixed|string
     */
    public function path()
    {
        if(!is_null($this->path)) return $this->path;
        $suffix = Conf::get("URL_HTML_SUFFIX"); //伪静态后缀
        $pathinfo = $this->pathInfo();
        if($suffix === false){
            //禁止伪静态访问
            $this->path = $pathinfo;
        }elseif ($suffix){
            foreach ($suffix as $suf){
                //用空替换掉URL后缀  '/\.(html)$/i' i修饰符,不区分大小写
                $ret = preg_replace('/\.(' . ltrim($suf, '.') . ')$/i', '', $pathinfo);
                if($ret != $pathinfo){
                    $this->path = $ret;
                    break;
                }
            }
        }else{
            //允许任何后缀访问
            $this->path = preg_replace('/\.' . $this->ext() . '$/i', '', $pathinfo);
        }
        return $this->path;
    }

    /**
     * 当前URL的后缀
     * @return mixed
     */
    public function ext()
    {
        return pathinfo($this->pathInfo(), PATHINFO_EXTENSION);
    }

    /**
     * 获取当前请求时间
     * @param bool $float
     * @return mixed
     */
    public function time($float = false)
    {
        return $float ? $_SERVER['REQUEST_TIME_FLOAT']:$_SERVER['REQUEST_TIME'];
    }

    /**
     * 当前请求的资源类型
     * @return bool|int|string
     */
    public function source()
    {
        $accept = (isset($this->server['HTTP_ACCEPT']))?$this->server['HTTP_ACCEPT']:$_SERVER['HTTP_ACCEPT'];
        if(empty($accept)) return false;
        foreach ($this->source as $key => $val){
            $array = explode(',', $val);
            foreach ($array as $k => $v){
                //stristr 查找第一次出现的位置，返回剩余部分
                if(stristr($accept, $v)) return $key;
            }
        }
        return false;
    }

    /**
     * 设置资源类型
     * @param string $type  资源类型名称
     * @param string $val  资源类型
     */
    public function sourceType($type, $val = '')
    {
        if(is_array($type)){
            $this->source = array_merge($this->source, $type);
        }else{
            $this->source[$type] = $val;
        }
    }

    /**
     * 获取当前请求方法
     * 当使用POST请求来模拟其他请求时，以 $_POST['_method'] 作为当前请求的方法；
     *否则，如果存在 X_HTTP_METHOD_OVERRIDE HTTP头时，以该HTTP头所指定的方法作为请求方法， 如 X-HTTP-Method-Override: PUT 表示该请求所要执行的是 PUT 方法；
     *如果 X_HTTP_METHOD_OVERRIDE 不存在，则以 REQUEST_METHOD 的值作为当前请求的方法。 如果连 REQUEST_METHOD 也不存在，则视该请求是一个 GET 请求。
     * @param bool $method  true 获取原始请求类型
     * @return mixed|string
     */
    public function method($method = false)
    {
        if($method === true) return IS_CML ? 'GET' : (isset($this->server['REQUEST_METHOD']))?$this->server['REQUEST_METHOD']:$_SERVER['REQUEST_METHOD'];
        if(!$method){
            if(isset($_POST[Conf::get('VAR_METHOD')])){
                $this->method = strtoupper($_POST[Conf::get('VAR_METHOD')]);
                $this->{$this->method}($_POST); //调用方法
            }elseif (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])){
                $this->method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
            }else{
                $this->method = IS_CML ? 'GET' : (isset($this->server['REQUEST_METHOD']))?$this->server['REQUEST_METHOD']:$_SERVER['REQUEST_METHOD'];
            }
        }
        return $this->method;
    }

    /**
     * 当前请求是否为get请求
     * @return bool
     */
    public function isGet()
    {
        return $this->method() == 'GET';
    }

    /**
     * 当前请求是否为put请求
     * @return bool
     */
    public function isPut()
    {
        return $this->method() == 'PUT';
    }

    /**
     * 当前请求是否为post请求
     * @return bool
     */
    public function isPost()
    {
        return $this->method() == 'POST';
    }

    /**
     * 当前请求是否为delete请求
     * @return bool
     */
    public function isDelete()
    {
        return $this->method() == 'DELETE';
    }

    /**
     * 当前请求是否为head请求
     * @return bool
     */
    public function isHead()
    {
        return $this->method() == 'HEAD';
    }

    /**
     * 当前请求是否为patch请求
     * @return bool
     */
    public function isPatch()
    {
        return $this->method() == 'PATCH';
    }

    /**
     * 当前请求是否为options请求
     * @return bool
     */
    public function isOptions()
    {
        return $this->method() == 'OPTIONS';
    }

    /**
     * PHP运行环境是否为 cli
     * PHP CLI模式开发不需要任何一种Web服务器（包括Apache或MS IIS等），CLI可以运行在各种场合。
     * @return bool
     */
    public function isCli()
    {
        return PHP_SAPI == "cli";
    }

    /**
     * PHP运行环境是否为 cgi
     *  PHP 默认编译为 CLI 和 CGI 程序
     * CGI是Common Gateway Interface的缩写，翻译成中文就是通用网关接口，它是网页的后台处理程序，运行在服务器端上，可以用多种语言书写
     * @return bool
     */
    public function isCgi()
    {
        return strpos(PHP_SAPI, 'cgi') === 0;
    }

    /**
     * 获取当前请求参数
     * @param string $name 变量名
     * @param null $default  默认值
     * @param string $filter 过滤方法
     * @return mixed
     */
    public function param($name = '', $default = null, $filter = '')
    {
        if(empty($this->param)){
            $method = $this->method(true);
            //获取请求变量
            switch ($method){
                case 'POST':
                    $vars = $this->post(false); //false返回原数据，不过滤
                    break;
                case 'PUT':
                case 'DELETE':
                case 'PATCH':
                    $vars = $this->put(false);
                    break;
                default:
                    $vars  = [];
            }
            //合并 get post patch route参数
            $this->param = array_merge($this->get(false), $vars, $this->route(false));
        }
        //文件上传参数
        if($name === true){
            $file = $this->file();
            $data = array_merge($this->param, $file);
            return $this->obtain($data, '', $default, $filter);
        }
        //综合所有请求参数，获取指定的过滤
        return $this->obtain($this->param, $name, $default, $filter);
    }


    /**
     * 获取上传的文件信息
     * @param string $name
     * @return array|mixed|null
     */
    public function file($name = '')
    {
        //原始文件上传信息
        if(empty($this->file)) $this->file = isset($_FILES)?$_FILES:[];
        //如果是数组，设置信息
        if(is_array($name)) return array_merge($this->file, $name);
        $file = $this->file;
        if(!empty($file)){
            //处理上传文件
            $array = [];
            //遍历上传文件数组，一个数组包含一次提交所含的若干文件信息
            foreach ($file as $key => $val){
                //$val 一次提交包含的若干文件信息
                if(is_array($val['name'])){
                    $file_obj = [];
                    //文件上传信息键值,文件信息数组
                    $msg_key = array_keys($val);
                    //上传个数
                    $count = count($val['name']);
                    //提取一次上传的文件各自信息
                    for($i = 0; $i < $count; $i++){
                        $temp_file = [];
                        if(empty($val['tmp_name'][$i])) continue;
                        $temp_file['key'] = $key;
                        foreach ($msg_key as $item){
                            $temp_file[$item] = $val[$item][$i];
                        }
                        //处理各自信息,得到文件处理对象
                        $file_obj[] = (new File($temp_file['tmp_name']))->setInfo($temp_file);
                    }
                    $array[$key] = $file_obj;
                }else{
                    if($val instanceof File){
                        $array[$key] = $val;    //如果是文件处理对象
                    }else{
                        if(empty($val['tmp_name'])) continue;
                        $array[$key] = (new File($val['tmp_name']))->setInfo($val); //如果是单个文件
                    }
                }
            }
            //要获取的上传文件的名字
            if(strpos($name, '.')) list($name, $sub) = explode('.', $name);
            //根据名字获取内容
            if($name === ''){
                return $array;
            }elseif (isset($sub) && isset($array[$name][$sub])){
                return $array[$name][$sub];
            }elseif (isset($array[$name])){
                return $array[$name];
            }
        }
        return null;
    }

    /**
     * 设置、获取delete参数
     * @param string|array $name  要设置获取的值
     * @param null $default  默认值
     * @param string $filter  过滤方式
     * @return array|mixed
     */
    public function delete($name = '', $default = null, $filter = '')
    {
        return $this->put($name, $default, $filter);
    }

    /**
     * 设置、获取patch参数
     * @param string|array $name  要是指获取的值
     * @param null $default  默认值
     * @param string $filter  过滤方式
     * @return array|mixed|string
     */
    public function patch($name = '', $default = null, $filter = '')
    {
        return $this->put($name, $default, $filter);
    }

    /**
     * 设置、获取REQUEST参数
     * @param string $name
     * @param null $default
     * @param $filter
     * @return array|mixed
     */
    public function request($name = '', $default = null, $filter)
    {
        if(empty($this->request)) $this->request = $_REQUEST;
        if(is_array($name)){
            $this->param = [];
            $this->request = array_merge($this->request, $name);
        }
        return $this->obtain($this->request, $name, $default, $filter);
    }

    /**
     * 获取设置session
     * @param string|array $name  要设置获取的cookie参数
     * @param null $default 默认值
     * @param string $filter  过滤方式
     * @return array|mixed
     */
    public function session($name = '', $default = null, $filter = '')
    {
        if(empty($this->session)) $this->session = Session::get();
        if(is_array($name)) $this->session = array_merge($this->session, $name);
        return $this->obtain($this->session, $name, $default, $filter);
    }

    /**
     * 设置获取cookie
     * @param string|array $name  要设置获取的cookie参数
     * @param null $default  默认值
     * @param string $filter  过滤方式
     * @return array|mixed
     */
    public function cookie($name = '', $default = null, $filter = '')
    {
        if(empty($this->cookie)) $this->cookie = $_COOKIE;
        if(is_array($name)) $this->cookie = array_merge($this->cookie, $name);
        return $this->obtain($this->cookie, $name, $default, $filter);
    }

    /**
     * 获取设置SERVER参数
     * @param string|array $name 要获取设置的参数
     * @param null $default 默认值
     * @param string $filter  过滤方式
     * @return array|mixed
     */
    public function server($name = '', $default = null, $filter = '')
    {
        if(empty($this->server)) $this->server = $_SERVER;
        if(is_array($name)) $this->server = array_merge($this->server, $name);
        return $this->obtain($this->server, false === $name ? false : strtoupper($name), $default, $filter);
    }

    /**
     * 获取、设置环境变量
     * @param string|array $name 要获取设置的变量
     * @param null $default 默认值
     * @param string $filter 过滤方法
     * @return array|mixed
     */
    public function env($name = '', $default = null, $filter = '')
    {
        if(empty($this->env)) $this->env = $_ENV;
        if(is_array($name)) $this->env = array_merge($this->env, $name);
        return $this->obtain($this->env, false === $name ? false : strtoupper($name), $default, $filter);
    }

    /**
     * 设置、过滤获取put参数
     * @param string|array $name  要获取的参数名。或者要设置的参数数组
     * @param null $default  默认值
     * @param string $filter  过滤方式
     * @return array|mixed|string
     */
    public function put($name = '', $default = null, $filter = '')
    {
        //设置put参数
        if(is_array($name)){
            $this->param = [];
            return $this->put = is_null($this->put)?$name:array_merge($this->put, $name);
        }

        //过滤，获取put参数
        if(is_null($this->put)){
            $put = $this->input;
            if(strpos($put, '":')){
                //json格式
                $this->put = json_decode($put);
            }else{
                //query格式
                parse_str($put, $this->put);
            }
        }
        return $this->obtain($this->put, $name, $default, $filter);
    }

    /**
     * 设置、过滤获取get参数
     * @param string|array $name  要设置的参数或者要获取的参数名
     * @param null $default  默认值
     * @param string $filter 过滤方式
     * @return array|mixed
     */
    public function get($name = '', $default = null, $filter = '')
    {
        //设置get参数
        if(is_array($name)){
            $this->param = [];
            return $this->get = array_merge($this->get, $name);
        }
        //过滤获取get参数
        if(empty($this->get)) $this->get = $_GET;
        return $this->obtain($this->get, $name, $default, $filter);
    }

    /**
     * 设置获取post参数
     * @param string $name  post参数名，为空返回整个post数组
     * @param null $default  参数过滤默认值
     * @param string $filter  过滤方式
     * @return array|mixed
     */
    public function post($name = '', $default = null, $filter = '')
    {
        //设置post参数
        if(is_array($name)){
            $this->param = [];
            return $this->post = array_merge($this->post, $name);
        }
        //过滤、获取post参数，支持多重名称a.b.c@s
        if(empty($this->post)) $this->post = $_POST;
        return $this->obtain($this->post, $name, $default, $filter);
    }

    /**
     * 设置、获取route参数
     * @param string|array $name  要设置获取的参数
     * @param null $default  默认值
     * @param string $filter 过滤方法
     * @return array|mixed
     */
    public function route($name = '', $default = null, $filter = '')
    {
        //设置路由参数
        if(is_array($name)){
            $this->param = [];
            return $this->route = array_merge($this->route, $name);
        }
        //过滤、获取route参数
        return $this->obtain($this->route, $name, $default, $filter);
    }

    /**
     * 获取、设置头信息
     * @param string $name
     * @param null $default
     * @return array|mixed|null
     */
    public function header($name = '', $default = null)
    {
        if(empty($this->header)){
            $header = [];
            //Fetch all HTTP request headers
            if(function_exists('apache_request_headers') && $result = apache_request_headers()){
                $header = $result;
            }else{
                $server = $this->server?:$_SERVER;
                foreach ($server as $k => $v){
                    if(strpos($k, 'HTTP_') === 0){
                        //截取HTTP_后面部分，第二个参数替换第一个参数
                        $k = str_replace('_', '-', strtolower(substr($k, 5)));
                        $header[$k] = $v;
                    }
                }
                //类型
                if(isset($server['CONTENT_TYPE'])) $header['content-type'] = $server['CONTENT_TYPE'];
                //长度
                if(isset($server['CONTENT_LENGTH'])) $header['content-length'] = $server['CONTENT_LENGTH'];
            }
            //转化为小写
            $this->header = array_change_key_case($header);
        }
        //数组设置值
        if(is_array($name)) return $this->header = array_merge($this->header, $name);
        //名称为空，返回所有头信息
        if($name === '') return $this->header;
        //名称不为空
        $name = str_replace('_', '-', strtolower($name));
        return isset($this->header[$name])?$this->header[$name]:$default;
    }

    /**
     * 过滤并获取数据，支持整体过滤（name为空） 也支持单个元素过滤（name不为空）
     * @param array $data
     * @param string $name
     * @param null $default
     * @param string $filter
     * @return array|mixed
     */
    public function obtain($data = [], $name = '', $default = null, $filter = '')
    {
        //返回原始数据
        if($name === false) return $data;
        $name = (string)$name;
        //name不为空 a.b.c@s，获取指定的元素
        if($name != ''){
            //解析name
            if(empty($type)) $type = 's';   //变量默认转化为字符串
            if(strpos($name, '@'))list($name, $type) = explode('@', $name);
            //获取要过滤的值
            $name = explode('.', $name);
            foreach ($name as $val){
                if(!isset($data[$val])) return $default;
                $data = $data[$val];
            }
            //如果是对象，直接返回
            if(is_object($data)) return $data;
        }

        //解析过滤器
        if(is_null($filter)){
            $filter = [];
        }else{
            $filter = $filter?:$this->filter;
            if(is_string($filter)){
                $filter = explode(',', $filter);
            }else{
                $filter = (array)$filter;
            }
        }
        //默认值添加进过滤数组,最后一个元素
        $filter[] = $default;
        //过滤数据
        if(is_array($data)){
            //myfunction 接受两个参数。array 参数的值作为第一个，键名作为第二个。如果提供了可选参数 userdata ，将被作为第三个参数传递给回调函数。
            array_walk_recursive($data, [$this, 'filter'], $filter);
            reset($data);   //将数组的内部指针设置为第一个元素
        }else{
            $this->filter($data, $name, $filter);
        }
        //强制类型转换
        if(isset($type) && $data != $default){
            $this->typeCast($data, $type);
        }
        return $data;
    }

    /**
     * 递归过滤给定的值，必须满足所有过滤条件
     * @param mixed $value  待过滤的值
     * @param string $key  无具体意义，仅用来接受 array_walk_recursive()的参数
     * @param array $filter  过滤方法（函数+正则匹配）+默认值
     * @return array
     */
    public function filter(&$value, $key, $filter)
    {
        //弹出并返回 array 数组的最后一个单元，并将数组 array 的长度减一
        $default = array_pop($filter);
        //变量要满足所有的过滤条件
        foreach ($filter as $method){
            //匹配规则是方法，过滤非标量,/调用函数或方法过滤
            if(is_callable($method)){
                $value = call_user_func($method, $value);
            }else {
                if(!is_scalar($value)) continue;
                //检测变量是否是一个标量; integer、float、string 或 boolean是标量，而 array、object 和 resource 则不是标量。
                if(strpos($method, '/') !== false){
                    //正则过滤，值是否匹配正则表达式
                    if(!preg_match($method, $value)){
                        $value = $default;
                        break;  //不匹配退出循环过滤，返回默认值
                    }
                }elseif (!empty($method)){
                    //过滤器ID，filter_id()函数返回指定过滤器的 ID 号
                    $filter_id = is_int($method)?$method:filter_id($method);
                    //如果不是正则匹配，filter_var() 函数通过指定的过滤器过滤变量。
                    $value = filter_var($value, $filter_id);
                    //过滤失败
                    if($value === false){
                        $value = $default;
                        break;  //不匹配退出循环过滤，返回默认值
                    }
                }
            }
        }
        //过滤SQL注入字段
        $this->filterExp($value);
    }

    /**
     * 过滤表单中的表达式
     * @param string $value
     * @return void
     */
    public function filterExp(&$value)
    {
        // 过滤查询特殊字符
        if (is_string($value) && preg_match('/^(EXP|NEQ|GT|EGT|LT|ELT|OR|XOR|LIKE|NOTLIKE|NOT BETWEEN|NOTBETWEEN|BETWEEN|NOTIN|NOT IN|IN)$/i', $value)) {
            $value .= ' ';
        }
    }

    /**
     * 类型转换
     * @param mixed $data  待转换的数据
     * @param string $type  转换类型
     */
    public function typeCast(&$data, $type)
    {
        switch (strtolower($type)){
            //数组
            case 'a':
                $data = (array)$data;
                break;
            //数字
            case 'd':
                $data = (int)$data;
                break;
            //浮点数
            case 'f':
                $data = (float)$data;
                break;
            //布尔值
            case 'b':
                $data = (bool)$data;
                break;
            //字符串
            case 's':
            default:
                if(is_scalar($data)){
                    $data = (string)$data;
                }else{
                    throw new \InvalidArgumentException('变量类型错误：' . gettype($data));
                }
        }
    }

    /**
     * 检查参数是否存在
     * @param string $name 参数名
     * @param string $type  参数类型
     * @param bool $checkEmpty  检查参数是否为空字符串
     * @return bool
     */
    public function exist($name, $type = 'param', $checkEmpty = false)
    {
        if(empty($this->$type)){
            //值为空，获取
            $param = $this->$type();
        }else{
            $param = $this->$type;
        }
        //支持多维数组
        $name = explode('.', $name);
        foreach ($name as $value){
            if(isset($param[$value])){
                $param = $param[$value];
            }else{
                return false;
            }
        }
        //检查是否为空字符串
        return ($checkEmpty && $param === '')?false:true;
    }

    /**
     * 获取指定参数
     * @param string $name 参数名
     * @param string $type 参数类型
     * @return array
     */
    public function gain($name, $type = 'param')
    {
        $param = $this->$type();
        if(is_string($name))  $name = explode(',', $name);
        $val = [];
        foreach ($name as $key){
            if(isset($param[$key])) $val[$key] = $param[$key];
        }
        return $val;
    }

    /**
     * 排除某参数获取其他参数
     * @param string $name  参数名
     * @param string $type  参数类型
     * @return mixed
     */
    public function except($name, $type = 'param')
    {
        $param = $this->$type();
        if(is_string($name))  $name = explode(',', $name);
        foreach($name as $key){
            if(isset($param[$key])) unset($param[$key]);
        }
        return $param;
    }

    /**
     * SSL协议位于TCP/IP协议与各种应用层协议之间，为数据通讯提供安全支持
     * Secure Socket Layer,是对TCP/IP协议的封装，方便应用层使用
     * @return bool
     */
    public function isSsl()
    {
        $server = array_merge($this->server, $_SERVER);
        if(isset($server['HTTPS']) && ('1' == $server['HTTPS'] || 'on' == strtolower($server['HTTPS']))){
            return true;
        }elseif (isset($server['REQUEST_SCHEME']) && 'https' == $server['REQUEST_SCHEME']){
            return true;
        }elseif (isset($server['SERVER_PORT']) && ('443' == $server['SERVER_PORT'])){
            return true;
        }elseif (isset($server['HTTP_X_FORWARDED_PROTO']) && 'https' == $server['HTTP_X_FORWARDED_PROTO']){
            return true;
        }
        return false;
    }

    /**
     * 当前是否是ajax请求
     * @param bool $ajax true 获取原始ajax请求
     * @return bool
     */
    public function isAjax($ajax = false)
    {
        $item = $this->server('HTTP_X_REQUESTED_WITH', '', 'strtolower');
        $result = ($item == 'xmlhttprequest') ? true : false;
        if($ajax === $result){
            return $result;
        }else{
            return $this->param(Conf::get('VAR_AJAX'))?true:$result;
        }
    }

    /**
     * 是否是pjax请求
     * @param bool $pjax true 获取原始pjax请求
     * @return bool
     */
    public function isPjax($pjax = false)
    {
        $item = !is_null($this->server('HTTP_X_PJAX')) ? true : false;
        if($pjax === true){
            return $item;
        }else{
            return $this->param(Conf::get('VAR_PJAX'))?true:$item;
        }
    }

    /**
     * 获取客户端IP地址
     * @param int $type  type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @param bool $adv    $adv 是否进行高级模式获取（有可能被伪装）
     * @return mixed
     */
    public function ip($type = 0, $adv =false)
    {
        $type = $type?1:0;
        static $ip = null;
        if(!is_null($ip)) return $ip[$type];
        if($adv){
            if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
                $array = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                //索键值，返回键名
                $pos = array_search('unknown', $array);
                if($pos !== false) unset($array[$pos]);
                //数组当前元素
                $ip = trim(current($array));
            }elseif (isset($_SERVER['HTTP_CLIENT_IP'])){
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            }elseif (isset($_SERVER['REMOTE_ADDR'])){
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        }elseif (isset($_SERVER['REMOTE_ADDR'])){
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        //验证IP的合法性;%u - 不包含正负号的十进制数（大于等于 0）;sprintf() 函数把格式化的字符串写写入一个变量中
        $long = sprintf("%u", ip2long($ip));    //ip2long  把ip地址转换成整型;long2ip,相反，把整型还原为ip地址
        $ip = $long?array($ip, $long):array('0.0.0.0', 0);
        return $ip[$type];
    }

    /**
     * 检查是否使用手机访问
     * @return bool
     */
    public function mobile()
    {
        if(isset($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'], "wap")){
            return true;
        }elseif (isset($_SERVER['HTTP_ACCEPT']) && strpos(strtoupper($_SERVER['HTTP_ACCEPT']), "VND.WAP.WML")){
            return true;
        }elseif (isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE'])){
            return true;
        }elseif (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $_SERVER['HTTP_USER_AGENT'])){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 获取URL中scheme请求参数
     * @return string
     */
    public function scheme()
    {
        return $this->isSsl()?'https':'http';
    }

    /**
     * 获取URL中query参数
     * @return mixed
     */
    public function query()
    {
        return $this->server('QUERY_STRING');
    }

    /**
     * 获取当前请求的host
     * @return array|mixed
     */
    public function host()
    {
        return $this->server('HTTP_HOST');
    }

    /**
     * 当前请求URL地址中的port参数
     * @return integer
     */
    public function port()
    {
        return $this->server('SERVER_PORT');
    }

    /**
     * 当前请求 SERVER_PROTOCOL
     * @return integer
     */
    public function protocol()
    {
        return $this->server('SERVER_PROTOCOL');
    }

    /**
     * 当前请求 REMOTE_PORT
     * @return integer
     */
    public function remotePort()
    {
        return $this->server('REMOTE_PORT');
    }

    /**
     * 获取设置当前routeInfo信息
     * @param array $route
     * @return array
     */
    public function routeInfo($route = [])
    {
        //设置
        if(!empty($route))return $this->routeInfo = $route;
        //获取
        return $this->routeInfo;
    }

    /**
     * 设置、获取当前调度信息
     * @param null $dispatch
     * @return array|null
     */
    public function dispatch($dispatch = null)
    {
        //设置
        if(!is_null($dispatch)) return $this->dispatch = $dispatch;
        //获取
        return $this->dispatch;
    }

    /**
     * 获取设置当前请求模块
     * @param null $module
     * @return $this|string
     */
    public function module($module = null)
    {
        if(!is_null($module)) {
            $this->module = $module;
            return $this;
        }else{
            return $this->module?:'';
        }
    }

    /**
     * 获取设置当前请求命令
     * @param null $command
     * @return $this|string
     */
    public function command($command = null)
    {
        if(!is_null($command)){
            $this->command = $command;
            return $this;
        }else{
            return $this->command?:'';
        }
    }

    /**
     * 获取、设置当前请求控制器
     * @param null $controller
     * @return $this|string
     */
    public function controller($controller = null)
    {
        if(!is_null($controller)){
            $this->controller = $controller;
            return $this;
        }else{
            return $this->controller?:'';
        }
    }

    /**
     * 获取当前请求语言
     * @param null $lang
     * @return $this|string
     */
    public function langset($lang = null)
    {
        if(!is_null($lang)){
            $this->language = $lang;
            return $this;
        }else{
            return $this->language?:'';
        }
    }

    /**
     * 设置、获取当前请求的content
     * @return null|string
     */
    public function getContent()
    {
        //设置
        if(is_null($this->content)) return $this->content = $this->input;
        //获取
        return $this->content;
    }

    /**
     * 获取当前请求php://input
     * @return string
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * 生成请求令牌
     * @param string $name 令牌名
     * @param string $type 加密类型
     * @return string 请求令牌
     */
    public function token($name = '__token__', $type = 'md5')
    {
        $type = is_callable($type)?$type:'md5';
        $token = call_user_func($type, $_SERVER['REQUEST_TIME_FLOAT']);
        //header() 函数向客户端发送原始的 HTTP 报头。
        if($this->isAjax())header($name.':'.$token);
        Session::set($name, $token);
        return $token;
    }

    /**
     * 请求地址设置缓存访问，并设置有效期
     * 第二次访问相同的路由地址的时候，会自动获取请求缓存的数据响应输出，并发送304状态码。
     *默认请求缓存的标识为当前访问的pathinfo地址，
     * 可以定义请求缓存的标识
     * cache('blog/:id',3600) 对blog/:id定义的动态访问地址进行3600秒的请求缓存
     * cache('__URL__',600)  使用当前的URL地址作为缓存标识
     * cache('[html]',600)  对某个URL后缀的请求进行缓存
     * @access public
     * @param string $key 缓存标识，支持变量规则 ，例如 item/:name/:id
     * @param array $except 排除指定 URL
     * @param mixed  $expire 缓存有效期
     * @return void
     */
    public function cache($key, $expire = null, $except = [])
    {
        //只缓存get请求
        if($key === false || !$this->isGet() || $this->checkCache) return;
        //设置缓存请求检查，缓存之后为true
        $this->checkCache = true;
        //关闭缓存
        if($expire === false) return;
        //获取缓存key
        $keyInfo = $this->getCacheKey($key, $except);
        $key = $keyInfo['key'];
        $fun = $keyInfo['fun'];
        if($key === null) return;
        //解析缓存key 的形式
        $key = $this->parseCacheKey($key);
        if(is_null($key)) return;
        //调用函数，获取缓存键值
        if (!is_null($fun))  $key = $fun($key);
        //如果请求时间小于，资源最后修改时间+资源过期时间，则客户端缓存有效
        if (strtotime($this->server('HTTP_IF_MODIFIED_SINCE')) + $expire > $_SERVER['REQUEST_TIME']) {
            //告知客户端可以直接读取缓存
            $response = Response::create()->code(304);
            throw new HttpResponseException($response);
        } elseif (Cache::exist($key)) {
            //如果有缓存的请求，用缓存的请求创建响应实例
            list($content, $header) = Cache::get($key);
            //链式调用
            $response = Response::create($content)->header($header);
            throw new HttpResponseException($response);
        } else {
            $this->cache = [$key, $expire];
        }

    }

    /**
     *
     * @param $key
     * @param array $except
     * @return array|void
     */
    public function getCacheKey($key, $except = [])
    {
        //如果key是匿名函数
        if($key instanceof \Closure){
            //调用闭包匿名函数,获取缓存的标识名
            $key = call_user_func_array($key, [$this]);
        }elseif ($key === true){
            //排除指定的URL缓存
            foreach ($except as $rule) {
                if (strpos($this->url(), $rule) === 0) null;
            }
            //自动缓存，默认缓存标识__URL__,true为自动根据当前URL缓存
            $key = '__URL__';
        }elseif (strpos($key, '|')){
            list($key,$fun) = explode('|', $key);
        }
        //特殊规则替换
        if(strpos($key, '__') !== false){
            //后者替换前者
            $key = str_replace(['__MODULE__','__COMMAND__', '__CONTROLLER__', '__URL__'], [$this->module,$this->command, $this->controller, md5($this->url())], $key);
        }
        return [
            'key' => $key,
            'fun' => !isset($fun)?null:$fun,
        ];

    }

    /**
     * 解析缓存key 是  blog/:id 形式  还是 [html] 形式
     * @param $key
     * @return mixed|null|string
     */
    public function parseCacheKey($key)
    {
        //blog/:id
        if (strpos($key, ':') !== false) {
            $param = $this->param();
            //参数值替换参数名
            foreach ($param as $item => $val) {
                if (is_string($val) && strpos($key, ':' . $item) !== false) {
                    //把key里面的参数名替换成参数值
                    $key = str_replace(':' . $item, $val, $key);
                }
            }
        } elseif (strpos($key, ']')) {
            //[html]
            if ('[' . $this->ext() . ']' == $key) {
                // 缓存某个后缀的请求
                $key = md5($this->url());
            } else {
                $key = null;
            }
        }
        return $key;
    }


    /**
     * 获取缓存
     * @return mixed
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * 可以动态注入当前Request对象的属性
     * 设置当前请求绑定的对象实例
     * @param string $name 注入对象属性标识
     * @param null $obj   注入属性、对象实例
     */
    public function attrInj($name, $obj = null)
    {
        if(is_array($name)){
            $this->bind = array_merge($this->bind, $name);
        }else{
            $this->bind[$name] = $obj;
        }
    }

    /**
     * 向一个不能访问的属性赋值的时候 __set() 方法被调用
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->bind[$name] = $value;
    }

    /**
     * 从一个不能访问的属性读取数据的时候 __get() 方法被调用
     * @param $name
     * @return mixed|null
     */
    public function __get($name)
    {
        return isset($this->bind[$name]) ? $this->bind[$name] : null;
    }

    /**
     * 用isset() 判断对象不可见的属性时(protected/private/不存在的属性),__isset()被调用
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->bind[$name]);
    }


}
?>