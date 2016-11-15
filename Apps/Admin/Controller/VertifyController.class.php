<?php
//验证码
namespace Admin\Controller;
use Think\Controller;
use Think\Verify;
class VertifyController extends Controller {
    
    //创建验证码
    public function createVertify(){
        $config = [
	       'imageW' => 300,//验证码宽度300px
        ];
        $vertify = new Verify($config);
        $vertify->entry();
    }
}