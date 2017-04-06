<?php
namespace Core\Lib;
use Core\Lib\Driver\Cache\File;
use Core\Lib\Driver\Session\Memcache;
use Core\Lib\Exception\Exception;
use Core\Lib\Exception\HttpResponseException;
use Core\Lib\Registry\RequestHelper;
use Core\Lib\Registry\RequestRegistry;

/**
 * 简易测试类
 * Class Test
 * @package Core\Lib
 */
class Test
{

    /**
     * TEST
     */
    static public function test()
    {
        //self::cache();
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
    static public function aoutload()
    {
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
    static public function conf()
    {
        $type = pathinfo('/var/www/Dragon/test.php', PATHINFO_EXTENSION);
        // E($type);

        //E(\Core\Lib\Conf::analysis(json_encode(123),'json'));

        \Core\Lib\Conf::init('Config.php', '');



        $a = \Core\Lib\Conf::get('PAGINATE');
        E($a);
        ini_set('date.timezone','Asia/Shanghai');
        // E(date('c'));
    }

    /**
     * log
     */
    static public function log()
    {
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
        $a = new \Core\Lib\Driver\Log\File($configure);
        //$a->save($log);

        $a = new \Core\Lib\Driver\Log\Socket([
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
    static public function session()
    {
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
    static public function cookie()
    {
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
    static public function request()
    {
        ini_set('date.timezone','Asia/Shanghai');

//        echo "<form action=\"index.php?d=888\" method=\"post\">
//                <input name=\"aaa\" type=\"hidden\" value=\"6669879879\">
//                <input type=\"submit\">
//            </form>";
        \Core\Lib\Conf::init('Config.php', '');
        $req = new Request();

        //$req->none('ddd');
//        Request::hook(['test'=>'test']);
//        Request::hook('t','test');
//        Request::test();

       // E($_SERVER);
        RequestHelper::instance()->create('http://username:password@www.dragon-god.com:80/Dragon/Login/login.htmll?d=888&user=chen&pass=daye','delete',$param = ['a'=>1,'b'=>2]);
        //RequestHelper::instance()->init();
       //$_SESSION = $_COOKIE = $_POST = $_GET;
//        \Core\Lib\Session::set('fff', 'Dragon', 'Dragon');
//        E(\Core\Lib\Session::get());
       // E($_ENV);
//        try{
//            dump(RequestRegistry::getRequest()->cache(true));
//            $r = (new HttpResponseException())->getResponse();
//            dump($r);exit;
//        }catch (Exception $exception){
//            dump($exception);exit;
//        }
        RequestRegistry::getRequest()->bind('chendaye', '666');
        dump(RequestRegistry::getRequest()->chendaye);
        exit;
        dump(RequestRegistry::getRequest()->session($name = '', $default = 'chendaye', $filter = ''));
        //E($_SESSION);
        dump(RequestRegistry::getRequest()->cookie($name = '', $default = 'chendaye', $filter = ''));
        dump(RequestRegistry::getRequest()->server($name = '', $default = 'chendaye', $filter = ''));
        dump(RequestRegistry::getRequest()->env($name = '', $default = 'chendaye', $filter = ''));
        dump(RequestRegistry::getRequest()->header($name = '', $default = 'chendaye'));
        //$_SESSION = $_COOKIE = $_POST = $_GET;
//        if(session_status() == PHP_SESSION_ACTIVE){
//            E($_SESSION,true);
//        }

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
        RequestRegistry::getRequest()->typeCast($var, 's');
        //E($var);
        $var = 'ttexptt';
        RequestRegistry::getRequest()->filterExp($var);
        //E($var);
        //dump(RequestRegistry::getRequest());


        $reg = '/^qwer$/i';
        $filter = ['filtertest',$reg,1];
        $filter[] = 'default';
        $var = 'fdgwer';
        //$ret = RequestRegistry::getRequest()->filter($var,$key, $filter);
        //E([$var, $ret]);
        //$r = RequestRegistry::getRequest()->input(['a'=>['b'=>['c'=>666]],'b'=>'qwer'],'a.b.c/a','default', ['filtertest',$reg,1]);
        $var = '456';
       $ret =  RequestRegistry::getRequest()->obtain($var,'','过滤未通过', ['filtertest',$reg]);
        //E([$ret,$var]);

        function a(){
            for($i = 0; $i<5; $i++){
                if($i==2) break;
            }
            E($i);
        }


        //文件上传
//        echo '<form action = "" method="post" enctype="multipart/form-data"  id="products_enquiry">';
//        echo '<input type="file" name="file[]" />';
//        echo '<input type="file" name="file[]" />';
//        echo '<input type="file" name="file[]" />';
//        echo '<input type="submit" id="enquiry_submit"   value="更新信息"></form>';
        $file = RequestRegistry::getRequest()->file('file.1');
       // E($file);
        //dump($file->isTest());
//        dump($file->getInfo('size'));
//        dump($file->setName('chendaye'));
//        dump($file->getName());
//        dump($file->hash());
//       // dump($file->checkDir(CORE));
//        dump($file->getMime());
//        dump($file->rule([]));
//        dump($file->valid([]));
//        dump($file->check([]));
//        dump($file->checkExt(['xls']));
//        dump($file->checkImg());
//        //dump($file->imageType('test.png'));
//        dump($file->checkSize(33066666));
//        dump($file->checkMime('xls'));
       // dump($file->move(RUNTIME));
//        dump($file->saveName('chendaye'));
//        dump($file->saveName(true));

//        dump(RequestRegistry::getRequest()->put($name = '', $default = null, $filter = ''));
//        dump(RequestRegistry::getRequest()->get($name = '', $default = 'chendaye', $filter = ''));
//        dump(RequestRegistry::getRequest()->post($name = '', $default = 'chendaye', $filter = ''));
//        dump(RequestRegistry::getRequest()->route($name = '', $default = 'chendaye', $filter = ''));
//        dump(RequestRegistry::getRequest()->request($name = '', $default = 'chendaye', $filter = ''));
        $_POST['qwer'] = '666';
        dump(RequestRegistry::getRequest()->exist('c','param', true));
        dump(RequestRegistry::getRequest()->gain('qwer','post'));
        dump(RequestRegistry::getRequest()->except('a','param'));
        dump(RequestRegistry::getRequest()->isSsl());
        dump(RequestRegistry::getRequest()->isAjax());
        dump(RequestRegistry::getRequest()->isPjax());
        dump(RequestRegistry::getRequest()->ip());
        dump(RequestRegistry::getRequest()->mobile());
        dump(RequestRegistry::getRequest()->scheme());
        dump(RequestRegistry::getRequest()->query());
        dump(RequestRegistry::getRequest()->host());
        dump(RequestRegistry::getRequest()->port());
        dump(RequestRegistry::getRequest()->protocol());
        dump(RequestRegistry::getRequest()->remotePort());
        dump(RequestRegistry::getRequest()->routeInfo());
        dump(RequestRegistry::getRequest()->module());
        dump(RequestRegistry::getRequest()->command());
        dump(RequestRegistry::getRequest()->controller());
        dump(RequestRegistry::getRequest()->langset());
        dump(RequestRegistry::getRequest()->getContent());
        dump(RequestRegistry::getRequest()->getInput());
        dump(RequestRegistry::getRequest()->token());

        E(Session::get());

        E(substr('123456789', 2,1));


    }

    static public function cache()
    {
//        $driver = new File([
//            'expire'        => 3600,   //缓存过期时间，0：不过期
//            'subdirectory'  => true,   //启用子目录
//            'prefix'        => 'chen',  //缓存前级目录
//            'path'          => CACHE,   //缓存路径
//            'compress' => false,   //是否压缩文件
//        ]);
//       // dump($driver);
//        $driver->set('exporeTest', ['a'=>'666', 'b'=>'888', 'c'=> '555e']);
//
//        E($driver->get('exporeTest', 'exporeTest', [new self(),'exporeTest']));
        //E($driver->getTagItem('chen'));
        //E(md5(serialize(false)));
        //E(Conf::get('cache.redis'));

        //Cache::init([]);
        Cache::set('DRAGON', ['file','driver', 'success']);
        E(Cache::get('DRAGON'));
        Cache::remove('DRAGON');
        E(Cache::get('DRAGON'));
        Cache::tag('tag', 'chen,da,ye',true);
        E(Cache::get('tag_'.md5('tag')));

        //dump(Cache::test());

        exit;
    }

    static function exporeTest($filename){
        if($filename) return true;

    }


}
?>