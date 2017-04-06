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

use Core\Lib\Registry\RequestRegistry;


/**
 * 响应客户端请求
 * Class Response
 * @package Core\Lib
 */
class Response
{
    //原始数据
    protected $data;
    // 当前的contentType
    protected $contentType = 'text/html';
    //字符集
    protected $charset = 'utf-8';
    //状态值
    protected $code = 200;
    //输出参数
    protected $options = [];
    //header参数
    protected $header = [];
    //内容
    protected $content = null;

    public function __construct($data, $code = 200, array $header = [], $options = [])
    {
        //初始化原始数据
        $this->data($data);
        //头信息
        $this->header = $header;
        //状态码
        $this->code = $code;
        //输出参数
        if(!empty($options)) array_merge($this->options, $options);
        //初始化发送的媒体类型
        $this->contentType($this->contentType, $this->charset);
    }

    /**
     * 创建response对象
     * @param string $data 响应数据
     * @param string $type 响应数据驱动类型
     * @param int $code 状态码
     * @param array $header 头信息
     * @param array $options 参数项
     * @return static  Response|JsonResponse|ViewResponse|XmlResponse|RedirectResponse|JsonpResponse
     */
    static public function create($data = '', $type = '', $code = 200, array $header = [], $options = [])
    {
        //响应类型
        $type = empty($type)?'null':$type;
        //响应驱动
        $driver = (strpos($type, '\\') !== false)?$type:'\\Core\\Lib\\Driver\\'.ucfirst(strtolower($type));
        if(class_exists($driver)){
            $res = new $driver($data, $code, $header, $options);
        }else{
            //类似$this,当前实例化的类
            $res = new static($data, $code, $header, $options);
        }
        return $res;
    }

    /**
     * 发送数据到客户端
     */
    public function send()
    {
        //处理输出数据
        $data = $this->getContent();
        if (!headers_sent() && !empty($this->header)) {
            // 发送状态码
            http_response_code($this->code);
            // 发送头部信息
            foreach ($this->header as $name => $val) {
                header($name . ':' . $val);
            }
        }
        if($this->code == 200){
            //获取缓存的客户端请求
            $cache = RequestRegistry::getRequest()->getCache();
            if($cache){
                //Cache-Control   用于指定缓存指令，缓存指令是单向的（响应中出现的缓存指令在请求中未必会出现），
                //且是独立的（一个消息的缓存指令不会影响另一个消息处理的缓存机制），HTTP1.0使用的类似的报头域为Pragma。
                //max-age指示客户机可以接收生存期不大于指定时间（以秒为单位）的响应
                header('Cache-Control: max-age=' . $cache[1] . ',must-revalidate');
                //Last-Modified实体报头域用于指示资源的最后修改日期和时间
                header('Last-Modified:' . gmdate('D, d M Y H:i:s') . ' GMT');
                //Expires实体报头域给出响应过期的日期和时间
                header('Expires:' . gmdate('D, d M Y H:i:s', $_SERVER['REQUEST_TIME'] + $cache[1]) . ' GMT');
                //资源类型
                $header['Content-Type'] = $this->header['Content-Type'];
                //缓存 key  data  expire
                Cache::set($cache[0], [$data, $header], $cache[1]);
            }
        }
        echo $data;
        // 提高页面响应
        if (function_exists('fastcgi_finish_request')) {
            //调用的时候, 会发送响应, 关闭连接. 但是不会结束PHP的运行.
            //提高请求的处理速度,如果有些处理可以在页面生成完后再进行,就可以使用这个方法
            fastcgi_finish_request();
        }
        //清空当次请求的有效数据
        Session::flush();
    }

    /**
     * 获取输出数据
     * @return null|string
     */
    public function getContent()
    {
        if($this->content === null){
            $content = $this->output();
            if(!is_string($content) && !is_numeric($content) && $content !== null && !is_callable([$content,'__toString'])){
                throw new  \InvalidArgumentException(sprintf('变量类型错误： %s', gettype($content)));
            }
            $this->content = (string)$content;
        }
        return $this->content;
    }

    /**
     * 设置页面输出内容
     * @param $content
     * @return $this
     */
    public function content($content)
    {
        if (null !== $content && !is_string($content) && !is_numeric($content) && !is_callable([$content, '__toString',])) {
            throw new \InvalidArgumentException(sprintf('变量类型错误： %s', gettype($content)));
        }
        //设置页面输出内容
        $this->content = (string) $content;
        return $this;
    }

    /**
     * 获取状态码
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }


    /**
     * 处理数据
     * @return mixed
     */
    protected function output()
    {
        return $this->data;
    }

    /**
     * 输出参数设置
     * @param array $options  输出参数
     * @return $this
     */
    public function options($options = [])
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * 设置原始数据
     * @param mixed $data 原始数据
     * @return $this
     */
    public function data($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * 设置头信息
     * @param mixed $name 设置内容（名称）
     * @param null $value 内容
     * @return $this
     */
    public function header($name, $value = null)
    {
        if(is_array($name)){
            $this->header = array_merge($this->header, $name);
        }else{
            $this->header[$name] = $value;
        }
        return $this;
    }

    /**
     * Content-Type实体报头域用于指明发送给接收者的实体正文的媒体类型
     * @param $contentType
     * @param string $charset
     * @return $this
     */
    public function contentType($contentType, $charset = 'utf-8')
    {
        $this->header['Content-Type'] = $contentType . '; charset=' . $charset;
        return $this;
    }

    /**
     * 设置http状态码
     * @param $code
     * @return $this
     */
    public function code($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * Last-Modified实体报头域用于指示资源的最后修改日期和时间
     * @param string $time
     * @return $this
     */
    public function lastModified($time)
    {
        $this->header['Last-Modified'] = $time;
        return $this;
    }

    /**
     * Expires，资源过期时间
     * @param string $time
     * @return $this
     */
    public function expires($time)
    {
        $this->header['Expires'] = $time;
        return $this;
    }

    /**
     * ETag
     * Etag主要为了解决Last-Modified无法解决的一些问题.
     * 他能比Last_Modified更加精确的知道文件是否被修改过.如果有个文件修改非常频繁，
     * 比如在秒以下的时间内进行修改，比如1秒内修改了10次，If-Modified-Since能检查只能秒级的修改，
     * 所以这种修改无法判断.原因是UNIX记录MTIME只能精确到秒.所以我们选择生成Etag，因为Etag可以综合Inode，MTime和Size，可以避免这个问题
     * @param string $eTag
     * @return $this
     */
    public function eTag($eTag)
    {
        $this->header['ETag'] = $eTag;
        return $this;
    }

    /**
     * 页面缓存控制
     * 请求时的缓存指令包括：no-cache（用于指示请求或响应消息不能缓存）、no-store、max-age、max-stale、min-fresh、only-if-cached;
     *响应时的缓存指令包括：public、private、no-cache、no-store、no-transform、must-revalidate、proxy-revalidate、max-age、s-maxage.
     * @param string $cache 状态码
     * @return $this
     */
    public function cacheControl($cache)
    {
        $this->header['Cache-control'] = $cache;
        return $this;
    }

    /**
     * 获取头部信息
     * @param string $name 头部名称
     * @return mixed
     */
    public function getHeader($name = '')
    {
        return !empty($name) ? $this->header[$name] : $this->header;
    }

    /**
     * 获取原始数据
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}
?>