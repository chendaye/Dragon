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

/**
 * 文件日志驱动
 * Class File
 * @package Core\Lib\Driver\Log
 */
class File implements Driver
{

    //默认配置参数
    private $configure = [
        'TIME_FORMAT'     => 'c',   //ISO-8601 标准的日期（例如 2013-05-05T16:34:42+00:00）
        'SIZE'            => 1024*2048,
        'PATH'            => LOG,
        'APART_LEVEL'     => []     //分开独立记录的日志级别
    ];

    /**
     * 初始化配置信息
     * File constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        if(is_array($config)){
            $this->configure = array_merge($this->configure, $config);  //配置文件优先级更高
        }
    }

    /**
     * 记录日志信息
     * @param array $content
     * @return  void
     */
    public function save(array $content)
    {
        $now = date($this->configure['TIME_FORMAT']);
        $log_path = $this->configure['PATH'].date('Ym').SP.date('d').'.log'; //以月为单位创建文件夹，天为单位创建日志文件
        $path = dirname($log_path);
        !is_dir($path) && mkdir($path, 0755, true); //创建目录，权限0755
        //如果当天的日志存在，且日志大小超过限制,重新命名日志文件
        if(is_file($log_path) && filesize($log_path) > floor($this->configure['SIZE'])) {
            rename($log_path, $path.SP.$_SERVER['REQUEST_TIME'].'-'.basename($log_path));
        }
        //运行信息
        $runinfo = $this->request_info();
        //记录信息
        foreach ($content as $level => $msg){
            $level_info = '';
            //拼接一个级别的日志信息
            foreach ($msg as $val){
                if(!is_string($val)){
                    $val = var_export($val, true);  //var_export() 函数用于返回关于变量的结构信息，返回合法格式的代码
                }
                $level_info .= '['.$level.':]'.$val;
            }
            if(in_array($level, $this->configure['APART_LEVEL'])){
                //独立记录的日志
                $log_name = $path.SP.date('d').'_'.$level.'.log';  //当天独立记录的日志信息
                $apart_info = "[RecordTime{$now}:] Server:{$runinfo['server']} Remote:{$runinfo['remote']} Method:{$runinfo['req_method']} URI:{$runinfo['req_uri']} \r\n{$level_info}
                                \r\n+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++\r\n";
                error_log($apart_info, 3, $log_name);   //日志单独写入一个文件
            }else{
                $runinfo['info'] .= $level_info;    //运行信息，拼接当前级别信息
            }
        }
        $total_info = "[RecordTime{$now}:] Server:{$runinfo['server']} Remote:{$runinfo['remote']} Method:{$runinfo['req_method']} URI:{$runinfo['req_uri']} \r\n{$runinfo['info']}
                                \r\n+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++\r\n";
        error_log($total_info, 3, $log_path);   //所有级别的日志信息记录完整日志信息
    }

    /**
     * 运行时信息
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

        //运行信息
        return [
            'info' => '[Runinfo:]'.$uri.$time_info.$memory_info.$load_consume."\r\n",
            'server' => isset($_SERVER['SERVER_ADDR'])?$_SERVER['REMOTE_ADDR']:'',   //本机IP
            'remote' => isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'',    //主机IP
            'req_method' => isset($_SERVER['REQUEST_METHOD'])?$_SERVER['REQUEST_METHOD']:'CLI',
            'req_uri' => isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:'',
        ];
    }
}
?>