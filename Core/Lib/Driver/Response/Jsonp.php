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

namespace Core\Lib\Driver\Response;

use Core\Lib\Registry\RequestRegistry;
use Core\Lib\Response;

/**
 * jsonp数据响应
 * Class Jsonp
 * @package Core\Lib\Driver\Response
 */
class Jsonp extends Response
{
    // 输出参数
    protected $options = [
        'var_jsonp_handler'     => 'callback',      //回调函数
        'default_jsonp_handler' => 'jsonpReturn',   //处理函数名
        'json_encode_param'     => JSON_UNESCAPED_UNICODE,
    ];
    //返回类型
    protected $contentType = 'application/javascript';

    /**
     * 处理数据
     * @access protected
     * @param mixed $data 要处理的数据
     * @return mixed
     * @throws \Exception
     */
    protected function output($data)
    {
        try {
            // 返回JSON数据格式到客户端 包含状态信息 [当url_common_param为false时法获取到$_GET的数据，故使用Request来获取]
            $var_jsonp_handler = RequestRegistry::getRequest()->param($this->options['var_jsonp_handler'], "");
            $handler           = !empty($var_jsonp_handler) ? $var_jsonp_handler : $this->options['default_jsonp_handler'];
            //编码json数据
            $data = json_encode($data, $this->options['json_encode_param']);
            //编码错误抛出异常
            if ($data === false) throw new \InvalidArgumentException(json_last_error_msg());
            //处理的函数  funName($data)
            $data = $handler . '(' . $data . ');';
            return $data;
        } catch (\Exception $e) {
            //前一次错误信息
            if ($e->getPrevious()) throw $e->getPrevious();
            throw $e;
        }
    }

}
