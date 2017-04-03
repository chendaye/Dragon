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

use phpDocumentor\Reflection\DocBlock\Tags\Return_;

class File extends \SplFileObject {
    //错误信息
    private $error = '';
    //当前文件名
    protected $filename = '';
    //上传文件名
    protected $saveName = '';
    //文件上传命名规则
    protected $nameRule = 'time';
    //文件上传验证规则
    protected $validate = [];
    //单元测试
    protected $isTest;
    //文件上传信息
    protected $fileInfo;
    //文件hash信息
    protected $hash = [];

    /**
     * 构造函数，继承父类的构造方法
     * File constructor.
     * @param $file_name
     * @param $open_mode
     */
    public function __construct($file_name, $open_mode){
        parent::__construct($file_name, $open_mode);
        //初始化文件名
        $this->filename = $this->getRealPath()?:$this->getPathname();
    }

    /**
     * 是否测试
     * @param bool $test
     * @return bool
     */
    public function isTest($test = false){
        $this->isTest = $test;
        return $this->isTest;
    }

    /**
     * 设置上传文件信息
     * @param $info
     * @return $this
     */
    public function setInfo($info){
        $this->fileInfo = $info;
        //链式调用
        return $this;
    }

    /**
     * 获取上传信息
     * @param string $name
     * @return mixed
     */
    public function getInfo($name = ''){
        return isset($this->fileInfo[$name])?$this->fileInfo[$name]:$this->fileInfo;
    }

    /**
     * 获取上传的文件名
     * @return string
     */
    public function getName(){
        return $this->saveName;
    }

    /**
     * 设置上传文件名
     * @param $saveName
     * @return $this
     */
    public function setName($saveName){
        $this->saveName = $saveName;
        return $this;
    }

    /**
     * 获取文件的hash散列值
     * 哈希值就是文件的身份证,根据文件大小，时间，类型，创建者，机器等计算出来，极易发生改变
     * @param string $type
     * @return mixed
     */
    public function hash($type = 'sha1'){
        //获取hash 值
        if(!isset($this->hash[$type])) $this->hash[$type] = hash_file($type, $this->filename);
        return $this->hash[$type];
    }

    /**
     * 检查目录是否有写权限
     * @param $path
     * @return bool
     */
    protected function checkDir($path){
        if(is_dir($path)) return true;
        if(mkdir($path, 755, true)){
            return true;
        }else{
            $this->error = "创建目录{$path}失败！";
            return false;
        }
    }

    /**
     * 获取文件类型信息
     * @return mixed
     */
    public function getMime(){
        $type = finfo_open(FILEINFO_MIME_TYPE);
        return finfo_file($type, $this->filename);
    }

    /**
     * 设置文件命名规则
     * @param string $rule 命名规则
     * @return $this
     */
    public function rule($rule){
        $this->nameRule = $rule;
        return $this;
    }

    /**
     * 设置验证方式
     * @param array $rule
     * @return $this
     */
    public function validate($rule = []){
        $this->validate = $rule;
        return $this;
    }

    /**
     * 检查是否是合法的上传文件
     * @return bool
     */
    public function valid(){
        if($this->isTest) return is_file($this->filename);
        //判断指定的文件是否是通过 HTTP POST 上传
        return is_uploaded_file($this->filename);
    }

    public function check($rule = []){
        $rule = $rule?:$this->validate;
        //检查文件大小
    }
}