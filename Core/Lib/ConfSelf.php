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
/**
 * 配置文件解析类
 * Class Conf
 * @package Dragon\Core\Lib
 */
class Confself{
    static protected $appConf = [];    //存放获取的配置文件
    static protected $comConf = [];

    /**
     * 获取配置项
     * @param $conf
     * @param $confFile
     * @return mixed
     * @throws DragonException
     */
    static public function get($confFile, $conf = null){
        $path = self::exist($confFile); //检查配置文件是否存在
        $com = null;
        $app = null;
        //模块配置文件
        if(empty(self::$appConf[$confFile]) && !is_null($path['apppath'])){
            self::$appConf[$confFile] = require $path['apppath'];
        }
        //支持数组，字符串
        if(!empty(self::$appConf[$confFile])) {
            $app_content = self::$appConf[$confFile];
            if (is_array($app_content) && $conf !== null) {
                $app = $app_content[$conf];
            } else {
                $app = $app_content;
            }
        }
        //公共配置文件
        if(empty(self::$comConf[$confFile]) && !is_null($path['compath'])){
            self::$comConf[$confFile] = require $path['compath'];
        }
        //支持数组字符串
        if(!empty(self::$comConf[$confFile])) {
            $com_content = self::$comConf[$confFile];
            if (is_array($com_content) && $conf !== null) {
                $com = $com_content[$conf];
            } else {
                $com = $com_content;
            }
        }
        //读取配置项
        if(!empty($app)){
            return $app;
        }elseif (!empty($com)){
            return $com;
        }else{
            throw new DragonException("配置项不存在！");
        }
    }

    /**
     * 检查配置文件是否存在
     * @param $file string 配置文件名
     * @return string
     * @throws DragonException
     */
    static private function exist($file){
        $comConfPath = APP.$file.'.php';
        $appConfPath = APP.'Module/'.$file.'.php';
        if (!is_file($comConfPath) && is_file($appConfPath)){
            throw new DragonException("配置文件不存在!");
        }
        return [
            'apppath' => is_file($appConfPath)?$appConfPath:null,
            'compath' => is_file($comConfPath)?$comConfPath:null
        ];
    }
}
?>