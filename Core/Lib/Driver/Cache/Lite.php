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
 * Cache_Lite缓存类
 * @author chendaye <chendaye666@gmail.com>
 */
class Lite extends Driver
{
    protected $options = [
        'prefix' => '',
        'path'   => '',
        'expire' => 0, // 等于 10*365*24*3600（10年）
    ];

    /**
     * 构造函数
     * @access public
     * @param array $options
     */
    public function __construct($options = [])
    {
        //初始化配置
        if (!empty($options))$this->options = array_merge($this->options, $options);
        //初始化路径
        if (substr($this->options['path'], -1) != SP) $this->options['path'] .= SP;

    }

    /**
     * 取得变量的存储文件名
     * @access protected
     * @param string $name 缓存变量名
     * @return string
     */
    protected function cacheKey($name)
    {
        return $this->options['path'] . $this->options['prefix'] . md5($name) . '.php';
    }

    /**
     * 判断缓存是否存在
     * @access public
     * @param string $name 缓存变量名
     * @return mixed
     */
    public function exist($name)
    {
        return $this->get($name) ? true : false;
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @param string $callable 回调函数，自定义过期方式
     * @param mixed  $default 默认值
     * @return mixed
     */
    public function get($name, $default = false, $callable = '')
    {
        $filename = $this->cacheKey($name);
        if (is_file($filename)) {
            // 判断是否过期
            if (!$this->isExpire($filename, '', $callable)) {
                // 清除已经过期的文件
                unlink($filename);
                return $default;
            }
            return include $filename;
        } else {
            return $default;
        }
    }

    /**
     * 检查缓存是否过期
     * @param string $filename 缓存文件名
     * @param int $expire  过期时间
     * @param string|array $callable 用户自定义回调函数，自定义过期判断方式; 为数组时调用类中方法
     * @return bool true 未过期 false 过期
     */
    protected function isExpire($filename, $expire, $callable)
    {
        if(is_callable($callable)){
            $status = call_user_func($callable, $filename);
            return $status?true:false;
        }
        if($_SERVER['REQUEST_TIME'] > filemtime($filename)){
            return false;
        }else{
            return true;
        }
    }

    /**
     * 写入缓存
     * @access   public
     * @param string    $name  缓存变量名
     * @param mixed     $value 存储数据
     * @param int       $expire 有效时间 0为永久
     * @return bool
     */
    public function set($name, $value, $expire = null)
    {
        //过期时间
        if (is_null($expire)) $expire = $this->options['expire'];
        // 模拟永久
        if ($expire === 0) $expire = 10 * 365 * 24 * 3600;
        //缓存文件名
        $filename = $this->cacheKey($name);
        //标签存在，且同名缓存不存在
        if ($this->tag && !is_file($filename))  $first = true;
        //缓存写成一个PHP文件
        $ret = file_put_contents($filename, ("<?php return " . var_export($value, true) . ";"));
        // 通过设置修改时间实现有效期
        if ($ret) {
            isset($first) && $this->setTagItem($filename);
            //touch() 函数设置指定文件的访问和修改时间
            touch($filename, $_SERVER['REQUEST_TIME'] + $expire);
        }
        return $ret;
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
        if ($this->exist($name)) {
            $value = $this->get($name) + $step;
        } else {
            $value = $step;
        }
        return $this->set($name, $value, 0) ? $value : false;
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
        if ($this->exist($name)) {
            $value = $this->get($name) - $step;
        } else {
            $value = $step;
        }
        return $this->set($name, $value, 0) ? $value : false;
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    public function remove($name)
    {
        return unlink($this->cacheKey($name));
    }

    /**
     * 清除缓存
     * @access   public
     * @param string $tag 标签名
     * @return bool
     */
    public function clear($tag = null)
    {
        if ($tag) {
            // 指定标签清除
            $keys = $this->getTagItem($tag);
            foreach ($keys as $key) {
                unlink($key);
            }
            $this->remove('tag_' . md5($tag));
            return true;
        }
        array_map("unlink", glob($this->options['path'] . ($this->options['prefix'] ? $this->options['prefix'] . SP : '') . '*.php'));
    }
}
