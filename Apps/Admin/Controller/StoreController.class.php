<?php
namespace Admin\Controller;
use Think\Controller;
use Think\Upload;
use Common\Model\StoreModel;
use Common\Model\UserModel;
use Think\Exception;
class StoreController extends BaseController {
    
    //店铺二维码收款展示
    public function qrcode()
    {
        $this->_setPageAction('固定收款二维码');
        $this->assign('storeMap', $this->getStoreMap());
        return $this->display();
    }
    
    //生成二维码图片
    public function createQr()
    {
        //引入微信库里的二维码库
        $wxPayLibPath = APP_PATH . 'Api/Extend/WxpaySdk';
        require_once $wxPayLibPath . '/example/phpqrcode/phpqrcode.php';
        $url = urldecode(I('get.data'));
        if (I('get.file'))
        {
            header("Content-type: application/octet-stream");
            header("Accept-Ranges: bytes");
            header("Content-Disposition: attachment; filename=qrcode.png");
        }
        \QRcode::png($url, false, QR_ECLEVEL_L, 5);
        
    }
    
    //门店列表
    public function index(){
        $merchantId = session('merchant_id');
        $storeList = D('Store')->getStoreListByMerchantId($merchantId);
        foreach ($storeList as $k => $storeData)
        {
            list($dayStat,$weekStat,$monStat) = D('Order')->getStatByStoreId($storeData['id']);
            $storeList[$k]['day_stat'] = $dayStat;
            $storeList[$k]['week_stat'] = $weekStat;
            $storeList[$k]['mon_stat'] = $monStat;
        }
        $arrAssign = [];
        $arrAssign['list'] = $storeList;
        $this->assign($arrAssign);
        $this->_setPageAction('列表');
        $this->display();
    }
    
    //新增门店
    public function add()
    {
        if(session('user_role') != (UserModel::USER_ROLE_BOSS))
        {
            $this->error('没有权限','index');
        }
        $this->_setPageAction('新增');
        $this->display();
    }
    
    public function addHandler()
    {
        $arrInput = I('post.');
        $arrInput['merchant_id'] = session('merchant_id');
    	$res = D('Store')->addStore($arrInput);
    	if($res !== false)
    	{
    		ajaxRedirect('index');
    	}
    	adminOutputErr('失败');
    }
    
    public function easyEditHandler()
    {
        $arrInput = I('post.');
        $id = $arrInput['id'];
        if($id)
        {
            $res = D('Store')->editStore($arrInput, $id);
            if($res !== false)
            {
                D('Store')->clearStoreByIdFromRedis($id);
                ajaxRedirect('index');
                //ajaxRedirect('easyEdit');
            }
        }
        adminOutputErr('失败');
    }
    
    public function easyEdit()
    {
        $id = I('get.id');
        if($id)
        {
            $where = $this->getWhereByUserRole(session('user_role'));
            $where['id'] = $id;
            unset($where['store_id']);
            $storeData = D('Store')->where($where)->find();
            if($storeData)
            {
                $this->assign('storeInfo',$storeData);
                $this->_setPageAction('店铺编辑');
                $this->display('easyEdit');
                exit;
            }
        }
        $this->redirect('index');
    }
    
    //提交审核,正常请求
    public function submitAudit()
    {
        $storeId = I('get.id');
        if($storeId)
        {
            $data = [];
            $data['audit_status'] = StoreModel::AUDIT_STATUS_SUBMIT;
            $res = D('Store')->editStore($data, $storeId);
            if($res !== false)
            {
                D('Store')->clearStoreByIdFromRedis($storeId);
                $this->success('提交成功');exit;
                //adminOutputData('success');
            }
        }
        $this->error('提交失败');
        //adminOutputErr('提交失败');
    }
    
    //门店信息完善
    public function edit()
    {
        $storeId = I('get.id');
        if($storeId)
        {
            $storeInfo = D('store')->getById($storeId);
            $arrAssign = [];
            $arrAssign['storeInfo'] = $storeInfo;
            $this->assign($arrAssign);
            $this->_setPageAction('编辑门店');
            $this->display();
        }
        else
        {
            $this->redirect('index');
        }
    }
    
    //编辑执行
    public function editHandler()
    {
        $arrInput = I('post.');
        $storeId = $arrInput['store_id'];
        if($storeId)
        {
            $storeInfo = D('Store')->getById($storeId);
            if($storeInfo)
            {
                //多图过滤','号
                $arrInput['audit_images'] = rtrim($arrInput['audit_images'], ',');
                $arrInput['other_authorization'] = rtrim($arrInput['other_authorization'], ',');
                $arrInput['audit_status'] = ($storeInfo['audit_status'] == StoreModel::AUDIT_STATUS_SUCCESS) ? StoreModel::AUDIT_STATUS_MODIFY : StoreModel::AUDIT_STATUS_WAIT;
                $res = D('Store')->editStore($arrInput, $storeId);
                if($res !== false)
                {
                    D('Store')->clearStoreByIdFromRedis($storeId);
                	ajaxRedirect('edit',['id'=>$storeId]);
                }
            }
        }
        adminOutputErr('修改失败');
    }
    
    public function _setPageAction($action)
    {
        $this->_setPageHeader($action, '门店');
    }
    
}