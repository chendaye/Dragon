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
use Core\Lib\Registry\ApplicationRegistry;
use Core\Lib\Exception\DragonException;


/**
 * 该类类似于一个Client, 通过Db::table(xxx),来进行链式操作
 * Class Db
 * @package Core\Lib
 */
class Db
{
    static private $instrance = [];    //数据库连接实例
    static public $queryTimes = 0;      //查询次数
    static public $executeTimes = 0;    //执行次数
    static public function connect($config = [], $link = false)
    {
        if($link === false){
            $link = md5(serialize($config));    //把配置信息序列化后，MD5加密，生成一个唯一标识
        }
        if($link === true || !isset(self::$instrance[$link])){
            $options = self::parseConfig($config);
        }
        DragonException::error(!empty($options['DB_TYPE']),"未定义数据库类型!");
        return null;
    }

    /**
     * 获取数据库配置数组
     * @param $config mixed 配置参数
     * @return mixed|null|void
     * @throws DragonException
     */
    static public function parseConfig($config)
    {
        if(empty($config)){
            $config = ApplicationRegistry::getOptions();
        }
        DragonException::error(!empty($config),"没有找到到数据库配置！");
        if(is_string($config)){
           return self::parseDsn($config);
        }elseif (is_array($config)){
            return $config;
        }
        throw new DragonException("数据库配置{$config} 格式有误！");
    }

    /**
     * 解析DSN
     * 格式： mysql://username:passwd@localhost:3306/DbName?param1=val1&param2=val2#utf8
     * @param $config
     * @return array
     */
    static public function parseDsn($config)
    {
        $configure = parse_url($config);    //解析DSN
        if(!$configure){
            return [];
        }
        $dsn = [
            'DB_TYPE'     => $configure['scheme'],
            'DB_PWD' => isset($configure['pass']) ? $configure['pass'] : '',
            'DB_HOST' => isset($configure['host']) ? $configure['host'] : '',
            'DB_PORT' => isset($configure['port']) ? $configure['port'] : '',
            'DB_NAME' => !empty($configure['path']) ? ltrim($configure['path'], '/') : '',
            'DB_CHARSET'  => isset($configure['fragment']) ? $configure['fragment'] : 'utf8',
        ];
        //解析查询字符串,给$dsn['param']
        if($configure['query']){
            parse_str($configure['query'], $dsn['DB_PARAM']);
        }else{
            $dsn['DB_PARAM'] = [];
        }
        return $dsn;
    }

    /**
     * 当调用不存在的静态方法时，此函数被自动调用
     * @param $method   string  被调用的方法名
     * @param $params   mixed  被调用的方法参数
     * @return mixed    mixed  调用指定方法的结果
     */
    static public function __callStatic($method, $params)
    {
        // TODO: 调用类  self::connect() 里面的  $method 方法， $params 是方法的参数
        return call_user_func_array([self::connect(), $method], $params);

    }
}
?>