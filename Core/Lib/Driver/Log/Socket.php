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

namespace Core\Lib\Driver\Log;

use Core\Lib\Exception\DragonException;

/**
 * Socket 日志驱动
 * Class Socket
 * @package Core\Lib\Driver\Log
 */
class Socket implements Driver
{
    //SocketLog 服务器端口
    public $port = 116;

    //客户端参数
    static $args = [];

    //Socket 配置
    protected $configure = [
        'HOST'               => 'localhost',  //Socket 服务器地址
        'SHOW_INCLUDES'      => false,   //是否显示加载文件列表
        'FORCE_TO_CLIENT_ID' => [],     //强制记录日志到设置的客户端ID
        'ALLOW_CLIENT_ID'    => []     //限制日志读取
    ];
    //默认样式
    protected $style = [
        'SQL'      => 'color:#009bb4;',
        'SQL_WARN' => 'color:#009bb4;font-size:14px;',
        'ERROR'    => 'color:#f4006b;font-size:14px;',
        'PAGE'     => 'color:#40e2ff;background:#171717;',
        'BIG'      => 'font-size:20px;color:red;',
    ];
    //强制推送，且被授权的客户端ID
    protected $forcePushClientId = [];

    /**
     * 初始化Socket配置
     * Socket constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        //初始化配置
        if(!empty($config)){
            $this->configure = array_merge($this->configure, $config);
        }
        if(empty(self::$args)){
            $this->initArg();
        }
    }

    /**
     * 发送日志到客户端
     * @param array $content
     * @return bool
     */
    public function save(array $content)
    {
        if(!$this->check()) return false;   //不强制发送日志
        $msg = $this->request_info();   //运行时信息

        //基本信息
        $trace[] = [
            'TYPE' => 'group',
            'MSG'  => $msg,
            'CSS'  => $this->style['page'],
        ];
        foreach ($content as $level => $value){
            $trace[] = [
                'TYPE' => 'groupCollapsed',
                'MSG'  => '['.$level.']',
                'CSS'  => isset($this->style[$level])?$this->style[$level]:'',
            ];
            foreach ($value as $item){
                if(!is_string($item)){
                    $item = var_export($item, true);    //格式化输出
                }
                $trace[] = [
                    'TYPE' => 'log',
                    'MSG'  => $item,
                    'CSS'  => '',
                ];
            }
            $trace[] = [
                'TYPE' => 'groupEnd',
                'MSG'  => '',
                'CSS'  => '',
            ];
        }
        //显示加载的文件
        if($this->configure['SHOW_INCLUDES']){
            $trace[] = [
                'TYPE' => 'groupCollapsed',
                'MSG'  => '[file]',
                'CSS'  => '',
            ];
            $trace[] = [
                'TYPE' => 'log',
                'MSG'  => implode("\n", get_included_files()),
                'CSS'  => '',
            ];
            $trace[] = [
                'TYPE' => 'groupEnd',
                'MSG'  => '',
                'CSS'  => '',
            ];
        }
        $trace[] = [
            'TYPE' => 'groupEnd',
            'MSG'  => '',
            'CSS'  => '',
        ];
        $tabid = $this->getClientArg('tabid');
        $client_id = $this->getClientArg('client_id')?:'';

        //发送日志到客户端ID
        if(!empty($this->forcePushClientId)){
            foreach ($this->forcePushClientId as $force_id){
                $client_id = $force_id;
                $this->sendToClient($tabid, $client_id, $trace, $force_id);     //强制发送
            }
        }else{
            $this->sendToClient($tabid, $client_id, $trace, '');
        }
        return true;
    }

    /**
     * 向指定的客户端发送日志
     * @param $tabid
     * @param $client_id
     * @param $log
     * @param $force_client_id
     */
    protected function sendToClient($tabid, $client_id, $log, $force_client_id)
    {
        $info = [
            'tabid'           => $tabid,
            'client_id'       => $client_id,
            'log'             => $log,
            'force_client_id' => $force_client_id,
        ];
        $msg = @json_encode($info); //json格式，屏蔽错误
        $address = '/'.$client_id;  //将client_id 作为地址，sever端通过地址判断向谁发送日志
        $this->inform($this->configure['HOST'], $msg, $address);    //发送日志
    }

    /**
     * curl发送服务器消息
     * @param string $host 服务器 host
     * @param string $msg  消息内容
     * @param string $address 目标地址
     * @return mixed
     * @throws DragonException
     */
    protected function inform($host, $msg = '', $address = '/')
    {
        $url = 'http://'.$host.':'.$this->port.$address;    //消息发送地址
        $header = [
            "Content-Type: application/json;charset=UTF-8", //请求头信息
        ];
        $curl = curl_init();    //初始化
        curl_setopt($curl, CURLOPT_URL, $url);  //地址
        curl_setopt($curl, CURLOPT_PORT, true);  //POST 方式
        curl_setopt($curl, CURLOPT_POSTFIELDS, $msg);  //数据
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);  //数据
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 1);  //连接超时
        curl_setopt($curl, CURLOPT_TIMEOUT, 12);  //请求超时
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);  //请求头信息
        $response = curl_exec($curl);
        if(!$response) throw new DragonException('curl请求失败！');
        return $response;
    }

    /**
     * 获取运行时信息
     * @return array
     */
    public function request_info()
    {
        //当前访问信息
        if(isset($_SERVER['HTTP_HOST'])){
            $uri = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];   //当前URI
        }else{
            $uri = "cmd:".implode(' ', $_SERVER['argv']);
        }
        $runtime = number_format(microtime(true) - DRAGON_START_TIME, 10);  //框架运行时间
        $reqs = $runtime>0?number_format(1/$runtime, 2):'∞';     //吞吐率 =  请求数/请求时间  每秒钟处理的请求
        $time_info = '[运行时间：'.number_format($runtime, 6).'s] [吞吐率：'.$reqs.'req/s]'; //运行时间吞吐率

        $memory_consume = number_format((memory_get_usage() - DRAGON_START_MEMORY)/1024, 2);    //消耗内存
        $memory_info = '[内存消耗： '.$memory_consume.'kb]';

        $load_consume = '[文件加载数：'.count(get_included_files()).']';

        $msg = '[Runinfo:]'.$uri.$time_info.$memory_info.$load_consume."\r\n";
        //运行信息
       return $msg;
    }



    /**
     * 初始化用户客户端信息参数
     * @return null|void
     */
    protected function initArg()
    {
        $key = 'HTTP_USER_AGENT';   //用户操作系统，浏览器信息
        if(!empty($_SERVER['HTTP_SOCKETLOG'])) $key = 'HTTP_SOCKETLOG';
        //参数不存在，返回空
        if(!isset($_SERVER[$key])) return null;

        //初始化参数
        if (!preg_match('/SocketLog\((.*?)\)/', $_SERVER[$key], $match)) {
            self::$args = ['tabid' => null];
            return null;    //没匹配到返回空
        }
        //把参数解析成数组，赋给$args
        parse_str($match[1], self::$args);
    }

    /**
     * 获取客户端信息参数
     * @param string $name  参数名
     * @return mixed|null
     */
    protected function getClientArg($name)
    {
        if(self::$args[$name]) return self::$args[$name];
        return null;
    }

    /**
     * 是否强制发送记录日志，授权数组和强制数组；客户端ID有授权或者强制推送或者授权表为空
     * @return bool
     */
    protected function check()
    {
        $tabid = $this->getClientArg('tabid');
        //检查是否强制记录日志
        if(!$tabid && !$this->configure['FORCE_TO_CLIENT_ID']) return false;
        //授权表非空
        if(!empty($this->configure['ALLOW_CLIENT_ID'])){
            //有授权列表，看是否要强制推送
            $this->forcePushClientId = array_intersect($this->configure['ALLOW_CLIENT_ID'], $this->configure['FORCE_TO_CLIENT_ID']);
            //强制推送客户端ID不为空
            if(!$tabid && count($this->forcePushClientId)) return true;

            //客户端ID没有授权
            $client_id = $this->getClientArg('client_id');
            if(!in_array($client_id, $this->configure['ALLOW_CLIENT_ID'])) return false;
        }else{
            $this->forcePushClientId = $this->configure['FORCE_TO_CLIENT_ID'];
        }
        return true;
    }

  
}
?>