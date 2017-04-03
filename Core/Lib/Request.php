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

class Request{

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

    //控制信息
    protected $dispatch = [];
    protected $module;
    protected $command;
    protected $action;
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

    protected $content;

    //全局过滤规则
    protected $filter;

    //Hook扩展方法
    static protected $hook = [];

    //绑定属性
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
    public function __construct($options = []){
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
     * 魔术方法，调用不类存在的方法时，指定调用某个方法
     * @param $name
     * @param $arguments
     * @throws DragonException
     */
    public function __call($name, $arguments){
        //检查扩展方法是否存在
        if(array_key_exists($name, self::$hook)){
            array_unshift($arguments, $this);   //在数组开头插入元素
            call_user_func_array(self::$hook[$name], $arguments);
        }else{
            throw  new DragonException("方法不存在：".__CLASS__."->$name");
        }
    }

    /**
     * 注册Hook方法（钩子），支持单个和数组两种注册方式
     * @param $method
     * @param null $callback
     */
    static public function hook($method, $callback = null){
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
    public function domain($domain = null){
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
    public function url($url = null){
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
    public function baseUrl($url = null){
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
    public function baseFile($file = null){
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
    public function root($url = null){
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
    public function pathInfo(){
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
    public function path(){
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
    public function ext(){
        return pathinfo($this->pathInfo(), PATHINFO_EXTENSION);
    }

    /**
     * 获取当前请求时间
     * @param bool $float
     * @return mixed
     */
    public function time($float = false){
        return $float ? $_SERVER['REQUEST_TIME_FLOAT']:$_SERVER['REQUEST_TIME'];
    }

    /**
     * 当前请求的资源类型
     * @return bool|int|string
     */
    public function source(){
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
    public function sourceType($type, $val = ''){
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
    public function method($method = false){
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
    public function isGet(){
        return $this->method() == 'GET';
    }

    /**
     * 当前请求是否为put请求
     * @return bool
     */
    public function isPut(){
        return $this->method() == 'PUT';
    }

    /**
     * 当前请求是否为post请求
     * @return bool
     */
    public function isPost(){
        return $this->method() == 'POST';
    }

    /**
     * 当前请求是否为delete请求
     * @return bool
     */
    public function isDelete(){
        return $this->method() == 'DELETE';
    }

    /**
     * 当前请求是否为head请求
     * @return bool
     */
    public function isHead(){
        return $this->method() == 'HEAD';
    }

    /**
     * 当前请求是否为patch请求
     * @return bool
     */
    public function isPatch(){
        return $this->method() == 'PATCH';
    }

    /**
     * 当前请求是否为options请求
     * @return bool
     */
    public function isOptions(){
        return $this->method() == 'OPTIONS';
    }

    /**
     * PHP运行环境是否为 cli
     * PHP CLI模式开发不需要任何一种Web服务器（包括Apache或MS IIS等），CLI可以运行在各种场合。
     * @return bool
     */
    public function isCli(){
        return PHP_SAPI == "cli";
    }

    /**
     * PHP运行环境是否为 cgi
     *  PHP 默认编译为 CLI 和 CGI 程序
     * CGI是Common Gateway Interface的缩写，翻译成中文就是通用网关接口，它是网页的后台处理程序，运行在服务器端上，可以用多种语言书写
     * @return bool
     */
    public function isCgi(){
        return strpos(PHP_SAPI, 'cgi') === 0;
    }

    /**
     * 获取当前请求参数
     * @param string $name 变量名
     * @param null $default  默认值
     * @param string $filter 过滤方法
     * @return mixed
     */
    public function param($name = '', $default = null, $filter = ''){
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
            $this->param = array_merge($this->get(false), $vars, $this->route(false));
        }
        if($name === true){
            $file = $this->file();
            $data = array_merge($this->param, $file);
            return $this->obtain($data, '', $default, $filter);
        }
        return $this->obtain($this->param, $name, $default, $filter);
    }

    /**
     * 设置、过滤获取put参数
     * @param string|array $name  要获取的参数名。或者要设置的参数数组
     * @param null $default  默认值
     * @param string $filter  过滤方式
     * @return array|mixed|string
     */
    public function put($name = '', $default = null, $filter = ''){
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
    public function get($name = '', $default = null, $filter = ''){
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
    public function post($name = '', $default = null, $filter = ''){
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
    public function route($name = '', $default = null, $filter = ''){
        //设置路由参数
        if(is_array($name)){
            $this->param = [];
            return $this->route = array_merge($this->route, $name);
        }
        //过滤、获取route参数
        return $this->obtain($this->route, $name, $default, $filter);
    }

    /**
     * 过滤并获取数据，支持整体过滤（name为空） 也支持单个元素过滤（name不为空）
     * @param array $data
     * @param string $name
     * @param null $default
     * @param string $filter
     * @return array|mixed
     */
    public function obtain($data = [], $name = '', $default = null, $filter = ''){
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
            array_walk_recursive($data, [$this, 'filter'], $filter);
            reset($data);   //将数组的内部指针设置为第一个元素
        }else{
            $this->filter($data, $filter);
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
     * @param array $filter  过滤方法（函数+正则匹配）+默认值
     * @return array
     */
    public function filter(&$value, $filter){
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
    public function filterExp(&$value){
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
    public function typeCast(&$data, $type){
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


    public function host(){

    }
    public function scheme(){

    }

}
?>