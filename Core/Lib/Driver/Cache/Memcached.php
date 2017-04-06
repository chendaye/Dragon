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
 * Memcached缓存驱动
 * Class Memcached
 * @package Core\Lib\Driver\Cache
 */
class Memcached extends Driver
{
    protected $options = [
        'host'     => '127.0.0.1',
        'port'     => 11211,
        'expire'   => 0,
        'timeout'  => 0, // 超时时间（单位：毫秒）
        'prefix'   => '',
        'username' => '', //账号
        'password' => '', //密码
        'option'   => [],
    ];

    /**
     * 架构函数
     * @param array $options 缓存参数
     * @access public
     */
    public function __construct($options = [])
    {
        //是否开启memcached扩展
        if (!extension_loaded('memcached')) throw new \BadFunctionCallException('未开启memcached扩展！');
        //初始化配置
        if (!empty($options)) $this->options = array_merge($this->options, $options);
        //连接实例
        $this->handler = new \Memcached;
        //配置
        if (!empty($this->options['option'])) $this->handler->setOptions($this->options['option']);
        // 设置连接超时时间（单位：毫秒）
        if ($this->options['timeout'] > 0) $this->handler->setOption(\Memcached::OPT_CONNECT_TIMEOUT, $this->options['timeout']);
        // 支持集群
        $hosts = explode(',', $this->options['host']);
        $ports = explode(',', $this->options['port']);
        //默认端口
        if (empty($ports[0]))$ports[0] = 11211;
        // 建立连接
        $servers = [];
        foreach ((array) $hosts as $i => $host) {
            $port = isset($ports[$i]) ? $ports[$i] : $ports[0];
            $servers[] = [$host, $port, 1];
        }
        $this->handler->addServers($servers);
        //登录
        if ($this->options['username'] != '') {
            $this->handler->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
            $this->handler->setSaslAuthData($this->options['username'], $this->options['password']);
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
        return ($result !== false) ? $result : $default;
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
        if (is_null($expire)) $expire = $this->options['expire'];
        //第一次写缓存
        if ($this->tag && !$this->exist($name))$first = true;
        //缓存文件路径
        $key    = $this->cacheKey($name);
        //过期时间
        $expire = 0 == $expire ? 0 : $_SERVER['REQUEST_TIME'] + $expire;
        //写入缓存
        if ($this->handler->set($key, $value, $expire)) {
            //设置标签
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
        return false === $ttl ?
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
            $this->handler->deleteMulti($keys);
            $this->remove('tag_' . md5($tag));
            return true;
        }
        return $this->handler->flush();
    }
}
