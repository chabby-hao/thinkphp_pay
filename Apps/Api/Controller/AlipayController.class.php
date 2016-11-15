<?php
/**
 * Copyright (c) 2016,上海诱梦网络
 * 文件名称：AlipayController.class.php
 * 摘    要：支付宝接口控制器类
 * 作    者：王志浩
 * 修改日期：2016.10.17
 */
namespace Api\Controller;
use Think\Controller;
use Api\Msg\ErrMsg;
use Think\Exception;
use Common\Model\MerchantModel;
use Common\Model\StoreApiModel;
use Common\Model\StoreModel;

class AlipayController extends BaseAlipayController {
    
    
    /**
     * 根据用户授权code,在某个店铺创建订单，调用支付宝统一收单交易创建接口
     * 参   数：无
     * 返   回：无
     * 功   能：根据用户授权code,在某个店铺创建订单，调用支付宝统一收单交易创建接口
     * 作   者：王志浩
     * 修改日期： 2016-10-17
     */
    public function createTrade($authCode, $storeId, $totalAmount)
    {
        //先根据authcode换取用户id
        $request = new \AlipaySystemOauthTokenRequest();
        $request->setGrantType('authorization_code');
        $request->setCode($authCode);
        $jsonResponse =  $this->execute($request);
        $response = json_decode($jsonResponse, true);
        $response = $response['alipay_system_oauth_token_response'];
        if(isset($response['user_id']))
        {
            //拿到用户id，开始根据店铺和金额创建新订单
            $storeInfo = D('Store')->getStoreInfoByIdForApi($storeId);
            $this->setAppAuthToken($storeInfo['alipay_token']);//设置授权token
            $arrInput = [];
            $arrInput['total_amount'] = $totalAmount;
            $arrInput['goods_detail'] = $this->getGoodsDetail();
            $arrInput['subject'] = $storeInfo['merchant_name'] . '(' . $storeInfo['store_name'] . ')';
            $arrInput['merchant_id'] = $storeInfo['merchant_id'];
            $arrInput['store_id'] = $storeId;
            $arrInput['user_id'] = 0;//固定收款码，默认操作员id是0
            $orderNo = $this->_createNewOrder($arrInput);
            //支付宝统一收单创建接口
            $request = new \AlipayTradeCreateRequest();
            
            $bizContent = [
                'out_trade_no' => $orderNo,
                // 'subject' => isset($_REQUEST['subject']) ? $_REQUEST['subject'] : 'iphone6',
                'alipay_store_id' => $storeInfo['alipay_store_id'], // 支付宝店铺id
                'extend_params' => [
                    'sys_service_provider_id' => self::ALIPAY_PID // 返佣参数
                ],
                'buyer_id' => $response['user_id'],
            ];
            //http://qr.liantu.com/api.php?text=asdasdasd  二维码生成地址
            unset($arrInput['merchant_id'], $arrInput['store_id'], $arrInput['user_id']);
            $bizContent = array_merge($bizContent, $arrInput);
            $bizContent = json_encode($bizContent);
            
            //先记一段时间日志
            file_put_contents('alipay_trade_create.log', $bizContent."\r\n\r\n", FILE_APPEND);
            
            
            $request->setBizContent($bizContent);
            $request->setNotifyUrl($this->getNotifyUrl());//设置后台通知地址
            $jsonResponse = $this->execute($request);
            $orderResponse = json_decode($jsonResponse, true);
            $orderResponse = $orderResponse['alipay_trade_create_response'];
            if($orderResponse['code'] == 10000)
            {
                $data = [];
                $data['out_trade_no'] = $orderResponse['out_trade_no'];
                $data['trade_no'] = $orderResponse['trade_no'];
                return $data;
            }
            
        }
        return false;
    }
    
    /**
     * 支付宝店铺审核异步通知接口
     * 参   数：无
     * 返   回：无
     * 功   能：支付宝店铺审核异步通知接口
     * 作   者：王志浩
     * 修改日期： 2016-10-17
     */
    public function notifyShop()
    {
        file_put_contents('notifyShop.log', json_encode($_POST)."----".date('Y-m-d H:i:s')."\r\n",FILE_APPEND);
        //签名验证
        if($this->checkNotifySign())
        {
            //通过apply_id拿到店铺申请记录
            $storeApiData = D('StoreApi')->getByApplyId($_POST['apply_id']);
            if( $storeApiData)
            {
                //如果已经收到过通知就不需要进行后续处理,直接返回给支付宝success，退出程序即可
                if( $storeApiData['api_status'] == StoreApiModel::API_STATUS_RECEIVED)
                {
                    echo 'success';
                    exit;
                }
                $where = [];
                $where['apply_id'] = $_POST['apply_id'];
                $time = time();
                //审核通过
               
                M()->startTrans();//开启事务
                try{
                    //修改店铺申请记录
                	$data = [];
                	$data['api_status'] = StoreApiModel::API_STATUS_RECEIVED;
                	$data['audit_status'] = $_POST['audit_status'];
                	$data['result_desc'] = $_POST['result_desc'];
                	$data['update_time'] = $time;
                	$data['api_desc'] = json_encode($_POST);
                	$res = D('StoreApi')->where($where)->save($data);
                	if($res === false)
                	{
                		E('修改店铺接口API失败');
                	}
                	if($_POST['audit_status'] == StoreApiModel::AUDIT_STATUS_AUDIT_SUCCESS)
                	{
                	    //修改店铺审核状态
                	    $storeWhere = [];
                	    $storeWhere['id'] = $storeApiData['store_id'];
                	    $storeData = [];
                	    $storeData['alipay_store_id'] = $_POST['shop_id'];
                	    $storeData['update_time'] = $time;
                	    $storeData['audit_status'] = StoreModel::AUDIT_STATUS_SUCCESS;
                	    $res = D('Store')->where($storeWhere)->save($storeData);
                	    if($res === false)
                	    {
                	    	E('店铺数据修改失败');
                	    }
                	}
                	M()->commit();
                	echo 'success';
                }catch (Exception $e){
                    M()->rollback();
                }
            }
        }
    }
    
    
    /**
     * 授权url同意后的回调地址，获取app_auth_token，同时开店
     * 参   数：无
     * 返   回：无
     * 功   能：授权url同意后的回调地址，获取app_auth_token，同时开店
     * 作   者：王志浩
     * 修改日期： 2016-10-17
     */
    public function getAppAuthToken()
    {
        file_put_contents('mytmp.log', json_encode($_REQUEST)."\r\n\r\n", FILE_APPEND);
        //获取app_auth_code,merchant_id,先拿到app_auth_token,再查询是否merchant_id已经开过店铺，没开过，就新开一个，同时保存app_auth_token到店铺
        $appAuthCode = I('get.app_auth_code');
        $merchantId = I('get.merchant_id', null);//商户id
        if(!$merchantId)
        {
            exit();
        }
        $bizContent = [
	       'grant_type'=>'authorization_code',
	       'code'=>$appAuthCode,//代理授权code
        ];
        $bizContent = json_encode($bizContent);
        $request = new \AlipayOpenAuthTokenAppRequest();
        $request->setBizContent($bizContent);
        $jsonResponse =  $this->execute($request);
        $response = json_decode($jsonResponse, true);
        $response = $response['alipay_open_auth_token_app_response'];
        if($response['code'] == 10000)
        {
            $where = ['id'=>':id'];
            $bind = [':id'=>$merchantId];
            $data = [];
            $data['alipay_token'] = $response['app_auth_token'];
            $data['api_desc'] = $jsonResponse;
            D('Merchant')->where($where)->bind($bind)->save($data);//更新商户信息
            //商户开店
            if( !D('Store')->checkStoreExistsByMerchantId($merchantId))
            {//如果不存在门店,新建门店
                $store = [];
                $store['store_name'] = '默认店铺';
                $store['merchant_id'] = $merchantId;
                $store['create_time'] = $store['update_time'] = time();
                D('store')->add($store);
            }
        }
        echo '<h1>授权成功！请关闭此页</h1>';
    }
    
    /**
     * 扫码支付异步通知
     * 参   数：无
     * 返   回：无
     * 功   能：扫码支付异步通知
     * 作   者：王志浩
     * 修改日期： 2016-10-17
     */
    public function notify()
    {
        //签名验证
        if($this->checkNotifySign())
        {
            //如果交易成功
            if( $_POST['trade_status'] == 'TRADE_SUCCESS' || $_POST['trade_status'] == 'TRADE_FINISHED')
            {
            	$objOrderPay = D('OrderPay');
            	$blnCheckApi = $objOrderPay->checkApiPayState($_POST['out_trade_no']);
            	//如果没有收到过支付成功通知
            	if( !$blnCheckApi)
            	{
            		//直接返回支付成功，修改订单状态
            		$objOrder = D('Order');
            		$payInfo = [];
            		$payInfo['pay_amount'] = $_POST['buyer_pay_amount'];
            		$payInfo['received_amount'] = $_POST['receipt_amount'];
            		$payInfo['buyer_id'] = $_POST['buyer_id'];
            		$payInfo['buyer_name'] = $_POST['buyer_logon_id'];
            		//$payInfo['api_desc'] = json_encode($_POST);
            		$blnSave = $objOrder->orderPaySuccess($_POST['out_trade_no'], $_POST['trade_no'], 'alipay', $payInfo);
            		if($blnSave !== false)
            		{
            			//修改接口记录状态
            			$objOrderPay->saveApiPayState($_POST['out_trade_no'], $_POST['trade_no'], json_encode($_POST));
            		}
            	}
            	echo 'success';
            }
        }
    }
    
    /**
     * 订单查询
     * 参   数：无
     * 返   回：无
     * 功   能：订单查询
     * 作   者：王志浩
     * 修改日期： 2016-10-17
     */
    public function orderQuery()
    {
        $arrInput = checkFieldInArray('token', $this->getPost('post.'));
        $token = $arrInput['token'];
        $this->checkUserToken($token);
        $outTradeNo = $this->getPost('post.out_trade_no', null);//商户订单号
        $tradeNo = $this->getPost('post.trade_no', null);//支付宝订单号
        
        $bizContent = [
            //'out_trade_no' => '20160907110812992',
            //'trade_no' => '2016090721001004430200308937',
        ];
        if( $outTradeNo)//商户单号
        {
            $bizContent['out_trade_no'] = $outTradeNo;
        }
        if( $tradeNo)//支付宝单号
        {
            $bizContent['trade_no'] = $tradeNo;
        }
        //必须至少传商户订单号和支付宝订单号中的一项
        if( !isset($bizContent['out_trade_no']) && !isset($bizContent['trade_no']))
        {
            outputErr(ErrMsg::$err['404']);
        }
        $request = new \AlipayTradeQueryRequest();
        $request->setBizContent( json_encode( $bizContent));
        $jsonResponse = $this->execute($request);
        $this->_orderQueryOut($jsonResponse, $outTradeNo, $tradeNo);
    }
    
    /**
     * 订单查询输出
     * 参   数：无
     * 返   回：无
     * 功   能：订单查询输出
     * 作   者：王志浩
     * 修改日期： 2016-10-17
     */
    public function _orderQueryOut($jsonResponse, $outTradeNo, $tradeNo)
    {
        $response = json_decode($jsonResponse, true);
        $response = $response['alipay_trade_query_response'];
        if( $response['code'] == 10000)
        {
            //如果交易成功
            if( $response['trade_status'] == 'TRADE_SUCCESS' || $response['trade_status'] == 'TRADE_FINISHED')
            {
                $objOrderPay = D('OrderPay');
                $blnCheckApi = $objOrderPay->checkApiPayState($response['out_trade_no']);
                //如果没有收到过支付成功通知
                if( !$blnCheckApi)
                {
                    //直接返回支付成功，修改订单状态
                    $objOrder = D('Order');
                    $payInfo = [];
                    $payInfo['pay_amount'] = $response['buyer_pay_amount'];
                    $payInfo['received_amount'] = $response['receipt_amount'];
                    $payInfo['buyer_id'] = $response['buyer_user_id'];
                    $payInfo['buyer_name'] = $response['buyer_logon_id'];
                    //$payInfo['api_desc'] = $jsonResponse;
                    $orderData = $objOrder->orderPaySuccess($response['out_trade_no'], $response['trade_no'], 'alipay', $payInfo);
                    if($orderData !== false)
                    {
                        //修改接口记录状态
                        $objOrderPay->saveApiPayState($response['out_trade_no'], $response['trade_no'], $jsonResponse);
                    }
                    else
                    {
                        outputErr(ErrMsg::$api['order.modify.error']);
                    }
                    
                }
            }
        }
        elseif($response['code'] == 40004)
        {
            //支付系统那边不存在，但是有可能商户有
        }
        else
        {
            //支付宝系统返回异常，直接输出
            outputErr(ErrMsg::$api['pay.exception']);
        }
        
        if( !isset($orderData))
        {
        	$orderData = D('Order')->getOrderByNo($outTradeNo, $tradeNo);
        }
        if( !$orderData)
        {//如果商户也没有
            outputErr(ErrMsg::$api['trade.not.exists']);
        }
        outputData($this->_formatOrder($orderData));
    }
    
    /**
     * 二维码支付
     * 参   数：无
     * 返   回：无
     * 功   能：二维码支付
     * 作   者：王志浩
     * 修改日期： 2016-10-17
     */
    public function qrPay()
    {
        //过滤参数
        $arrInput = checkFieldInArray('total_amount,token', $this->getPost('post.'));
        $arrInput['subject'] = $this->getSubject();
        $userInfo = $this->checkUserToken($arrInput['token']);
        $arrInput['goods_detail'] = $this->getGoodsDetail();
        $objOrder = D('Order');
        $orderNo = $this->_createNewOrder($arrInput);
        //预下单，不是真的支付，会拿到一个二维码链接
        $request = new \AlipayTradePrecreateRequest();
        
        $bizContent = [
            'out_trade_no' => $orderNo,
            //'subject' => isset($_REQUEST['subject']) ? $_REQUEST['subject'] : 'iphone6',
            'alipay_store_id' => $userInfo['alipay_store_id'], // 支付宝店铺id
            'extend_params' => [
                'sys_service_provider_id' => self::ALIPAY_PID // 返佣参数
            ],
        ];
        //http://qr.liantu.com/api.php?text=asdasdasd  二维码生成地址
        $bizContent = array_merge($bizContent, $arrInput);
        $bizContent = json_encode($bizContent);
        $request->setBizContent($bizContent);
        $request->setNotifyUrl($this->getNotifyUrl());//设置后台通知地址
        $jsonResponse = $this->execute($request);
        $this->_qrPayOut($jsonResponse, $orderNo);
    }
    
    /**
     * 二维码支付输出
     * 参   数：无
     * 返   回：无
     * 功   能：二维码支付输出
     * 作   者：王志浩
     * 修改日期： 2016-10-17
     */
    private function _qrPayOut($jsonResponse, $orderNo)
    {
        $response = json_decode($jsonResponse, true);
        $response = $response['alipay_trade_precreate_response'];
        // 生成条码成功
        if ($response['code'] == 10000)
        {
            $data = [];
            //生成二维码图片,这个图片方便测试时使用，客户端并不需要这个字段
            $data['qr_img'] = 'http://qr.liantu.com/api.php?text=' . $response['qr_code'];
            $data['qr_code'] = $response['qr_code'];
            $data['out_trade_no'] = $response['out_trade_no'];
            $data = array_filter($data);
            outputData($data);
        }
        else
        {
            $data['out_trade_no'] = $orderNo;
            //支付宝系统返回异常，直接输出
            outputErr(ErrMsg::$api['pay.exception'], [], $data);
        }
    }
    
    /**
     * 条码支付
     * 参   数：无
     * 返   回：无
     * 功   能：条码支付
     * 作   者：王志浩
     * 修改日期： 2016-10-17
     */
    public function barPay()
    {
        //过滤参数
        $arrInput = checkFieldInArray('auth_code,total_amount,token', $this->getPost('post.'));
        $arrInput['subject'] = $this->getSubject();
        $userInfo = $this->checkUserToken($arrInput['token']);
        $arrInput['goods_detail'] = $this->getGoodsDetail();
        $orderNo = $this->_createNewOrder($arrInput);
        $request = new \AlipayTradePayRequest();
        $bizContent = [
            'out_trade_no' => $orderNo,
            'scene' => 'bar_code',
            //'auth_code' => $a,
            //'subject' => isset($_REQUEST['subject'])?$_REQUEST['subject']:'iphone6',
            'alipay_store_id' => $userInfo['alipay_store_id'],//支付宝店铺id
            'extend_params' => [
                'sys_service_provider_id' => self::ALIPAY_PID//返佣参数
            ],
            /* 'goods_detail' => [
                [
                    'goods_id' => 'apple-01',
                    'goods_name' => 'ipad',
                    'quantity' => 1,
                    'price' => 0.01 
                ]
            ], */
            //'total_amount' => 3000000,
        ];
        $bizContent = array_merge($bizContent, $arrInput);
        $bizContent = json_encode($bizContent);
        file_put_contents('barPay.log', $bizContent."----".date('Y-m-d H:i:s')."\r\n",FILE_APPEND);
        $request->setBizContent($bizContent);
        $request->setNotifyUrl($this->getNotifyUrl());//设置后台通知地址
        $jsonResponse = $this->execute($request);
        $this->_barPayOut($jsonResponse, $orderNo);
    }
    
    /**
     * 格式化输出
     * 参   数：无
     * 返   回：无
     * 功   能：格式化输出
     * 作   者：王志浩
     * 修改日期： 2016-10-17
     */
    private function _barPayOut($jsonResponse, $orderNo)
    {
        /*
         * //返回此类型为等待用户输入密码,需要轮询
        * {"alipay_trade_pay_response":{"code":"10003","msg":" order success pay inprocess","buyer_logon_id":"503***@qq.com","buyer_pay_amount":"0.00","buyer_user_id":"2088012049401966","invoice_amount":"0.00","open_id":"20881042887781575158813220010596","out_trade_no":"20160913114747732","point_amount":"0.00","receipt_amount":"0.00","total_amount":"3000000.00","trade_no":"2016091321001004960256548386"},"sign":"pL9CqSvRC6CXP0qDSC4QSITFP0uUujbwPqBdb+6r\/5VaXeSwiyBy+GVz6fSY+i4JZe1Er4wOvPqK4vSRbFNm0sdWL7phWfkfq+\/RHWhLx8CwAJQ6d6FvDz\/G2QqTjYhdHcBacc3di56Ayb0SSvFFAd85NA6SWL249Tyvz8OaL3c="}
        *
        */
        $response = json_decode($jsonResponse, true);
        $response = $response['alipay_trade_pay_response'];
        $data = [];
        $data['buyer_logon_id'] = $response['buyer_logon_id'];//支付宝账户
        //$data['buyer_pay_amount'] = $response['buyer_pay_amount'];
        $data['receipt_amount'] = floatval($response['receipt_amount']);//卖家收到的金额
        $data['total_amount'] = floatval($response['total_amount']);
        $data['trade_no'] = $response['trade_no'];
        $data['out_trade_no'] = $orderNo;
        $data['create_time'] = time();
        //$data['invoice_amount'] = $response['invoice_amount'];
        //$data['point_amount'] = $response['point_amount'];
        // 支付成功
        if ($response['code'] == 10000)
        {
            $objOrderPay = D('OrderPay');
            $blnCheckApi = $objOrderPay->checkApiPayState($response['out_trade_no']);
            //如果没有收到过支付成功通知
            if( !$blnCheckApi)
            {
                //直接返回支付成功，修改订单状态
                $objOrder = D('Order');
                $payInfo = [];
                $payInfo['pay_amount'] = $response['buyer_pay_amount'];
                $payInfo['received_amount'] = $response['receipt_amount'];
                $payInfo['buyer_id'] = $response['buyer_user_id'];
                $payInfo['buyer_name'] = $response['buyer_logon_id'];
                //$payInfo['api_desc'] = $jsonResponse;
                $blnSave = $objOrder->orderPaySuccess($data['out_trade_no'], $data['trade_no'], 'alipay', $payInfo);
                if($blnSave !== false)
                {
                    //修改接口记录状态
                    $objOrderPay->saveApiPayState($data['out_trade_no'], $data['trade_no'], $jsonResponse);
                }
            }
            
            //$data['fund_bill_list'] = $response['fund_bill_list'];
            $data['gmt_payment'] = strtotime($response['gmt_payment']);
            $data = array_filter($data);
            outputData($data);
        }
        elseif ($response['code'] == 10003)
        { // 或者等待用户输入密码支付，需要之后轮询订单支付情况
            $data = array_filter($data);
            outputErr(ErrMsg::$api['pay.password.wait'], [], $data);
        }
        elseif($response['code'] == 40004)
        {//付款码过期，重新输入
            $data = array_filter($data);
            outputErr(ErrMsg::$api['pay.token.invalid'], [], $data);
        }
        else
        {
            $data = array_filter($data);
            //支付宝系统返回异常，直接输出
            outputErr(ErrMsg::$api['pay.exception'], [], $data);
        }
    }
    
    
    
    //退款
    /**
     * 支付宝退款
     * 参   数：无
     * 返   回：无
     * 功   能：支付宝退款
     * 作   者：王志浩
     * 修改日期： 2016-10-17
     */
    public function refund()
    {
        $arrInput = checkFieldInArray('token', $this->getPost('post.'));
        $this->checkUserToken($arrInput['token']);
        $outTradeNo = $this->getPost('post.out_trade_no', null);//商户订单号
        $tradeNo = $this->getPost('post.trade_no', null);//支付宝订单号
        $refundReason = $this->getPost('post.refund_reason', '正常退款');
        
        //必须至少传商户订单号和支付宝订单号中的一项
        if( !$outTradeNo && !$tradeNo)
        {
        	outputErr(ErrMsg::$err['404'], ['msg'=>'请至少传商户单号和支付宝单号的一种']);
        }
        
        $objOrder = D('Order');
        $arrOrderData = $objOrder->getOrderByNo($outTradeNo, $tradeNo);
        
        //没有这个订单，输出
        if(!$arrOrderData)
        {
            outputErr(ErrMsg::$err['order.non.existend']);
        }
        
        //新增退款单
        $objRefund = D('OrderRefund');
        $arrOrderRefund = [];
        $arrOrderRefund['order_id'] = $arrOrderData['id'];
        $arrOrderRefund['refund_amount'] = $arrOrderData['total_amount'];
        $arrRefundData = $objRefund->createOrderRefund($arrOrderRefund);
        if( $arrRefundData === false)
        {
            outputErr(ErrMsg::$err['refund.create.error']);
        }
        
        
        //根据token判断是否有权限执行退款功能,  暂时先预留
        
        $bizContent = [
            //'out_trade_no' => '20160907110812992',
            //'trade_no' => '2016090721001004430200308937',
            'refund_reason' => $refundReason,
            //退款金额根据订单号查询出来，目前是全额退
            'refund_amount' => floatval($arrOrderRefund['refund_amount']),
            'out_request_no'=>$arrRefundData['refund_no'],
        ];
        if( $outTradeNo)//商户单号
        {
        	$bizContent['out_trade_no'] = $outTradeNo;
        }
        if( $tradeNo)//支付宝单号
        {
        	$bizContent['trade_no'] = $tradeNo;
        }
        
        $request = new \AlipayTradeRefundRequest();
        $request->setBizContent( json_encode( $bizContent));
        $jsonResponse = $this->execute($request);
        $this->_refundOut($jsonResponse, $arrRefundData);
    }
    
    /**
     * 支付宝退款输出
     * 参   数：无
     * 返   回：无
     * 功   能：支付宝退款输出
     * 作   者：王志浩
     * 修改日期： 2016-10-17
     */
    private function _refundOut($jsonResponse, $arrRefundData)
    {
        $response = json_decode($jsonResponse, true);
        $response = $response['alipay_trade_refund_response'];
        $objRefund = D('OrderRefund');
        if($response['code'] == 10000)
        {
            //退款成功
            D('Order')->orderRefundSuccess($arrRefundData['order_id'], $arrRefundData['refund_amount']);
            $objRefund->orderRefundSuccess($arrRefundData['order_id'], $arrRefundData['refund_no'], ['api_desc'=>$jsonResponse]);
            
            $data = [];
            $data['trade_no'] = $response['trade_no'];
            $data['out_trade_no'] = $response['out_trade_no'];
            $data['buyer_logon_id'] = $response['buyer_logon_id'];
            //$data['fund_change'] = $response['fund_change'];
            $data['refund_fee'] = floatval($response['refund_fee']);
            $data['gmt_refund_pay'] = strtotime($response['gmt_refund_pay']);
            //$data['refund_detail_item_list'] = $response['refund_detail_item_list'];
            //$data['send_back_fee'] = $response['send_back_fee'];
            $data = array_filter($data);
            outputData($data);
        }
        elseif(isset($response['sub_code']) && $response['sub_code'] == 'ACQ.SELLER_BALANCE_NOT_ENOUGH')
        {
            //退款余额不足
            D('Order')->orderRefundFail($arrRefundData['order_id']);
            $objRefund->OrderRefundFail($arrRefundData['order_id'], $arrRefundData['refund_no'], ['api_desc'=>$jsonResponse]);
            outputErr(ErrMsg::$api['refund.not.enough']);
        }
        elseif(isset($response['sub_code']) && $response['sub_code'] == 'ACQ.TRADE_HAS_FINISHED')
        {
            //交易已完结
            D('Order')->orderRefundFail($arrRefundData['order_id']);
            $objRefund->OrderRefundFail($arrRefundData['order_id'], $arrRefundData['refund_no'], ['api_desc'=>$jsonResponse]);
            outputErr(ErrMsg::$api['refund.has.finished']);
        }
        else 
        {
            //退款失败
            D('Order')->orderRefundFail($arrRefundData['order_id']);
            $objRefund->OrderRefundFail($arrRefundData['order_id'], $arrRefundData['refund_no'], ['api_desc'=>$jsonResponse]);
            outputErr(ErrMsg::$api['refund.api.fail']);
        }
    }
}