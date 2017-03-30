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

namespace Core\Lib\Registry;
use Core\Lib\Request;

/**
 * 初始化请求注册表
 * Class RequestHelper
 * @package Core\Lib\Registry
 */
class RequestHelper extends RegistryHelper {
    private static $instance;
    /**
     * 单例构造方法
     * RequestHelper constructor.
     */
    private function __construct(){}
    /**
     * 获取单例实例
     * @return RequestHelper
     */
    static public function instance(){
        if(!isset(self::$instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }
    /**
     * 初始化注册表信息
     */
    public function init(){
        //若没有注册，注册
        $this->registryOption();
    }

    /**
     * 在请求注册表中注册请求对象
     * @param array $option
     * @param bool $flash
     * @return  void
     */
     protected function registryOption(array $option = [], $flash = false){
        $request = new Request($option);
        RequestRegistry::setRequest($request, $flash);
    }

    /**
     * 创建URL请求
     * @param $uri
     * @param string $method
     * @param array $param
     * @param array $cookie
     * @param array $file
     * @param array $server
     * @param null $content
     */
     public function create($uri, $method = 'GET', $param = [], $cookie = [], $file = [], $server = [], $content = null){
        $server['PATH_INFO'] = '';
        $server['REQUEST_METHOD'] = strtoupper($method);
        //解析url
        $info = parse_url($uri);
        //域名主机名
        if(isset($info['host'])){
            $server['SERVER_NAME'] = $info['host'];
            $server['HTTP_HOST'] = $info['host'];
        }
        //IP协议
        if(isset($info['scheme'])){
            if($info['scheme'] == 'https'){
                $server['HTTPS'] = 'on';
                $server['SERVER_PORT'] = 443;
            }else{
                unset($info['HTTPS']);
                $server['SERVER_PORT'] = 80;
            }
        }
        //端口
        if(isset($info['port'])){
            $server['SERVER_PORT'] = $info['port'];
            $server['HTTP_HOST'] = $server['HTTP_HOST'].':'.$info['port'];
        }
        //用户
        if(isset($info['user'])){
            $server['PHP_AUTH_USER'] = $info['user'];
        }
        //密码
        if(isset($info['pass'])){
            $server['PHP_AUTH_PW'] = $info['pass'];
        }
        //请求路径，域名之后的路径（除参数）
        if(!isset($info['path'])){
            $info['path'] = '/';
        }
        $option = [];
        $queryString = '';
        //查询信息存在
        if(isset($info['query'])){
            //解析query信息,为数组形式
            parse_str(html_entity_decode($info['query']), $query);
            //如果指定参数，替换解析出来的参数
            if(!empty($param)){
                //参数信息，$param 替换 $query,有就替换没有就创建
                $param = array_replace($query, $param);
                //生成数组转化为query参数
                $queryString = http_build_query($param, '', '&');
            }else{
                //参数信息，参数接在URL后面
                $param = $query;
                $queryString = $info['query'];
            }
        }elseif (!empty($info['param'])){
            //查询信息不存在，把参数拼接为query字符串
            $queryString = http_build_query($param, '', '&');
        }
        $server['REQUEST_URI'] = $info['path'].(empty($queryString)?'':'?'.$queryString);   //请求URL，请求路径+query
        $server['QUERY_STRING'] = $queryString;     //查询字串，query
        $option['cookie'] = $cookie;
        $option['param'] =  $param;     //查询参数
        $option['file'] = $file;
        $option['server'] = $server;    //服务器信息
        $option['url'] = $server['REQUEST_URI'];    //请求URL，请求路径+query
        $option['baseUrl'] = $info['path'];     //请求路径，除域名和查询字段外
        $option['pathinfo'] = ($info['path'] == '/')?'/':ltrim($info['path'], '/');     //请求路径
        $option['method'] = $server['REQUEST_METHOD'];
        $option['domain'] = isset($info['scheme'])?$info['scheme'].'://'.$server['HTTP_HOST']:'';   //IP协议
        $option['content'] = $content;
       //创建请求
       $this->registryOption($option, true);
    }
}
?>