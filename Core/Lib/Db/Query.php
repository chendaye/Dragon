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

namespace Core\Lib\Db;
use Core\Lib\Db;

/**
 * SQL操作类，所有关于SQL的链式操作都封装在这里，具体的组装在Builder中完成
 * Class Query
 * @package Core\Lib\Db
 */
class Query
{
    protected $connection;  //数据库Connection对象实例
    protected $builder;     //数据库驱动类型
    protected $model;       //当前模型类model的名称
    protected $table = '';       //当前数据表名称，含前缀
    protected $name = '';        //当前数据表名称，不含前缀
    protected $pk;          //当前数据表主键
    protected $prefix = '';      //当前数据表前缀
    protected $options = [];      //查询参数
    protected $bind = [];       //参数绑定
    static protected $info = [];    //数据表信息

    public function __construct(Connection $connection = null, $model = '')
    {
        $this->connection = $connection?:Db::connect([], true); //数据库连接实例,$connection为空就重新获取

    }
}
?>