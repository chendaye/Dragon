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
 * Sqlite缓存驱动
 * Class Sqlite
 * @package Core\Lib\Driver\Cache
 */
class Sqlite extends Driver
{
    protected $options = [
        'db'         => ':memory:',
        'table'      => 'sharedmemory',
        'prefix'     => '',
        'expire'     => 0,
        'persistent' => false,
    ];

    /**
     * 架构函数
     * @param array $options 缓存参数
     * @throws \BadFunctionCallException
     * @access public
     */
    public function __construct($options = [])
    {
        //检查扩展
        if (!extension_loaded('sqlite')) throw new \BadFunctionCallException('没开启Sqlite扩展');
        //初始化配置项
        if (!empty($options))$this->options = array_merge($this->options, $options);
        //长连接
        $func          = $this->options['persistent'] ? 'sqlite_popen' : 'sqlite_open';
        //连接
        $this->handler = $func($this->options['db']);
    }

    /**
     * 获取实际的缓存标识
     * @access public
     * @param string $name 缓存名
     * @return string
     */
    protected function cacheKey($name)
    {
        return $this->options['prefix'] . sqlite_escape_string($name);
    }

    /**
     * 判断缓存
     * @access public
     * @param string $name 缓存变量名
     * @return bool
     */
    public function exist($name)
    {
        $name   = $this->cacheKey($name);
        //查询数据库缓存
        $sql    = 'SELECT value FROM ' . $this->options['table'] . ' WHERE var=\'' . $name . '\' AND (expire=0 OR expire >' . $_SERVER['REQUEST_TIME'] . ') LIMIT 1';
        $result = sqlite_query($this->handler, $sql);
        return sqlite_num_rows($result);
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
        $name   = $this->cacheKey($name);
        //查询数据库缓存
        $sql    = 'SELECT value FROM ' . $this->options['table'] . ' WHERE var=\'' . $name . '\' AND (expire=0 OR expire >' . $_SERVER['REQUEST_TIME'] . ') LIMIT 1';
        $result = sqlite_query($this->handler, $sql);
        //缓存不为空，解压，反序列化返回
        if (sqlite_num_rows($result)) {
            $content = sqlite_fetch_single($result);
            //解压数据
            if (function_exists('gzcompress')) $content = gzuncompress($content);
            //反序列化并返回
            return unserialize($content);
        }
        //缓存不存在，返回默认值
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
        $name  = $this->cacheKey($name);
        //保存缓存
        $value = sqlite_escape_string(serialize($value));
        //过期时间
        if (is_null($expire)) $expire = $this->options['expire'];
        //缓存有效期为0表示永久缓存
        $expire = ($expire == 0) ? 0 : ($_SERVER['REQUEST_TIME'] + $expire);
        //数据压缩
        if (function_exists('gzcompress'))  $value = gzcompress($value, 3);
        //标签
        if ($this->tag) {
            $tag       = $this->tag;
            $this->tag = null;
        } else {
            $tag = '';
        }
        //存储数据的SQL
        $sql = 'REPLACE INTO ' . $this->options['table'] . ' (var, value, expire, tag) VALUES (\'' . $name . '\', \'' . $value . '\', \'' . $expire . '\', \'' . $tag . '\')';
        //存储数据
        if (sqlite_query($this->handler, $sql))return true;
        //失败返回FALSE
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
        $name = $this->cacheKey($name);
        //从数据库中删除缓存
        $sql  = 'DELETE FROM ' . $this->options['table'] . ' WHERE var=\'' . $name . '\'';
        sqlite_query($this->handler, $sql);
        return true;
    }

    /**
     * 清除缓存
     * @access public
     * @param string $tag 标签名
     * @return boolean
     */
    public function clear($tag = null)
    {
        //删除指定标签的缓存
        if ($tag) {
            $name = sqlite_escape_string($tag);
            $sql  = 'DELETE FROM ' . $this->options['table'] . ' WHERE tag=\'' . $name . '\'';
            sqlite_query($this->handler, $sql);
            return true;
        }
        //删除所有缓存
        $sql = 'DELETE FROM ' . $this->options['table'];
        sqlite_query($this->handler, $sql);
        return true;
    }
}
