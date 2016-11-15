<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends Controller {
    public function index(){
        return $this->redirect('Admin/User/login');
    }
}