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

namespace Core\Lib\Driver\Cache;

/**
 * Memcache 缓存驱动
 * Class Memcache
 * @package Core\Lib\Driver\Cache
 */
class Memcache extends Driver
{
    protected $options = [
        'host'       => '127.0.0.1',
        'port'       => 11211,
        'expire'     => 0,
        'timeout'    => 0, // 超时时间（单位：毫秒）
        'persistent' => true,
        'prefix'     => '',
    ];

    /**
     * 构造函数
     * @param array $options 缓存参数
     * @access public
     * @throws \BadFunctionCallException
     */
    public function __construct($options = [])
    {
        //检测memcache扩展是否开启
        if (!extension_loaded('memcache')) throw new \BadFunctionCallException('未开启memcache扩展！ ');
        //初始化参数
        if (!empty($options)) $this->options = array_merge($this->options, $options);
        //实例句柄
        $this->handler = new \Memcache;
        // 支持集群
        $hosts = explode(',', $this->options['host']);
        $ports = explode(',', $this->options['port']);
        //默认端口
        if (empty($ports[0])) $ports[0] = 11211;
        // 建立连接
        foreach ((array) $hosts as $i => $host) {
            //端口
            $port = isset($ports[$i]) ? $ports[$i] : $ports[0];
            //连接
            $this->options['timeout'] > 0 ?
            $this->handler->addServer($host, $port, $this->options['persistent'], 1, $this->options['timeout']) :
            $this->handler->addServer($host, $port, $this->options['persistent'], 1);
        }
    }

    /**
     * 判断缓存
     * @access public
     * @param string $name 缓存变量名
     * @return bool
     */
    public function exist($name)
    {
        $key = $this->cacheKey($name);
        return $this->handler->get($key) ? true : false;
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed  $default 默认值
     * @return mixed
     */
    public function get($name, $default = false)
    {
        $result = $this->handler->get($this->cacheKey($name));
        return  ($result !== false) ? $result : $default;
    }


    /**
     * 写入缓存
     * @access public
     * @param string    $name 缓存变量名
     * @param mixed     $value  存储数据
     * @param integer   $expire  有效时间（秒）
     * @return bool
     */
    public function set($name, $value, $expire = null)
    {
        //过期时间
        if (is_null($expire))$expire = $this->options['expire'];
        //第一个缓存的标签文件
        if ($this->tag && !$this->exist($name)) $first = true;
        //文件名
        $key = $this->cacheKey($name);
        //写入缓存，并且设置标签
        if ($this->handler->set($key, $value, 0, $expire)) {
            isset($first) && $this->setTagItem($key);
            return true;
        }
        return false;
    }

    /**
     * 自增缓存（针对数值缓存）
     * @access public
     * @param string    $name 缓存变量名
     * @param int       $step 步长
     * @return false|int
     */
    public function inc($name, $step = 1)
    {
        $key = $this->cacheKey($name);
        return $this->handler->increment($key, $step);
    }

    /**
     * 自减缓存（针对数值缓存）
     * @access public
     * @param string    $name 缓存变量名
     * @param int       $step 步长
     * @return false|int
     */
    public function dec($name, $step = 1)
    {
        $key   = $this->cacheKey($name);
        $value = $this->handler->get($key) - $step;
        $res   = $this->handler->set($key, $value);
        if (!$res) {
            return false;
        } else {
            return $value;
        }
    }

    /**
     * 删除缓存
     * @param    string  $name 缓存变量名
     * @param bool|false $ttl
     * @return bool
     */
    public function remove($name, $ttl = false)
    {
        $key = $this->cacheKey($name);
        return ($ttl === false)  ?
        $this->handler->delete($key) :
        $this->handler->delete($key, $ttl);
    }

    /**
     * 清除缓存
     * @access public
     * @param string $tag 标签名
     * @return bool
     */
    public function clear($tag = null)
    {
        if ($tag) {
            // 指定标签清除
            $keys = $this->getTagItem($tag);
            foreach ($keys as $key) {
                $this->handler->delete($key);
            }
            $this->remove('tag_' . md5($tag));
            return true;
        }
        return $this->handler->flush();
    }
}
