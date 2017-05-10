<?php
namespace Core\Lib;
use Core\Lib\Driver\Cache\File;
use Core\Lib\Driver\Response\Json;
use Core\Lib\Driver\Response\Xml;
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
        self::route();
        self::response();
        self::cache();
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
        RequestHelper::instance()->create('http://username:password@www.dragon-god.com:80/Dragon/Login/login.html?d=888&user=chen&pass=daye','delete',$param = ['id'=>123,'name'=>2]);
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
        RequestRegistry::getRequest()->attrInj('chendaye', '666');
        dump(RequestRegistry::getRequest()->chendaye);


//        RequestRegistry::getRequest()->hook('key', 'hk');
//
//        dump(RequestRegistry::getRequest()->getCacheKey(function ($obj)  { return $obj->key('chendaye');}));
//        dump(RequestRegistry::getRequest()->getCacheKey('rrrr|fffff'));
//        dump(RequestRegistry::getRequest()->getCacheKey('__URL__'));
       dump(RequestRegistry::getRequest()->parseCacheKey('blog/:id'));
       dump(RequestRegistry::getRequest()->parseCacheKey('[html]'));
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


        //E(Session::get());

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

    /**
     * 响应测试
     */
    static public function response(){
        \Core\Lib\Conf::init('Config.php', '');
        ini_set('date.timezone','Asia/Shanghai');
        RequestHelper::instance()->create('http://username:password@www.dragon-god.com:80/Dragon/Login/login.html?d=888&user=chen&pass=daye','delete',$param = ['id'=>123,'name'=>2]);

        //dump($res = new Json(['测试数据','6666']));
        //dump($res = new Xml(['测试数据','6666']));
        //dump($res = Json::create(['测试数据'],'json'));
       // E($res->getContent());

//        dump($res->content(json_encode(4564)));
//        dump($res->getCode());
//        dump($res->options([1=>2,2=>3]));
//        dump($res->header([1=>2,2=>3]));
        $res = Json::create(['测试数据'],'json');
       $res->lastModified(3306);
       $res->expires(3306);
       $res->eTag(3306);
       $res->cacheControl(3306);
        dump($res->getHeader());
        $res->send();
        exit;
    }

    /**
     * 路由测试
     */
    static public function route(){

       // E(substr('qwer', 0, 1),true);
//        E(preg_match_all('/<(\w+(\??))>/', '<qwer ><sdf><qqqq?=>', $matches));
//        E($matches,true);
        \Core\Lib\Conf::init('Config.php', '');
        ini_set('date.timezone','Asia/Shanghai');
        RequestHelper::instance()->create('http://username:password@three.www.dragon-god.com:80/Dragon/Login/login.html?d=888&user=chen&pass=daye','delete',$param = ['id'=>123,'name'=>2]);

//        Route::setGroup('Artical', ['complete_match' => false,'ext'=>'shtml','modular'=>'module'], ['id'=>'\d+']);


//        Route::group('blog',function(){
//            Route::rule(':id','blog/read','put',[],['id'=>'\d+']);
//            Route::anyone(':name','blog/read',[],['name'=>'\w+']);
//        },['method'=>'get','ext'=>'html']);
//
//        Route::group('user',function(){
//            Route::rule(':id','blog/read','put',[],['id'=>'\d+']);
//            Route::anyone(':name','blog/read',[],['name'=>'\w+']);
//        },['method'=>'get','ext'=>'html']);

//
//        Route::group('artical',[
//            ':id/[:ccc]'   => ['artical/read', ['method' => 'get'], ['id' => '\d+']],
//            ':name/{%ddd}' => ['artical/read', ['method' => 'post']],
//            [':id/[:a]/{%b}$','News/read','put',['complete_match' => false,'ext'=>'shtml','modular'=>'module'],['id'=>'\d+']],
//        ],['modular'=>'mod'],['id'=>'\s+']);


//        Route::group('',function(){
//            //Route::rule('A:id','blog/read','put',[],['id'=>'\d+']);
//            Route::rule([['new_4/:id/[:a]/{%b}$','News/read' ,['complete_match' => false,'ext'=>'shtml','modular'=>'module', 'method'=>'get|post'],['id'=>'\d+']],],'','put',[],['id'=>'\d+']);
//            //Route::anyone('B:name','blog/read',[],['name'=>'\w+']);
//        },['method'=>'get','ext'=>'html']);


//        Route::group('',[ 'new/:id'=>'News/read','new_3/:name'=>['Blog/detail','head', ['ext'=>'yml'], ['id'=>'\w+']],],['method'=>'get','ext'=>'html']);


//       Route::rule([
//            'new_1/:id'=>'News/read',
//            'new_2/:name'=>['Blog/detail','put', ['ext'=>'yml'], ['name'=>'\w+']],
//            'new_7/:name/{%id}'=>['Blog/detail','*', ['ext'=>'yml'], ['name'=>'\w+']],
//            'new_8/:name'=>['Blog/detail','head|options', ['ext'=>'yml'], ['id'=>'\w+']],
//            'new_3/:name'=>['Blog/detail', ['ext'=>'yml'], ['id'=>'\w+']],
//            ['new_4/:id/[:a]/{%b}$','News/read','post',['complete_match' => false,'ext'=>'shtml','modular'=>'module'],['id'=>'\d+']],
//            ['new_5/:id/{%b}$','News/read', ['complete_match' => false,'ext'=>'shtml','modular'=>'module'],['id'=>'\d+']],
//        ], '', 'GET', ['ext'=>'php'], ['id'=>'\W+']);


//        Route::rule('new_6/:id/[:pid]/{%cid}', 'News/read', 'get', ['complete_match' => true,'ext'=>'xml','modular'=>'User'], ['id'=>'\d+']);


//        Route::rule('new/:id/{%uid}', 'News/user', 'post', ['complete_match' => true,'ext'=>'xml','modular'=>'User'], ['id'=>'\d+']);

//        Route::domain('com', function(){
//            Route::anyone('A:id','blog/read',[],['id'=>'\d+']);
//            Route::rule([ 'new_3/:name'=>['Blog/detail', ['ext'=>'yml','method'=>''], ['id'=>'\w+']],],'','put',[],['name'=>'\w+']);
//        }, $option = ['complete_match' => true,'ext'=>'xml','modular'=>'User'], $pattern = ['id'=>'\d+']);


//        Route::domain('dragon-god.com:80', [
//            'new_1/:id'=>'News/read',
//            //'new_2/:name'=>['Blog/detail','put', ['ext'=>'yml'], ['name'=>'\w+']],
//            //'new_7/:name/{%id}'=>['Blog/detail','*', ['ext'=>'yml'], ['name'=>'\w+']],
//            'new_8/:name'=>['Blog/detail','head|options', ['ext'=>'yml'], ['id'=>'\w+']],
//            '//new_3/:name'=>['Blog/detail', ['ext'=>'yml'], ['id'=>'\w+']],
//            ['new_4/:id/[:a]/{%b}$','News/read','post',['complete_match' => false,'ext'=>'shtml','modular'=>'module'],['id'=>'\d+']],
//           // '[ggg]'=>[':id/[:ccc]'   => ['artical/read', ['method' => 'get'], ['id' => '\d+']],],
//        ], $option = ['complete_match' => true,'ext'=>'xml','modular'=>'User'], $pattern = ['id'=>'\d+']);

//        Route::domain('*.www', [
//            'new_1/:id'=>'News/read',
//            //'new_2/:name'=>['Blog/detail','put', ['ext'=>'yml'], ['name'=>'\w+']],
//            //'new_7/:name/{%id}'=>['Blog/detail','*', ['ext'=>'yml'], ['name'=>'\w+']],
////            'new_8/:name'=>['Blog/detail','head|options', ['ext'=>'yml'], ['id'=>'\w+']],
//            '//new_3/:name'=>['Blog/detail', ['ext'=>'yml'], ['id'=>'\w+']],
////            '[bind]'=>['Blog/detail', ['ext'=>'yml'], ['id'=>'\w+']],
//           // '[ggg]'=>[':id/[:ccc]'   => ['artical/read', ['method' => 'get'], ['id' => '\d+']],],
//        ], $option = ['complete_match' => true,'ext'=>'xml','modular'=>'User','[bind]'=>['\app\index\behavior', [],[]]], $pattern = ['id'=>'\d+']);

//        Route::domain([
//            'com'=>function(){
//            Route::rule('A:id','blog/read','put',[],['id'=>'\d+']);
//            Route::anyone('B:name','blog/read',[],['name'=>'\w+']);
//        },
//            'cn'=>[
//                'new_1/:id'=>'News/read',
//                'new_2/:name'=>['Blog/detail','put', ['ext'=>'yml'], ['name'=>'\w+']],
//                'new_7/:name/{%id}'=>['Blog/detail','*', ['ext'=>'yml'], ['name'=>'\w+']],
//                'new_8/:name'=>['Blog/detail','head|options', ['ext'=>'yml'], ['id'=>'\w+']],
//                'new_3/:name'=>['Blog/detail', ['ext'=>'yml'], ['id'=>'\w+']],
//                ['new_4/:id/[:a]/{%b}$','News/read','post',['complete_match' => false,'ext'=>'shtml','modular'=>'module'],['id'=>'\d+']],
//                '[ggg]'=>[':id/[:ccc]'   => ['artical/read', ['method' => 'get'], ['id' => '\d+']],],
//            ],
//        ],'' , $option = ['complete_match' => true,'ext'=>'xml','modular'=>'User'], $pattern = ['is'=>'\W+']);

        /*域名绑定 绑定到命名空间*/
        Route::domain('*.www','\app\index\behavior?name=*&id=132&sum=100',['ext'=>'yml','method'=>'put'],['id'=>'\w+']);
        /*域名绑定 绑定到类*/
//        Route::domain('*.www','@app\index\behavior?name=*&id=132&sum=100',['ext'=>'yml','method'=>'put'],['id'=>'\w+']);
        /*域名绑定 绑定到模块*/
//        Route::domain('*.www','index/behavior?name=*&id=132&sum=100',['ext'=>'yml','method'=>'put'],['id'=>'\w+']);
//
//        $name = Route::name('artical/read');
//        E($name, true);
//        Route::put('new_4/:id/[:a]/{%b}$', 'Blog/detail', ['complete_match' => true,'ext'=>'xml','modular'=>'User'], ['is'=>'\W+']);
        //Route::resource('blog.comment','index/comment',['var'=>['blog/:blog_id/comment'=>8, 'blog'=>6],'except'=>['edit','read']],[]);
        //Route::resource('blog.articl.comment','index/comment',['var'=>['blog'=>8, 'articl' => 9, 'comment' => 10],'except'=>['',]],[]);
       // Route::command('A:id','blog/read',[],['id'=>'\d+']);
//        Route::alias([
//                'new_1/:id'=>'News/read',
//                'new_2/:name'=>['Blog/detail','put', ['ext'=>'yml'], ['name'=>'\w+']],
//                'new_7/:name/{%id}'=>['Blog/detail','*', ['ext'=>'yml'], ['name'=>'\w+']],
//                'new_8/:name'=>['Blog/detail','head|options', ['ext'=>'yml'], ['id'=>'\w+']],
//                'new_3/:name'=>['Blog/detail', ['ext'=>'yml'], ['id'=>'\w+']],
//                ['new_4/:id/[:a]/{%b}$','News/read','post',['complete_match' => false,'ext'=>'shtml','modular'=>'module'],['id'=>'\d+']],
//                '[ggg]'=>[':id/[:ccc]'   => ['artical/read', ['method' => 'get'], ['id' => '\d+']],],
//            ]);



//        Route::setMethodPrefix('PUT', 'ppt');
//          Route::rest('create', ['put', ':id', 'create']);
//        Route::miss('index/index', 'get', ['ext'=>'yml']);
//        Route::auto('index/index');
//        E(Route::rules(''));
        $request = RequestRegistry::getRequest();
        $ru = 'new_3/:name';
       // Route::checkDomain($request, $ru, 'get');

        //Alias  等价Blog/detail  http://serverName/index.php/Alias/edit/id/5  http://serverName/index.php/Blog/detail/edit/id/5
        //Route::alias('Alias', 'Blog/detail', ['ext'=>'html','allow'=>['index','read','edit','delete']]);
        //E(explode(',','0,1,2,3,4,5,6',3));

        //路由到类
//        Route::alias('Alias', '\Blog\detail', ['ext'=>'html','allow'=>['index','read','edit','delete']]);
//        Route::check($request,'Alias/edit/id/5', $depr = '/', $checkDomain = false);
        //路由到命令类
//        Route::alias('Alias', '@Blog/detail', ['ext'=>'html','allow'=>['index','read','edit','delete']]);
//        Route::check($request,'Alias/edit/id/5', $depr = '/', $checkDomain = false);
        //路由到模块
//        Route::alias('Alias', 'Blog/detail', ['ext'=>'html','allow'=>['index','read','edit','delete']]);
//        Route::check($request,'Alias/edit/id/5', $depr = '/', $checkDomain = false);

        Route::check($request,'Blog/detail/edit/id/5', $depr = '/', $checkDomain = false);

        Route::test('');
        exit;
    }

    static function exporeTest($filename){
        if($filename) return true;

    }


}
?>