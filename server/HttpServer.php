<?php
/**
 * Created by Yuankui
 * Date: 2018/12/26
 * Time: 11:27
 */

class HttpServer {

    public $http;

    public function __construct($config)
    {
        //$config = require __DIR__ . '/swoole.config.php';

        if(empty($config['host']) || empty($config['port']) || empty($config['setting'])) {
            die('the necessary configuration lacked');
        }

        $this->http = new swoole_http_server($config['host'], $config['port']);
        //加载服务配置，主要是静态资源相关解析指向
        $this->http->set($config['setting']);

        $this->http->on('start', [$this, 'onStart']);
        $this->http->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->http->on('request', [$this, 'onRequest']);

        $this->http->start();
    }

    public function onStart(swoole_server $server)
    {
        //给主进程命名，方便为之后的热重启shell命令做准备
        swoole_set_process_name('yb_biny_php');
    }

    public function onWorkerStart(swoole_server $server,  $workerId)
    {
        //将框架的主要资源文件和配置进行加载，常驻内存，之后的swoole热重启也就是为了重启这部分包含的所有代码

        date_default_timezone_set('Asia/Shanghai');

        defined('SYS_DEBUG') or define('SYS_DEBUG', true);
        defined('SYS_CONSOLE') or define('SYS_CONSOLE', true);
        //dev pre pub
        defined('SYS_ENV') or define('SYS_ENV', 'dev');
        defined('isMaintenance') or define('isMaintenance', false);

        if (SYS_DEBUG){
            ini_set('display_errors','On');
        }
        error_reporting(E_ALL ^ E_NOTICE);

        include __DIR__.'/../lib/TXApp.php';
    }

    public function onRequest($request, $response)
    {
        $_SERVER  =  [];
        if(isset($request->server)) {
            foreach($request->server as $k => $v) {
                $_SERVER[strtoupper($k)] = $v;
            }
        }
        if(isset($request->header)) {
            foreach($request->header as $k => $v) {
                $_SERVER[strtoupper($k)] = $v;
            }
        }

        $_GET = [];
        if(isset($request->get)) {
            foreach($request->get as $k => $v) {
                $_GET[$k] = $v;
            }
        }
        $_POST = [];
        if(isset($request->post)) {
            foreach($request->post as $k => $v) {
                $_POST[$k] = $v;
            }
        }

        //记录下访问日志
        $this->log();

        ob_start();
        //执行请求应用并响应
        try {
            TXApp::registry(realpath(__DIR__. '/../app'));
            TXApp::run();
        }catch (\Exception $e) {
            //TODO 可以进行自定义的错误处理
        }

        $res = ob_get_contents();
        ob_end_clean();

        $response->end($res);
    }

    public function log()
    {
        //日志参数准备
        $accessTime = '[' . date('Y-m-d H:i:s') . ']';
        $request    = $_SERVER['REQUEST_URI'] ?? ($_SERVER['PATH_INFO'] ?? '');
        $params     = http_build_query(array_merge($_GET, $_POST));

        //日志参数拼接和格式化
        $logTxt  = $accessTime . ' ' . $request . PHP_EOL;
        $logTxt .= $params . PHP_EOL;
        $logTxt .= '[$_SERVER]:' . PHP_EOL;
        foreach ($_SERVER as $key => $item) {
            $logTxt .= $key . ' => '.$item . PHP_EOL;
        }
        $logTxt .= PHP_EOL;

        $logPathName = __DIR__ . '/../logs/access/' . date('Ymd') . '_access.log';

        //异步写日志
        swoole_async_writefile($logPathName, $logTxt, function ($filename) {
            //TODO 写完之后可以做你想做的
        }, FILE_APPEND);
    }
}

$config = require __DIR__ . '/swoole.config.php';

(new HttpServer($config));