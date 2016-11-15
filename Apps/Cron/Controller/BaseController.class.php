<?php
//定时任务基类
namespace Cron\Controller;
use Think\Controller;
class BaseController extends Controller {
    
    
    public function __construct()
    {
        parent::__construct();
        register_shutdown_function(array($this, 'shutdown'));
    }
    
    
    public function shutdown()
    {
        echo "\nsuccess";
    }
    
}