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
use Core\Lib\Collection;
use Core\Lib\Db;
use Core\Lib\Debug;
use Core\Lib\DragonException;
use Core\Lib\Log;

/**
 * 连接数据库
 * 封装关于PDO的操作方法
 * Class Connection
 * @package Core\Lib\Db
 */
abstract class Connection
{
    protected $PDOStatement;    //PDO连接实例
    protected $queryStr = '';    //当前SQL语句
    protected $numRows = 0;     //影响的记录条数
    protected $transTimes = 0;  //事务指令条数
    protected $error = '';      //错误信息
    protected $link = [];       //数据库连接ID，支持多个连接
    /**
     * @var  int 当前PDO连接ID
     */
    protected $linkID;
    protected $linkRead;
    protected $linkWrite;

    protected $resultSetType = 'array'; //查询结果类型
    protected $fetchType = \PDO::FETCH_ASSOC;   //查询结果类型
    protected $attrCase = \PDO::CASE_LOWER;     //字段属性大小写
    static protected $event;    //监听回调
    protected $query = [];  //查询对象Model
    /***
     * @var array 数据库连接参数
     */
    protected $config = [
        'type' => '',   //数据库类型
        'hostname' => '',   //服务器地址
        'database' => '',   //数据库名
        'username' => '',   //用户名
        'password' => '',   //密码
        'hostport' => '',   //数据库端口，MySQL是3306
        'dsn' => '',    //数据库连接dsn
        'params' => [], //数据库连接参数
        'charset' => 'utf8',    //字符编码
        'prefix' => '',     //数据表前缀
        'debug' => false,   //数据库调试模式，默认是false
        'deploy' => 0,  //数据库部署方式，0：集中式（单一服务器）；1：分布式（主从服务器）
        'rw_separate' => false, //数据库读写是否分离，分布式部署，主从服务器有效
        'master_num' => 1,  //读写分离后主服务器数量
        'slave_no' => '',   //指定从服务器序号
        'fields_strict' => true,    //严格检查字段是否存在
        'resultset_type' => 'array',    //数据集返回类型
        'auto_timestamp' => false,  //自动写入时间戳
        'sql_explain' => false, //分析SQL性能
        'builder' => '',    //Builder类
        'query' => '\\Core\\Lib\\Db\\Query'   //Query类
    ];
    /**
     * @var array PDO 连接参数
     */
    protected $params = [
        \PDO::ATTR_CASE => \PDO::CASE_NATURAL,  //	用类似 PDO::CASE_* 的常量强制列名为指定的大小写。
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,  //错误处理，如果发生错误，则抛出一个 PDOException 异常
        \PDO::ATTR_ORACLE_NULLS => \PDO::NULL_NATURAL,  //在获取数据时将空字符串转换成 SQL 中的 NULL
        \PDO::ATTR_STRINGIFY_FETCHES => false,  //获取时将数值转换为字符串。
        \PDO::ATTR_EMULATE_PREPARES => false    //启用或禁用准备语句的仿真。有些驱动程序不支持本地编写的语句或对它们有有限的支持。使用该设置为总是模仿力PDO的准备好的语句，或者尝试使用本土的准备好的语句（假）。
    ];

    /**
     * 初始化配置信息
     * Connection constructor.
     * @param $config array 系统定义数据库配置信息
     */
    public function __construct($config)
    {
        if(!empty($config)){
            //array_merge() 函数用于把一个或多个数组合并为一个数组;如果两个或更多个数组元素有相同的键名，则最后的元素会覆盖其他元素
            $this->config = array_merge($this->config, $config);
        }
    }

    /**
     * 初始化数据库连接，分布式和单一服务器连接
     * @param bool $master  是否是主服务器
     */
    protected function initConnect($master = true)
    {
        //分布式部署
        if(!empty($this->config['deploy'])){
            if($master){    //是主服务器
                if(!$this->linkWrite){
                    $this->linkWrite = $this->multiConnect(true);
                }
                $this->linkID = $this->linkWrite;    //连接实例
            }else{  //不是主服务器
                if(!$this->linkRead){
                    $this->linkRead = $this->multiConnect(false);
                }
                $this->linkID = $this->linkRead;    //连接实例
            }
        }elseif(!$this->linkID){
            //默认单数据库
            $this->linkID = $this->connect();   //连接实例
        }
    }

    /**
     * 连接分布式服务器
     * @param bool $master  主服务器
     * @return mixed    “读”服务器的连接实例
     */
    protected function multiConnect($master = false)
    {
        $_config = [];
        //解析分布式数据库配置
        foreach (['username', 'password', 'hostname', 'hostport', 'database', 'dsn', 'charset'] as $item){
            //多个username，password.....字符串化为数组
            $_config[$item] = explode(',', $this->config[$item]);   //username 数组
        }
        //floor函数向下舍入为最接近的整数,即向下取整;mt_rand() 生成随机整数
        $m = floor(mt_rand(0, $this->config['master_num'] - 1));    //随机生成。选择一个服务器作为主服务器
       //todo:确定“读”服务器的序号 $r
        if($this->config['rw_separate']){
            //如果采用读写分离
            if($master){
                $r = $m;    //如果，主服务器是“读”
            }elseif($this->config['slave_no']){
                $r = $this->config['slave_no'];     //如果指定“读”服务器
            }else{
                //如果没指定主服务器 是“读”， 也没有指定具体的“读”服务器， 则随机选择一台服务器作为 “读” 服务器
                $r = floor(mt_rand($this->config['master_num'], count($_config['hostname']) - 1));
            }
        }else{
            //读写不分离；每次随机连接数据库
            $r = floor(mt_rand(0, count($_config['hostname']) - 1));
        }
        $dbMaster = false;
        //不主服务器不是 “读”
        if($m != $r){
            $dbMaster = [];
            foreach (['username', 'password', 'hostname', 'hostport', 'database', 'dsn', 'charset'] as $item){
                $dbMaster[$item] = isset($_config[$item][$m])?$_config[$item][$m]:$_config[$item][0];   //主服务器配置信息
            }
        }
        $dbConfig = [];
        foreach (['username', 'password', 'hostname', 'hostport', 'database', 'dsn', 'charset'] as $item){
            $dbConfig[$item] = isset($_config[$item][$r])?$_config[$item][$r]:$_config[$item][0];   //“读”服务器的配置信息
        }
        //返回读服务器的连接实例
        return $this->connect($dbConfig, $r, $dbMaster);
    }

    /**
     * 连接数据库
     * @param array $config    连接参数
     * @param int $linkNum      连接实例的序号
     * @param bool $autoConnection
     * @return mixed    PDO连接实例
     */
    public function connect(array $config = [], $linkNum = 0, $autoConnection = false)
    {
        if(!isset($this->link[$linkNum])){  //如果未连接数据库， $linkNum 不存在
            //连接信息
            if(!$config){
                $config = $this->config;    //没有给连接信息，读取默认配置信息
            }else{
                $config = array_merge($this->config, $config);  //给出了配置信息，与默认的合并取最终的配置信息
            }
            //连接参数
            if(isset($config['params']) && is_array($config['params'])){
                //"+"操作符,如果两个数组存在相同的key,前面的一个会覆盖后面的; array_merge 是后面的覆盖前面的
                $params = $config['params'] + $this->params;
            }else{
                $params = $this->params;
            }
            //字段大小写设置
            $this->attrCase = $config[\PDO::ATTR_CASE];
            //数据返回类型设置
            if(isset($config['resuleset_type'])){
                $this->resultSetType = $config['resuleset_type'];
            }
            try{
                //数据库连接DSN，没有则根据配置信息生成
                if(empty($config['dsn'])) $config['dsn'] = $this->parseDsn($config);
                //函数返回当前 Unix 时间戳的微秒数
                if($config['debug']) $startTime = microtime(true);
                //连接数据库,并将连接的实例存入 $link 数组中
                $this->link[$linkNum] = new \PDO($config['dsn'], $config['username'], $config['password'], $params);
                //调试模式
                if($config['debug']){
                    //记录PDO连接信息
                    Log::log('[ DB ] CONNECT:[ UseTime:' . number_format(microtime(true) - $startTime, 6) . 's ] ' . $config['dsn'], 'pdo_connect_msg');
                }
            }catch (\PDOException $exception){
                //如果自动连接
                if($autoConnection){
                    //记录连接错误信息
                    Log::log($exception->getMessage(), 'connect_error');
                    //自动连接
                    return $this->connect($config,$linkNum,$autoConnection);
                }else{
                    //不自动连接，直接抛出错误
                    throw $exception;
                }
            }
        }
        //返回数据库连接实例
        return $this->link[$linkNum];
    }

    /**
     * 获取查询对象实例
     * @param $model string 模型名
     * @param $queryClass   string 查询对象名
     * @return mixed  查询对象实例
     */
    public function model($model, $queryClass)
    {
        if(!isset($this->query[$model])){
            $classname = $queryClass?:$this->getConfig('query');    //如果$queryClass为空，就获取配置数组中的：'query' => '\\Core\\Lib\\Db\\Query'；查询对象名
            $this->query[$model] = new $classname($this, $model);   //实例化查询对象Query
        }
        return $this->query[$model];    //返回查询对象
    }

    /**
     * 用Connection类调用不存在的方法时，用这个函数把调用委托给Query查询类的方法来执行
     * @param $name  string  调用的方法名
     * @param $arguments  mixed  调用方法的参数
     * @return mixed    调用方法
     */
    public function __call($name, $arguments)
    {
        if(!isset($this->query['database'])){
            $classname = $this->getConfig('query');     //获取配置数组中的：'query' => '\\Core\\Lib\\Db\\Query'；查询对象名
            $this->query['database'] = new $classname($this);   //查询对象Query实例
        }
        return call_user_func_array([$this->query['database'], $name], $arguments);     //调用查询类Query的方法
    }

    /**
     * 解析PDO的DSN连接信息
     * @param $config   array  配置数组
     * @return string   DSN
     */
    abstract protected function parseDsn($config);

    /**
     * 获取数据表字段信息
     * @param $tableName string 数据表名
     * @return array 数据表字段信息
     */
    abstract protected function getFields($tableName);

    /**
     * 获取数据表信息
     * @param $dbName string 数据库名
     * @return array  数据表信息
     */
    abstract protected function getTables($dbName);

    /**
     * 获取SQL性能信息
     * @param $sql string  sql语句
     * @return array  sql性能信息
     */
    abstract protected function getExplain($sql);

    /**
     * 对字段信息进行大小写转换
     * @param $info
     * @return array
     */
    public function fieldsCase($info)
    {
        switch ($this->attrCase){
            case \PDO::CASE_LOWER:
                $info = array_change_key_case($info);   //函数将数组的所有的键都转换为大写字母或小写字母,默认是小写
                break;
            case \PDO::CASE_UPPER:
                $info = array_change_key_case($info, CASE_UPPER);   //数组键值转化为大写
                break;
            case \PDO::CASE_NATURAL:
            default:
                //不转换
        }
        return $info;
    }
    /**
     * 获取配置信息
     * @param string $config
     * @return array|mixed
     */
    public function getConfig($config = '')
    {
        return $config?$this->config[$config]:$this->config;
    }

    /**
     * 设置数据库连接配置
     * @param $config
     * @param string $value
     */
    public function setConfig($config, $value = '')
    {
        if(is_array($value)){
            $this->config = array_merge($this->config, $value); //参数可以覆盖默认配置
        }else{
            $this->config[$config] = $value;
        }
    }

    /**
     * 释放查询结果集
     */
    public function free()
    {
        $this->PDOStatement = null;
    }

    /**
     * 获取PDO连接对象
     * @return bool|int
     */
    public function getPdo()
    {
        if(!$this->linkID){
            return false;
        }else{
            return $this->linkID;
        }
    }

    /**
     * 执行查询返回结数据集
     * @param $sql string sql语句
     * @param array $bind   参数绑定
     * @param bool $master   是否在主服务器进行读操作
     * @param bool $class   指定返回的数据集对象
     * @return mixed
     */
    public function query($sql, $bind = [], $master = false, $class = false)
    {
        $this->initConnect($master);    //初始化连接
        if(!$this->linkID){
            return false;   //没有连接返回FALSE
        }
        //获取参数绑定后的最终SQL
        $this->queryStr = $this->getRealSql($sql, $bind);
        //释放前一次查询的结果集
        if(!empty($this->PDOStatement)){
            $this->free();
        }
        Db::$queryTimes++;  //查询次数加一
        try{
            //调试开始
            $this->debug(true);
            //预处理
            $this->PDOStatement = $this->linkID->prepare($sql);
            //参数绑定
            $this->bindValue($bind);
            //执行查询
            $result = $this->PDOStatement->execute();
            //调试结束
            $this->debug(false);
            $procedure = in_array(strtolower(substr(trim($sql), 0, 4)), ['call', 'exec']);
            $this->getResult($class, $procedure);
        }catch (\PDOException $exception){
            throw new \PDOException($exception, $this->config, $this->queryStr);
        }
    }

    /**
     * 执行SQL语句，返回影响的行数
     * @param $sql  string  要执行的SQL
     * @param array $bind   参数绑定
     * @return bool|int     影响的行数
     */
    public function execute($sql, $bind = [])
    {
        $this->initConnect(true);   //初始化连接
        if(!$this->linkID){
            return false;   //未连接返回FALSE
        }
        //释放前一次查询的结果集
        if(!empty($this->PDOStatement)){
            $this->free();
        }
        Db::$queryTimes++;  //执行次数加一
        try{
            //调试开始
            $this->debug(true);
            //预处理
            $this->PDOStatement = $this->linkID->prepare($sql);
            //参数绑定
            $this->bindValue($bind);
            //执行语句
            $result = $this->PDOStatement->execute();
            //调试结束
            $this->debug(false);
            $this->numRows = $this->PDOStatement->rowCount();
            return $this->numRows; //影响的行数
        }catch (\PDOException $exception){
            throw new \PDOException($exception, $this->config, $this->queryStr);
        }
    }

    /**
     *
     * @param $start
     * @param string $sql
     */
    public function debug($start, $sql = '')
    {
        if(!empty($this->config['debug'])){
            //开启调试
            if($start){
                Debug::record('queryStartTime', 'time');    //记录SQL开始L时间
            }else{
                Debug::record('queryEndTime', 'time');      //SQL结束时间
                $runTime = Debug::rangeTime('queryStartTime', 'queryEndTime');  //SQL运行时间
                $sql = $sql?$sql:$this->queryStr;
                $timeLog = "{$sql} [RunTime: {$runTime}s]";
                $ret = [];
                //SQL性能分析
                if($this->config['sql_explain'] && stripos(trim($sql), 'select') === 0){
                    $ret = $this->getExplain($sql);
                }
                //SQL监听
                $this->trigger($sql, $runTime, $ret);
            }
        }
    }

    /**
     * 触发注册的SQL监听事件
     * @param $sql  string 要监听的SQL
     * @param $runTime  string  SQL运行时间
     * @param array $explain    性能分析
     */
    protected function trigger($sql, $runTime, $explain = [])
    {
        if(!empty(self::$event)){
            foreach (self::$event as $callback){
                if(is_callable($callback)){
                    call_user_func_array($callback, [$sql, $runTime, $explain]);
                }
            }
        }else{
            Log::log('[SQL:]'.$sql.' [RunTime:]'.$runTime.'s', 'sql');  //在日志中记录SQL运行时间
            if(!empty($explain)){
                Log::log('[EXPLAIN: '.var_export($explain,true).']', 'sql_explain');    //var_export -- 输出或返回一个变量的字符串表示
            }
        }
    }

    /**
     * 注册监听SQL的事件
     * @param $callback
     */
    public function listen($callback)
    {
        self::$event[] = $callback;
    }

    /**
     * 根据参数绑定，组装最终SQL
     * @param $sql  string 带参数绑定的SQL语句
     * @param array $bind   参数绑定数组
     * @return string   最终SQL
     */
    public function getRealSql($sql, $bind = [])
    {
        if($bind){
            foreach ($bind as $key => $val){
                $value = is_array($val)?$val[0]:$val;
                $type = is_array($val)?$val[1]:\PDO::PARAM_STR;
                if($type == \PDO::PARAM_STR){
                    $value = $this->quote($value);  //如果是字符串类型
                }elseif ($type == \PDO::PARAM_INT && $value == ''){
                    $value = 0;    //如果是整数类型
                }
                //判断占位符,拼接SQL
                $sql = is_numeric($key)?
                    substr_replace($sql, $value, strpos($sql, '?'), 1):
                    str_replace(
                        [':' . $key . ')', ':' . $key . ',', ':' . $key . ' '],
                        [$value . ')', $value . ',', $value . ' '],
                        $sql . ' ');
            }
        }
        return rtrim($sql);
    }

    /**
     * 参数绑定
     * 形如 ['name'=>'value','id'=>123]  或  ['value',123]
     * @param array $bind   要绑定的参数
     */
    protected function bindValue(array $bind = [])
    {
        foreach ($bind as $key => $val){
            //占位符
            $param = is_numeric($key)?$key+1:':'.$key;
            if(is_array($val)){
                //如果是整型，且值未空
                if(\PDO::PARAM_INT == $val[1] && $val[0] == ''){
                    $val[0] = 0;
                }
                //绑定参数
                $result = $this->PDOStatement->bindValue($param, $val[0], $val[1]);
            }else{
                $result = $this->PDOStatement->bindValue($param, $val);
            }
            DragonException::error($result, "绑定参数{$param}时发生错误！");
        }
    }

    /**
     * 获取数据集
     * @param string $class true 返回PDOStatement 字符串用于指定返回的类名
     * @param bool $procedure   是否是存储过程
     * @return mixed
     */
    protected function getResult($class = '', $procedure = false)
    {
        if($class === true){
            return $this->PDOStatement; //返回PDOStatement对象处理
        }
        if($procedure){
            return $this->procedure($class);    //存储过程返回结果
        }
        $result = $this->PDOStatement->fetchAll($this->fetchType);  //返回结果
        $this->numRows = count($result);   //影响的条数
        if(!empty($class)){
            $result = new $class($result);  //返回指定对象的数据集
        }elseif ($this->resultSetType == 'collection'){
            //Collection 类封装了基本的操作数组的方法，提供了对传入参数的各种处理方法
            $result = new Collection($result);
        }
        return $result;
    }

    /**
     * 获取存储过程数据集
     * @param $class true 返回PDOStatement 字符串用于指定返回的类名
     * @return array
     */
    protected function procedure($class)
    {
        $item = [];
        do{
            $result = $this->getResult($class);
            if($result){
                $item[] = $result;
            }
        }while($this->PDOStatement->nextRowset());
        $this->numRows = count($item);  //影响条数
        return $item;
    }

    /**
     * 执行数据库事务操作
     * @param $callback callable 数据库操作方法回调
     * @return mixed|null
     * @throws DragonException
     * @throws \Throwable
     */
    public function transaction($callback)
    {
        $this->startTrans();    //开启事务
        try{
            $result = null;
            //is_callable()验证变量的内容是否能够进行函数调用。可以用于检查一个变量是否包含一个有效的函数名称，或者一个包含经过合适编码的函数和成员函数名的数组
            if(is_callable($callback)){
                $result = call_user_func_array($callback, [$this]);
            }
            $this->commit();    //提交事务
            return $result;
        }catch (DragonException $exception){
            $this->rollback();  //事务回滚
            throw $exception;
        }catch (\Throwable $throwable){
            $this->rollback();  //事务回滚
            throw $throwable;
        }
    }

    /**
     * 开启事务
     * @return bool
     */
    public function startTrans()
    {
        $this->initConnect(true);   //初始化连接
        if(!$this->linkID) return false;
        ++$this->transTimes;    //事物次数自增
        //第一次事务处理
        if($this->transTimes == 1){
            $this->linkID->beginTransaction(); //开启事务
        }elseif ($this->transTimes > 1 && $this->supportSavepoint()){
            $this->linkID->exec(
                $this->parseSavepoint('trans' . $this->transTimes)
            );
        }
    }

    /**
     * 提交事务
     */
    public function commit()
    {
        $this->initConnect(true);   //初始化连接
        //事务数为1，提交事务
        if($this->transTimes == 1){
            $this->linkID->commit();
        }
        //归零
        --$this->transTimes;
    }

    /**
     * 事务回滚
     */
    public function rollback()
    {
        $this->initConnect(true);   //初始化连接
        if($this->transTimes == 1){
            $this->linkID->rollBack();  //事务回滚
        }elseif($this->transTimes > 1 && $this->supportSavepoint()){
            $this->linkID->exec(
                $this->parseSavepointRollBack('trans' . $this->transTimes)
            );
        }
    }

    /**
     * 是否支持事务嵌套
     * 在MySQL的官方文档中有明确的说明不支持嵌套事务，但是可以在系统架构层面来支持事务的嵌套（laravel）
     * 一个复杂的系统时难免在事务中嵌套了事务
     * @return bool
     */
    protected function supportSavepoint()
    {
        return false;
    }

    /**
     * 生成定义保存点的SQL
     * MySQL使你能够对一个事务进行部分回滚。
     * 这需要你在事务过程中使用SAVEPOINT语句设置一些称为保存点（savepoint）的标记。
     * 在后续的事务里，如果你想回滚到某个特定的保存点，在ROLLBACK语句里给出改保存点的名字就可以了
     * @param $name
     * @return string
     */
    protected function parseSavepoint($name)
    {
        return 'SAVEPOINT'.$name;
    }

    /**
     * 生成回滚到保存点的SQL
     * @param $name
     * @return string
     */
    protected function parseSavepointRollBack($name)
    {
        return 'ROLLBACK TO SAVEPOINT'.$name;
    }

    /**
     * 批量处理SQL，自动开启事务支持
     * @param array $sqlArray
     * @return bool
     * @throws DragonException
     */
    public function batchQuery($sqlArray = [])
    {
        if(!is_array($sqlArray)) return false;
        $this->startTrans(); //自动开启事务支持
        try{
            foreach ($sqlArray as $sql){
                $this->execute($sql);   //执行SQL
            }
            $this->commit();    //提交事务
        }catch (DragonException $dragonException){
            $this->rollback();  //批处理出错，事务回滚
            throw $dragonException; //抛出异常
        }
        return true;
    }

    /**
     * 获取SQL查询的次数
     * @param bool $execute
     * @return int
     */
    public function getQueryTime($execute = false)
    {
        //查询的次数
        return $execute?Db::$queryTimes + Db::$executeTimes:Db::$queryTimes;
    }

    /**
     * SQL执行的次数
     * @return int
     */
    public function getExecuteTime()
    {
        return Db::$executeTimes;
    }

    /**
     * 关闭数据库
     */
    public function close()
    {
        $this->linkID = null;
    }

    /**
     * 获取最后一次执行的SQL
     * @return string
     */
    public function getLastSql()
    {
        return $this->queryStr;
    }

    /**
     * 获取最近一次SQL影响的ID
     * @param null $sequence    序列自增
     * @return mixed
     */
    public function getLastInsertId($sequence = null)
    {
        return $this->linkID->lastTnsertId($sequence);
    }

    /**
     * 获取影响的行数
     * @return int
     */
    public function getnumRows()
    {
        return $this->numRows;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        if($this->PDOStatement){
            $error = $this->PDOStatement->errorInfo();
            $error = $error[1].':'.$error[2];
        }else{
            $error = '';
        }
        if(!$this->queryStr){
            $error .= "\n [SQL]:".$this->queryStr;
        }
        return $error;
    }

    /**
     * SQL指令安全过滤
     * @param $str
     * @param bool $master
     * @return mixed
     */
    public function quote($str, $master = true)
    {
        $this->initConnect($master);
        return $this->linkID?$this->linkID->quote($str):$str;
    }

    /**
     * 析构方法
     */
    public function __destruct()
    {   //释放查询结果集
        if($this->PDOStatement) $this->free();
        //关闭数据库
        $this->close();
    }
}
?>