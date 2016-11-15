<?php
//微信支付控制器
namespace Api\Controller;
use Think\Controller;
use Api\Msg\ErrMsg;
use Think\Exception;
use Common\Model\MerchantModel;
use Common\Model\OrderModel;
use Common\Model\OrderRefundModel;

class WxpayController extends BaseWxpayController {
    
   
    //创建微信预付单,用在wap支付的时候
    public function createTrade($openid, $storeInfo, $totalAmount)
    {
        require_once $this->wxpaySdkpath . '/example/WxPay.JsApiPay.php';
        
        $arrInput = [];
        $arrInput['total_amount'] = $totalAmount;
        $arrInput['goods_detail'] = $this->getGoodsDetail();
        $arrInput['subject'] = $storeInfo['merchant_name'] . '(' . $storeInfo['store_name'] . ')';
        $arrInput['merchant_id'] = $storeInfo['merchant_id'];
        $arrInput['store_id'] = $storeInfo['id'];
        $arrInput['user_id'] = 0;//固定收款码，默认操作员id是0
        $orderNo = $this->_createNewOrder($arrInput);
        
        //②、统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetBody($arrInput['subject']);
        $input->SetOut_trade_no($orderNo);
        $input->SetTotal_fee($arrInput['total_amount'] * 100);//单位分
        $input->SetNotify_url(U('/Api/Wxpay/notify', '', false, true));
        $input->SetTrade_type("JSAPI");
        //$input->SetSubOpenid($subOpenId);
        $input->SetOpenid($openid);
        $input->setSubAppid($storeInfo['wechat_app_id']);
        $input->setSubMchId($storeInfo['wechat_mch_id']);
        $result = \WxPayApi::unifiedOrder($input);
        
        if($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS')
        {
            //return $result;
            return ['result'=>$result,'order_no'=>$orderNo];
            /* $data = [];
            $data['prepay_id'] = $result['prepay_id'];//有效期2小时,预支付id
            return $data; */
        }
        return false;
        
    }
    
    //条码支付
    public function barPay()
    {
        //过滤参数
        $arrInput = checkFieldInArray('auth_code,total_amount,token', $this->getPost('post.'));
        $arrInput['subject'] = $this->getSubject();
        $userInfo = $this->checkUserToken($arrInput['token']);
        $arrInput['goods_detail'] = $this->getGoodsDetail();
        $orderData = $this->_createNewOrder($arrInput, true);
        $orderNo = $orderData['order_no'];
        require $this->wxpaySdkpath . '/example/WxPay.MicroPay.php';
        $input = new \WxPayMicroPay();
    	$input->SetAuth_code($arrInput['auth_code']);
    	$input->SetBody($this->getSubject());
    	$input->SetTotal_fee($arrInput['total_amount'] * 100);//单位分
    	$input->SetOut_trade_no($orderNo);
    	$input->setSubMchId($this->getWxpaySubMchId($arrInput['token']));
    	$input->setSubAppid($this->getWxpaySubAppid($arrInput['token']));
    	$microPay = new \MicroPay();
    	$arrResponse = $microPay->pay($input);
    	$this->_barPayOut($arrResponse, $userInfo, $orderData);
    }
    
    //格式化输出条码支付
    private function _barPayOut($arrResponse, $userInfo, $orderData)
    {
        $orderNo = $orderData['order_no'];
        $create_time = $orderData['create_time'];
        //等待用户输入密码
        if( $arrResponse === 2)
        {
            //需要同时返给订单号，供继续查询
        	outputErr(ErrMsg::$api['pay.password.wait'], [], ['out_trade_no'=>$orderNo,'create_time'=>$create_time]);
        }
        elseif( $arrResponse === false)
        {//支付失败
            outputErr(ErrMsg::$api['pay.token.invalid']);
        }
        else
        {
            $arrResponse['openid'] = $arrResponse['sub_openid'] ? $arrResponse['sub_openid'] : $arrResponse['openid'];
            $data = [];
            $nickname = $this->getWxpayUserPickname($arrResponse['openid'], $userInfo['wechat_app_id'], $userInfo['wechat_app_secret']);//微信昵称
            $data['buyer_logon_id'] = $nickname ? : $arrResponse['openid'];
            $data['receipt_amount'] = $arrResponse['settlement_total_fee'] ? $arrResponse['settlement_total_fee'] / 100: $arrResponse['total_fee'] / 100;//卖家收到的金额
            $data['total_amount'] = $arrResponse['total_fee'] / 100;
            $data['trade_no'] = $arrResponse['transaction_id'];
            $data['out_trade_no'] = $arrResponse['out_trade_no'];
            //$data['fund_bill_list'] = $response['fund_bill_list'];
            $data['gmt_payment'] = strtotime($arrResponse['time_end']);
            $objOrderPay = D('OrderPay');
            $blnCheckApi = $objOrderPay->checkApiPayState($data['out_trade_no']);
            //如果没有收到过支付成功通知
            if( !$blnCheckApi)
            {
            	//直接返回支付成功，修改订单状态
            	$objOrder = D('Order');
            	$payInfo = [];
            	$payInfo['pay_amount'] = $arrResponse['cash_fee'] / 100;
            	$payInfo['received_amount'] = $data['receipt_amount'];
            	$payInfo['buyer_id'] = $arrResponse['openid'];
            	$payInfo['buyer_name'] = $arrResponse['openid'];
            	$payInfo['buyer_nickname'] = $nickname;
            	//$payInfo['api_desc'] = json_encode($arrResponse);
            	$blnSave = $objOrder->orderPaySuccess($data['out_trade_no'], $data['trade_no'], OrderModel::PAY_TYPE_WXPAY, $payInfo);
            	if($blnSave !== false)
            	{
            		//修改接口记录状态
            		$objOrderPay->saveApiPayState($data['out_trade_no'], $data['trade_no'], json_encode($arrResponse));
            	}
            }
            $data = array_filter($data);
            outputData($data);
        }
    }
    
    //二维码支付
    public function qrPay()
    {
        //过滤参数
        $arrInput = checkFieldInArray('total_amount,token', $this->getPost('post.'));
        $arrInput['subject'] = $this->getSubject();
        $userInfo = $this->checkUserToken($arrInput['token']);
        $arrInput['goods_detail'] = $this->getGoodsDetail();
        $objOrder = D('Order');
        $orderNo = $this->_createNewOrder($arrInput);
        require_once $this->wxpaySdkpath . '/example/WxPay.NativePay.php';
        //模式二
        /**
         * 流程：
         * 1、调用统一下单，取得code_url，生成二维码
         * 2、用户扫描二维码，进行支付
         * 3、支付完成之后，微信服务器会通知支付成功
         * 4、在支付成功通知中需要查单确认是否真正支付成功（见：notify.php）
         */
        $notify = new \NativePay();
        
        $input = new \WxPayUnifiedOrder();
        $input->SetBody($arrInput['subject']);
        $input->SetOut_trade_no($orderNo);
        $input->SetTotal_fee($arrInput['total_amount'] * 100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetNotify_url(U('/Api/Wxpay/notify', '', false, true));
        $input->SetTrade_type("NATIVE");
        $input->SetProduct_id($arrInput['goods_detail'][0]['goods_id']);
        $input->setSubMchId($this->getWxpaySubMchId($arrInput['token']));
        $input->setSubAppid($this->getWxpaySubAppid($arrInput['token']));
        $result = $notify->GetPayUrl($input);
        if($result['result_code'] == 'SUCCESS' && $result['return_code'] == 'SUCCESS')
        {
            $data = [];
            //生成二维码图片
            $data['qr_img'] = 'http://qr.liantu.com/api.php?text=' . $result['code_url'];
            $data['qr_code'] = $result['code_url'];
            $data['out_trade_no'] = $orderNo;
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
    
    //订单查询，（查看支付状态）
    public function orderQuery()
    {
        $arrInput = checkFieldInArray('token', $this->getPost('post.'));
        $token = $arrInput['token'];
        $this->checkUserToken($token);
        $outTradeNo = $this->getPost('post.out_trade_no', null);//商户订单号
        $tradeNo = $this->getPost('post.trade_no', null);//支付宝订单号
        $input = new \WxPayOrderQuery();
        $input->setSubMchId($this->getWxpaySubMchId($token));
        $input->setSubAppid($this->getWxpaySubAppid($token));
        if($outTradeNo)
        {
            $input->SetOut_trade_no($outTradeNo);
        }
        if($tradeNo)
        {
            $input->SetTransaction_id($tradeNo);
        }
        $response = \WxPayApi::orderQuery($input);
        if( $response['result_code'] == 'SUCCESS' && $response['return_code'] == 'SUCCESS')
        {
        	//如果交易成功
        	if( $response['trade_state'] == 'SUCCESS')
        	{
        		$objOrderPay = D('OrderPay');
        		$blnCheckApi = $objOrderPay->checkApiPayState($response['out_trade_no']);
        		//如果没有收到过支付成功通知
        		if( !$blnCheckApi)
        		{
        			//直接返回支付成功，修改订单状态
        			$response['openid'] = $response['sub_openid'] ? : $response['openid'];
        			$objOrder = D('Order');
        			$payInfo = [];
        			$payInfo['pay_amount'] = $response['cash_fee'];
        			$payInfo['received_amount'] = $response['total_fee'];
        			$payInfo['buyer_id'] = $response['openid'];
        			$payInfo['buyer_name'] = $response['openid'];
        			list($appid, $appsecret) = $this->_getAppInfo($outTradeNo, $tradeNo);
        			$payInfo['buyer_nickname'] = $this->getWxpayUserPickname($response['openid'], $appid, $appsecret);
        			//$payInfo['api_desc'] = $jsonResponse;
        			$orderData = $objOrder->orderPaySuccess($response['out_trade_no'], $response['transaction_id'], OrderModel::PAY_TYPE_WXPAY, $payInfo);
        			if($orderData !== false)
        			{
        				//修改接口记录状态
        				$objOrderPay->saveApiPayState($response['out_trade_no'], $response['transaction_id'], json_encode($response));
        			}
        			else
        			{
        				outputErr(ErrMsg::$api['order.modify.error']);
        			}
        
        		}
        	}
        }
        elseif( isset($response['err_code']) && $response['err_code'] == 'ORDERNOTEXIST')
        {
        	//微信系统那边不存在，但是有可能商户有
        }
        else
        {
        	//微信系统返回异常，直接输出
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
    
    //退款
    public function refund()
    {
        $arrInput = checkFieldInArray('token', $this->getPost('post.'));
        $this->checkUserToken($arrInput['token']);
        $outTradeNo = $this->getPost('post.out_trade_no', null);//商户订单号
        $tradeNo = $this->getPost('post.trade_no', null);//支付宝订单号
        //$refundReason = $this->getPost('post.refund_reason', '正常退款');
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
        
        $input = new \WxPayRefund();
        $input->SetTotal_fee($arrOrderRefund['refund_amount'] * 100);
        $input->SetRefund_fee($arrOrderRefund['refund_amount'] * 100);
        $input->SetOut_refund_no($arrRefundData['refund_no']);
        $subMchId = $this->getWxpaySubMchId($arrInput['token']);
        $subAppid = $this->getWxpaySubAppid($arrInput['token']);
        $input->setSubMchId($subMchId);
        $input->setSubAppid($subAppid);
        $input->SetOp_user_id($subMchId);
        if($outTradeNo)
        {
            $input->SetOut_trade_no($outTradeNo);
        }
        if($tradeNo)
        {
            $input->SetTransaction_id($tradeNo);
        }
        
        $response = \WxPayApi::refund($input);
        //退款申请成功，SUCCESS退款申请接收成功，结果通过退款查询接口查询
        if($response['return_code'] == 'SUCCESS' && $response['result_code'] == 'SUCCESS')
        {
            D('Order')->orderRefundSubmit($arrRefundData['order_id'], $arrRefundData['refund_amount']);//修改订单退款状态,退款处理中
            //再次调用微信退款查询接口查询退款
            $input = new \WxPayRefundQuery();
            $input->setSubMchId($subMchId);
            $input->setSubAppid($subAppid);
            $input->SetOut_refund_no($arrRefundData['refund_no']);
            $queryResponse = \WxPayApi::refundQuery($input);
            if($queryResponse['return_code'] == 'SUCCESS' && $queryResponse['result_code'] == 'SUCCESS')
            {
                if($queryResponse['refund_status_0'] == 'NOTSURE')
                {
                    //NOTSURE—未确定，需要商户原退款单号重新发起
                    $this->refund();
                }
                elseif($queryResponse['refund_status_0'] == 'FAIL' || $queryResponse['refund_status_$n'] == 'CHANGE')
                {
                    //CHANGE—转入代发，退款到银行发现用户的卡作废或者冻结了，导致原路退款银行卡失败，资金回流到商户的现金帐号，需要商户人工干预，通过线下或者财付通转账的方式进行退款
                    //FAIL—退款失败
                    //退款失败
                    D('Order')->orderRefundFail($arrRefundData['order_id']);
                    $objRefund->OrderRefundFail($arrRefundData['order_id'], $arrRefundData['refund_no'], ['api_desc'=>json_encode($queryResponse)]);
                    outputErr(ErrMsg::$api['refund.api.fail']);
                }
                elseif($queryResponse['refund_status_0'] == 'PROCESSING')
                {
                    //PROCESSING—退款处理中
                    outputErr(ErrMsg::$api['refund.api.processing']);
                }
                elseif($queryResponse['refund_status_0'] == 'SUCCESS')
                {
                    //SUCCESS—退款成功
                    D('Order')->orderRefundSuccess($arrRefundData['order_id'], $arrRefundData['refund_amount']);
                    $objRefund->orderRefundSuccess($arrRefundData['order_id'], $arrRefundData['refund_no'], ['api_desc'=>json_encode($queryResponse)]);
                    $data = [];
                    $data['trade_no'] = $arrOrderData['third_order_no'];
                    $data['out_trade_no'] = $arrOrderData['order_no'];
                    $data['buyer_logon_id'] = $arrOrderData['buyer_name'];
                    //$data['fund_change'] = $response['fund_change'];
                    $data['refund_fee'] = floatval($arrOrderRefund['refund_amount']);
                    $data['gmt_refund_pay'] = strtotime($arrOrderData['pay_time']);
                    //$data['refund_detail_item_list'] = $response['refund_detail_item_list'];
                    //$data['send_back_fee'] = $response['send_back_fee'];
                    $data = array_filter($data);
                    outputData($data);
                }
                else 
                {
                    //退款失败
                    D('Order')->orderRefundFail($arrRefundData['order_id']);
                    $objRefund->OrderRefundFail($arrRefundData['order_id'], $arrRefundData['refund_no'], ['api_desc'=>json_encode($response)]);
                    outputErr(ErrMsg::$api['refund.api.fail']);
                }
            }
            else 
            {
                //退款失败
                D('Order')->orderRefundFail($arrRefundData['order_id']);
                $objRefund->OrderRefundFail($arrRefundData['order_id'], $arrRefundData['refund_no'], ['api_desc'=>json_encode($queryResponse)]);
                outputErr(ErrMsg::$api['refund.api.fail']);
            }
        }
        elseif(isset($response['err_code']) && $response['err_code'] == 'NOTENOUGH')
        {
            //余额不足
            D('Order')->orderRefundFail($arrRefundData['order_id']);
            $objRefund->OrderRefundFail($arrRefundData['order_id'], $arrRefundData['refund_no'], ['api_desc'=>json_encode($response)]);
            outputErr(ErrMsg::$api['refund.not.enough']);
        }
        elseif(isset($response['err_code']) && $response['err_code'] == 'TRADE_STATE_ERROR')
        {
            //订单状态错误，退款失败
            D('Order')->orderRefundFail($arrRefundData['order_id']);
            $objRefund->OrderRefundFail($arrRefundData['order_id'], $arrRefundData['refund_no'], ['api_desc'=>json_encode($response)]);
            outputErr(ErrMsg::$api['refund.trade.status.error']);
        }
        else 
        {
            //退款失败,微信的为没有授权退款
            D('Order')->orderRefundFail($arrRefundData['order_id']);
            $objRefund->OrderRefundFail($arrRefundData['order_id'], $arrRefundData['refund_no'], ['api_desc'=>json_encode($response)]);
            outputErr(ErrMsg::$api['refund.api.nopermis']);
            //outputErr(ErrMsg::$api['refund.api.fail']);
        }
    }
    
    //根据订单号或者第三方单号获取商户的微信APPID,APPSECRET
    private function _getAppInfo($orderNo = null, $thirdNo = null)
    {
        $objOrder = D('Order');
        $arrOrder = $objOrder->getOrderByNo($orderNo, $thirdNo);
        $merchantId = $arrOrder['merchant_id'];
        //$arrMerchant = D('Merchant')->getById($merchantId);
        $arrMerchant = D('Merchant')->getMerchantInfoByIdForApi($merchantId);
        return [$arrMerchant['wechat_app_id'], $arrMerchant['wechat_app_secret']];
    }
    
    //扫码支付被动通知地址
    public function notify()
    {
        require_once $this->wxpaySdkpath . '/lib/WxPay.Notify.php';
        require_once $this->wxpaySdkpath . '/example/notify.php';
        $notify = new \PayNotifyCallBack();
        $notify->Handle();
        
        if($notify->GetReturn_code() == 'SUCCESS')
        {
            $data = $notify->getData('data');
            $objOrderPay = D('OrderPay');
            $blnCheckApi = $objOrderPay->checkApiPayState($data['out_trade_no']);
            //如果没有收到过支付成功通知
            if( !$blnCheckApi)
            {
                $objOrder = D('Order');
                
                list($appid, $appsecret) = $this->_getAppInfo($data['out_trade_no']);
                
                $data['openid'] = $data['sub_openid'] ? $data['sub_openid'] : $data['openid'];
            	//直接返回支付成功，修改订单状态
            	$payInfo = [];
            	$payInfo['pay_amount'] = $data['cash_fee'] / 100;
            	$payInfo['received_amount'] = $data['total_fee'] / 100;
            	$payInfo['buyer_id'] = $data['openid'];
            	$payInfo['buyer_name'] = $data['openid'];
            	$payInfo['buyer_nickname'] = $this->getWxpayUserPickname($data['openid'], $appid, $appsecret);
            	//$payInfo['api_desc'] = json_encode($_POST);
            	$blnSave = $objOrder->orderPaySuccess($data['out_trade_no'], $data['transaction_id'], OrderModel::PAY_TYPE_WXPAY, $payInfo);
            	if($blnSave !== false)
            	{
            		//修改接口记录状态
            		$objOrderPay->saveApiPayState($data['out_trade_no'], $data['transaction_id'], json_encode($data));
            	}
            }
        }
        
    }
}