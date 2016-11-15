<?php
namespace Admin\Controller;
use Think\Controller;
use Common\Model\UserModel;
use Think\Exception;
use Think\Verify;
class UserController extends BaseController {
    
    //商户登录
    public function login()
    {
        layout(false);
        $this->display(); 
    }
    
    public function logout()
    {
        session('[destroy]');
        $this->redirect('login');
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
            $userInfo = D('User')->getUser($userName, $userPwd);
            if(in_array($userInfo['user_role'], [UserModel::USER_ROLE_ACCOUNTANT, UserModel::USER_ROLE_BOSS]))
            {
                if($userInfo !== false)
                {
                	session('user_id',$userInfo['id']);
                	session('store_id', $userInfo['store_id']);
                	session('merchant_id',$userInfo['merchant_id']);
                	session('user_role',$userInfo['user_role']);
                	session('user_name',$userInfo['user_name']);
                	session('user_pickname',$userInfo['user_pickname']);
                	session('alipay_token',$userInfo['alipay_token']);
                	ajaxRedirect('Index/index');
                }
            }
        }
        adminOutputErr('用户名或者密码错误');
    }
    
    public function index()
    {
        $userRole = $this->getUserRole();
        $where = $this->getWhereByUserRole($userRole, true, 'user.');
        $userList = D('User')->field('user.*,store.store_name')->join('store on user.store_id=store.id')->where($where)->order('store_id desc')->select();
        $arrAssign = [];
        $arrAssign['userList'] = $userList;
        $this->assign($arrAssign);
        $this->_setPageHeaderAction('员工列表');
        $this->display();        
    }
    
    //新增店员
    public function add()
    {
        $userRole = $this->getUserRole();
        $userRoleMap = UserModel::$userRoleMap;
        unset($userRoleMap[UserModel::USER_ROLE_BOSS]);//只有一个老板
        $where = ['merchant_id'=>session('merchant_id')];
        if($userRole == UserModel::USER_ROLE_ACCOUNTANT)
        {
            unset($userRoleMap[UserModel::USER_ROLE_ACCOUNTANT]);
        }
        $storeMap = $this->getStoreMap();
        $this->assign('storeMap', $storeMap);
        $this->assign('userRoleMap', $userRoleMap);
        $this->_setPageHeaderAction('新增店员');
        $this->display();
    }
    
    //新增店员执行
    public function addHandler()
    {
        $arrInput = I('post.');
        $arrInput['merchant_id'] = session('merchant_id');
        try{
            $res = D('User')->addUser($arrInput);
            if($res !== false)
            {
            	ajaxRedirect('index');
            }
        }catch (Exception $e)
        {
            adminOutputErr('子账号已占用，请更换');
        }
    }
    
    public function modifyPwd()
    {
        $id = I('get.id', 0);
        if($id > 0)
        {
            $userRole = $this->getUserRole();
            $where = $this->getWhereByUserRole($userRole);
            $condition = ['id'=>':id'];
            $where = array_merge($where, $condition);
            $bind = [':id'=>$id];
            $userInfo = D('User')->where($where)->bind($bind)->find();
            if($userInfo)
            {
                $this->_setPageHeaderAction('修改密码');
                $this->assign('userInfo',$userInfo);
                return $this->display('modifyPwd');
            }
        }
        $this->redirect('index');
    }
    
    public function modifyPwdHandler()
    {
        $arrInput = I('post.');
        if($arrInput)
        {
            $objUser = D('User');
            unset($arrInput['user_name']);
            $userId = $arrInput['user_id'];
            unset($arrInput['user_id']);
            $res = D('User')->editUser($arrInput, $userId);
            if($res !== false)
            {
                ajaxRedirect('index');
                //ajaxRedirect('modifyPwd',['id'=>$userId]);
            }
        }
        adminOutputErr('修改失败');
    }
    
    protected function _setPageHeaderAction($action)
    {
        $this->_setPageHeader($action, '店员');
    }
    
}