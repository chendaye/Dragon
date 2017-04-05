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
    public static function instance(){
        if(!isset(self::$instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }
    /**
     * 初始化注册表信息
     */
    public function init(){
        //是否已经注册
        $request = RequestRegistry::getRequest();
        if(is_null($request)){
            //若没有注册，注册
            $this->registryOption([]);
        }
    }

    /**
     * 在请求注册表中注册请求对象
     * @param array $option
     * @return  void
     */
    protected function registryOption(array $option){
        //在注册表中注册
        RequestRegistry::setRequest(new Request());
    }

    static public function create($uri, $method = 'GET', $param = [], $cookie = [], $file = [], $server = [], $content = null){
        $server['PATH_INFO'] = '';
        $server['REQUEST_METHOD'] = strtoupper($method);
        $info = parse_url($uri);
        if(isset($info['host'])){
            $server['SERVER_NAME'] = $info['host'];
            $server['HTTP_HOST'] = $info['host'];
        }
        if(isset($info['scheme'])){
            if($info['scheme'] == 'https'){
                $server['HTTPS'] = 'on';
                $server['SERVER_PORT'] = 443;
            }else{
                unset($info['HTTPS']);
                $server['SERVER_PORT'] = 80;
            }
        }
        if(isset($info['port'])){
            $server['SERVER_PORT'] = $info['port'];
            $server['HTTP_HOST'] = $server['HTTP_HOST'].':'.$info['port'];
        }
        if(isset($info['user'])){
            $server['PHP_AUTH_USER'] = $info['user'];
        }
        if(isset($info['pass'])){
            $server['PHP_AUTH_PW'] = $info['pass'];
        }
        if(!isset($info['path'])){
            $info['path'] = '/';
        }
        $option = [];
        $queryString = '';
        if(isset($info['query'])){
            parse_str(html_entity_decode($info['query']), $query);
            if(!empty($param)){
                $param = array_replace($query, $param);
                //生成 URL-encode 之后的请求字符串
                $queryString = http_build_query($query, '', '&');
            }else{
                $param = $query;
                $queryString = $info['query'];
            }
        }elseif (!empty($info['param'])){
            $queryString = http_build_query($param, '', '&');
        }
    }
}
?>