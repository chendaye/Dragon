<?php
require_once(__DIR__ . '/../../Core/Dragon.php');     //__DIR__，指向当前执行的PHP脚本所在的目录
require_once(__DIR__ . '/../../Core/Common/Dragon.function.php');     //__DIR__，指向当前执行的PHP脚本所在的目录
/**
 * vendor\bin\phpunit Tests/Core/DragonTest.php
 * Class DragonTest
 */
class DragonTest extends PHPUnit_Framework_TestCase{
    private $class;
    public function setUp(){
        $this->class = new \Dragon\Core\Dragon();
    }

    /**
     * 对输出进行测试
     */
    public function testengine(){
        $this->expectOutputString(\Dragon\Core\Dragon::engine());
        //$this->assertEquals(123, \Dragon\Core\Dragon::engine());
    }
    public function testautoLoad(){
        spl_autoload_register("\\Dragon\\Core\\Dragon::autoLoad");
        $this->expectOutputString(\Dragon\Core\Dragon::engine());
    }
    public function tearDown(){
        unset($this->class);
    }
}
?>