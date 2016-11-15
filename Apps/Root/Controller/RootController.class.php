<?php
namespace Root\Controller;
use Think\Controller;
use Think\Verify;
class RootController extends BaseController {
    
    //超级管理员登录
    public function login()
    {
        layout(false);
        $this->display(); 
    }
    
    //退出
    public function logout()
    {
        session('[destroy]');
        $this->redirect('login');
    }
    
    //管理员列表
    public function index()
    {
        $rootList = D('Root')->order('id desc')->select();
        $this->assign('list', $rootList);
        $this->_setPageHeaderAction('管理员列表');
        $this->display();        
    }
    
    //新增管理员
    public function add()
    {
        $this->_setPageHeaderAction('新增管理员');
        return $this->display();
    }
    
    //新增管理员执行
    public function addHandler()
    {
        $rootName = I('post.root_name');
        $rootPwd = I('post.pwd');
        if($rootName && $rootPwd)
        {
            $data = [];
            $data['root_name'] = $rootName;
            $data['root_pwd'] = $rootPwd;
            $res = D('Root')->addRoot($data);
            if($res !== false)
            {
                ajaxRedirect('index');
            }
        }
        adminOutputErr('添加管理员失败');
    }
    
    //ajax登录
    public function loginHandler()
    {
        $code = I('post.code');//验证码
        $vertify = new Verify();
        if( !$vertify->check($code))
        {
            adminOutputErr('验证码有误');
        }
        $userName = I('post.user_name');
        $userPwd = I('post.user_pwd');
        if($userName && $userPwd)
        {
            $rootInfo = D('Root')->getRoot($userName, $userPwd);
            if($rootInfo !== false)
            {
                session('root_id', $rootInfo['id']);
                session('root_name', $rootInfo['root_name']);
                ajaxRedirect('Merchant/index');
            }
        }
        adminOutputErr('用户名或者密码错误');
    }
    
    
    public function modifyPwd()
    {
        $this->_setPageHeaderAction('修改密码');
        return $this->display('modifyPwd');
    }
    
    //修改当前管理员密码
    public function modifyPwdHandler()
    {
        $rootId = session('root_id');
        $rootPwd = I('post.pwd', null);
        if($rootPwd)
        {
            $data = [];
            $data['root_pwd'] = $rootPwd;
            $res = D('Root')->updateRoot($data, $rootId);
            if($res !== false)
            {
                ajaxRedirect('index');
                //adminOutputData('302', ['url' => $_SERVER['HTTP_REFERER']]);
            }
        }
        
        adminOutputErr('修改失败');
    }
    
    protected function _setPageHeaderAction($action)
    {
        $this->_setPageHeader($action, '管理员');
    }
}