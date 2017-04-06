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
 * Wincache缓存驱动
 * Class Wincache
 * @package Core\Lib\Driver\Cache
 */
class Wincache extends Driver
{
    protected $options = [
        'prefix' => '',
        'expire' => 0,
    ];

    /**
     * 构造函数
     * @param array $options 缓存参数
     * @throws Exception
     * @access public
     */
    public function __construct($options = [])
    {
        //检查是否支持
        if (!function_exists('wincache_ucache_info')) throw new \BadFunctionCallException('不支持WinCache！ ');
        //初始配置
        if (!empty($options))$this->options = array_merge($this->options, $options);
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
        return wincache_ucache_exists($key);
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
        //获取缓存
        return wincache_ucache_exists($key) ? wincache_ucache_get($key) : $default;
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
        if (is_null($expire))$expire = $this->options['expire'];
        $key = $this->cacheKey($name);
        //标签
        if ($this->tag && !$this->exist($name))  $first = true;
        //保存缓存
        if (wincache_ucache_set($key, $value, $expire)) {
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
        return wincache_ucache_inc($key, $step);
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
        return wincache_ucache_dec($key, $step);
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    public function remove($name)
    {
        return wincache_ucache_delete($this->cacheKey($name));
    }

    /**
     * 清除缓存
     * @access public
     * @param string $tag 标签名
     * @return boolean
     */
    public function clear($tag = null)
    {
        //清除标签下的所有缓存
        if ($tag) {
            $keys = $this->getTagItem($tag);
            foreach ($keys as $key) {
                wincache_ucache_delete($key);
            }
            //删除标签
            $this->remove('tag_' . md5($tag));
            return true;
        } else {
            //清除所有缓存
            return wincache_ucache_clear();
        }
    }

}
