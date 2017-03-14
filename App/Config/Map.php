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
    // | 命名空间映射注册 PSR-4 PSR-0
    // +----------------------------------------------------------------------
    //框架默认映射 DIR => NAMESPACE
    'namespace'             =>[
        COMMAND          => 'Command',   //命令层映射
        CONTROLLER       => 'Controller',     //控制层映射
        MODEL            => 'Model',   //数据层映射
        OBSERVER         => 'Observer',     //观察映射
        EVENT            => 'Observer\Event',     //事件映射
        LISTEN           => 'Observer\Listen',    //监听映射
    ],
    //自定义模块映射
    'custom_namespace'      => [
        COMMAND.SP.'Back'.SP     => 'Command\Back',
        COMMAND.SP.'Front'.SP     => 'Command\Front',
        CONTROLLER.SP.'Back'.SP     => 'Command\Back',
        CONTROLLER.SP.'Front'.SP     => 'Command\Front',
    ]
]
?>