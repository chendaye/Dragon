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
 * Xcache缓存驱动
 * Class Xcache
 * @package Core\Lib\Driver\Cache
 */
class Xcache extends Driver
{
    protected $options = [
        'prefix' => '',
        'expire' => 0,
    ];

    /**
     * 架构函数
     * @param array $options 缓存参数
     * @access public
     * @throws \BadFunctionCallException
     */
    public function __construct($options = [])
    {
        //是否支持Xcache
        if (!function_exists('xcache_info')) throw new \BadFunctionCallException('不支持Xcache！ ');
        //初始配置
        if (!empty($options)) $this->options = array_merge($this->options, $options);
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
        //判断缓存是否存在
        return xcache_isset($key);
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
        $key = $this->cacheKey($name);
        if($this->exist($name))return xcache_get($key);
        return $default;
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
        //第一次缓存
        if ($this->tag && !$this->exist($name))$first = true;
        //缓存名
        $key = $this->cacheKey($name);
        //缓存
        if (xcache_set($key, $value, $expire)) {
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
        return xcache_inc($key, $step);
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
        return xcache_dec($key, $step);
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    public function remove($name)
    {
        return xcache_unset($this->cacheKey($name));
    }

    /**
     * 清除缓存
     * @access public
     * @param string $tag 标签名
     * @return boolean
     */
    public function clear($tag = null)
    {
        // 指定标签清除
        if ($tag) {
            $keys = $this->getTagItem($tag);
            foreach ($keys as $key) {
                xcache_unset($key);
            }
            $this->remove('tag_' . md5($tag));
            return true;
        }
        //为指定标签
        if (function_exists('xcache_unset_by_prefix')) {
            //清除prefix下缓存
            return xcache_unset_by_prefix($this->options['prefix']);
        } else {
            //清除所有缓存
            return false;
        }
    }
}
