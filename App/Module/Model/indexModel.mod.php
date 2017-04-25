<?php
namespace Model;
use Core\Lib\Dbp;

class indexModel extends Dbp
{
    public function getUser()
    {
        $ret = self::find('user', 1);
        return $ret;
    }

    public function all()
    {
        $sql = "select * from time_zone_transition_type";
        return self::getAll($sql);
    }
}
?>