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

use Core\Lib\Exception\HttpException;
use Core\Lib\Registry\RequestRegistry;

/**
 * Class Route
 * @package Core\Lib
 */
class Route
{
    // 路由规则
    static private $rules = [
        //get类型路由
        'get'     => [],
        //post类型路由
        'post'    => [],
        //put类型路由
        'put'     => [],
        //delete类型路由
        'delete'  => [],
        //patch类型路由
        'patch'   => [],
        //head类型路由
        'head'    => [],
        //路由配置
        'options' => [],
        //路由模式
        'pattern' => [],
        //分组路由
        'group'   => [],
        //域名内的路由
        'domain'  => [],
        //路由别名
        'alias'   => [],
        //每条路由的记录
        'name'    => [],
    ];

    // REST路由操作方法定义
    static private $rest = [
        'index'  => [
            'request' => 'get',
            'param' =>  '',
            'operate' =>  'index'
        ],
        'create' => [
            'request' => 'get', 
            'param' =>  '/create', 
            'operate' =>  'create'
        ],
        'edit'   => [
            'request' => 'get', 
            'param' =>  '/:id/edit', 
            'operate' =>  'edit'
        ],
        'read'   => [
            'request' => 'get', 
            'param' =>  '/:id', 
            'operate' =>  'read'
        ],
        'save'   => [
            'request' => 'post', 
            'param' =>  '', 
            'operate' =>  'save'
        ],
        'update' => [
            'request' => 'put', 
            'param' =>  '/:id', 
            'operate' =>  'update'
        ],
        'delete' => [
            'request' => 'delete', 
            'param' =>  '/:id', 
            'operate' =>  'delete'
        ],
    ];

    // 不同请求类型的方法前缀
    static private $methodPrefix = [
        'get'    => 'get',
        'post'   => 'post',
        'put'    => 'put',
        'delete' => 'delete',
        'patch'  => 'patch',
    ];

    // 子域名
    static private $subDomain = '';
    // 域名绑定
    static private $bind = [];
    // 当前分组信息
    static private $group = [];
    // 当前子域名绑定
    static private $domainBind;
    static private $domainRule;
    //路由作用域名
    static private $domain;
    // 当前路由执行过程中的参数
    static private $option = [];

    /**
     * 注册全局变量规则
     * Route::pattern('name','\w+')  new/:name
     * @access public
     * @param string|array  $name 变量名
     * @param string        $rule 变量规则
     * @return void
     */
    static public  function pattern($name = null, $rule = '')
    {
        if (is_array($name)) {
            self::$rules['pattern'] = array_merge(self::$rules['pattern'], $name);
        } else {
            self::$rules['pattern'][$name] = $rule;
        }
    }

    /**
     * 注册子域名部署规则
     * @access public
     * @param string|array  $domain 子域名
     * @param mixed         $rule 路由规则
     * @param array         $option 路由参数
     * @param array         $pattern 变量规则
     * @return void
     */
    static public  function domain($domain, $rule = '', $option = [], $pattern = [])
    {
        if (is_array($domain)) {
            //数组批量设置，使用递归
            foreach ($domain as $key => $item) {
                //key=>domain item=>rule
                self::domain($key, $item, $option, $pattern);
            }
        } else{
            if ($rule instanceof \Closure) {
                self::setDomain($domain);
                //注册域名下的路由
                call_user_func_array($rule, []);
                self::setDomain(null);
            } elseif (is_array($rule)) {
                self::setDomain($domain);
                $func = function () use ($rule) {
                    self::registerRules($rule);
                };
                //group匿名函数注册
                self::group('', $func, $option, $pattern);
                self::setDomain(null);
            } else {
                //域名绑定
                self::$rules['domain'][$domain]['[bind]'] = [$rule, $option, $pattern];
            }
        }
    }

    /**
     * 设置域名
     * @param $domain
     */
    static private function setDomain($domain)
    {
        self::$domain = $domain;
    }

    /**
     * 设置路由绑定,使用路由绑定简化URL或者路由规则的定义
     * @access public
     * @param mixed     $bind 绑定信息
     * @param string    $type 绑定类型 默认为module 支持 namespace class command
     * @return mixed
     */
    static public  function bind($bind, $type = 'module')
    {
        self::$bind = ['type' => $type, $type => $bind];
    }

    /**
     * 设置或者获取路由标识
     * @access public
     * @param string|array     $name 路由命名标识 数组表示批量设置
     * @param array            $value 路由地址及变量信息
     * @return array
     */
    static public function name($name = '', $value = null)
    {
        //数组就设置
        if(is_array($name))return self::$rules['name'] = $name;
        //name空，返回已有值
        if ($name === '') return self::$rules['name'];
        //value不为空，单个设置
        if ($value !== null)return self::$rules['name'][strtolower($name)][] = $value;
        //否则返回name指定的值
        return isset(self::$rules['name'][strtolower($name)])?self::$rules['name'][strtolower($name)]:null;
    }

    /**
     * 读取路由绑定信息 模块、命令，控制器
     * @access public
     * @param string    $type 绑定类型
     * @return mixed
     */
    static public  function getBind($type)
    {
        return isset(self::$bind[$type]) ? self::$bind[$type] : null;
    }

    /**
     * 导入配置文件的路由规则
     * @access public
     * @param array     $rule 路由规则
     * @param string    $type 请求类型
     * @return void
     */
    static public  function import(array $rule, $type = '*')
    {
        // 检查域名部署
        if (isset($rule['__domain__'])) {
            self::domain($rule['__domain__']);
            unset($rule['__domain__']);
        }

        // 检查变量规则
        if (isset($rule['__pattern__'])) {
            self::pattern($rule['__pattern__']);
            unset($rule['__pattern__']);
        }

        // 检查路由别名
        if (isset($rule['__alias__'])) {
            self::alias($rule['__alias__']);
            unset($rule['__alias__']);
        }

        // 检查资源路由
        if (isset($rule['__rest__'])) {
            self::resource($rule['__rest__']);
            unset($rule['__rest__']);
        }
        self::registerRules($rule, strtolower($type));
    }

    /**
     * 批量注册路由(包括分组路由)
     * @param array $rules
     * @param string $type
     */
    static protected  function registerRules($rules, $type = '*')
    {
        foreach ($rules as $key => $val) {
            if (is_numeric($key)) $key = array_shift($val);
            if (empty($val))  continue;
            //[a]表分组名
            if (is_string($key) && strpos($key, '[') === 0) {
                $key = substr($key, 1, -1);
                //注册分组路由
                self::group($key, $val);
            } elseif (is_array($val)) {
                //注册路由
                self::rule([$key=>$val],'',$type);
            } else {
                //注册路由
                self::rule($key, $val, $type, [], []);
            }
        }
    }

    /**
     * 注册路由规则
     * 完整的路由  Route::rule(['new/:id'=>'News/read','blog/:name'=>['Blog/detail',POST, [], []]， ['new/:id', 'News/read', 'POST', [], []]], '', 'GET', [], [])
     * @access public
     * @param string    $rule 路由规则
     * @param string    $route 路由地址
     * @param string    $type 请求类型
     * @param array     $option 路由参数
     * @param array     $pattern 变量规则
     * @return void
     */
    static public  function rule($rule, $route = '', $type = '*', $option = [], $pattern = [])
    {
        //分组名
        $group = self::getGroup('name');
        if (!is_null($group)) {
            // 路由分组,参数项  匹配模式
            $option  = array_merge(self::getGroup('option'), $option);
            $pattern = array_merge(self::getGroup('pattern'), $pattern);
        }
        //外层路由类型
        $type = self::parseType($type);
        //记录路由类型
        $option['method'] = $type;
        //批量注册路由
        if (is_array($rule) && empty($route)) {
            foreach ($rule as $key => $val) {
                $rule_info = self::parseArrayRule($key, $val, $type, $option, $pattern);
                //设置路由
                self::setRule($rule_info[0], $rule_info[1], $rule_info[2], $rule_info[3], $rule_info[4], $group);
            }
            return;
        }
        self::setRule($rule, $route, $type, $option, $pattern, $group);
    }

    /**
     * 解析批量注册
     * @param $rule
     * @param $route
     * @param $type
     * @param $option
     * @param $pattern
     * @return array  批量注册信息
     */
    static protected function parseArrayRule($rule, $route, $type, $option, $pattern)
    {
        //路由以'new/:id'=>'News/read'形式给出
        if (!is_array($route))return [$rule, $route, $type, $option, $pattern];
        //路由['new1/:id/[:a]/{%b}$','News/read','post',['complete_match' => false,'ext'=>'shtml','modular'=>'module'],['id'=>'\d+']]
        //删除并返回第一个元素
        if (is_numeric($rule)) $rule = array_shift($route);
        //路由
        $route_path    = $route[0];
        //如果第二个参数是类型
        if(is_string($route[1]) && !empty($route[1])){
            $son_type = self::parseType($route[1]);
            $route[2]['method'] = $son_type;
            //子选项
            $son_option = $route[2];
            //子模式
            $son_pattern = $route[3];
        }else{
            //子类型
            $son_type = (isset($route[1]['method']) && !empty($route[1]['method']))?self::parseType($route[1]['method']):$type;
            $route[1]['method'] = $son_type;
            //子选项
            $son_option = empty($route[1])?[]:$route[1];
            //子模式
            $son_pattern = empty($route[2])?[]:$route[2];
        }
        //路由参数
        $son_option  = array_merge($option, isset($son_option)?$son_option:[]);
        //匹配模式
        $son_pattern = array_merge($pattern, isset($son_pattern) ? $son_pattern : []);
        return [$rule, $route_path, $son_type, $son_option, $son_pattern];
    }

    /**
     * 解析路由类型
     * @param string $type  路由类型
     * @return array|null
     */
    static protected function parseType($type)
    {
        if(empty($type)) return null;
        $type = strtolower($type);
        if (strpos($type, '|')) {
            $option['method'] = $type;
            $type = explode('|', $type);
        }elseif ($type == '*'){
            $type = ['get', 'post', 'put', 'delete', 'patch', 'head', 'options'];
        }else{
            $type = [$type];
        }
        return $type;
    }

    /**
     * 设置路由规则
     * 支持设置指定域名的路由
     * 分组路由，把路由注册金组
     * 各个类型的路由，若指定类型为 * 则默认注册所有类型的路由
     * @access public
     * @param string    $rule 路由规则
     * @param string    $route 路由地址
     * @param string    $type 请求类型
     * @param array     $option 路由参数
     * @param array     $pattern 变量规则
     * @param string    $group 所属分组
     * @return void
     */
    static protected  function setRule($rule, $route, $type = '*', $option = [], $pattern = [], $group = '')
    {
        //是否完整匹配路由规则
        if (!isset($option['complete_match']) || empty($option['complete_match'])) {
            // 是否完整匹配
            if (substr($rule, -1, 1) == '$') {
                $option['complete_match'] = true;
            }elseif (Conf::get('route_complete_match')){
                $option['complete_match'] = true;
            }
        }
        //已$结尾，取首字母
        if (substr($rule, -1, 1) == '$') $rule = substr($rule, 0, -1);
        if ($rule != '/' || $group) $rule = trim($rule, '/');
        //路由标识
        $key = $group ? $group . ($rule ? '/' . $rule : '') : $rule;
        //解析路由变量
        $vars = self::parseVar($rule);
        //后缀
        $suffix = isset($option['ext']) ? $option['ext'] : null;
        //每个路由对应的信息
        self::name($route, ['rule' => $key,'var' => $vars, 'type' => $type, 'domain' => self::$domain, 'suffix' => $suffix]);
        //模块拼接路由
        if (isset($option['modular']) && !empty($option['modular'])) $route = $option['modular'] . '/' . $route;
        //有分组
        if ($group) {
           self::saveGroupRoute($rule, $route, $option, $pattern, $group, $vars);
        } else {
            self::saveSingleRoute($rule, $route,$type, $option, $pattern, $vars);
        }
    }

    /**
     * 注册分组路由
     * @param string $rule  路由规则
     * @param string $route  路由
     * @param array $option  路由选项
     * @param array $pattern  路由模式
     * @param string $group  分组名
     * @param array $vars  路由参数
     */
    static protected function saveGroupRoute($rule, $route, $option, $pattern, $group, $vars)
    {
        //绑定域名
        if (self::$domain) {
            self::$rules['domain'][self::$domain]['group'][$group][$rule] = [
                'rule' => $rule,
                'route' => $route,
                'var' => $vars,
                'option' => $option,
                'pattern' => $pattern
            ];
        }
        //无域名绑定
        if(empty(self::$domain)){
            self::$rules['group'][$group][$rule] = [
                'rule' => $rule,
                'route' => $route,
                'var' => $vars,
                'option' => $option,
                'pattern' => $pattern
            ];
        }
    }

    /**
     * 注册单一路由
     * @param string $rule  路由规则
     * @param string $route  路由
     * @param array $type  路由类型
     * @param array $option  路由选项
     * @param array $pattern  路由模式
     * @param array $vars 路由参数
     */
    static protected function saveSingleRoute($rule, $route,$type, $option, $pattern, $vars)
    {
        //绑定域名
        if (self::$domain) {
            foreach ($type as $item){
                self::$rules['domain'][self::$domain][$item][$rule] = [
                    'rule' => $rule,
                    'route' => $route,
                    'var' => $vars,
                    'option' => $option,
                    'pattern' => $pattern
                ];
            }
        }
        //无域名绑定
        if(empty(self::$domain)){
            //类型下注册路由
            foreach ($type as $item){
                self::$rules[$item][$rule] = [
                    'rule' => $rule,
                    'route' => $route,
                    'var' => $vars,
                    'option' => $option,
                    'pattern' => $pattern
                ];
            }
        }
    }

    /**
     * 设置当前执行的参数信息
     * @access public
     * @param array    $options 参数信息
     * @return mixed
     */
    static protected  function setOption($options = [])
    {
        self::$option[] = $options;
    }

    /**
     * 获取当前执行的所有参数信息
     * @access public
     * @return array
     */
    static public  function getOption()
    {
        return self::$option;
    }

    /**
     * 获取当前的分组信息
     * @access public
     * @param string    $type 分组信息名称 name option pattern
     * @return mixed
     */
    static public  function getGroup($type)
    {
        if (isset(self::$group[$type])) {
            //分组存在，返回
            return self::$group[$type];
        } else {
            //要获取名字返回null其他的返回空
            return $type == 'name' ? null : [];
        }
    }

    /**
     * 设置当前的路由分组
     * @access public
     * @param string    $name 分组名称
     * @param array     $option 分组路由参数
     * @param array     $pattern 分组变量规则
     * @return void
     */
    static public  function setGroup($name, $option = [], $pattern = [])
    {
        self::$group['name']    = $name;
        self::$group['option']  = $option ?: [];
        self::$group['pattern'] = $pattern ?: [];
    }

    /**
     * 注册路由分组
     * @access public
     * @param string|array      $name 分组名称或者参数option[]
     * @param array|\Closure    $routes 路由地址或者匿名函数注册路由
     * @param array             $option 路由参数
     * @param array             $pattern 变量规则
     * @return void
     */
    static public  function group($name, $routes, $option = [], $pattern = [])
    {
        //如果是分组参数，获取分组名称
        if (is_array($name)) {
            $option = $name;
            $name   = isset($option['name']) ? $option['name'] : '';
        }
        // 当前是否存在分组
        $currentGroup = self::getGroup('name');
        //分组名+路由名=完整rule
        if ($currentGroup)  $name = $currentGroup . ($name ? '/' . ltrim($name, '/') : '');

        //注册分组路由
        if (!empty($name)) self::groupSet($name, $routes, $option, $pattern);
        //分组名为空,为普通的路由注册
        if(empty($name)) {
            if($routes instanceof \Closure){
                // 闭包注册
                $currentOption  = self::getGroup('option');
                $currentPattern = self::getGroup('pattern');
                self::setGroup('', array_merge($currentOption, $option), array_merge($currentPattern, $pattern));
                call_user_func_array($routes, []);
                self::setGroup($currentGroup, $currentOption, $currentPattern);
            }else{
                // 批量注册路由
                $type = (isset($option['method']) && !empty($option['method']))?$option['method']:'*';
                self::rule($routes, '', $type, $option, $pattern);
            }
        }
    }

    /**
     * 注册分组路由
     * @param string $name  分组名
     * @param \Closure $routes 匿名函数
     * @param array $option  分组选项
     * @param array $pattern  分组模式
     */
    static protected function groupSet($name, $routes, $option, $pattern)
    {
        //当前分组名
        $currentGroup = self::getGroup('name');
        //当前分组参数信息
        $currentOption  = self::getGroup('option');
        //当前路由模式
        $currentPattern = self::getGroup('pattern');
        //设置当前路由分组信息
        self::setGroup($name, array_merge($currentOption, $option), array_merge($currentPattern, $pattern));
        //闭包,调用Route::rule注册路由，会注册进当前分组
        //如果路由以匿名函数形式给出
        if ($routes instanceof \Closure) {
            call_user_func_array($routes, []);
        } else {
            self::rule($routes, '', '*', $option, $pattern);
        }
        //还原,等待下一次注册分组
        self::setGroup($currentGroup, $currentOption, $currentPattern);
        //要注册的分组名称非空，注册分组公共信息
        if ($currentGroup != $name) {
            if(empty(self::$domain)){
                self::$rules['group'][$name]['route']   = '';
                self::$rules['group'][$name]['var']     = self::parseVar($name);    //分组的参数
                self::$rules['group'][$name]['option']  = $option;
                self::$rules['group'][$name]['pattern'] = $pattern;
            }else{
                self::$rules['domain'][self::$domain]['group'][$name]['route']   = '';
                self::$rules['domain'][self::$domain]['group'][$name]['var']     = self::parseVar($name);    //分组的参数
                self::$rules['domain'][self::$domain]['group'][$name]['option']  = $option;
                self::$rules['domain'][self::$domain]['group'][$name]['pattern'] = $pattern;
            }
        }
    }

    /**
     * 注册路由
     * @access public
     * @param string    $rule 路由规则
     * @param string    $route 路由地址
     * @param array     $option 路由参数
     * @param array     $pattern 变量规则
     * @return void
     */
    static public  function anyone($rule, $route = '', $option = [], $pattern = [])
    {
        self::rule($rule, $route, '*', $option, $pattern);
    }

    /**
     * 注册GET路由
     * @access public
     * @param string    $rule 路由规则
     * @param string    $route 路由地址
     * @param array     $option 路由参数
     * @param array     $pattern 变量规则
     * @return void
     */
    static public  function get($rule, $route = '', $option = [], $pattern = [])
    {
        self::rule($rule, $route, 'GET', $option, $pattern);
    }

    /**
     * 注册POST路由
     * @access public
     * @param string    $rule 路由规则
     * @param string    $route 路由地址
     * @param array     $option 路由参数
     * @param array     $pattern 变量规则
     * @return void
     */
    static public  function post($rule, $route = '', $option = [], $pattern = [])
    {
        self::rule($rule, $route, 'POST', $option, $pattern);
    }

    /**
     * 注册PUT路由
     * @access public
     * @param string    $rule 路由规则
     * @param string    $route 路由地址
     * @param array     $option 路由参数
     * @param array     $pattern 变量规则
     * @return void
     */
    static public  function put($rule, $route = '', $option = [], $pattern = [])
    {
        self::rule($rule, $route, 'PUT', $option, $pattern);
    }

    /**
     * 注册DELETE路由
     * @access public
     * @param string    $rule 路由规则
     * @param string    $route 路由地址
     * @param array     $option 路由参数
     * @param array     $pattern 变量规则
     * @return void
     */
    static public  function delete($rule, $route = '', $option = [], $pattern = [])
    {
        self::rule($rule, $route, 'DELETE', $option, $pattern);
    }

    /**
     * 注册PATCH路由
     * @access public
     * @param string    $rule 路由规则
     * @param string    $route 路由地址
     * @param array     $option 路由参数
     * @param array     $pattern 变量规则
     * @return void
     */
    static public  function patch($rule, $route = '', $option = [], $pattern = [])
    {
        self::rule($rule, $route, 'PATCH', $option, $pattern);
    }

    /**
     * 注册资源路由
     * 一个资源  的请求类型(操作)  get(edit/create/read)  put(save/update) delete(delete)，
     * 每一种操作都要注册响应的路由
     * @access public
     * @param string    $rule 路由规则
     * @param string    $route 路由地址
     * @param array     $option 路由参数
     * @param array     $pattern 变量规则
     * @return void
     */
    static public  function resource($rule, $route = '', $option = [], $pattern = [])
    {
        if (is_array($rule)) {
            //key=>rule val=>route
            foreach ($rule as $key => $val) {
                //返回3个元素，并将[]插入新元素中
                if (is_array($val)) list($val, $option, $pattern) = array_pad($val, 3, []);
                self::resource($key, $val, $option, $pattern);
            }
        } else {
            //嵌套资源 Route::resource('blog.comment','index/comment');
            if (strpos($rule, '.')) {
                // 是否多个资源
                $array = explode('.', $rule);
                //获取资源的操作方法
                $last  = array_pop($array);
                $item  = [];
                //拼接资源的路由
                foreach ($array as $val) {
                    //拼接资源和参数 blog/:8   articl/:articl_id
                    $item[] = $val . '/:' . (isset($option['var'][$val]) ? $option['var'][$val] : $val . '_id');
                }
                //  blog/:8/articl/:articl_id/comment
                $rule = implode('/', $item) . '/' . $last;
            }
            // 注册资源操作方法,路由
            foreach (self::$rest as $key => $restful) {
                //限制操作方法
                if(isset($option['only']) && !in_array($key, $option['only'])) continue;
                if(isset($option['except']) && in_array($key, $option['except'])) continue;
                //匹配资源操作方法参数
                if (isset($last) && strpos($restful['param'], ':id') && isset($option['var'][$last])) {
                    //根据操作方法名 匹配参数  $restful['param'] => /:id
                    $restful['param'] = str_replace(':id', ':' . $option['var'][$last], $restful['param']);
                } elseif (strpos($restful['param'], ':id') && isset($option['var'][$rule])) {
                    //根据路由规则  匹配参数
                    $restful['param'] = str_replace(':id', ':' . $option['var'][$rule], $restful['param']);
                }
                //资源名+资源参数+资源操作方法+操作方法参数 路由标识
                $resource_rule = ltrim($rule . $restful['param'], '/') .'$';
                //资源路由
                $resource_route = $route . '/' . $restful['operate'];
                //restful 参数
                $option['rest'] = $key;
                //restful 操作信息
                $option['restful'] = $restful;
                self::rule($resource_rule, $resource_route, $restful['request'], $option, $pattern);
            }
        }
    }

    /**
     * 注册命令路由 命令的控制器对应不同的请求后缀
     * @access public
     * @param string    $rule 路由规则
     * @param string    $route 路由地址
     * @param array     $option 路由参数
     * @param array     $pattern 变量规则
     * @return void
     */
    static public  function command($rule, $route = '', $option = [], $pattern = [])
    {
        // 'put' => 'put'
        foreach (self::$methodPrefix as $type => $method) {
            self::$type($rule . '/:controller', $route . '/' . $method . ':controller', $option, $pattern);
        }
    }

    /**
     * 注册别名路由
     * Route::alias('user','index/User')
     * @access public
     * @param string|array  $rule 路由别名
     * @param string        $route 路由地址
     * @param array         $option 路由参数
     * @return void
     */
    static public  function alias($rule = null, $route = '', $option = [])
    {
        if (is_array($rule)) {
            self::$rules['alias'] = array_merge(self::$rules['alias'], $rule);
        } else {
            self::$rules['alias'][$rule] = $option ? [$route, $option] : $route;
        }
    }

    /**
     * 设置不同请求类型下面的方法前缀
     * @access public
     * @param string    $method 请求类型
     * @param string    $prefix 类型前缀
     * @return void
     */
    static public  function setMethodPrefix($method, $prefix = '')
    {
        if (is_array($method)) {
            //键名小写
            self::$methodPrefix = array_merge(self::$methodPrefix, array_change_key_case($method));
        } else {
            //'put' => 'putttt'
            self::$methodPrefix[strtolower($method)] = $prefix;
        }
    }

    /**
     * rest方法定义和修改
     * @access public
     * @param string        $name 方法名称
     * @param array|bool    $resource 资源
     * @return void
     */
    static public  function rest($name, $resource = [])
    {
        if (is_array($name)) {
            self::$rest = array_merge(self::$rest, $name);
        } else {
            self::$rest[$name] = $resource;
        }
    }

    /**
     * 匹配路由规则失败的路由注册
     * @access public
     * @param string    $route 路由地址
     * @param string    $type 请求类型
     * @param array     $option 路由参数
     * @return void
     */
    static public  function miss($route, $type = '*', $option = [])
    {
        self::rule('__miss__', $route, $type, $option, []);
    }

    /**
     * 注册一个自动解析的URL路由
     * @access public
     * @param string    $route 路由地址
     * @return void
     */
    static public  function auto($route)
    {
        self::rule('__auto__', $route, '*', [], []);
    }

    /**
     * 获取或者批量设置路由定义
     * Route::rule('new/:id','News/read','GET|POST')
     * @access public
     * @param mixed $rules 请求类型或者路由定义数组
     * @return array
     */
    static public  function rules($rules = '')
    {
        if (is_array($rules)) {
            //批量注册路由
            self::$rules = $rules;
        } elseif ($rules) {
            //获取现有路由，或者获取指定路由
            return ($rules === true) ? self::$rules : self::$rules[strtolower($rules)];
        } else {
            //空字符串，销毁 $rules['pattern'], $rules['alias'], $rules['domain'], $rules['name']
            $rules = self::$rules;
            unset($rules['pattern'], $rules['alias'], $rules['domain'], $rules['name']);
            return $rules;
        }
    }

    /**
     * 检测子域名部署
     * @access public
     * @param Request   $request Request请求对象
     * @param array     $currentRules 当前路由规则
     * @param string    $method 请求类型
     * @return void
     */
    static public  function checkDomain($request, &$currentRules, $method = 'get')
    {
        // 域名规则
        $rules = self::$rules['domain'];
        // 开启子域名部署 支持二级和三级域名
        if (!empty($rules)) {
            $host = $request->host();
            //域名配置
            $domainInfo = self::getHostRule($rules, $host);
            //域名路由配置
            $item = $domainInfo['item'];
            //二级 三级 域名
            $panDomain = $domainInfo['domain'];
            //域名配置空
            if(empty($item)) return;
            //保存泛域名 二级 三级 域名
            if (!is_null($panDomain)) $request->route(['__domain__' => $panDomain]);
            if (isset($item['[bind]'])) {
                //域名绑定
                self::domainBind($item['[bind]'], $request, $host, $panDomain);
            } else {
                //非绑定配置
                self::$domainRule = $item;
                $currentRules  = isset($item[$method]) ? $item[$method] : $item['*'];
            }
        }
    }

    /**
     * 域名绑定处理
     * @param $bind array 域名绑定信息
     * @param $request  object 请求对象单例
     * @param $host  string 完整域名
     * @param $panDomain string 要绑定的 二级  三级 域名
     */
    static protected function domainBind($bind, $request, $host, $panDomain)
    {
        // 解析子域名部署规则
        list($rule, $option, $pattern) = $bind;
        // https检测
        if (!empty($option['https']) && !$request->isSsl()) throw new HttpException(404, 'must use https request:' . $host);
        //解析 路由 路径 参数
        if (strpos($rule, '?')) {
            // 解析路由
            $array  = parse_url($rule);
            //路径
            $path = $array['path'];
            //参数
            parse_str($array['query'], $params);
            if (!is_null($panDomain)) {
                //搜索某个键值，并返回对应的键名
                $pos = array_search('*', $params);
                // 泛域名作为参数 绑定到次级域名
                if ($pos !== false) $params[$pos] = $panDomain;
            }
            //参数并入 $_GET
            $_GET = array_merge($_GET, $params);
        } else {
            $path = $rule;
        }
        //路由绑定
        if (strpos($path, '\\') === 0) {
            // 绑定到命名空间 例如 \app\index\behavior
            self::$bind = ['type' => 'namespace', 'namespace' => $path];
        } elseif (strpos($path, '@') === 0) {
            // 绑定到类 例如 @app\index\controller\User
            self::$bind = ['type' => 'class', 'class' => substr($path, 1)];
        } else {
            // 绑定到模块/命令 例如 index/user
            self::$bind = ['type' => 'module', 'module' => $path];
        }
        self::$domainBind = true;
    }

    /**
     * 获取域名路由配置
     * 结合框架配置 获取  three.www.dragon-god.com:80 二级  三级 域名  three.www
     * 首先查找 除开 顶级和一级域名  dragon-god.com 的  下级域名对应的配置  无结果 顺次查找 泛三级域名 *.www  泛二级域名 * 的配置
     * @param array $rules  域名路由规则
     * @param string $host  域名
     * @return array  域名配置
     */
    static protected function getHostRule($rules ,$host)
    {
        // 完整域名或者IP配置
        if (isset($rules[$host])) return $rules[$host];
        //非完整域名
        $rootDomain = Conf::get('URL_DOMAIN_ROOT');  //根域名
        if ($rootDomain) {
            // 配置域名根 例如 thinkphp.cn 163.com.cn 如果是国家级域名 com.cn net.cn 之类的域名需要配置
            $domain = explode('.', rtrim(stristr($host, $rootDomain, true), '.')); //搜索字符串在另一字符串中的第一次出现，返回剩余部分
        } else {
            //除了最后两个元素外的所有元素  去掉顶级域名 一级域名
            $domain = explode('.', $host, -2);
        }

        // 子域名不存在
        if(empty($domain)) return [];
        // 当前子域名
        $subDomain = implode('.', $domain);
        //二级域名三级域名
        self::$subDomain = $subDomain;
        //二级域名
        $domain2  = array_pop($domain);
        //三级域名
        if ($domain) $domain3 = array_pop($domain);
        // 子域名配置
        if ($subDomain && isset($rules[$subDomain])) {
            $item = $rules[$subDomain];
        } elseif (isset($rules['*.' . $domain2]) && !empty($domain3)) {
            // 泛三级域名 *.www  对应所有三级域名
            $item = $rules['*.' . $domain2];
            //三级域名
            $panDomain = $domain3;
        } elseif (isset($rules['*']) && !empty($domain2)) {
            // 泛二级域名  对应所有二级域名
            if ($domain2 != 'www') {
                $item = $rules['*'];
                $panDomain = $domain2;
            }
        }
        return [
            'item'    => !empty($item)?$item:[],
            'domain'  => isset($panDomain)?$panDomain:null,
        ];
    }

    /**
     * 检测URL路由
     * @access public
     * @param Request   $request Request请求对象
     * @param string    $url URL地址
     * @param string    $depr URL分隔符
     * @param bool      $checkDomain 是否检测域名规则
     * @return false|array
     */
    static public  function check($request, $url, $depr = '/', $checkDomain = false)
    {
        // 分隔符替换 确保路由定义使用统一的分隔符 |
        $url = str_replace($depr, '|', $url);
        //检查此URL是否是别名，并获取别名对应的配置
        if (strpos($url, '|') && isset(self::$rules['alias'][strstr($url, '|', true)])) {
            // 检测路由是否为别名
            $result = self::checkRouteAlias($request, $url, $depr);
            if ($result !== false) return $result;
        }
        //此URL restful  操作类型
        $method = strtolower($request->method());
        // 获取当前请求类型的路由规则
        $rules = self::$rules[$method];
        // 检测域名部署 包括域名绑定  域名配置信息
        if ($checkDomain) self::checkDomain($request, $rules, $method);
        // 检测URL绑定
        $return = self::checkUrlBind($url, $rules, $depr);
        if ($return !== false) return $return;
        if ('|' != $url) {
            $url = rtrim($url, '|');
        }
        $item = str_replace('|', '/', $url);
        if (isset($rules[$item])) {
            // 静态路由规则检测
            $rule = $rules[$item];
            if (true === $rule) {
                $rule = self::getRouteExpress($item);
            }
            if (!empty($rule['route']) && self::checkOption($rule['option'], $request)) {
                self::setOption($rule['option']);
                return self::parseRule($item, $rule['route'], $url, $rule['option']);
            }
        }
        // 路由规则检测
        if (!empty($rules)) {
            return self::checkRoute($request, $rules, $url, $depr);
        }
        return false;
    }

    static private function getRouteExpress($key)
    {
        return self::$domainRule ? self::$domainRule['*'][$key] : self::$rules['*'][$key];
    }

    /**
     * 检测路由规则
     * @access private
     * @param Request   $request
     * @param array     $rules 路由规则
     * @param string    $url URL地址
     * @param string    $depr URL分割符
     * @param string    $group 路由分组名
     * @param array     $options 路由参数（分组）
     * @return mixed
     */
    static private function checkRoute($request, $rules, $url, $depr = '/', $group = '', $options = [])
    {
        foreach ($rules as $key => $item) {
            if (true === $item) {
                $item = self::getRouteExpress($key);
            }
            if (!isset($item['rule'])) {
                continue;
            }
            $rule    = $item['rule'];
            $route   = $item['route'];
            $vars    = $item['var'];
            $option  = $item['option'];
            $pattern = $item['pattern'];

            // 检查参数有效性
            if (!self::checkOption($option, $request)) {
                continue;
            }

            if (isset($option['ext'])) {
                // 路由ext参数 优先于系统配置的URL伪静态后缀参数
                $url = preg_replace('/\.' . $request->ext() . '$/i', '', $url);
            }

            if (is_array($rule)) {
                // 分组路由
                $pos = strpos(str_replace('<', ':', $key), ':');
                if (false !== $pos) {
                    $str = substr($key, 0, $pos);
                } else {
                    $str = $key;
                }
                if (is_string($str) && $str && 0 !== strpos(str_replace('|', '/', $url), $str)) {
                    continue;
                }
                self::setOption($option);
                $result = self::checkRoute($request, $rule, $url, $depr, $key, $option);
                if (false !== $result) {
                    return $result;
                }
            } elseif ($route) {
                if ('__miss__' == $rule || '__auto__' == $rule) {
                    // 指定特殊路由
                    $var    = trim($rule, '__');
                    ${$var} = $item;
                    continue;
                }
                if ($group) {
                    $rule = $group . ($rule ? '/' . ltrim($rule, '/') : '');
                }

                self::setOption($option);
                if (isset($options['bind_model']) && isset($option['bind_model'])) {
                    $option['bind_model'] = array_merge($options['bind_model'], $option['bind_model']);
                }
                $result = self::checkRule($rule, $route, $url, $pattern, $option, $depr);
                if (false !== $result) {
                    return $result;
                }
            }
        }
        if (isset($auto)) {
            // 自动解析URL地址
            return self::parseUrl($auto['route'] . '/' . $url, $depr);
        } elseif (isset($miss)) {
            // 未匹配所有路由的路由规则处理
            return self::parseRule('', $miss['route'], $url, $miss['option']);
        }
        return false;
    }

    /**
     * 检测路由别名
     * @access private
     * @param Request   $request
     * @param string    $url URL地址
     * @param string    $depr URL分隔符
     * @return mixed
     */
    static private function checkRouteAlias($request, $url, $depr)
    {
        $array = explode('|', $url);
        //获取并删除第一个元素
        $alias = array_shift($array);
        $item  = self::$rules['alias'][$alias];
        if (is_array($item)) {
            list($rule, $option) = $item;
            //模块/命令/控制器 =>  Alias/控制器
            $ctrl = $array[0];
            //授权操作
            if (isset($option['allow']) && !in_array($ctrl, $option['allow'])) return false;
            //禁止操作
            if (isset($option['except']) && in_array($ctrl, $option['except'])) return false;
            //指定控制器
            if (isset($option['method'][$ctrl])) $option['method'] = $option['method'][$ctrl];
        } else {
            $rule = $item;
        }
        //拼接控制器 参数
        $bind = implode('|', $array);
        // 参数有效性检查
        if (isset($option) && !self::checkOption($option, $request)) {
            // 路由不匹配
            return false;
        } elseif (strpos($rule, '\\') === 0) {
            // 路由到类
            return self::bindToClass($bind, substr($rule, 1), $depr);
        } elseif (strpos($rule, '@') === 0) {
            // 路由到命令类
            return self::bindToCommand($bind, substr($rule, 1), $depr);
        } else {
            // 路由到模块/控制器
            return self::bindToModule($bind, $rule, $depr);
        }
    }

    /**
     * 检测URL绑定
     * @access private
     * @param string    $url URL地址
     * @param array     $rules 路由规则
     * @param string    $depr URL分隔符
     * @return mixed
     */
    static private function checkUrlBind(&$url, &$rules, $depr = '/')
    {
        //当前访问路由是否有绑定信息
        if (!empty(self::$bind)) {
            $type = self::$bind['type'];
            $bind = self::$bind[$type];
            // 记录绑定信息
            Dispatch::$debug && Log::log('[ BIND ] ' . var_export($bind, true), 'info');
            // 如果有URL绑定 则进行绑定检测
            switch ($type) {
                case 'class':
                    // 绑定到类
                    return self::bindToClass($url, $bind, $depr);
                case 'command':
                    // 绑定到控制器类
                    return self::bindToCommand($url, $bind, $depr);
                case 'namespace':
                    // 绑定到命名空间
                    return self::bindToNamespace($url, $bind, $depr);
            }
        }
        return false;
    }

    /**
     * 绑定到类
     * @access public
     * @param string    $url URL地址
     * @param string    $class 类名（带命名空间）
     * @param string    $depr URL分隔符
     * @return array
     */
    static public  function bindToClass($url, $class, $depr = '/')
    {
        $url = str_replace($depr, '|', $url);
        //截取成2个元素的数组  控制器与参数
        $array  = explode('|', $url, 2);
        //控制器
        $ctrl = !empty($array[0]) ? $array[0] : Conf::get('default_controller');
        //参数  设置请求参数
        if (!empty($array[1])) self::parseUrlParams($array[1]);
        return ['type' => 'ctrl', 'ctrl' => [$class, $ctrl]];
    }

    /**
     * 绑定到命名空间
     * @access public
     * @param string    $url URL地址
     * @param string    $namespace 命名空间
     * @param string    $depr URL分隔符
     * @return array
     */
    static public  function bindToNamespace($url, $namespace, $depr = '/')
    {
        $url    = str_replace($depr, '|', $url);
        $array  = explode('|', $url, 3);
        $class  = !empty($array[0]) ? $array[0] : Conf::get('default_command');
        $method = !empty($array[1]) ? $array[1] : Conf::get('default_controller');
        if (!empty($array[2])) self::parseUrlParams($array[2]);
        return ['type' => 'ctrl', 'ctrl' => [$namespace . '\\' . Visit::parseName($class, 1), $method]];
    }

    /**
     * 绑定到控制器类
     * @access public
     * @param string    $url URL地址
     * @param string    $command 控制器名 （支持带模块名 index/user ）
     * @param string    $depr URL分隔符
     * @return array
     */
    static public  function bindToCommand($url, $command, $depr = '/')
    {
        $url = str_replace($depr, '|', $url);
        $array  = explode('|', $url, 2);
        $ctrl = !empty($array[0]) ? $array[0] : Conf::get('default_controller');
        if (!empty($array[1])) {
            self::parseUrlParams($array[1]);
        }
        return ['type' => 'command', 'command' => $command . '/' . $ctrl];
    }

    /**
     * 绑定到模块/控制器
     * @access public
     * @param string    $url URL地址
     * @param string    $controller 控制器类名（带命名空间）
     * @param string    $depr URL分隔符
     * @return array
     */
    static public  function bindToModule($url, $controller, $depr = '/')
    {
        $url = str_replace($depr, '|', $url);
        $array  = explode('|', $url, 2);
        $action = !empty($array[0]) ? $array[0] : Conf::get('default_controller');
        if (!empty($array[1])) {
            self::parseUrlParams($array[1]);
        }
        return ['type' => 'module', 'module' => $controller . '/' . $action];
    }

    /**
     * 路由参数有效性检查
     * @access private
     * @param array     $option 路由参数
     * @param Request   $request Request对象
     * @return bool
     */
    static private function checkOption($option, $request)
    {
        //请求方法检测  查找字符串在另一字符串中最后一次出现的位置（不区分大小写）
        if((isset($option['method']) && is_string($option['method']) && stripos($option['method'], $request->method()) === false)) return false;
        // Ajax检测
        if((isset($option['ajax']) && $option['ajax'] && !$request->isAjax())) return false;
        // 非Ajax检测
        if((isset($option['ajax']) && !$option['ajax'] && $request->isAjax())) return false;
        //Pjax检测
        if((isset($option['pjax']) && $option['pjax'] && !$request->isPjax())) return false;
        // 伪静态后缀检测
        if((isset($option['ext']) && stripos('|' . $option['ext'] . '|', '|' . $request->ext() . '|') === false)) return false;
        //禁用的伪静态后缀检测
        if((isset($option['deny_ext']) && stripos('|' . $option['deny_ext'] . '|', '|' . $request->ext() . '|') !== false)) return false;
        //域名检测
        if((isset($option['domain']) && !in_array($option['domain'], [$_SERVER['HTTP_HOST'], self::$subDomain]))) return false;
        //https检测
        if((isset($option['https']) && $option['https'] && !$request->isSsl())) return false;
        //非https检测
        if((isset($option['https']) && !$option['https'] && $request->isSsl())) return false;
        //行为检测
        if((!empty($option['before_behavior']) && Hook::exec($option['before_behavior']) === false)) return false;
        //自定义检测
        if((!empty($option['callback']) && is_callable($option['callback']) && call_user_func($option['callback']) === false)) return false;
        //检测通过
        return true;
    }

    /**
     * 检测路由规则
     * @access private
     * @param string    $rule 路由规则
     * @param string    $route 路由地址
     * @param string    $url URL地址
     * @param array     $pattern 变量规则
     * @param array     $option 路由参数
     * @param string    $depr URL分隔符（全局）
     * @return array|false
     */
    static private function checkRule($rule, $route, $url, $pattern, $option, $depr)
    {
        // 检查完整规则定义
        if (isset($pattern['__url__']) && !preg_match('/^' . $pattern['__url__'] . '/', str_replace('|', $depr, $url))) {
            return false;
        }
        // 检查路由的参数分隔符
        if (isset($option['param_depr'])) {
            $url = str_replace(['|', $option['param_depr']], [$depr, '|'], $url);
        }

        $len1 = substr_count($url, '|');
        $len2 = substr_count($rule, '/');
        // 多余参数是否合并
        $merge = !empty($option['merge_extra_vars']);
        if ($merge && $len1 > $len2) {
            $url = str_replace('|', $depr, $url);
            $url = implode('|', explode($depr, $url, $len2 + 1));
        }

        if ($len1 >= $len2 || strpos($rule, '[')) {
            if (!empty($option['complete_match'])) {
                // 完整匹配
                if (!$merge && $len1 != $len2 && (false === strpos($rule, '[') || $len1 > $len2 || $len1 < $len2 - substr_count($rule, '['))) {
                    return false;
                }
            }
            $pattern = array_merge(self::$rules['pattern'], $pattern);
            if (false !== $match = self::match($url, $rule, $pattern, $merge)) {
                // 匹配到路由规则
                return self::parseRule($rule, $route, $url, $option, $match, $merge);
            }
        }
        return false;
    }

    /**
     * 解析模块的URL地址 [模块/控制器/操作?]参数1=值1&参数2=值2...
     * @access public
     * @param string    $url URL地址
     * @param string    $depr URL分隔符
     * @param bool      $autoSearch 是否自动深度搜索控制器
     * @return array
     */
    static public  function parseUrl($url, $depr = '/', $autoSearch = false)
    {

        if (isset(self::$bind['module'])) {
            $bind = str_replace('/', $depr, self::$bind['module']);
            // 如果有模块/控制器绑定
            $url = $bind . ('.' != substr($bind, -1) ? $depr : '') . ltrim($url, $depr);
        }
        $url              = str_replace($depr, '|', $url);
        list($path, $var) = self::parseUrlPath($url);
        $route            = [null, null, null];
        if (isset($path)) {
            // 解析模块
            $module = Conf::get('app_multi_module') ? array_shift($path) : null;
            if ($autoSearch) {
                // 自动搜索控制器
                $dir    = MODULE . ($module ? $module . SP : '') . Conf::get('URL_CONTROLLER_LAYER');
                $suffix = Dispatch::$suffix || Conf::get('controller_suffix') ? ucfirst(Conf::get('url_controller_layer')) : '';
                $item   = [];
                $find   = false;
                foreach ($path as $val) {
                    $item[] = $val;
                    $file   = $dir . SP . str_replace('.', SP, $val) . $suffix . EXT;
                    $file   = pathinfo($file, PATHINFO_DIRNAME) . SP . Visit::parseName(pathinfo($file, PATHINFO_FILENAME), 1) . EXT;
                    if (is_file($file)) {
                        $find = true;
                        break;
                    } else {
                        $dir .= SP . Visit::parseName($val);
                    }
                }
                if ($find) {
                    $controller = implode('.', $item);
                    $path       = array_slice($path, count($item));
                } else {
                    $controller = array_shift($path);
                }
            } else {
                // 解析控制器
                $controller = !empty($path) ? array_shift($path) : null;
            }
            // 解析操作
            $action = !empty($path) ? array_shift($path) : null;
            // 解析额外参数
            self::parseUrlParams(empty($path) ? '' : implode('|', $path));
            // 封装路由
            $route = [$module, $controller, $action];
            // 检查地址是否被定义过路由
            $name  = strtolower($module . '/' . Visit::parseName($controller, 1) . '/' . $action);
            $name2 = '';
            if (empty($module) || isset($bind) && $module == $bind) {
                $name2 = strtolower(Visit::parseName($controller, 1) . '/' . $action);
            }

            if (isset(self::$rules['name'][$name]) || isset(self::$rules['name'][$name2])) {
                throw new HttpException(404, 'invalid request:' . str_replace('|', $depr, $url));
            }
        }
        return ['type' => 'module', 'module' => $route];
    }

    /**
     * 解析URL的pathinfo参数和变量
     * @access private
     * @param string    $url URL地址
     * @return array
     */
    static private function parseUrlPath($url)
    {
        // 分隔符替换 确保路由定义使用统一的分隔符
        $url = str_replace('|', '/', $url);
        $url = trim($url, '/');
        $var = [];
        if (false !== strpos($url, '?')) {
            // [模块/控制器/操作?]参数1=值1&参数2=值2...
            $info = parse_url($url);
            $path = explode('/', $info['path']);
            parse_str($info['query'], $var);
        } elseif (strpos($url, '/')) {
            // [模块/控制器/操作]
            $path = explode('/', $url);
        } else {
            $path = [$url];
        }
        return [$path, $var];
    }

    /**
     * 检测URL和规则路由是否匹配
     * @access private
     * @param string    $url URL地址
     * @param string    $rule 路由规则
     * @param array     $pattern 变量规则
     * @return array|false
     */
    static private function match($url, $rule, $pattern)
    {
        $m2 = explode('/', $rule);
        $m1 = explode('|', $url);

        $var = [];
        foreach ($m2 as $key => $val) {
            // val中定义了多个变量 <id><name>
            if (false !== strpos($val, '<') && preg_match_all('/<(\w+(\??))>/', $val, $matches)) {
                $value   = [];
                $replace = [];
                foreach ($matches[1] as $name) {
                    if (strpos($name, '?')) {
                        $name      = substr($name, 0, -1);
                        $replace[] = '(' . (isset($pattern[$name]) ? $pattern[$name] : '\w+') . ')?';
                    } else {
                        $replace[] = '(' . (isset($pattern[$name]) ? $pattern[$name] : '\w+') . ')';
                    }
                    $value[] = $name;
                }
                $val = str_replace($matches[0], $replace, $val);
                if (preg_match('/^' . $val . '$/', isset($m1[$key]) ? $m1[$key] : '', $match)) {
                    array_shift($match);
                    foreach ($value as $k => $name) {
                        if (isset($match[$k])) {
                            $var[$name] = $match[$k];
                        }
                    }
                    continue;
                } else {
                    return false;
                }
            }

            if (0 === strpos($val, '[:')) {
                // 可选参数
                $val      = substr($val, 1, -1);
                $optional = true;
            } else {
                $optional = false;
            }
            if (0 === strpos($val, ':')) {
                // URL变量
                $name = substr($val, 1);
                if (!$optional && !isset($m1[$key])) {
                    return false;
                }
                if (isset($m1[$key]) && isset($pattern[$name])) {
                    // 检查变量规则
                    if ($pattern[$name] instanceof \Closure) {
                        $result = call_user_func_array($pattern[$name], [$m1[$key]]);
                        if (false === $result) {
                            return false;
                        }
                    } elseif (!preg_match('/^' . $pattern[$name] . '$/', $m1[$key])) {
                        return false;
                    }
                }
                $var[$name] = isset($m1[$key]) ? $m1[$key] : '';
            } elseif (!isset($m1[$key]) || 0 !== strcasecmp($val, $m1[$key])) {
                return false;
            }
        }
        // 成功匹配后返回URL中的动态变量数组
        return $var;
    }

    /**
     * 解析规则路由
     * @access private
     * @param string    $rule 路由规则
     * @param string    $route 路由地址
     * @param string    $pathinfo URL地址
     * @param array     $option 路由参数
     * @param array     $matches 匹配的变量
     * @return array
     */
    static private function parseRule($rule, $route, $pathinfo, $option = [], $matches = [])
    {
        $request = RequestRegistry::getRequest();
        // 解析路由规则
        if ($rule) {
            $rule = explode('/', $rule);
            // 获取URL地址中的参数
            $paths = explode('|', $pathinfo);
            foreach ($rule as $item) {
                $fun = '';
                if (0 === strpos($item, '[:')) {
                    $item = substr($item, 1, -1);
                }
                if (0 === strpos($item, ':')) {
                    $var           = substr($item, 1);
                    $matches[$var] = array_shift($paths);
                } else {
                    // 过滤URL中的静态变量
                    array_shift($paths);
                }
            }
        } else {
            $paths = explode('|', $pathinfo);
        }

        // 获取路由地址规则
        if (is_string($route) && isset($option['prefix'])) {
            // 路由地址前缀
            $route = $option['prefix'] . $route;
        }
        // 替换路由地址中的变量
        if (is_string($route) && !empty($matches)) {
            foreach ($matches as $key => $val) {
                if (false !== strpos($route, ':' . $key)) {
                    $route = str_replace(':' . $key, $val, $route);
                }
            }
        }

        // 绑定模型数据
        if (isset($option['bind_model'])) {
            $bind = [];
            foreach ($option['bind_model'] as $key => $val) {
                if ($val instanceof \Closure) {
                    $result = call_user_func_array($val, [$matches]);
                } else {
                    if (is_array($val)) {
                        $fielSP    = explode('&', $val[1]);
                        $model     = $val[0];
                        $exception = isset($val[2]) ? $val[2] : true;
                    } else {
                        $fielSP    = ['id'];
                        $model     = $val;
                        $exception = true;
                    }
                    $where = [];
                    $match = true;
                    foreach ($fielSP as $field) {
                        if (!isset($matches[$field])) {
                            $match = false;
                            break;
                        } else {
                            $where[$field] = $matches[$field];
                        }
                    }
                    if ($match) {
                        $query  = strpos($model, '\\') ? $model::where($where) : Visit::model($model)->where($where);
                        $result = $query->failException($exception)->find();
                    }
                }
                if (!empty($result)) {
                    $bind[$key] = $result;
                }
            }
            $request->bind($bind);
        }

        // 解析额外参数
        self::parseUrlParams(empty($paths) ? '' : implode('|', $paths), $matches);
        // 记录匹配的路由信息
        $request->routeInfo(['rule' => $rule, 'route' => $route, 'option' => $option, 'var' => $matches]);

        // 检测路由after行为
        if (!empty($option['after_behavior'])) {
            if ($option['after_behavior'] instanceof \Closure) {
                $result = call_user_func_array($option['after_behavior'], []);
            } else {
                foreach ((array) $option['after_behavior'] as $behavior) {
                    $result = Hook::exec($behavior, '');
                    if (!is_null($result)) {
                        break;
                    }
                }
            }
            // 路由规则重定向
            if ($result instanceof Response) {
                return ['type' => 'response', 'response' => $result];
            } elseif (is_array($result)) {
                return $result;
            }
        }

        if ($route instanceof \Closure) {
            // 执行闭包
            $result = ['type' => 'function', 'function' => $route];
        } elseif (0 === strpos($route, '/') || strpos($route, '://')) {
            // 路由到重定向地址
            $result = ['type' => 'redirect', 'url' => $route, 'status' => isset($option['status']) ? $option['status'] : 301];
        } elseif (false !== strpos($route, '\\')) {
            // 路由到方法
            list($path, $var) = self::parseUrlPath($route);
            $route            = str_replace('/', '@', implode('/', $path));
            $method           = strpos($route, '@') ? explode('@', $route) : $route;
            $result           = ['type' => 'method', 'method' => $method, 'var' => $var];
        } elseif (0 === strpos($route, '@')) {
            // 路由到控制器
            $route             = substr($route, 1);
            list($route, $var) = self::parseUrlPath($route);
            $result            = ['type' => 'controller', 'controller' => implode('/', $route), 'var' => $var];
            $request->action(array_pop($route));
            $request->controller($route ? array_pop($route) : Conf::get('default_controller'));
            $request->module($route ? array_pop($route) : Conf::get('default_module'));
            Dispatch::$modulePath = MODULE . (Conf::get('app_multi_module') ? $request->module() . SP : '');
        } else {
            // 路由到模块/控制器/操作
            $result = self::parseModule($route);
        }
        // 开启请求缓存
        if ($request->isGet() && isset($option['cache'])) {
            $cache = $option['cache'];
            if (is_array($cache)) {
                list($key, $expire) = $cache;
            } else {
                $key    = str_replace('|', '/', $pathinfo);
                $expire = $cache;
            }
            $request->cache($key, $expire);
        }
        return $result;
    }

    /**
     * 解析URL地址为 模块/控制器/操作
     * @access private
     * @param string    $url URL地址
     * @return array
     */
    static private function parseModule($url)
    {
        list($path, $var) = self::parseUrlPath($url);
        $action           = array_pop($path);
        $controller       = !empty($path) ? array_pop($path) : null;
        $module           = Conf::get('app_multi_module') && !empty($path) ? array_pop($path) : null;
        $method           = RequestRegistry::getRequest()->method();
        if (Conf::get('use_action_prefix') && !empty(self::$methodPrefix[$method])) {
            // 操作方法前缀支持
            $action = 0 !== strpos($action, self::$methodPrefix[$method]) ? self::$methodPrefix[$method] . $action : $action;
        }
        // 设置当前请求的路由变量
        RequestRegistry::getRequest()->route($var);
        // 路由到模块/控制器/操作
        return ['type' => 'module', 'module' => [$module, $controller, $action], 'convert' => false];
    }

    /**
     * 解析URL地址中的参数Request对象
     * @access private
     * @param array     $var 变量
     * @return void
     */
    static private function parseUrlParams($url, &$var = [])
    {
        if ($url) {
            if (Conf::get('url_param_type')) {
                //按顺序解析参数 [0] => id [1] => 5
                $var += explode('|', $url);
            } else {
                //按成对解析参数  [id] => 5  执行一个正则表达式搜索并且使用一个回调函数进行替处理
                preg_replace_callback('/(\w+)\|([^\|]+)/', function ($match) use (&$var) {
                    $var[$match[1]] = strip_tags($match[2]);    //strip_tags() 函数剥去字符串中的 HTML、XML 以及 PHP 的标签
                }, $url);
            }
        }
        // 设置当前请求的参数
        RequestRegistry::getRequest()->route($var);
    }

    /**
     * 分析路由规则中的变量
     * 'blog/read/:name/[:ff]/{%qwer}/{qq}'
     * @param $rule
     * @return array
     */
    static private function parseVar($rule)
    {
        // 提取路由规则中的变量
        $var = [];
        foreach (explode('/', $rule) as $val) {
            $optional = false;
            //匹配包括下划线的任何单词字符 'blog/read/{%qwer}{ccc}'
            if (strpos($val, '{') !== false && preg_match_all('/\{((%?)\w+)\}/', $val, $matches)) {
                foreach ($matches[1] as $name) {
                    if (strpos($name, '%') === 0) {
                        //截取%后面的变量名
                        $name     = substr($name, 1);
                        $optional = true;
                    } else {
                        $optional = false;
                    }
                    //2代表可选参数
                    $var[$name] = $optional ? 2 : 1;
                }
            }
            //'blog/read/[:id]'
            if (strpos($val, '[:') === 0) {
                // 可选参数
                $optional = true;
                $val = substr($val, 1, -1);
            }
            //'blog/read/:name
            if (strpos($val, ':') === 0) {
                // URL变量
                $name = substr($val, 1);
                $var[$name] = $optional ? 2 : 1;
            }
        }
        return $var;
    }

    static public function test($var)
    {
        //E(self::$group);

        //self::registerRules([':id/[:ccc]'   => ['artical/read', ['method' => 'get'], ['id' => '\d+']],], $type = '*');
        //self::registerRules(['[ccc]'   => [['new1/:id/[:a]/{%b}$','News/read',['complete_match' => false,'ext'=>'shtml','modular'=>'module'],['id'=>'\d+']]],], $type = '*');
//        E(self::$rules['name']);
//        E(self::$rules['group']);
//        E(self::$rules['domain']);
        E(self::$rules,true);
    }
}
?>