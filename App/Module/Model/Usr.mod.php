<?php
namespace Model;
use Core\Lib\Dbp;
class Usr extends Dbp
{
    public function getUser()
    {
        $ret = self::find('user', 1);
        return $ret;
    }

    public function all()
    {
        $sql = "select * from `user`";
        return self::getAll($sql);
    }
}
?>