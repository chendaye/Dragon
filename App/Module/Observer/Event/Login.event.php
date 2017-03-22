<?php
namespace Observer\Event;
use Core\Lib\Observe\Event;
use Model\Dbs;
use Model\Usr;

/**
 * 登录事件，涉及到model类
 * Class Login
 * @package Observer\Event
 */
class Login extends Event {
    public $usr;
    public $dbs;
    public function __construct()
    {
        parent::__construct();
        $this->usr = new Usr();
        $this->dbs = new Dbs();
    }
}
?>