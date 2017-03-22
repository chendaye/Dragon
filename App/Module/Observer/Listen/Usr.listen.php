<?php
namespace Observer\Listen;
use Core\Lib\Observe\Event;
use Core\Lib\Observe\Observe;

class Usr implements Observe {
    public function execute(Event $event)
    {
        // TODO: Implement execute() method.
        $usr = $event->usr->all();
        E($usr);
    }
}
?>