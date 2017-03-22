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

class Dbp{
    protected static $config = array();   //连接参数，配置信息
    protected static $link = null;    //连接标志符
    protected static $pconnect = false;   //是否开启长连接
    protected static $dbVersion = null;   //数据库版本
    protected static $connected = false;  //是否连接成功
    protected static $PDOStatement = null;    //保存PDOStatement 对象；结果集
    protected static $queryStr = null;    //保存最后的操作
    protected static $error = null;   //错误信息
    protected static $lastInsertId = null;    //上次操作产生的AUTO_INCREMENT
    protected static $numRows = 0;  //上次操作影响的记录条数

    /**
     * pdo类构造函数，连接数据库，初始化配置
     * PdoMysql constructor.
     * @param string $dbConfig
     */
    public function __construct($dbConfig = '')
    {
        if(!class_exists("PDO")){
            self::throw_exception("未开启PDO扩展！");   //把抛出错误封装成一个内置函数
        }
        //从注册表获取DSN
        $dsn_registry = ApplicationRegistry::instance();
        $dsn = $dsn_registry::getDSN();
        $dsn = Conf::get('db','DB_TYPE').":dbname=".Conf::get('db','DB_NAME').";host=".Conf::get('db','DB_HOST');
        //初始化配置
        if(empty($dbConfig)){
            $dbConfig = array(
                'hostname' => Conf::get('db','DB_HOST'),
                'username' => Conf::get('db','DB_USER'),
                'dbpassword' => Conf::get('db','DB_PWD'),
                'databases' => Conf::get('db','DB_NAME'),
                'dbtype' => Conf::get('db','DB_TYPE'),
                //'dsn'=>Conf::get('DB_TYPE', 'database').":dbname=".Conf::get('DB_NAME', 'database').";host=".Conf::get('DB_HOST', 'database')  //第一个是冒号，第二个是分号
                'dsn' => $dsn
            );
        }
        if(empty($dbConfig['hostname'])) self::throw_exception("数据库配置缺失！");   //一个条件语句就没必要括起来了
        self::$config = $dbConfig;
        //todo:初始参数
        if(empty(self::$config['params']))self::$config['params']=array();
        //todo:如果没没链接就连接数据库
        if(!isset(self::$link)){
            $config = self::$config;
            //如果设置长连接
            if(self::$pconnect){
                $config['params'][constant("PDO_ATTR_PERSISTENT")] = true; //把状态值加入配置数组
            }
            //连接数据库
            try{
                self::$link = new \PDO($config['dsn'], $config['username'], $config['dbpassword'], $config['params']);
            }catch (PDOException $e){
                self::throw_exception($e->getMessage());
            }
            if(!self::$link){
                self::throw_exception("连接失败！");
                return false;
            }
            self::$link->exec("SET NAME".Conf::get('db','DB_CHARSET'));
            self::$dbVersion = self::$link->getAttribute(constant("PDO::ATTR_SERVER_VERSION")); //数据库版本
            self::$connected = true;
            unset($config); //销毁临时配置数组
        }
    }

    /**
     * 获取所有记录
     * @param null $sql
     * @return mixed
     */
    public static function getAll($sql = null){
        //todo:sql不为空，先执行sql；得到结果集对象，并保存在静态属性中
        if($sql != null){
            self::query($sql);
        }
        $result = self::$PDOStatement->fetchAll(constant("PDO::FETCH_ASSOC"));
        return $result;
    }

    /**
     * 获取单条记录
     * @param null $sql
     * @return mixed
     */
    public static function getRow($sql = null){
        if($sql != null){
            self::query($sql);
        }
        $result = self::$PDOStatement->fetch(constant("PDO::FETCH_ASSOC"));
        return $result;
    }

    /**
     * 通过主键查找记录
     * @param static $tabName   表名
     * @param int $priId    主键
     * @param string $fields    字段
     * @return mixed
     */
    public static function findById($tabName,$priId,$fields='*'){
        $sql='SELECT %s FROM %s WHERE id=%d';
        //sprintf()把百分号（%）符号替换成一个作为参数进行传递的变量
        return self::getRow(sprintf($sql,self::parseFields($fields),$tabName,$priId));
    }

    /**
     * 简单查找
     * @param string $tables    表名
     * @param string $fields    字段
     * @param null $where 查询条件
     * @param null $group   分组
     * @param null $having  having子句
     * @param null $order   排序
     * @param null $limit  limit子句
     * @return mixed
     */
    public static function find($tables,$where=null,$fields='*',$group=null,$having=null,$order=null,$limit=null){
        $sql='SELECT '.self::parseFields($fields).' FROM '.$tables
            .self::parseWhere($where)
            .self::parseGroup($group)
            .self::parseHaving($having)
            .self::parseOrder($order)
            .self::parseLimit($limit);
        $dataAll=self::getAll($sql);
        return count($dataAll)==1?$dataAll[0]:$dataAll;
    }

    /**
     * 添加记录
     * @param string $table 表名
     * @param array|string $data    添加的字段
     * @return bool
     */
    public static function add($data,$table){
        $keys=array_keys($data);
        array_walk($keys,array('PdoMySQL','addSpecialChar'));
        $fieldsStr=join(',',$keys);
        $values="'".join("','",array_values($data))."'";
        $sql="INSERT {$table}({$fieldsStr}) VALUES({$values})";
        //echo $sql;
        return self::execute($sql);
    }

    /**
     * 更新记录
     * @param $data
     * @param $table
     * @param null $where
     * @param null $order
     * @param int $limit
     * @return bool|unknown
     */
    public static function update($data,$table,$where=null,$order=null,$limit=0){
        $sets = '';
        foreach($data as $key=>$val){
            $sets.=$key."='".$val."',";
        }
        //echo $sets;
        $sets=rtrim($sets,',');
        $sql="UPDATE {$table} SET {$sets} ".self::parseWhere($where).self::parseOrder($order).self::parseLimit($limit);
        return self::execute($sql);
    }

    /**
     * 删除记录
     * @param $table
     * @param null $where
     * @param null $order
     * @param int $limit
     * @return bool|unknown
     */
    public static function delete($table,$where=null,$order=null,$limit=0){
        $sql="DELETE FROM {$table} ".self::parseWhere($where).self::parseOrder($order).self::parseLimit($limit);
        return self::execute($sql);
    }

    /**
     * 得到最后执行的SQL语句
     * @return bool|null
     */
    public static function getLastSql(){
        $link=self::$link;
        if(!$link)return false;
        return self::$queryStr;
    }

    /**
     * 得到最后插入的id
     * @return bool|null
     */
    public static function getLastInsertId(){
        $link=self::$link;
        if(!$link)return false;
        return self::$lastInsertId;
    }
    /**
     * 获取数据库的版本
     * @return mixed|null
     */
    public static function getDbVerion(){
        $link=self::$link;
        if(!$link)return false;
        return self::$dbVersion;
    }

    /**
     * 获取数据表
     * @return array
     */
    public static function showTables(){
        $tables=array();
        if(self::query("SHOW TABLES")){
            $result=self::getAll();
            foreach($result as $key=>$val){
                $tables[$key]=current($val);
            }
        }
        return $tables;
    }

    /**
     * 解析where条件
     * @param $where
     * @return string
     */
    public static function parseWhere($where){
        $whereStr='';
        if(is_string($where)&&!empty($where)){
            $whereStr=$where;
        }
        return empty($whereStr)?'':' WHERE '.$whereStr;
    }

    /**
     * 解析分组条件
     * @param $group
     * @return string
     */
    public static function parseGroup($group){
        $groupStr='';
        if(is_array($group)){
            $groupStr.=' GROUP BY '.implode(',',$group);
        }elseif(is_string($group)&&!empty($group)){
            $groupStr.=' GROUP BY '.$group;
        }
        return empty($groupStr)?'':$groupStr;
    }

    /**
     * 解析having子句
     * @param $having
     * @return string
     */
    public static function parseHaving($having){
        $havingStr='';
        if(is_string($having)&&!empty($having)){
            $havingStr.=' HAVING '.$having;
        }
        return $havingStr;
    }

    /**
     * 解析order by 子句
     * @param $order
     * @return string
     */
    public static function parseOrder($order){
        $orderStr='';
        if(is_array($order)){
            $orderStr.=' ORDER BY '.join(',',$order);
        }elseif(is_string($order)&&!empty($order)){
            $orderStr.=' ORDER BY '.$order;
        }
        return $orderStr;
    }

    /**
     * 解析limit 子句
     * @param $limit
     * @return string
     */
    public static function parseLimit($limit){
        $limitStr='';
        if(is_array($limit)){
            if(count($limit)>1){
                $limitStr.=' LIMIT '.$limit[0].','.$limit[1];
            }else{
                $limitStr.=' LIMIT '.$limit[0];
            }
        }elseif(is_string($limit)&&!empty($limit)){
            $limitStr.=' LIMIT '.$limit;
        }
        return $limitStr;
    }

    /**
     * 解析字段
     * @param $fields
     * @return string
     */
    public static function parseFields($fields){
        if(is_array($fields)){
            array_walk($fields,array('Dbp','addSpecialChar'));
            $fieldsStr=implode(',',$fields);
        }elseif(is_string($fields)&&!empty($fields)){
            if(strpos($fields,'`')===false){
                $fields=explode(',',$fields);
                array_walk($fields,array('Dbp','addSpecialChar'));
                $fieldsStr=implode(',',$fields);
            }else{
                $fieldsStr=$fields;
            }
        }else{
            $fieldsStr='*';
        }
        return $fieldsStr;
    }

    /**
     * 通过反引号引用字段
     * @param $value
     * @return bool|string
     */
    public static function addSpecialChar(&$value){
        if($value==='*'||strpos($value,'.')!==false||strpos($value,'`')!==false){
            //不用做处理
        }elseif(strpos($value,'`')===false){
            $value='`'.trim($value).'`';
        }
        return $value;
    }

    /**
     * 执行增删改 操作，返回受影响的记录条数
     * @param null $sql
     * @return bool|int
     */
    public static function execute($sql=null){
        $link=self::$link;
        if(!$link) return false;
        self::$queryStr=$sql;
        if(!empty(self::$PDOStatement))self::free();
        $result=$link->exec(self::$queryStr);
        self::haveErrorThrowException();
        if($result){
            self::$lastInsertId=$link->lastInsertId();
            self::$numRows=$result;
            return self::$numRows;
        }else{
            return false;
        }
    }

    /**
     * 释放结果集
     */
    protected static function free(){
        self::$PDOStatement = null;
    }

    /**
     * 执行查询操作，返回结果
     * @param string $sql
     * @return bool
     */
    public static function query($sql=''){
        $link=self::$link;
        if(!$link) return false;
        //判断之前是否有结果集，如果有的话，释放结果集
        if(!empty(self::$PDOStatement))self::free();
        self::$queryStr=$sql;
        self::$PDOStatement=$link->prepare(self::$queryStr);
        $res=self::$PDOStatement->execute();
        self::haveErrorThrowException();
        return $res;
    }

    /**
     * 抛出错误信息
     * @return bool
     */
    public static function haveErrorThrowException(){
        $obj=empty(self::$PDOStatement)?self::$link: self::$PDOStatement;
        $arrError=$obj->errorInfo();
        //print_r($arrError);
        if($arrError[0]!='00000'){
            self::$error='SQLSTATE: '.$arrError[0].' <br/>SQL Error: '.$arrError[2].'<br/>Error SQL:'.self::$queryStr;
            self::throw_exception(self::$error);
            return false;
        }
        if(self::$queryStr==''){
            self::throw_exception('没有执行SQL语句');
            return false;
        }
    }

    /**
     * 自定义错误样式
     * @param $errMsg
     */
    public static function throw_exception($errMsg){
        echo '<div style="width:80%;background-color:#ABCDEF;color:black;font-size:20px;padding:20px 0px;">
				'.$errMsg.'
		</div>';
    }

    /**
     *销毁连接对象，关闭数据库
     */
    public static function close(){
        self::$link = null;
    }
}
?>