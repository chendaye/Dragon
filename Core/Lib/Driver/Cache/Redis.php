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
 * Redis缓存驱动，适合单机部署、有前端代理实现高可用的场景，性能最好
 * 有需要在业务层实现读写分离、或者使用RedisCluster的需求，请使用Redisd驱动
 * 要求安装phpredis扩展：https://github.com/nicolasff/phpredis
 */
class Redis extends Driver
{
    protected $options = [
        'host'       => '127.0.0.1',
        'port'       => 6379,   //端口
        'password'   => '',
        'select'     => 0,
        'timeout'    => 0,  //超时时间
        'expire'     => 0,  //缓存过期时间
        'persistent' => false,  //长连接
        'prefix'     => '',
    ];

    /**
     * 构造函数
     * @param array $options 缓存参数
     * @access public
     */
    public function __construct($options = [])
    {
        //检查Redis扩展
        if (!extension_loaded('redis'))throw new \BadFunctionCallException('未开启Redis扩展！');
        //初始化配置
        if (!empty($options))$this->options = array_merge($this->options, $options);
        //长连接
        $func          = $this->options['persistent'] ? 'pconnect' : 'connect';
        //Redis句柄
        $this->handler = new \Redis;
        //连接
        $this->handler->$func($this->options['host'], $this->options['port'], $this->options['timeout']);
        //密码
        if ($this->options['password'] != '') $this->handler->auth($this->options['password']);
        //选择服务器
        if ($this->options['select'] != 0)$this->handler->select($this->options['select']);
    }

    /**
     * 判断缓存是否存在
     * @access public
     * @param string $name 缓存变量名
     * @return bool
     */
    public function exist($name)
    {
        return $this->handler->get($this->cacheKey($name)) ? true : false;
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
        //读取缓存
        $value = $this->handler->get($this->cacheKey($name));
        //缓存为空返回默认值
        if (is_null($value))return $default;
        //json解码
        $jsonData = json_decode($value, true);
        // 检测是否为JSON数据 true 返回JSON解析数组, false返回源数据
        return ($jsonData === null) ? $value : $jsonData;
    }

    /**
     * 写入缓存
     * @access public
     * @param string    $name 缓存变量名
     * @param mixed     $value  存储数据
     * @param integer   $expire  有效时间（秒）
     * @return boolean
     */
    public function set($name, $value, $expire = null)
    {
        //过期时间
        if (is_null($expire)) $expire = $this->options['expire'];
        //第一次写入缓存
        if ($this->tag && !$this->exist($name)) $first = true;
        //缓存名
        $key = $this->cacheKey($name);
        //对数组/对象数据进行json编码，再缓存
        $value = (is_object($value) || is_array($value)) ? json_encode($value) : $value;
        //有无过期时间，有保存过期时间
        if (is_int($expire) && $expire) {
            $result = $this->handler->setex($key, $expire, $value);
        } else {
            $result = $this->handler->set($key, $value);
        }
        //保存标签
        isset($first) && $this->setTagItem($key);
        return $result;
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
        return $this->handler->incrby($key, $step);
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
        $key = $this->cacheKey($name);
        return $this->handler->decrby($key, $step);
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    public function remove($name)
    {
        $key = $this->cacheKey($name);
        return $this->handler->delete($key);
    }

    /**
     * 清除缓存,按指定标签清除缓存
     * @access public
     * @param string $tag 标签名
     * @return boolean
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
        return $this->handler->flushDB();
    }

}
