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
use Core\Lib\Conf;
use Core\Lib\DragonException;

/**
 *读取系统配置文件内容，并在注册表中注册
 * 在读取配置信息之前会先检查注册表中是否已经注册（缓存是否存在），减小开关文件的开销
 * 通俗的说，就是把系统配置文件的内容，存到注册表里
 * Class ApplicationHelper
 * @package Core\Lib
 */
class ApplicationHelper extends RegistryHelper  {
    private static $instance;
    private $freezedir = APP;    //配置文件路径

    /**
     * 单例
     * ApplicationHelper constructor.
     */
    private function __construct(){}

    /**
     * 实例化
     * @return ApplicationHelper
     */
    public static function instance(){
        if(!isset(self::$instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 初始化程序所需的必要数据（从缓存文件中），如果没获取到，就重新读取配置文件，并生成
     * 以此减小系统花销
     * 先检查注册表中数据是否已经注册，若没有，则调用方法在注册表中注册
     */
    public function init(){
        //判断注册表中是否注册过DSN
        $dsn = ApplicationRegistry::getDSN();
        //判断是否注册过选项
        $options = ApplicationRegistry::getOptions();
        //若没注册过
        if(is_null($dsn) || is_null($options)){
            $this->registryOption('database');    //调用方法注册
        }
    }

    /**
     * 读取系统配置文件，可以使xml json
     * 并在注册表中注册获取的信息（注册时会序列化的生成缓存文件）
     * @param array|string $file
     * @return mixed
     */
    protected function registryOption($file){
        $options = Conf::get($file);
        if(is_array($options)){
            $dsn =  $options['DB_TYPE'].":dbname=".$options['DB_NAME'].";host=".$options['DB_HOST'];
        }else{
            $dsn = $options;
        }

        ApplicationRegistry::setDSN($dsn);  //注册dsn
        ApplicationRegistry::setOptions($options);
    }
}
?>