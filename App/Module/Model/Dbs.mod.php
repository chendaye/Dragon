<?php
namespace Model;
use Core\Lib\Dbp;
class Dbs extends Dbp {

    public function all(){
        $sql = "select * from `db`";
        return self::getAll($sql);
    }
}
?>