<?php
namespace Admin\Controller;
use Think\Controller;
use Think\Exception;
use Common\Model\MerchantModel;
use Common\Model\UserModel;
class MerchantController extends BaseController {
    
    //商户编辑
    public function edit()
    {
        $merchantId = session('merchant_id');
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
        $merchantId = session('merchant_id');
        $arrInput = I('post.');
        if($merchantId)
        {
            //查询店铺
            $res = D('Merchant')->where('id=:id')->bind(':id', $merchantId)->save($arrInput);
            if($res === false)
            {
                adminOutputErr('修改失败');
            }
        }
        D('Merchant')->clearMerchantInfoFromRedis($merchantId);
        ajaxRedirect('edit',['id'=>$merchantId]);
    }
    
    protected function _setPageAction($action)
    {
        $this->_setPageHeader($action, '商户');
    }
}