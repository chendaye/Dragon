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
    protected $put = [];
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
                }elseif (($pos = strpos($_SERVER['PHP_SELF'], '/' . $script_name)) !== false){
                    $url = substr($_SERVER['SCRIPT_NAME'], 0, $pos) . '/' . $script_name;
                }elseif (isset($_SERVER['DOCUMENT_ROOT']) && strpos($_SERVER['SCRIPT_FILENAME'], $_SERVER['DOCUMENT_ROOT']) === 0){
                    $url = str_replace('\\', '/', str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME']));
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
                $file = str_replace('\\', '/', dirname($file));
            }
            $this->root = rtrim($file, '/');
        }
        return ($url === true)?$this->domain().$this->root:$this->root;
    }

    public function pathInfo(){

    }

    public function host(){

    }
    public function scheme(){

    }

    static public function test(){
        E(self::$hook);
    }

}
?>