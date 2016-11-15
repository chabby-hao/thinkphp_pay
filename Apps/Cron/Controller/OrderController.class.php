<?php
namespace Cron\Controller;
use Think\Controller;
use Common\Model\OrderModel;
use Common\Model\OrderRefundModel;
use Common\Extend\Wechat;
use Think\Exception;
class OrderController extends BaseController {
    
    //生成账单定时任务，每天凌晨1点执行即可，一天一次
    public function createBill(){
        
        $where = $refundWhere = $billWhere = [];
        //获取前一天00：00：00
        $intLastTime = strtotime('-1 days');//前一天时间戳
        $startTime = strtotime(date('Y-m-d', $intLastTime). ' 00:00:00');
        $endTime = strtotime(date('Y-m-d', $intLastTime). ' 23:59:59');
        $where['create_time'] = ['between', [$startTime, $endTime]];
        $where['trade_status'] = OrderModel::TRADE_STATUS_PAY_SUCCESS;
        
        $refundWhere['order_refund.refund_time'] = ['between', [$startTime, $endTime]];
        $refundWhere['order_refund.refund_state'] = OrderRefundModel::REFUND_STATE_SUCCESS;
        
        
        $billWhere['bill_date'] = date('Ymd', $intLastTime);
        
        $objOrder = D('Order');
        $objRefund = D('OrderRefund');
        
        $data = [];
        $data['bill_date'] = $billWhere['bill_date'];
        $data['bill_month'] = date('n', $intLastTime);
        $data['bill_week'] = date('w', $intLastTime);
        $data['create_time'] = time();
        $storeList = D('Store')->getStoreList();
        foreach ($storeList as $storeData)
        {
            $billWhere['store_id'] = $storeData['id'];
            $billData = D('OrderBill')->where($billWhere)->find();
            if($billData)
            {
                //如果已经生成了，就跳过循环
                continue;
            }
            
            $where['store_id'] = $storeData['id'];
            $refundWhere['`order`.store_id'] = $storeData['id'];
            $totalTradeAmount = $objOrder->where($where)->sum('total_amount');
            $totalRefundAmount = $objRefund->join('`order` on order_refund.order_id=`order`.id')->where($refundWhere)->sum('order_refund.refund_amount');
            $totalTradeAmount = $totalTradeAmount ? $totalTradeAmount : 0;
            $totalRefundAmount = $totalRefundAmount ? $totalRefundAmount : 0;
            //生成账单记录
            $data['merchant_id'] = $storeData['merchant_id'];
            $data['store_id'] = $storeData['id'];
            $data['total_trade_amount'] = $totalTradeAmount;
            $data['total_refund_amount'] = $totalRefundAmount;
           
            D('OrderBill')->add($data);
        }
        
    }
    
    //刷新一下订单表的微信昵称,每5分钟执行一次
    public function flushNickname()
    {
        $where = [];
        $where['pay_type'] = OrderModel::PAY_TYPE_WXPAY;
        $where['buyer_nickname'] = '';
        
        $orderList = D('Order')->field('merchant_id,buyer_id')->where($where)->group('buyer_id')->select();
        
        if($orderList)
        {
            $objWechat = new Wechat();
            foreach ($orderList as $orderData)
            {
                $arrMerchant = D('Merchant')->getById($orderData['merchant_id']);
                list($appid, $appsecret) = [$arrMerchant['wechat_app_id'], $arrMerchant['wechat_app_secret']];
                $arrWxUserInfo = $objWechat->getUserInfoForApi($appid, $appsecret, $orderData['buyer_id']);
                $nickname = '';
                if($arrWxUserInfo)
                {
                    D('WxOpenidMap')->replaceByWxUser($arrWxUserInfo, $appid, $appsecret);
                    $nickname = $arrWxUserInfo['nickname'];
                    //return $arrWxUserInfo['nickname'];
                }
                else
                {
                    $where = [];
                    $where['appid'] = $appid;
                    $where['openid'] = $orderData['buyer_id'];
                    $arrData = D('WxOpenidMap')->where($where)->find();
                    if($arrData)
                    {
                        $nickname = $arrData['nickname'];
                        //return $arrData['nickname'];
                    }
                }
                $buyerWhere = [];
                $buyerWhere['buyer_id'] = $orderData['buyer_id'];
                $buyerWhere['pay_type'] = OrderModel::PAY_TYPE_WXPAY;
                $data = [];
                $data['buyer_nickname'] = $nickname;
                D('Order')->where($buyerWhere)->save($data);
            }
        }
        
    }
    
    //定时执行微信订单付款查询，1分钟一次
    public function wxOrderQuery()
    {
        //引入微信库
        $wxPayLibPath = APP_PATH . 'Api/Extend/WxpaySdk';
        require_once $wxPayLibPath . '/lib/WxPay.Api.php';
        $where = [];
        $where['`order`.trade_status'] = OrderModel::TRADE_STATUS_NO_PAY;
        $where['`order`.create_time'] = ['EGT', strtotime('-1 days')];
        $objOrder = D('Order');
        $orderList = $objOrder->where($where)->select();
        if($orderList)
        {
            $wxSubMchIdMap = D('Merchant')->getWxMchIdMap();
            $input = new \WxPayOrderQuery();
            foreach ($orderList as $orderData)
            {
                try
                {
                    
                    $input->setSubMchId($wxSubMchIdMap[$orderData['merchant_id']][0]);
                    $input->SetAppid($wxSubMchIdMap[$orderData['merchant_id']][1]);
                    $input->SetOut_trade_no($orderData['order_no']);
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
                                $payInfo = [];
                                $payInfo['pay_amount'] = $response['cash_fee'];
                                $payInfo['received_amount'] = $response['total_fee'];
                                $payInfo['buyer_id'] = $response['openid'];
                                $payInfo['buyer_name'] = $response['openid'];
                                $payInfo['buyer_nickname'] = '';//定时任务不用获取昵称，增加速度
                                //$payInfo['api_desc'] = $jsonResponse;
                                $orderData = $objOrder->orderPaySuccess($response['out_trade_no'], $response['transaction_id'], OrderModel::PAY_TYPE_WXPAY, $payInfo);
                                if($orderData !== false)
                                {
                                    //修改接口记录状态
                                    $objOrderPay->saveApiPayState($response['out_trade_no'], $response['transaction_id'], json_encode($response));
                                }
                                else
                                {
                                    //outputErr(ErrMsg::$api['order.modify.error']);
                                }
                    
                            }
                        }
                    }
                    elseif( isset($response['err_code']) && $response['err_code'] == 'ORDERNOTEXIST')
                    {
                        //微信系统那边不存在
                    }
                    else
                    {
                        //微信系统返回异常
                        //outputErr(ErrMsg::$api['pay.exception']);
                    }
                }
                catch (Exception $e)
                {
                    //捕捉异常，防止程序终端
                }
            }
        
        }
    }
   
    //定时执行微信退款查询,每5分钟执行一次
    public function refundQuery()
    {
        //引入微信库
        $wxPayLibPath = APP_PATH . 'Api/Extend/WxpaySdk';
        require_once $wxPayLibPath . '/lib/WxPay.Api.php';
        $where = [];
        $where['order_refund.refund_state'] = OrderRefundModel::REFUND_STATE_WAIT;
        $where['order_refund.create_time'] = ['EGT', strtotime('-1 months')];
        $where['`order`.pay_type'] = OrderModel::PAY_TYPE_WXPAY;
        $objRefund = D('OrderRefund');
        $orderRefundList = $objRefund->field('`order`.merchant_id,order_refund.*')->join('`order` on order_refund.order_id=`order`.id')->where($where)->select();
        if($orderRefundList)
        {
            $wxSubMchIdMap = D('Merchant')->getWxMchIdMap();
            $input = new \WxPayRefundQuery();
            foreach ($orderRefundList as $orderRefundData)
            {
                try
                {
                    
                    $input->setSubMchId($wxSubMchIdMap[$orderRefundData['merchant_id']][0]);
                    $input->SetAppid($wxSubMchIdMap[$orderRefundData['merchant_id']][1]);
                    $input->SetOut_refund_no($orderRefundData['refund_no']);
                    $queryResponse = \WxPayApi::refundQuery($input);
                    if($queryResponse['return_code'] == 'SUCCESS' && $queryResponse['result_code'] == 'SUCCESS')
                    {
                        if($queryResponse['refund_status_0'] == 'NOTSURE')
                        {
                            //NOTSURE—未确定，需要商户原退款单号重新发起
                            //不确定不处理，等待下次查询
                        }
                        elseif($queryResponse['refund_status_0'] == 'FAIL' || $queryResponse['refund_status_$n'] == 'CHANGE')
                        {
                            //CHANGE—转入代发，退款到银行发现用户的卡作废或者冻结了，导致原路退款银行卡失败，资金回流到商户的现金帐号，需要商户人工干预，通过线下或者财付通转账的方式进行退款
                            //FAIL—退款失败
                            //退款失败
                            D('Order')->orderRefundFail($orderRefundData['order_id']);
                            $objRefund->OrderRefundFail($orderRefundData['order_id'], $orderRefundData['refund_no'], ['api_desc'=>json_encode($queryResponse)]);
                        }
                        elseif($queryResponse['refund_status_0'] == 'PROCESSING')
                        {
                            //PROCESSING—退款处理中
                        }
                        elseif($queryResponse['refund_status_0'] == 'SUCCESS')
                        {
                            //SUCCESS—退款成功
                            D('Order')->orderRefundSuccess($orderRefundData['order_id'], $orderRefundData['refund_amount']);
                            $objRefund->orderRefundSuccess($orderRefundData['order_id'], $orderRefundData['refund_no'], ['api_desc'=>json_encode($queryResponse)]);
                        }
                        else
                        {
                            //退款失败
                            D('Order')->orderRefundFail($orderRefundData['order_id']);
                            $objRefund->OrderRefundFail($orderRefundData['order_id'], $orderRefundData['refund_no'], ['api_desc'=>json_encode($queryResponse)]);
                        }
                    }
                }
                catch (Exception $e)
                {
                    //捕捉异常，防止程序终端
                }
            }
    
        }
    }
    
}