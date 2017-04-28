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

class Cache
{
    //操作实例
    static protected $instance = [];
    //操作句柄
    static protected $handler;
    //读的次数
    static public $readTimes = 0;
    //写的次数
    static public $writeTimes = 0;

    /**
     * 初始化缓存驱动
     * @param array $options
     */
    static public function init(array $options = [])
    {
        if(is_null(self::$handler)){
            $driver = Conf::get('cache.type');
            if(!empty($options)){
                //原始配置
                self::connect($options);
            }elseif ($driver == 'default'){
                //默认配置
                self::connect(Conf::get('cache.file'));
            }else{
                //主配置
                self::connect(Conf::get('cache.'.$driver));
            }
        }
    }

    /**
     * 连接缓存驱动，获取操作实例
     * @param array $options 连接参数
     * @param bool|string $refresh 缓存连接标识  true 强制重新连接
     * @return mixed
     */
    static public function connect($options = [], $refresh = false)
    {
        //驱动类型
        $type = (empty($options['type']))?'File':$options['type'];
        //驱动类
        $driver = (strpos($type, '\\') !== false) ? $type : '\\Core\\Lib\\Driver\\Cache\\' . ucwords(strtolower($type));
        //获取驱动缓存实例
        if($refresh === true){
            //记录缓存驱动信息
            Log::log('[CACHE DRIVER INIT]:'.$type, 'info');
            return new $driver($options);
        }else{
            $name = md5(serialize($refresh));
            if(!isset(self::$instance[$name])) {
                //记录缓存驱动信息
                Log::log('[CACHE DRIVER INIT]:'.$type, 'info');
                self::$instance[$name] = new $driver($options);
            }
            self::$handler = self::$instance[$name];
            return self::$handler;
        }
    }

    /**
     * 切换缓存驱动
     * @param string $name 驱动名
     * @return mixed
     */
    static public function switchTo($name)
    {
        //连接指定的驱动
        self::connect(Conf::get('cache.'.$name), $name);
        //返回连接实例
        return self::$handler;
    }

    /**
     * 检查缓存是否存在
     * @param string $name 缓存名
     * @return mixed
     */
    static public function exist($name)
    {
        self::init();
        self::$readTimes++;
        return self::$handler->exist($name);
    }

    /**
     * 获取缓存
     * @param string $name 缓存名
     * @param null $default 默认值
     * @return mixed
     */
    static public function get($name, $default = null)
    {
        self::init();
        self::$readTimes++;
        return self::$handler->get($name, $default);
    }

    /**
     * 缓存数据
     * @param string $name 缓存名
     * @param mixed $value  缓存值
     * @param int $expire  过期时间
     * @return mixed
     */
    static public function set($name, $value, $expire = null)
    {
        self::init();
        self::$writeTimes++;
        return self::$handler->set($name, $value, $expire);
    }

    /**
     * 自增缓存（针对数值缓存）
     * @param string $name 缓存名
     * @param int $step 步长
     */
    static public function inc($name, $step = 1)
    {
        self::init();
        self::$writeTimes++;
        self::$handler->inc($name, $step);
    }

    /**
     * 自减缓存（针对数值）
     * @param string $name 缓存名
     * @param int $step 步长
     */
    static public function dec($name, $step = 1)
    {
        self::init();
        self::$writeTimes++;
        self::$handler->dec($name, $step);
    }

    /**
     * 删除缓存
     * @param string $name 缓存名
     */
    static public function remove($name)
    {
        self::init();
        self::$writeTimes++;
        self::$handler->remove($name);
    }

    /**
     * 清除缓存
     * @param null|string $tag 指定标签
     */
    static public function clear($tag = null)
    {
        self::init();
        self::$writeTimes++;
        self::$handler->clear($tag);
    }

    /**
     * 缓存标签
     * @param string $name 标签名
     * @param string|array  $keys 缓存标识
     * @param bool $cover
     * @return mixed
     */
    static public function tag($name, $keys, $cover = false)
    {
        self::init();
        return self::$handler->tag($name, $keys, $cover);
    }

    static public function test(){
        return self::$handler;
    }
}

?>