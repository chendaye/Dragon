<?php
namespace Observer\Listen;
use Core\Lib\Observe\Event;
use Core\Lib\Observe\Observe;

class Login implements Observe {
    public function execute(Event $event)
    {
        // TODO: Implement execute() method.
        $usr = $event->usr->all();
        $dbs = $event->dbs->all();
        E(serialize([$usr,$dbs]));
    }
}
?>