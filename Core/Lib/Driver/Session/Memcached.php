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

namespace Core\Lib\Driver\Session;
use Core\Lib\DragonException;

class Memcached extends \SessionHandler
{
    //连接实例
    protected $instance = null;
    //配置
    protected $configure = [
        'HOST' => '127.0.0.1',  //memcache 主机名
        'PORT' => '11211',      //memcache 端口
        'EXPIRE' => 3600,       //session 有效期
        'TIMEOUT' => 0,         //连接超时时间
        'SESSION_NAME' =>'',    //memcache key 前缀
        'USERNAME' => '',     //用户名
        'PASSWORD' => '',       //密码
    ];

    /**
     * 初始配置
     * Memcached constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->configure = array_merge($this->configure, $config);
    }

    /**
     * 连接memcached
     * @param string $session_path
     * @param string $session_name
     * @return bool
     * @throws DragonException
     */
    public function open($session_path, $session_name)
    {
        if(!extension_loaded('memcached')) throw new DragonException('不支持memcached!');
        //连接实例
        $this->instance = new \Memcached();
        //集群
        $hosts = explode(',', $this->configure['HOST']);
        $ports = explode(',', $this->configure['PORT']);
        //连接
        $servers = [];
        foreach ($hosts as $index => $host){
            $port = isset($ports[$index])?$ports[$index]:11211;
            $servers[] = [$host, $port, 1];
        }
        $this->instance->addServers($servers);
        if(!empty($this->configure['USERNAME'])){
            $this->instance->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
            $this->instance->resetServerList($this->configure['USERNAME'], $this->configure['PASSWORD']);
        }
        return true;
    }

    /**
     * 关闭session
     * @return bool
     */
    public function close()
    {
        //垃圾回收
        $this->gc(ini_get('session.gc_maxlifetime'));
        $this->instance->quit();
        $this->instance = null;
        return true;
    }

    /**
     * 读取session
     * @param string $session_id
     * @return mixed
     */
    public function read($session_id)
    {
        return $this->instance->get($this->configure['SESSION_NAME'].$session_id);
    }

    /**
     * 写入session
     * @param string $session_id
     * @param string $session_data
     * @return mixed
     */
    public function write($session_id, $session_data)
    {
        return $this->instance->set($this->configure['SESSION_NAME'].$session_id, $session_data, $this->configure['EXPIRE']);
    }

    /**
     * 删除session
     * @param string $session_id
     * @return mixed
     */
    public function destroy($session_id)
    {
        return $this->instance->delete($this->configure['SESSION_NAME'].$session_id);
    }

    /**
     * 垃圾回收
     * @param int $maxlifetime  session有效时间
     * @return bool
     */
    public function gc($maxlifetime)
    {
        return true;
    }

}
?>