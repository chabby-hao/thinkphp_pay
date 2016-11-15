<?php
namespace Root\Controller;
use Think\Controller;
class IndexController extends Controller {
    public function index(){
        //var_dump($_SESSION);
        $this->assign(['a'=>11]);
        $this->display();
    }
}