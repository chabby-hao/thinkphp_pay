<?php
namespace Root\Controller;
use Think\Controller;
use Common\Controller\AdminCommonController;
class BaseController extends AdminCommonController {
    
    protected $_noLoginRoute = [
        '/root/root/login'=>1,
        '/root/root/logout'=>1,
        '/root/root/loginhandler'=>1,
    ];
    
    protected function _initialize()
    {
        $route = __ACTION__;
        if( isset($this->_noLoginRoute[strtolower($route)]))
        {
            return true;
        }
        $userId = session('root_id');
        if(!$userId)
        {
            $this->redirect('Root/login');
        }
    }
    
    //获取所有的商户Map,['id'=>$merchantData]
    public function getMerchantMap()
    {
        $merchantList = D('Merchant')->select();
        $merchantMap = arrayMap($merchantList, 'id');
        return $merchantMap;
    }
    
    protected function _setPageHeader($action, $module)
    {
        $this->assign('pageHeaderAction', $action);
        $this->assign('pageHeaderModule', $module);
    }
    
    
}