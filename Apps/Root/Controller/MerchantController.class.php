<?php
namespace Root\Controller;
use Think\Controller;
use Think\Exception;
use Common\Model\MerchantModel;
use Common\Model\UserModel;
class MerchantController extends BaseController {
    
    //商户列表
    public function index(){
        $merchantList = D('Merchant')->select();
        $arrAssign = [];
        $arrAssign['list'] = $merchantList;
        $this->assign($arrAssign);
        $this->_setPageAction('列表');
        $this->display();
    }
    
    //添加商户展示
    public function add()
    {
        $this->_setPageAction('新增商户');
        $this->display();
    }
    
    //添加执行
    public function addHandler()
    {
        $arrInput = adminCheckPost('merchant_name,user_name,user_pwd');
        $merchantName = $arrInput['merchant_name'];
        $userName = $arrInput['user_name'];
        $userPwd = $arrInput['user_pwd'];
        $remark = $arrInput['remark'];
        $this->_checkSameMerchant($merchantName, $userName);
        
        //开始新增商户  和店铺  和用户
        M()->startTrans();
        try {
            if( ($merchantId = D('Merchant')->add($arrInput)) === false)
            {
                E('商户添加失败');
            }
            $arrInput['merchant_id'] = $merchantId;
            if( ($storeId = D('Store')->addStore($arrInput)) === false)
            {
            	E('添加门店失败');
            }
            $arrInput['store_id'] = $storeId;//默认关联第一家店铺
            $arrInput['user_role'] = UserModel::USER_ROLE_BOSS;
            if( D('User')->addUser($arrInput) === false)
            {
                E('用户添加失败');
            }
           
        }catch (Exception $e){
            M()->rollback();
            $message = $e->getMessage();
            adminOutputErr($message);
        }
        M()->commit();
        ajaxRedirect('index');
    }
    
    //商户编辑
    public function edit()
    {
        $merchantId = I('get.id');
        if($merchantId)
        {
            $merchantInfo = D('Merchant')->getById($merchantId);
            $userInfo = D('User')->where(['merchant_id'=>':merchant_id','user_role'=>UserModel::USER_ROLE_BOSS])->bind([':merchant_id'=>$merchantId])->find();
            $merchantInfo = array_merge($merchantInfo, $userInfo);
            $this->assign('merchantInfo', $merchantInfo);
            $this->assign('merchantId', $merchantId);
            $this->_setPageAction('编辑商户');
            $this->display();
        }
        else 
        {
            $this->redirect('index');
        }
    }
    
    //商户编辑执行
    public function editHandler()
    {
        $arrInput = adminCheckPost('merchant_id');
        $merchantId = $arrInput['merchant_id'];
        if($merchantId)
        {
            //查询店铺
            D('Merchant')->where('id=:id')->bind(':id', $merchantId)->save($arrInput);
            if($arrInput['user_pwd'])
            {
                $userPwd = D('User')->generateUserPwd($arrInput['user_pwd']);
                $res = D('User')->where(['merchant_id'=>':merchant_id','user_role'=>UserModel::USER_ROLE_BOSS])->bind([':merchant_id'=>$merchantId])->save(['user_pwd'=>$userPwd]);
                if($res === false)
                {
                	adminOutputErr('修改失败');
                }
            }
        }
        D('Merchant')->clearMerchantInfoFromRedis($merchantId);
        ajaxRedirect('edit',['id'=>$merchantId]);
    }
    
    //检测同名商户和同名用户
    protected function _checkSameMerchant($merchantName, $userName)
    {
        $merchantName = D('Merchant')->getByMerchantName($merchantName);
        if($merchantName)
        {
        	adminOutputErr('商户名已存在');
        }
        $userInfo = D('User')->getByUserName($userName);
        if($userInfo)
        {
        	adminOutputErr('用户名已存在');
        }
    }
    
    protected function _setPageAction($action)
    {
        $this->_setPageHeader($action, '商户');
    }
}