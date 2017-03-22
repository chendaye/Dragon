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
 *命令工厂
 * Class CommandFactory
 * @package Core\Lib
 */
class CommandFactory{
    static private $cmd;

    /**
     * 命令工厂
     * @param $command
     * @return mixed
     */
    static public function getCommand($command){
        //命令只能有字符串组成
        DragonException::error(preg_match('/^\w+$/', $command),"命令{$command}包含非法字符！");
        //拼接类名
        $classname = '\\Command\\'.$command;
        DragonException::error(class_exists($classname),"{$classname}不存在！");
        DragonException::insCheck($classname,'\Core\Lib\Command');
        $ref = new \ReflectionClass($classname);
        //获取实例
        return $ref->newInstance();
    }
}
?>