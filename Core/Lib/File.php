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
 * 文件上传，处理
 * Class File
 * @package Core\Lib
 */
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
    public function __construct($file_name, $open_mode = 'r'){
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

    /**
     * 检查上传文件
     * @param array $rule 检查规则
     * @return bool
     */
    public function check($rule = []){
        $rule = $rule?:$this->validate;
        //检查文件大小
        if(isset($rule['size']) && !$this->checkSize($rule['size'])) {
            $this->error = "上传文件大小不能超过 {$rule['size']}";
            return false;
        }

        //检查Mime类型
        if(isset($rule['mime']) && !$this->checkMime($rule['mime'])){
            $this->error = "文件Mime类型不符合要求！";
            return false;
        }

        //检查文件后缀
        if(isset($rule['ext']) && !$this->checkExt($rule['ext'])){
            $this->error = "上传文件后缀不合法！";
            return false;
        }

        //检查图像文件
        if(!$this->checkImg()){
            $this->error = "非法图像文件！";
            return false;
        }
        return true;
    }

    /**
     * 检查文件后缀
     * @param string|array $ext  合法后缀
     * @return bool
     */
    public function checkExt($ext){
        if(is_string($ext)) $ext = explode(',', $ext);
        $extension = strtolower(pathinfo($this->getInfo('name'), PATHINFO_EXTENSION));
        if(in_array($extension, $ext)) return true;
        return false;
    }

    /**
     * 检测图像文件
     * @return bool
     */
    public function checkImg(){
        $extension = strtolower(pathinfo($this->getInfo('name'), PATHINFO_EXTENSION));
        if(in_array($extension, ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf']) && !in_array($this->imageType($this->filename), [1, 2, 3, 4, 6])){
            return false;
        }
        return true;
    }

    /**
     * 获取图像类型
     * @param $image
     * @return int
     */
    public function imageType($image){
        if(function_exists('exif_imagetype')){
            return exif_imagetype($image);
        }else{
            $info = getimagesize($image);
            return $info[2];
        }
    }

    /**
     * 检查文件大小
     * @param $size
     * @return bool
     */
    public function checkSize($size){
        if($this->getSize() > $size) return false;
        return true;
    }

    /**
     * 检测上传文件类型
     * @param string|array $mime 合法类型
     * @return bool
     */
    public function checkMime($mime){
        if(is_string($mime)) $mime = explode(',', $mime);
        if(in_array(strtolower($this->getMime()), (array)$mime)) return true;
        return false;
    }

    /**
     * 移动文件到指定路径
     * @param $path
     * @param bool $saveName
     * @param bool $replace
     * @return bool|File
     */
    public function move($path, $saveName = true, $replace = true){
        //文件上传数百，捕获错误代码
        if(!empty($this->fileInfo['error'])){
            $this->error($this->fileInfo['error']);
            return false;
        }
        //合法性检查
        if(!$this->valid()){
            $this->error = "非法上传文件";
            return false;
        }

        //验证上传
        if(!$this->check()) return false;

        //保存文件名
        $path = rtrim($path, SP).SP;
        $name = $this->saveName($saveName);
        $filename = $path.$name;

        //检查目录
        if($this->checkDir(dirname($filename)) === false) return false;

        //覆盖同名文件
        if($replace && is_file($filename)){
            $this->error = "存在同名文件{$filename}";
            return false;
        }

        //是否测试，上传成功
        if($this->isTest){
            rename($this->filename, $filename);
        }else{
            if(is_dir($filename)){
                return $filename;
            }
            $status = move_uploaded_file($this->filename, $filename);
            if(!$status){
                $this->error = "上传文件保存失败！";
                return false;
            }
        }

        //返回自身实例,操作上传后的文件
        $file = new self($filename);
        $file->saveName($saveName);
        $file->setInfo($this->fileInfo);
        return $file;
    }

    /**
     * 生成获取保存文件名
     * @param $saveName
     * @return mixed|string
     */
    protected function saveName($saveName){
        if($saveName === true){
            //自动生成文件名
            if($this->nameRule instanceof \Closure){
                $saveName = call_user_func_array($this->nameRule, [$this]);
            }else{
                switch ($this->nameRule){
                    case 'time':
                        $saveName = date('Ymd') . SP . md5(microtime(true));
                        break;
                    default:
                        if(in_array($this->nameRule, hash_algos())){
                            $hash = $this->hash($this->nameRule);
                            $saveName = substr($hash, 0, 2) . SP . substr($hash, 2);
                        }elseif (is_callable($this->nameRule)){
                            $saveName = call_user_func($this->nameRule);
                        }else{
                            $saveName = date('Ymd') . SP . md5(microtime(true));
                        }
                }
            }
        }elseif($saveName === ''){
            $saveName = $this->getInfo('name');
        }
        if(!strpos($saveName, '.')){
            $saveName .= '.' . pathinfo($this->getInfo('name'), PATHINFO_EXTENSION);
        }
        return $saveName;
    }

    /**
     * 获取错误代码信息
     * @param int $errorNo  错误号
     */
    private function error($errorNo)
    {
        switch ($errorNo) {
            case 1:
            case 2:
                $this->error = '上传文件大小超过了最大值！';
                break;
            case 3:
                $this->error = '文件只有部分被上传！';
                break;
            case 4:
                $this->error = '没有文件被上传！';
                break;
            case 6:
                $this->error = '找不到临时文件夹！';
                break;
            case 7:
                $this->error = '文件写入失败！';
                break;
            default:
                $this->error = '未知上传错误！';
        }
    }

    /**
     * 获取上传错误信息
     * @return string
     */
    public function getError(){
        return $this->error;
    }

    /**
     * 魔术方法，调用不存在的方法时触发
     * @param $method
     * @param $args
     * @return mixed
     */
    public function __call($method, $args){
        return $this->hash($method);
    }
}