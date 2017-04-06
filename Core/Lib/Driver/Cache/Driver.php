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
 * 缓存基础类
 */
abstract class Driver
{
    protected $handler = null;
    protected $options = [];
    protected $tag;

    /**
     * 判断缓存是否存在
     * @access public
     * @param string $name 缓存变量名
     * @return bool
     */
    abstract public function exist($name);

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed  $default 默认值
     * @return mixed
     */
    abstract public function get($name, $default = false);

    /**
     * 写入缓存
     * @access public
     * @param string    $name 缓存变量名
     * @param mixed     $value  存储数据
     * @param int       $expire  有效时间 0为永久
     * @return boolean
     */
    abstract public function set($name, $value, $expire = null);

    /**
     * 自增缓存（针对数值缓存）
     * @access public
     * @param string    $name 缓存变量名
     * @param int       $step 步长
     * @return false|int
     */
    abstract public function inc($name, $step = 1);

    /**
     * 自减缓存（针对数值缓存）
     * @access public
     * @param string    $name 缓存变量名
     * @param int       $step 步长
     * @return false|int
     */
    abstract public function dec($name, $step = 1);

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    abstract public function remove($name);

    /**
     * 清除缓存
     * @access public
     * @param string $tag 标签名
     * @return boolean
     */
    abstract public function clear($tag = null);

    /**
     * 缓存过期检测
     * @param string $filename  缓存文件名（路径）
     * @param int $expire  过期时间
     * @param string|array $callable  用户自定义回调函数，自定义过期判断方式; 为数组时调用类中方法
     * @return mixed
     */
    protected function isExpire($filename, $expire, $callable)
    {
        if(is_callable($callable)){
            $status = call_user_func($callable, $filename);
            return $status?true:false;
        }
        if($_SERVER['REQUEST_TIME'] > filemtime($filename) + $expire && $expire != 0){
            return false;
        }else{
            return true;
        }
    }

    /**
     * 获取实际的缓存标识
     * @access public
     * @param string $name 缓存名
     * @return string
     */
    protected function cacheKey($name)
    {
        return $this->options['prefix'] . $name;
    }

    /**
     * 读取缓存并删除
     * @access public
     * @param string $name 缓存变量名
     * @return mixed
     */
    public function obtain($name)
    {
        $result = $this->get($name, false);
        if ($result) {
            $this->remove($name);
            return $result;
        } else {
            return null;
        }
    }

    /**
     * 如果不存在则写入缓存
     * @access public
     * @param string    $name 缓存变量名
     * @param mixed     $value  存储数据
     * @param int       $expire  有效时间 0为永久
     * @return mixed
     */
    public function write($name, $value, $expire = null)
    {
        if (!$this->exist($name)) {
            //Closure类,一个内部的final类,是用来表示匿名函数的，所有的匿名函数都是Closure类的实例。
            if ($value instanceof \Closure) $value = call_user_func($value);
            $this->set($name, $value, $expire);
        } else {
            $value = $this->get($name);
        }
        return $value;
    }

    /**
     * 缓存标签，把缓存文件名，追加到某个标签下
     * @access public
     * @param string        $name 标签名
     * @param string|array  $keys 缓存标识
     * @param bool          $cover 是否覆盖
     * @return $this
     */
    public function tag($name, $keys = null, $cover = false)
    {
        if (is_null($keys)) {
            $this->tag = $name;
        } else {
            //创建
            $key = 'tag_' . md5($name);
            //解析标签层次，[filename1,filename2,......]
            if (is_string($keys)) $keys = explode(',', $keys);
            //array_map()将用户自定义函数作用到数组中的每个值上，并返回用户自定义函数作用后的带有新值的数组
            $keys = array_map([$this, 'cacheKey'], $keys);  //为每一个文件名创建具体路径
            if ($cover) {
                //覆盖，该标签下的缓存路径
                $value = $keys;
            } else {
                //追加，该标签下的缓存路径
                $value = array_unique(array_merge($this->getTagItem($name), $keys));
            }
            //缓存文件名拼成字符串，filename1,filename2,......
            $this->set($key, implode(',', $value));
        }
        return $this;
    }

    /**
     * 更新标签
     * @access public
     * @param string $name 缓存文件名
     * @return void
     */
    protected function setTagItem($name)
    {
        if ($this->tag) {
            //标签键名，所有缓存文件名字符串集合，以逗号分隔
            $key       = 'tag_' . md5($this->tag);
            //清除标签
            $this->tag = null;
            if ($this->exist($key)) {
                //追加标签
                $value = $this->get($key);
                $value .= ',' . $name;
            } else {
                $value = $name;
            }
            //标签tag=>filename1,filename2,......
            $this->set($key, $value);
        }
    }

    /**
     * 获取标签包含的缓存标识
     * @access public
     * @param string $tag 缓存标签
     * @return array
     */
    protected function getTagItem($tag)
    {
        $key   = 'tag_' . md5($tag);
        $value = $this->get($key);
        //tag下面的所有缓存文件名
        if ($value)return explode(',', $value);
        return [];
    }

    /**
     * 返回句柄对象，可执行其它高级方法
     * @access public
     * @return object
     */
    public function handler()
    {
        return $this->handler;
    }
}
