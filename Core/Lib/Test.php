<?php
namespace Core\Lib;
use Core\Lib\Drives\Session\Memcache;

class Test{
    static public function test(){
        //exit(json_encode([$_POST,$_GET]));
        $psr4 = new \Command\Back\PSR4();
        $psr4->PSR4();
        $psr4 = new \Command\Front\PSR4();
        $psr4->PSR4();
        $psr4 = new \Controller\Back\PSR4();
        $psr4->PSR4();
        $psr4 = new \Model\PSR4();
        $psr4->PSR4();
        $psr4 = new \Observer\Event\PSR4();
        $psr4->PSR4();

        //E($_SERVER);

        \Core\Lib\Load::import('Cache\Cache');
        $cache = new \Cache\Cache();
        $cache->cache();

        $type = pathinfo('/var/www/Dragon/test.php', PATHINFO_EXTENSION);
       // E($type);

        //E(\Core\Lib\Conf::analysis(json_encode(123),'json'));

        \Core\Lib\Conf::cfgFile('Config.php', '');



        $a = \Core\Lib\Conf::get('PAGINATE');
       // E($a);
        ini_set('date.timezone','Asia/Shanghai');
       // E(date('c'));

      //  E(\Core\Lib\Conf::get('Log'));
        var_dump(\Core\Lib\Log::save());
        \Core\Lib\Log::savee();
       // E(2097152/1024);


        $configure = [
            'TIME_FORMAT'     => 'c',   //ISO-8601 标准的日期（例如 2013-05-05T16:34:42+00:00）
            'SIZE'            => 1024*2048,
            'PATH'            => LOG,
            'APART_LEVEL'     => ['sql']     //分开独立记录的日志级别
        ];

        $log = [
            'log' =>[
                'xiao' => ['175',15,1],
                'xioahong' => ['165',17,0]
            ],
            'sql' => [
                'select'=>'select * from admin',
                'delete'=>'delete from admin where id=2',
            ],
        ];
        $a = new \Core\Lib\Drives\Log\File($configure);
        $a->save($log);

        $a = new \Core\Lib\Drives\Log\Socket([
            'HOST'               => 'localhost',  //Socket 服务器地址
            'SHOW_INCLUDES'      => false,   //是否显示加载文件列表
            'FORCE_TO_CLIENT_ID' => ['192.168.0.10','192.168.0.11'],     //强制记录日志到设置的客户端ID
            'ALLOW_CLIENT_ID'    => ['192.168.0.10']     //限制日志读取
        ]);
        //$a->Test();

        //E(24*60);
//        \Core\Lib\Session::init([]);
//        Log::test();
        Memcache::test();
        new Log();
        exit;
    }

}
?>