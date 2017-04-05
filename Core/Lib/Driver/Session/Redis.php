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

class Redis extends \SessionHandler
{
  //连接实例
    protected $instance = null;
    //配置
    protected $configure = [
        'HOST' => '127.0.0.1',  //Redis 主机
        'PORT' => '6379',   //Redis 端口
        'PASSWORD' => '',   //密码
        'SELECT' => 0,      //操作库
        'EXPIRE' => 3600,   //session有效时间，秒
        'TIMEOUT' => 0,     //连接超时
        'PERSISTENT' => true,   //是否长连接
        'SESSION_NAME' => '',   //sessionkey 前缀
    ];

    /**
     * 初始配置
     * Redis constructor.
     * @param array $config
     */
    public function __construct($config = []){
        $this->configure = array_merge($this->configure, $config);
    }

    /**
     * 连接Redis
     * @param string $save_path
     * @param string $session_name
     * @return bool
     * @throws DragonException
     */
    public function open($save_path, $session_name)
    {
        if(!extension_loaded('redis')) throw new DragonException('不支持Redis!');
        //连接实例
        $this->instance = new \Redis();
        //连接
        $func = $this->configure['PERSISTENT']?'pconnect' : 'connect';  //连接函数
        $this->instance->$func($this->configure['HOST'], $this->configure['PORT'], $this->configure['TIMEOUT']);
        //设置密码
        if(!empty($this->configure['PASSWORD'])) $this->instance->auth($this->configure['PASSWORD']);
        //选择操作库
        if($this->configure['SELECT'] != 0) $this->instance->select($this->configure['SELECT']);
        return true;
    }

    /**
     * 关闭Session
     * @access public
     */
    public function close()
    {
        $this->gc(ini_get('session.gc_maxlifetime'));
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
        if($this->configure['EXPIRE'] > 0){
            return $this->instance->setex($this->configure['SESSION_NAME'].$session_id, $this->configure['EXPIRE'], $session_data);
        }else{
            return $this->instance->set($this->configure['SESSION_NAME'].$session_id, $session_data);
        }
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