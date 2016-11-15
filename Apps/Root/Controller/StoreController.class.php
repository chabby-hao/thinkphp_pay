<?php
namespace Root\Controller;
use Think\Controller;
use Think\Exception;
use Common\Model\MerchantModel;
use Common\Model\UserModel;
use Common\Model\StoreModel;
use Api\Controller\AlipayController;
use Api\Controller\BaseAlipayController;
class StoreController extends BaseController {
    
    //商户列表
    public function index(){
        $storeList = D('Store')->select();
        $arrAssign = [];
        $arrAssign['list'] = $storeList;
        $this->assign($arrAssign);
        $this->_setPageAction('列表');
        $this->display();
    }
    
    //待审核商户列表
    public function waitAudit()
    {
        $where = $bind = [];
        $storeAudit = I('post.audit_status', null);
        $merchantId = I('post.merchant_id', null);
        //审核状态
        if($storeAudit)
        {
            $where['audit_status'] = ':audit_status';
            //$bind[':audit_status'] = StoreModel::AUDIT_STATUS_SUBMIT;
            $bind[':audit_status'] = $storeAudit;
        }
        if($merchantId)
        {
            $where['merchant_id'] = ':merchant_id';
            $bind[':merchant_id'] = $merchantId;
        }
        //$where['audit_status'] = StoreModel::AUDIT_STATUS_SUBMIT;
        $storeList = D('Store')->field('merchant.id as merchant_id,merchant.merchant_name,store.*')->join('merchant on store.merchant_id=merchant.id')->where($where)->bind($bind)->select();
        $arrAssign = [];
        $arrAssign['list'] = $storeList;
        $this->assign($arrAssign);
        $this->assign('merchantMap', $this->getMerchantMap());
        $this->assign('auditStatusMap', StoreModel::$auditStatusMap);
        $this->_setPageAction('门店列表');
        $this->display('waitAudit');
    }
    
    //审核查看
    public function auditLook()
    {
        $storeId = I('get.id');
        if($storeId)
        {
        	$storeInfo = D('store')->getById($storeId);
        	$arrAssign = [];
        	$arrAssign['storeInfo'] = $storeInfo;
        	$storeApiList = D('StoreApi')->where('store_id=:store_id')->bind([':store_id'=>$storeId])->order('create_time DESC')->select();
        	$arrAssign['storeApiList'] = $storeApiList;
        	$this->assign($arrAssign);
        	$this->_setPageAction('审核门店');
        	$this->display('auditLook');
        }
        else
        {
        	$this->redirect('waitAudit');
        }
    }

    //支付宝图片效验，确保图片在支付宝那边是最新的
    protected function _updateAlipayImg($storeInfo, AlipayController $objAlipay)
    {
        if($storeInfo && is_array($storeInfo))
        {
            $storeId = $storeInfo['id'];
            $imgMap = [];
            $imgMap['brand_logo'] = $storeInfo['brand_logo'];
            $imgMap['main_image'] = $storeInfo['main_image'];
            $imgMap['audit_images'] = explode(',', $storeInfo['audit_images']);//多图
            $imgMap['other_authorization'] = explode(',', $storeInfo['other_authorization']);//多图
            
            $imgMap['licence'] = $storeInfo['licence'];
            $imgMap['business_certificate'] = $storeInfo['business_certificate'];
            $imgMap['auth_letter'] = $storeInfo['auth_letter'];
            $imgMap = array_filter($imgMap);
            if($imgMap)
            {
                $data = [];
                foreach ($imgMap as $key => $row)
                {
                    if(is_array($row))
                    {
                        $tmp = [];
                        foreach ($row as $imgPath)
                        {
                            //只有在原来上传记录找不到时候才调用支付宝上传接口
                            if( !D('AlipayImg')->getByLocalPath($imgPath))
                            {
                                if($this->_alipayImgUpload($imgPath, $objAlipay) !== false)
                                {
                                	$tmp[] = $this->_alipayImgUpload($imgPath, $objAlipay);
                                }
                            }
                        }
                        $data[$key . '_id'] = implode(',', $tmp);
                    }
                    else
                    {
                        //只有在原来上传记录找不到时候才调用支付宝上传接口
                        if( !D('AlipayImg')->getByLocalPath($row))
                        {
                            if($this->_alipayImgUpload($row, $objAlipay) !== false)
                            {
                            	$data[$key . '_id'] = $this->_alipayImgUpload($row, $objAlipay);
                            }
                        }
                        
                    }
                }
                $data = array_filter($data);
                if(D('Store')->editStore($data, $storeId) !== false)
                {
                    D('Store')->clearStoreByIdFromRedis($storeId);
                    return array_merge($storeInfo, $data);
                }
            }
        }
        return false;
    }
    
    //支付宝上传图片
    protected function _alipayImgUpload($imgPath, AlipayController $objAlipay)
    {
        $imgScriptPath = ROOT_PATH . '/Web' .  ltrim($imgPath, '.');
        $imgExt = pathinfo($imgScriptPath, PATHINFO_EXTENSION);
        $request = new \AlipayOfflineMaterialImageUploadRequest();
        $request->setImageType($imgExt);
        $request->setImageName($imgPath);
        $request->setImageContent("@" . $imgScriptPath);
        $jsonResponse = $objAlipay->execute($request);
        $response = json_decode($jsonResponse, true);
        $response = $response[str_replace('.', '_', $request->getApiMethodName()) . '_response'];
        if($response['code'] == 10000)
        {
            $data = [];
            $data['local_path'] = $imgPath;
            $data['alipay_id'] = $response['image_id'];
            $data['alipay_url'] = $response['image_url'];
            $lastId = D('AlipayImg')->add($data);
            return $lastId ? $data['alipay_id'] : false;
        }
        else
        {
            return false;
        }
    }
    
    //支付宝审核执行
    public function auditHandler()
    {
        $id = I('post.id');
        if($id)
        {
            $storeInfo = D('Store')->getById($id);
            $merchantInfo = D('Merchant')->getMerchantByStoreId($id);
            $alipayToken = $merchantInfo['alipay_token'];//支付宝授权token
            if($storeInfo && $alipayToken)
            {
                $objAlipay = new AlipayController();
                $objAlipay->setAppAuthToken($alipayToken);
                $storeInfo = $this->_updateAlipayImg($storeInfo, $objAlipay);//更新阿里图片
                $bizContent = $this->_getAlipayShopInfo($storeInfo);
                $requestId = date('YmdHis') . mt_rand(1000, 9999);                $extendContent = [                    'request_id' => $requestId,
                    'operate_notify_url' => U('Api/Alipay/notifyShop', '', true, true),
                    'op_role' => 'ISV',
                    'biz_version' => '2.0',
                    'isv_uid'=> BaseAlipayController::ALIPAY_PID,//返佣Uid                ];
                $bizContent = array_merge($bizContent, $extendContent);
                //var_dump($bizContent);exit;
                $bizContent = json_encode($bizContent);
                $request = new \AlipayOfflineMarketShopCreateRequest();
                $request->setBizContent($bizContent);
                $jsonResponse = $objAlipay->execute($request);
                $response = json_decode($jsonResponse, true);
                $response = $response[str_replace('.', '_', $request->getApiMethodName()) . '_response'];
                if($response['code'] == 10000)
                {
                    $data = [];
                    $data['store_id'] = $id;
                    $data['apply_id'] = $response['apply_id'];
                    $data['request_id'] = $requestId;
                    $data['audit_status'] = $response['audit_status'];
                    $data['create_time'] = $data['update_time'] = time();
                    $res = D('StoreApi')->add($data);
                    if($res !== false)
                    {
                        adminOutputData('success');
                    }
                }
                else 
                {
                    $msg = $response['sub_msg'] ? $response['sub_msg'] : $response['msg'];
                    adminOutputErr($msg);
                }
            }
            else 
            {
                adminOutputErr('信息有误，请确认该商户否授权');
            }
        }
        else
        {
            ajaxRedirect('waitAudit');
        }
    }
    
    public function editHandler()
    {
        $arrInput = I('post.');
        $storeId = $arrInput['store_id'];
        if($storeId)
        {
        	//多图过滤','号
        	$arrInput['audit_images'] = rtrim($arrInput['audit_images'], ',');
        	$arrInput['other_authorization'] = rtrim($arrInput['other_authorization'], ',');
        	$res = D('Store')->editStore($arrInput, $storeId);
        	if($res !== false)
        	{
        	    D('Store')->clearStoreByIdFromRedis($storeId);
        		ajaxRedirect('auditLook',['id'=>$storeId]);
        	}
        }
        adminOutputErr('修改失败');
    }
    
    //门店创建
    protected function _getAlipayShopInfo($storeInfo)
    {
    	$bizContent = [
    	    'shop_id' => $storeInfo['alipay_store_id'],            'store_id' => $storeInfo['id'],            'category_id' => $storeInfo['category_id'],            'brand_name' => $storeInfo['brand_name'],            'brand_logo' => $storeInfo['brand_logo_id'],            'main_shop_name' => $storeInfo['main_shop_name'],            'branch_shop_name' => $storeInfo['branch_shop_name'],            'province_code' => $storeInfo['province_code'],            'city_code' => $storeInfo['city_code'],            'district_code' => $storeInfo['district_code'],            'address' => $storeInfo['address'],            'longitude' => $storeInfo['longitude'],            'latitude' => $storeInfo['latitude'],            'contact_number' => $storeInfo['contact_number'],            'notify_mobile' => $storeInfo['notify_mobile'],            'main_image' => $storeInfo['main_image_id'],            'audit_images' => $storeInfo['audit_images_id'],            'licence' => $storeInfo['licence_id'],            'licence_code' => $storeInfo['licence_code'],
            'licence_name' => $storeInfo['licence_name'],            'business_certificate' => $storeInfo['business_certificate_id'],
            'other_authorization' => $storeInfo['other_authorization_id'],                    ];
    	return array_filter($bizContent);
    }
    
    protected function _setPageAction($action)
    {
        $this->_setPageHeader($action, '门店');
    }
}