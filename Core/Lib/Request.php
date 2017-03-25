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
    protected $hook = [];

    //绑定属性
    protected $bind = [];

    //php://input
    protected $input;

    //请求缓存
    protected $cache;

    //缓存检查
    protected $checkCache;

    public function __construct($options = []){
        //初始化参数
        foreach ($options as $name => $item){
            //检查给出的 property 是否存在于指定的类中以及是否能在当前范围内访问
            if(property_exists($this, $name)){
                $this->$name = $item;
            }
        }
        if(is_null($this->filter)) $this->filter = Conf::get('DEFAULT_FILTER');
        //php://input 可以读取http entity body中指定长度的值,由Content-Length指定长度,不管是POST方式或者GET方法提交过来的数据
        $this->input = file_get_contents('php://input');
        E([$this->filter, $this->input]);
    }

    static public function test(){
       // E(Conf::get('PAGINATE'));
    }

}
?>