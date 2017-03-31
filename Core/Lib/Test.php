<?php
namespace Core\Lib;
use Core\Lib\Drives\Session\Memcache;
use Core\Lib\Registry\RequestHelper;
use Core\Lib\Registry\RequestRegistry;

/**
 * 简易测试类
 * Class Test
 * @package Core\Lib
 */
class Test{

    /**
     * TEST
     */
    static public function test(){
        self::request();
        self::aoutload();
        self::conf();
        self::cookie();
        self::log();
        self::session();
    }

    /**
     * 自动加载
     */
    static public function aoutload(){
        //E(['init',session_status()],true);
        //E(PHP_SESSION_ACTIVE,true);
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
    }

    /**
     * conf
     */
    static public function conf(){
        $type = pathinfo('/var/www/Dragon/test.php', PATHINFO_EXTENSION);
        // E($type);

        //E(\Core\Lib\Conf::analysis(json_encode(123),'json'));

        \Core\Lib\Conf::cfgFile('Config.php', '');



        $a = \Core\Lib\Conf::get('PAGINATE');
        E($a);
        ini_set('date.timezone','Asia/Shanghai');
        // E(date('c'));
    }

    /**
     * log
     */
    static public function log(){
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
        //$a->save($log);

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
    }

    /**
     * session
     */
    static public function session(){
        \Core\Lib\Session::set('ggg.dayes', 'Dragon', 'Dragon');
        \Core\Lib\Session::set('nnn.dayes', 'Dragon');
        \Core\Lib\Session::set('www', 'Dragon');
        \Core\Lib\Session::set('fff', 'Dragon', 'Dragon');
        //E($_SESSION);
//       E(\Core\Lib\Session::get('ggg','Dragon'));
//       E(\Core\Lib\Session::get('www'));
//       E(\Core\Lib\Session::get('bbbbb'));
//        E(Session::obtain('ggg','Dragon'));
//        E($_SESSION);
        // E(Session::exist('fff','Dragon'));
        //E(Session::push('abc','abd'));
        Session::flash('next', 'gogogo', 'Dragon');
        //E($_SESSION);
        Memcache::test();
    }

    /**
     * cookie
     */
    static public function cookie(){
        // E( explode(',', 'www'));
        Cookie::init();

//        $_COOKIE['dragon_test'] = 'dragon:test';
//       // Cookie::get('test');
        Cookie::set('name', 'dragon', ['PREFIX'=>'TEST']);
        Cookie::set('name222', 'dragon', ['PREFIX'=>'TEST']);
        Cookie::set('name222', 'dragon', ['PREFIX'=>'DRAGON']);
//        E($_REQUEST);
//        E($_COOKIE);
        E( Cookie::get('name', 'TEST'));
        E(Cookie::exist('name','TEST'));
        Cookie::delete('name', 'TEST');
        Cookie::clear('DRAGON');
        Cookie::clear('TEST');
        E($_COOKIE);
    }

    /**
     * request
     */
    static public function request(){
        echo "<form action=\"index.php?d=888\" method=\"post\">
                <input name=\"aaa\" type=\"hidden\" value=\"6669879879\">
                <input type=\"submit\">
            </form>";
        \Core\Lib\Conf::cfgFile('Config.php', '');
        $req = new Request();

        //$req->none('ddd');
//        Request::hook(['test'=>'test']);
//        Request::hook('t','test');
//        Request::test();

       // E($_SERVER);
        RequestHelper::instance()->create('http://username:password@www.dragon-god.com:80/Dragon/Login/login.htmll?d=888&user=chen&pass=daye','delete',$param = ['a'=>1,'b'=>2]);
        //RequestHelper::instance()->init();
        dump(RequestRegistry::getRequest()->domain('www.dragon.com'));
        //dump(RequestRegistry::getRequest()->url('www.dragon.com'));
        dump(RequestRegistry::getRequest()->baseUrl(true));
        dump(RequestRegistry::getRequest()->baseFile());
        dump(RequestRegistry::getRequest()->root());
        dump(RequestRegistry::getRequest()->pathinfo());
        dump(RequestRegistry::getRequest()->path());
        dump(RequestRegistry::getRequest()->ext());
        dump(RequestRegistry::getRequest()->source());
        dump(RequestRegistry::getRequest()->method());
        dump(RequestRegistry::getRequest()->isCli());
        dump(RequestRegistry::getRequest()->isCgi());
        dump(RequestRegistry::getRequest()->isGet());
        dump(RequestRegistry::getRequest()->isPut());
        $var = 'qwer';
        RequestRegistry::getRequest()->typeCast($var, 'a');
        E($var);
        $var = 'ttexptt';
        RequestRegistry::getRequest()->filterExp($var);
        E($var);
        //dump(RequestRegistry::getRequest());


        $reg = '/^qwer$/i';

        $filter = ['filtertest',$reg];
        $filter[] = 'default';
        $var = 'qwerq';
        $ret = RequestRegistry::getRequest()->filter($var, $filter);
        E($var, $ret);
        exit;
    }

}
?>