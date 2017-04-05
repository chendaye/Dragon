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
use Core\Lib\Db\Model;

class Collection implements \ArrayAccess ,\Countable ,\IteratorAggregate ,\JsonSerializable
{
    protected $item = [];

    /**
     * 转化为数组
     * Collection constructor.
     * @param array $item
     */
    public function __construct($item = [])
    {
        $this->item = $this->convertToArray($item);
    }

    /**
     * 转化成数组
     * @param $item
     * @return array
     */
    public function convertToArray($item)
    {
        if($item instanceof self){
            return $item->all();
        }
        return (array)$item;
    }

    /**
     * @return array
     */
    public function all()
    {
        return $this->item;
    }

    /**
     * 返回类自身
     * @param array $item
     * @return static
     */
    static public function make($item = [])
    {
        /**
         * 后期静态绑定, static代表使用的这个类,
         * 就是你在父类里写的static, 然后通过子类直接/间接用到了这个static, 这个static指的就是这个子类,
         * 所以说static和$this很像, 但是static可以用于静态方法和属性等.
         *http://www.jb51.net/article/54167.htm
         */
        return new static($item);
    }

    /**
     * 是否为空
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->item);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        //array_map() 函数将用户自定义函数作用到数组中的每个值上，并返回用户自定义函数作用后的带有新的值的数组
        return array_map(function($value){
            //如果元素值是 Model 或者 本类 继承者，那么就把值转化为数组，否则不变
            return ($value instanceof Model || $value instanceof self)?$value->toArray():$value;    //闭包
        }, $this->item);
    }

    /**
     * 合并数组
     * @param $item
     * @return static
     */
    public function merge($item)
    {
        return new static(array_merge($this->item, $this->convertToArray($item)));
    }

    /**
     * 比较数组返回差集
     * @param $item
     * @return static
     */
    public function diff($item)
    {
        return new static(array_diff($this->item, $this->convertToArray($item)));
    }

    /**
     * 比较数返回交集
     * @param $item
     * @return static
     */
    public function intersect($item)
    {
        return new static(array_intersect($this->item, $this->convertToArray($item)));
    }
    /**
     * 交换数组中的键和值
     * @return array
     */
    public function flip()
    {
        return array_flip($this->item);
    }

    /**
     * 返回数组中的键名
     * @return array
     */
    public function keys()
    {
        return array_keys($this->item);
    }

    /**
     * 删除数组最后一个元素
     * @return mixed
     */
    public function pop()
    {
        return array_pop($this->item);
    }

    /**
     * 通过使用用户自定义函数，以字符串返回数组
     * @param callable $callback
     * @param null $initial 如果指定第三个参数，则该参数将被当成是数组中的第一个值来处理，或者如果数组为空的话就作为最终返回值
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null)
    {
        //array_reduce() 函数向用户自定义函数发送数组中的值，并返回一个字符串
        return array_reduce($this->item, $callback, $initial);
    }

    /**
     * 以相反的顺序返回数组
     * @return array
     */
    public function reverse()
    {
        return array_reverse($this->item);
    }

    /**
     * 删除数组的首个元素，并返回删除的值
     * @return mixed
     */
    public function shift()
    {
        return array_shift($this->item);
    }

    /**
     * 在数组前面插入一个值
     * @param $value
     * @param null $key
     */
    public function unshift($value, $key = null)
    {
        if($key === null){
            array_unshift($this->item, $value);
        }else{
            $this->item = [$key => $value] + $this->item;
        }
    }
    /**
     * 分割数组
     * @param int $size  分割的大小
     * @param bool $preserveKeys 它指定新数组的元素是否有和原数组相同的键（用于关联数组），还是从 0 开始的新数字键（用于索引数组）。默认是分配新的键。
     * @return array
     */
    public function chunk($size, $preserveKeys = false)
    {
        $chunk = [];
        foreach (array_chunk($this->item, $size, $preserveKeys) as $item){
            $chunk[] = new static($item);
        }
        return $chunk;
    }

    /**
     * 对每个数组元素执行回调函数，最后返回类自身
     * @param callable $callback
     * @return $this
     */
    public function each(callable $callback)
    {
        foreach($this->item as $key => $item){
            if($callback($key, $item) === false){
                break;
            }
        }
        return $this;
    }

    /**
     * 用回调函数过滤数组中的元素
     * @param callable|null $callback
     * @return array|void
     */
    public function filter(callable $callback = null)
    {
        if($callback === null){
            return array_filter($this->item);
        }
        return array_filter($this->item, $callback);
    }

    /**
     * 返回数组中指定的一列
     * @param $column_key
     * @param null $index_key
     * @return array
     */
    public function column($column_key, $index_key = null)
    {
        //内置函数支持
        if(function_exists('array_column')){
            return array_column($this->item, $column_key, $index_key);
        }
        //内置函数不支持
        $ret = [];
        foreach ($this->item as $item){
            $key = $value = null;
            $keySet = $valueSet = false;
            //每一行，$item[$index_key]的值
            if($index_key != null && array_key_exists($index_key, $item)){
                $keySet = true;
                $key = (string)$item[$index_key];
            }
            //每一行，$item[$column_key]的值
            if($column_key === null){
                $valueSet = true;
                $value = $item;
            }elseif(is_array($item) && array_key_exists($column_key, $item)){
                $valueSet = true;
                $value = $item[$column_key];
            }
            if($valueSet){
                if($keySet){
                    $ret[$key] = $value;
                }else{
                    $ret[] = $value;
                }
            }
        }
        return $ret;
    }

    /**
     * 对数组进行排序
     * @param callable|null $callback
     * @return static
     */
    public function sort(callable $callback = null)
    {
        $array = $this->item;
        //usort函数对指定数组(参数1)按指定方式(参数2)进行排序
        $callback?uasort($array, $callback):uasort($array, function ($a, $b){
            if($a == $b) return 0;
            return ($a > $b)?1:-1;
        });
        return new static($array);
    }

    /**
     * 将数组打乱
     * @return static
     */
    public function shuffle()
    {
        $item = $this->item;
        shuffle($item);
        return new static($item);
    }

    /**
     * 分割数组
     * @param $offset
     * @param null $length
     * @param bool $preserveKeys
     * @return static
     */
    public function slice($offset, $length = null, $preserveKeys = false)
    {
        return new static($this->item, $offset, $length, $preserveKeys);
    }

    /**
     * 数组键值是否存在
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->item);
    }

    /**
     * 获取指定的元素
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->item[$offset];
    }

    /**
     * 设置数组值
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->item[] = $value;
        } else {
            $this->item[$offset] = $value;
        }
    }
    /**
     * 销毁数组指定元素
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->item[$offset]);
    }

    /**
     * 获取数组的元素个数
     * @return int|mixed
     */
    public function count()
    {
        return count($this->item);
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->item);
    }

    /**
     * json化为数组
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * 把当前数据集转化为json
     * @param int $options
     * @return string
     */
    public function toJson($options = JSON_UNESCAPED_UNICODE)
    {
        return json_encode($this->toArray(),$options);
    }

    /**
     * 当前字符串转化为json字符串
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * 递归实现数组键名大小写转换
     * @param $array
     * @param int $case 默认转化为大写
     */
    public function keyToCase(&$array, $case=CASE_UPPER)
    {
        $array = array_change_key_case($array, $case);
        foreach ($array as $key => $value) {
            if ( is_array($value) ) {
                self::keyToCase($array[$key], $case);
            }
        }
    }
}
?>