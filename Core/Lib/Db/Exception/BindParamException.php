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

namespace Core\Lib\Db\Exception;

use \Core\Lib\Exception\DbException;

/**
 * PDO参数绑定异常
 */
class BindParamException extends DbException
{

    /**
     * BindParamException constructor.
     * @param string $message
     * @param array  $config
     * @param string $sql
     * @param array    $bind
     * @param int    $code
     */
    public function __construct($message, $config, $sql, $bind, $code = 10502)
    {
        $this->setData('Bind Param', $bind);
        parent::__construct($message, $config, $sql, $code);
    }
}
