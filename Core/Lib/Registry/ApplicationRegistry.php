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

namespace Core\Lib\Registry;
use Core\Lib\DragonException;


/**
 * 应用程序级别的注册表
 * 将内容序列化后保存在文件系统中；获取的时候优先取文件系统的值
 * Class ApplicationRegistry
 * @package Core\Lib\Registry
 */
class ApplicationRegistry extends Registry  {
    private static $instance;
    private $freezedir = '';    //配置文件路径
    private $value = [];    //系统值
    private $mtime = [];    //文件修改时间

    /**
     * 单例构造函数
     * ApplicationRegistry constructor.
     */
    private function __construct(){
        $this->freezedir = APP;
    }

    /**
     * 实例化单例
     * @return ApplicationRegistry
     */
    public static function instance(){
        if(!isset(self::$instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 从缓存文件系统中获取配置信息，不是直接读取配置文件
     * @param string $key 文件名同时也是键值
     * @return mixed|null
     */
    protected function get($key){
        $path = $this->freezedir.$key.'.php';  //文件路径
        //todo:文件存在
        if(file_exists($path)){
            clearstatcache();
            $mtime = filemtime($path);  //上次修改时间时间
            if(!isset($this->mtime[$key])){     //$key 的创建时间不存在就为0
                $this->mtime[$key] = 0;
            }
            //todo:文件被修改过,最近的修改时间大于上一次修改时间
            if($mtime > $this->mtime[$key]){
                $data = file_get_contents($path);   //获取文件内容
                //最新一次的修改时间存入数组
                $this->mtime[$key] = $mtime;
                return $this->value[$key] = unserialize($data);    //返回文件内容，文件内容储存在数组中并返回
            }
        }
        //todo:文件不存在，取数组里面的值
        if(isset($this->value[$key])){     //值存在
            return $this->value[$key]; //返回值
        }
        return null;
    }

    /**
     * 将读取的配置信息，序列化后存储到文件系统中
     * 这是缓存，获取配置是，依次寻找序列化的文件，静态数组，如果都为空就重新读取配置
     * @param string $key 文件名
     * @param mixed $val  文件值
     * @return mixed|null
     */
    protected function set($key, $val)
    {
        $this->value[$key] = $val;  //把值存入静态数组
        $path = $this->freezedir.$key.'.php';  //文件路径
        file_put_contents($path, serialize($val));  //把值序列化后写入文件
        $this->mtime[$key] = time();  //记录写入时间
    }

    /**
     * 从文件系统中获取DSN
     * @return mixed|null
     */
    static public  function getDSN(){
        return self::instance()->get('dsn');
    }

    /**
     * 从文件系统中获取数据库配置选项
     * @return mixed|null
     */
    static public function getOptions(){
        return self::instance()->get('options');
    }

    /**
     * 设置DSN
     * @param $dsn
     * @return mixed|null
     */
    static public  function setDSN($dsn){
        return self::instance()->set('dsn', $dsn);
    }

    /**
     * 在应用注册表中注册配置选项数组
     * @param $options
     * @return mixed|null
     */
    static public function setOptions($options){
        return self::instance()->set('options',$options);
    }
}
?>