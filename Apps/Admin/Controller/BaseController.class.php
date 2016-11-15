<?php
namespace Admin\Controller;
use Think\Controller;
use Common\Model\UserModel;
use Common\Controller\AdminCommonController;
class BaseController extends AdminCommonController {
    
    public $intPageLimit = 10;//默认分页数量
    
    //需要小写
    protected $_noLoginRoute = [
	   '/admin/user/login'=>1,
	   '/admin/user/logout'=>1,
	   '/admin/user/loginhandler'=>1,
	   '/admin/upload/fileupload'=>1,
    ];
    
    protected function _initialize()
    {
        $route = __ACTION__;
        if( isset($this->_noLoginRoute[strtolower($route)]))
        {
            return true;
        }
        $userId = session('user_id');
        if(!$userId)
        {
            $this->redirect('User/login');
        }
    }
    
    //获取当前权限下的店铺Map,['id'=>$storeData]
    public function getStoreMap()
    {
        $userRole = session('user_role');
        $where = ['merchant_id'=>session('merchant_id')];
        if($userRole == UserModel::USER_ROLE_ACCOUNTANT)
        {
        	$where['store_id'] = session('store_id');
        }
        $storeList = D('Store')->where($where)->select();
        $storeMap = arrayMap($storeList, 'id');
        return $storeMap;
    }
    
    //获取用户权限
    public function getUserRole()
    {
        $userRole = session('user_role');
        if(in_array($userRole, [UserModel::USER_ROLE_ACCOUNTANT, UserModel::USER_ROLE_BOSS]))
        {
            return $userRole;
        }
        //非法用户
        $this->redirect('user/login');
    }
    
    //获取根据角色分析出来的查询条件
    public function getWhereByUserRole($userRole, $boolUserRole= false, $strWherePre = '')    {        $where = [];        switch ($userRole)        {
        	case UserModel::USER_ROLE_BOSS:
        	    $where[$strWherePre . 'merchant_id'] = session('merchant_id');
        	    break;
        	case UserModel::USER_ROLE_ACCOUNTANT:
        	    $where[$strWherePre . 'merchant_id'] = session('merchant_id');
        	    $where[$strWherePre . 'store_id'] = session('store_id');
        	    if($boolUserRole)
        	    {
        	        $where['user_role'] = ['in',[UserModel::USER_ROLE_ACCOUNTANT, UserModel::USER_ROLE_OPERATOR]];
        	    }
        	    break;
        }
        return $where;
    }
    
    protected function _setPageHeader($action, $module)
    {
    	$this->assign('pageHeaderAction', $action);
    	$this->assign('pageHeaderModule', $module);
    }
    
}