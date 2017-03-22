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
 * 请求类获取请求信息
 * Class Request
 * @package Core\Lib
 */
class Request{
    private $property = [];  //http属性
    private $feedback = [];

    /**
     * 构造方法。调用init()初始化，并且在注册表中注册
     * Request constructor.
     */
    public function __construct()
    {
        $this->init();  //初始化
        //RequestRegistry::setRequest($this); //在注册表中注册
    }

    /**
     * 初始化方法，获取请求中的信息参数
     * @return array
     */
    public function init(){
        //GET POST 请求
        if(isset($_SERVER['REQUEST_METHOD'])){
            foreach ($_REQUEST as $k => $v){
                if($v) $this->property[$k] = $v;
            }
        }
        //$_SERVER 参数信息
        //$_SERVER["QUERY_STRING"]; //查询(query)的字符串
        //$_SERVER["REQUEST_URI"];   //访问此页面所需的URI,除域名外的部分
        //$_SERVER["SCRIPT_NAME"];   //包含当前脚本的路径
        //$_SERVER["PHP_SELF"];     //当前正在执行脚本的文件名
        foreach ($_SERVER as $key => $val){
            $this->setProperty($key, $val);
        }
        return $this->property;
    }

    /**
     * 获取请求参数
     * @param $key static 请求参数键值
     * @return mixed|null  返回请求参数
     */
    public function getProperty($key){
        DragonException::error(isset($this->property[$key]),"参数{$key}不存在存在！");
        return $this->property[$key];
    }

    /**
     * 设置参数
     * @param $key static 键值
     * @param $val mixed 参数内容
     */
    public function setProperty($key, $val){
        DragonException::error(!isset($this->property[$key]),"参数{$key}=>{$val}已经存在！");
        $this->property[$key] = $val;
    }

    /**
     * 添加反馈信息
     * @param $msg mixed 反馈信息
     */
    public function addFeedback($msg){
        array_push($this->feedback, $msg);  //在数组的最后插入信息
    }

    /**
     * 获取反馈信息
     * @param string $type  返回信息的类型
     * @return array|string 返回信息
     */
    public function getFeedback($type = 'array'){
        if($type == 'array'){
            return $this->feedback;
        }elseif ($type = 'string'){
            return implode($this->feedback, "\n");
        }
    }
}
?>