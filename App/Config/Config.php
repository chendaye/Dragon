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

return [
    // +----------------------------------------------------------------------
    // | 运行设置
    // +----------------------------------------------------------------------

    // 框架调试模式
    'DEBUG'                 => true,
    // 框架Trace
    'TRACE'                 => false,
    // 默认输出类型
    'DEFAULT_OUTPUT_TYPE'   => 'html',
    // 默认AJAX 数据返回格式,可选json xml ...
    'DEFAULT_AJAX'          => 'json',
    // 默认JSONP格式返回的处理方法
    'JSONP_DEAL'            => 'jsonp',
    // 默认JSONP处理方法
    'JSONP_CALLBACK'        => 'callback',
    // 默认时区
    'DEFAULT_TIMEZONE'      => 'PRC',
    // 控制器类后缀
    'CTRL_EXT'              => '.ctrl.php',
    //数据模型后缀
    'MOD_EXT'               => '.mod.php',
    //命令后缀
    'CMD_EXT'               => '.cmd.php',
    //观察者后缀
    'OBS_EXT'               => '.obs.php',
    //事件后缀
    'EVENT_EXT'             => '.event.php',
    //监听器后缀
    'LISTEN_EXT'            => '.listen.php',

    // +----------------------------------------------------------------------
    // | 访问设置
    // +----------------------------------------------------------------------
    // 默认模块名
    'DEFAULT_MODULE'        => 'index',
    // 禁止访问模块
    'DENY_MODULE'           => [''],
    // 默认命令
    'DEFAULT_COMMAND'       => 'Index',
    // 默认操作命
    'DEFAULT_ACTION'        => 'index',
    // 默认的空命令命
    'EMPTY_COMMAND'         => 'Error',
    // 操作方法后缀
    'ACTION_SUFFIX'         => '',
    // 自动搜索命令
    'COMMAND_SEARCH' => false,

    // +----------------------------------------------------------------------
    // | URL设置
    // +----------------------------------------------------------------------

    // PATHINFO变量名 用于兼容模式
    'VAR_PATHINFO'           => 's',
    // 兼容PATH_INFO获取
    'PATHINFO_FETCH'         => ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'],
    // pathinfo分隔符
    'PATHINFO_DEPR'          => '/',
    // URL伪静态后缀
    'URL_HTML_SUFFIX'        => 'html',
    // URL普通方式参数 用于自动生成
    'URL_COMMON_PARAM'       => false,
    // URL参数方式 0 按名称成对解析 1 按顺序解析
    'URL_PARAM_TYPE'         => 0,
    // 是否开启路由
    'URL_ROUTE_ON'           => true,
    // 路由使用完整匹配
    'ROUTE_COMPLETE_MATCH'   => false,
    // 路由配置文件（支持配置多个）
    'ROUTE_CONFIG_FILE'      => ['route'],
    // 是否强制使用路由
    'URL_ROUTE_MUST'         => false,
    // 域名部署
    'URL_DOMAIN_DEPLOY'      => false,
    // 域名根，如thinkphp.cn
    'URL_DOMAIN_ROOT'        => '',
    // 是否自动转换URL中的控制器和操作名
    'URL_CONVERT'            => true,
    // 默认的访问控制器层
    'URL_CONTROLLER_LAYER'   => 'controller',
    // 表单请求类型伪装变量
    'VAR_METHOD'             => '_method',
    // 表单ajax伪装变量
    'VAR_AJAX'               => '_ajax',
    // 表单pjax伪装变量
    'VAR_PJAX'               => '_pjax',
    // 是否开启请求缓存 true自动缓存 支持设置请求缓存规则
    'REQUEST_CACHE'          => false,
    // 请求缓存有效期
    'REQUEST_CACHE_EXPIRE'   => null,

    // +----------------------------------------------------------------------
    // | 模板设置
    // +----------------------------------------------------------------------

    'TEMPLATE'               => [
        // 模板引擎类型 支持 php think 支持扩展
        'TYPE'         => 'Think',
        // 模板路径
        'VIEW_PATH'    => '',
        // 模板后缀
        'VIEW_SUFFIX'  => 'html',
        // 模板文件名分隔符
        'VIEW_DEPR'    => SP,
        // 模板引擎普通标签开始标记
        'TPL_BEGIN'    => '{',
        // 模板引擎普通标签结束标记
        'TPL_END'      => '}',
        // 标签库标签开始标记
        'TAGLIB_BEGIN' => '{',
        // 标签库标签结束标记
        'TAGLIB_END'   => '}',
    ],

    // 视图输出字符串内容替换
    'VIEW_REPLACE_STR'       => [],
    // 默认跳转页面对应的模板文件
    'DISPATCH_SUCCESS_TMPL'  => PUB . 'tpl' . SP . 'dispatch_jump.tpl',
    'DISPATCH_ERROR_TMPL'    => PUB . 'tpl' . SP . 'dispatch_jump.tpl',

    // +----------------------------------------------------------------------
    // | 异常及错误设置
    // +----------------------------------------------------------------------

    // 异常页面的模板文件
    'EXCEPTION_TMPL'         => PUB . 'tpl' . SP . 'think_exception.tpl',

    // 错误显示信息,非调试模式有效
    'ERROR_MESSAGE'          => '页面错误！请稍后再试～',
    // 显示错误信息
    'SHOW_ERROR_MSG'         => false,
    // 异常处理handle类
    'EXCEPTION_HANDLE'       => '',

    // +----------------------------------------------------------------------
    // | 日志设置
    // +----------------------------------------------------------------------

    'LOG'                    => [
        // 日志记录方式，内置 file socket 支持扩展
        'TYPE'  => 'File',
        // 日志保存目录
        'PATH'  => '',
        // 日志记录级别
        'LEVEL' => [],
        //日志授权
        'KEY' =>  ['192.168.0.53']
    ],

    // +----------------------------------------------------------------------
    // | Trace设置 开启 app_trace 后 有效
    // +----------------------------------------------------------------------
    'TRACE'                  => [
        // 内置Html Console 支持扩展
        'TYPE' => 'html',
    ],

    // +----------------------------------------------------------------------
    // | 缓存设置
    // +----------------------------------------------------------------------

    'CACHE'                  => [
        // 驱动方式
        'TYPE'   => 'File',
        // 缓存保存目录
        'PATH'   => '',
        // 缓存前缀
        'PREFIX' => '',
        // 缓存有效期 0表示永久缓存
        'EXPIRE' => 0,
    ],

    // +----------------------------------------------------------------------
    // | 会话设置
    // +----------------------------------------------------------------------

    'SESSION'                => [
        'ID'             => '',
        // SESSION_ID的提交变量,解决flash上传跨域
        'VAR_SESSION_ID' => '',
        // SESSION 前缀
        'PREFIX'         => 'DRAGON',
        // 驱动方式 支持redis memcache memcached
        'TYPE'           => '',
        // 是否自动开启 SESSION
        'AUTO_START'     => true,
    ],

    // +----------------------------------------------------------------------
    // | Cookie设置
    // +----------------------------------------------------------------------
    'COOKIE'                 => [
        // cookie 名称前缀
        'PREFIX'    => '',
        // cookie 保存时间
        'EXPIRE'    => 0,
        // cookie 保存路径
        'PATH'      => '/',
        // cookie 有效域名
        'DOMAIN'    => '',
        //  cookie 启用安全传输
        'sSECURE'    => false,
        // httponly设置
        'HTTPONLY'  => '',
        // 是否使用 setcookie
        'SETCOOKIE' => true,
    ],

    //分页配置
    'PAGINATE'               => [
        'TYPE'      => 'bootstrap',
        'VAR_PAGE'  => 'page',
        'LIST_ROWS' => 15,
    ],
];
?>