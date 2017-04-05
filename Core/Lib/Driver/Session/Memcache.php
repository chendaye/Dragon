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

/**
 * memcache 驱动
 * Class Memcache
 * @package Core\Lib\Driver\Session
 */
class Memcache extends \SessionHandler
{
    //连接句柄
    protected $instance = null;
    //配置
    protected $configure = [
        'HOST' => '127.0.0.1', //memcache主机
        'PORT' => '11211',  //memcache端口号
        'EXPIRE' => 3600,   //连接时长
        'TIMEOUT' => 0,     //连接超时时间（毫秒）
        'PERSISTENT' => true,   //长连接
        'SESSION_NAME' => '',   //memcache key 前缀
    ];
    /**
     * 参数初始化
     * Memcache constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->configure = array_merge($this->configure, $config);
    }
    /**
     * 开启memcache连接
     * @param string $path
     * @param string $name
     * @throws DragonException
     * @return  bool
     */
    public function open($path, $name)
    {
        if(!extension_loaded('memcache')) throw new DragonException('未开启memcache支持！');
        $this->instance = new \Memcache();  //初始化句柄
        //支持集群
        $hosts = explode(',', $this->configure['HOST']); //主机
        $ports = explode(',', $this->configure['PORT']); //端口
        //连接
        foreach ($hosts as $index => $host){
            $port = isset($ports[$index])?$ports[$index]:11211;
            if($this->configure['TIMEOUT'] > 0){
                $this->instance->addServer($host, $port, $this->configure['PERSISTENT'], 1, $this->configure['TIMEOUT']);
            }else{
                $this->instance->addServer($host, $port, $this->configure['PERSISTENT'], 1);
            }
        }
        return true;
    }

    /**
     * 关闭连接
     * @return bool
     */
    public function close()
    {
        $this->gc(ini_set('session.gc_maxlifetime'));
        $this->instance->close();
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
        return $this->instance->set($this->configure['SESSION_NAME'], $session_id, $session_data, 0, $this->configure['EXPIRE']);
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
     * @param int $maxlifetime
     * @return bool
     */
    public function gc($maxlifetime)
    {
        return true;
    }
}
?>