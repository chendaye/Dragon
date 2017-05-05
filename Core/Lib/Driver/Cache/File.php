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
 * 文件类型缓存驱动
 */
class File extends Driver
{
    protected $options = [
        'expire'        => 0,   //缓存过期时间，0：不过期
        'subdirectory'  => false,   //启用子目录
        'prefix'        => '',  //缓存前级目录
        'path'          => CACHE,   //缓存路径
        'compress' => false,   //是否压缩文件
    ];

    /**
     * 构造函数
     * @param array $options
     */
    public function __construct($options = [])
    {
        //初始参数
        if (!empty($options)) $this->options = array_merge($this->options, $options);
        //缓存路径
        if (substr($this->options['path'], -1) != SP)  $this->options['path'] .= SP;
        $this->init();
    }

    /**
     * 初始化检查
     * @access private
     * @return boolean
     */
    private function init()
    {
        // 创建项目缓存目录
        if (!is_dir($this->options['path'])) {
            if (mkdir($this->options['path'], 0755, true)) return true;
        }
        return false;
    }

    /**
     * 取得变量的存储文件名
     * @param string $name
     * @return string
     */
    protected function cacheKey($name)
    {
        //名称加密
        $name = md5($name);
        // 使用子目录
        if ($this->options['subdirectory'])  $name = substr($name, 0, 2) . SP . substr($name, 2);
       //名称前缀，前缀为一级目录
        if ($this->options['prefix'])$name = $this->options['prefix'] . SP . $name;
        //存储文件名
        $filename = $this->options['path'] . $name . '.php';
        //目录部分
        $dir      = dirname($filename);
        //创建缓存目录
        if (!is_dir($dir))mkdir($dir, 0755, true);
        return $filename;
    }

    /**
     * 判断缓存是否存在
     * @access public
     * @param string $name 缓存变量名
     * @return bool
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
        //缓存文件路径
        $filename = $this->cacheKey($name);
        //不存在，返回默认值
        if (!is_file($filename)) return $default;
        //读取文件内容
        $content = file_get_contents($filename);
        if ($content !== false) {
            //缓存有效时间
            $expire = (int) substr($content, 8, 12);
            //当前请求时间大于缓存创建时间+有效时间，缓存过期
            if (!$this->isExpire($filename, $expire, $callable)) {
                //缓存过期删除缓存文件
                $this->unlink($filename);
                return $default;
            }
            //缓存有效内容
            $content = substr($content, 20, -3);
            if ($this->options['compress'] && function_exists('gzcompress')) {
                //启用数据压缩，解压，与gzcompress()对应
                $content = gzuncompress($content);
            }
            //反序列化
            $content = unserialize($content);
            return $content;
        } else {
            return $default;
        }
    }


    /**
     * 写入缓存
     * @access public
     * @param string    $name 缓存变量名
     * @param mixed     $value  存储数据
     * @param int       $expire  有效时间 0为永久
     * @return boolean
     */
    public function set($name, $value, $expire = null)
    {
        //缓存有效时间
        if (is_null($expire)) $expire = $this->options['expire'];
        //获取缓存文件名
        $filename = $this->cacheKey($name);
        //缓存标签存在，且不存在同名缓存文件
        if ($this->tag && !is_file($filename))  $first = true;
        //序列化缓存内容
        $data = serialize($value);
        //数据压缩
        if ($this->options['compress'] && function_exists('gzcompress')) {
            $data = gzcompress($data, 3);
        }
        //拼接上有效时间
        $data   = "<?php\n//" . sprintf('%012d', $expire) . $data . "\n?>";
        //缓存写入文件
        $result = file_put_contents($filename, $data);
        if ($result) {
            //把缓存文件路径追加到当前标签tag下
            isset($first) && $this->setTagItem($filename);
            //clearstatcache() 函数清除文件状态缓存,文件可能会被删除修5 改
            clearstatcache();
            return true;
        } else {
            return false;
        }
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
        return $this->unlink($this->cacheKey($name));
    }

    /**
     * 清除缓存
     * @access public
     * @param string $tag 标签名
     * @return boolean
     */
    public function clear($tag = null)
    {
        if ($tag) {
            // 指定标签清除,标签下所有缓存文件
            $keys = $this->getTagItem($tag);
            //全部清除
            foreach ($keys as $key) {
                $this->unlink($key);
            }
            //删除标签缓存
            $this->remove('tag_' . md5($tag));
            return true;
        }
        //glob() 函数返回匹配指定模式的文件名或目录，获取当前缓存目录，当前前缀下的文件目录名
        $files = (array) glob($this->options['path'] . ($this->options['prefix'] ? $this->options['prefix'] . SP : '') . '*');
        foreach ($files as $path) {
            if (is_dir($path)) {
                //如果是目录，删除所有后缀为.php 为文件
                array_map('unlink', glob($path . '/*.php'));
            } else {
                //如果是文件
                unlink($path);
            }
        }
        return true;
    }

    /**
     * 文件存在，删除
     * @param $path
     * @return bool
     * @author chendaye <chendaye666@gmail.com>
     * @return boolean
     */
    private function unlink($path)
    {
        return is_file($path) && unlink($path);
    }

}
