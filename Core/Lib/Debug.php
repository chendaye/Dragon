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
 * 运行状态
 * Class Debug
 * @package Core\Lib
 */
class Debug
{
    //时间信息
    static protected $timeinfo = [];
    //内存信息
    static protected $memory = [];

    /**
     * 记录时间和内存的使用情况
     * @param $name
     * @param string $value
     */
    static public function record($name, $value = '')
    {
        self::$timeinfo[$name] = is_float($value)?$value:microtime(true); //记录时间信息
        //记录内存的使用情况
        if($value != 'time'){
            self::$memory['memory'] = is_float($value)?$value:memory_get_usage();   //memory_get_usage()返回当前分配给你的 PHP 脚本的内存量，单位是字节（byte）
            self::$memory['peak'] = memory_get_peak_usage();    //脚本占用的内存峰值

        }
    }

    /**
     * 统计某个时间区间的的使用情况
     * @param $start string 开始标签
     * @param $end  string  结束标签
     * @param int $dsc  小数点
     * @return string
     */
    static public function rangeTime($start, $end, $dsc = 6)
    {
        if(!isset(self::$timeinfo['end'])){
            self::$timeinfo['end'] = microtime(true);
        }
        return number_format(self::$timeinfo['end'] - self::$timeinfo['start'], $dsc);
    }

    /**
     * 从框架运行开始到当前的使用情况
     * @param int $dsc
     * @return string
     */
    static public function useTime($dsc = 6)
    {
        return number_format(microtime(true) - DRAGON_START_TIME, $dsc);
    }

    /**
     * 当前访问的吞吐率
     * @return string
     */
    static public function throughputRate()
    {
        return number_format(1/self::useTime(), 2).'req/s';
    }

    /**
     * 记录区间内存使用情况
     * @param $start    string  开始标签
     * @param $end  string  结束标签
     * @param int $dsc  小数点
     * @return string  内存情况
     */
    static public function rangeMemory($start, $end, $dsc = 2)
    {
        if(!isset(self::$memory['memory'][$end])){
            self::$memory['memory'][$end] = memory_get_usage(); //获取内存情况
        }
        $size = self::$memory['memory'][$end] - self::$memory['memory'][$start];
        $unit = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pos = 0;
        while ($size > 1024){
            $size /= 1024;
            $pos++;
        }
        return round($size, $dsc)."  ".$unit[$pos];
    }

    /**
     * 记录框架运行开始到当前的内存使情况
     * @param int $dsc
     * @return string
     */
    static public function timeMemory($dsc = 2)
    {
        $size = memory_get_usage() - DRAGON_START_MEMORY;
        $unit = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pos = 0;
        while ($size > 1024){
            $size /= 1024;
            $pos++;
        }
        return round($size, $dsc)."  ".$unit[$pos];
    }

    /**
     * 记录区间内的内存峰值情况
     * @param $start string  开始标签
     * @param $end  string  结束标签
     * @param int $dsc  小数点
     * @return string
     */
    static public function rangePeak($start, $end, $dsc = 2)
    {
        if(!isset(self::$memory['peak'][$end])){
            self::$memory['peak'][$end] = memory_get_peak_usage();  //内存峰值
        }
        $size = self::$memory['peak'][$end] - self::$memory['peak']['start'];
        $unit = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pos = 0;
        while ($size > 1024){
            $size /= 1024;
            $pos++;
        }
        return round($size, $dsc)."  ".$unit[$pos];
    }

    /**
     * 获取文件加载信息
     * @param bool $detail
     * @return array|int
     */
    static public function fileMsg($detail = false)
    {
        if($detail){
            $files = get_included_files();
            $info = [];
            foreach ($files as $key => $file){
                $info[] = $file."(".number_format(filesize($file)/1024, 2)."KB)";
            }
            return $info;
        }
        return count(get_included_files());
    }

    /**
     * 浏览器友好的变量输出
     * @param mixed         $var 变量
     * @param boolean       $echo 是否输出 默认为true 如果为false 则返回输出字符串
     * @param string        $label 标签 默认为空
     * @param integer       $flags htmlspecialchars flags
     * @return void|string
     */
    static public function dump($var, $echo = true, $label = null, $flags = ENT_SUBSTITUTE)
    {
        $label = (null === $label) ? '' : rtrim($label) . ':';
        ob_start(); //开启缓存
        var_dump($var);  //输出到缓存
        $output = ob_get_clean();   //获取缓存值，并清空缓存
        $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);   //去除特殊字符
        //环境检测
        if (IS_CML) {
            $output = PHP_EOL . $label . $output . PHP_EOL;     //PHP_EOL通用换行符
        } else {
            if (!extension_loaded('xdebug')) {
                $output = htmlspecialchars($output, $flags);
            }
            $output = '<pre>' . $label . $output . '</pre>';
        }
        if ($echo) {
            echo ($output);
            return null;
        } else {
            return $output;
        }
    }
}
?>