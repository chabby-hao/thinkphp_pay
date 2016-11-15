<?php 
//订单
namespace Common\Model;
use Think\Model;
use Think\Exception;
class OrderModel extends Model {
    
    const REFUND_STATUS_NONE = 10;//未退款
    const REFUND_STATUS_PART = 20;//部分退款,当前业务用不到
    const REFUND_STATUS_COMPLETE = 30;//全额退款
    const REFUND_STATUS_HANDING = 40;//退款处理中
    const REFUND_STATUS_FAIL = 50;//退款失败
    
    const TRADE_STATUS_NO_PAY = 10;//未支付，等待支付
    const TRADE_STATUS_PAY_SUCCESS = 20;//已支付
    const TRADE_STATUS_PAY_CANCEL = 30;//交易取消
    const TRADE_STATUS_PAY_FAIL = 40;//付款失败
    
    const PAY_TYPE_ALIPAY = 'alipay';//支付宝支付
    const PAY_TYPE_WXPAY = 'wxpay';//微信支付
    
    public static $payTypeMap = [
	   self::PAY_TYPE_ALIPAY => '支付宝',
	   self::PAY_TYPE_WXPAY => '微信',
    ];
    
    //退款状态解释
    public static $refundStatusMap = [
	   //self::REFUND_STATUS_NONE => '无退款',
	   //self::REFUND_STATUS_PART => '部分退款',
	   self::REFUND_STATUS_COMPLETE => '订单退款成功',
	   self::REFUND_STATUS_HANDING => '订单退款中'
    ];
    
    //付款状态解释
    public static $tradeStatusMap = [
	   self::TRADE_STATUS_NO_PAY =>'待支付',
	   //self::TRADE_STATUS_PAY_CANCEL =>'交易取消',
	   //self::TRADE_STATUS_PAY_FAIL => '退款失败',
	   self::TRADE_STATUS_PAY_SUCCESS => '订单支付成功',
    ];
    
    protected $tableName = 'order';//表名
    
    //生成订单，返回订单号
    public function createOrder($arrInputOrder, $token = null)
    {
        $arrOrder = [];
        $objMerchant = D('User');
        if( $token)
        {
            $userInfo = $objMerchant->getUserByToken($token);
            $arrOrder['store_id'] = $userInfo['store_id'];//门店id
            $arrOrder['merchant_id'] = $userInfo['merchant_id'];//商户ID
            $arrOrder['user_id'] = $userInfo['user_id'];//操作员ID
        }
        
        $arrOrder['order_no'] = $this->generateOrderNo();//商户自己的单号
        $arrOrder['refund_amount'] = 0;
        $arrOrder['refund_status'] = self::REFUND_STATUS_NONE;//退款状态
        $arrOrder['pay_amount'] = 0;
        $arrOrder['create_time'] = time();
        $arrOrder['pay_time'] = 0;
        $arrOrder['received_amount'] = 0;
        $arrOrder['trade_status'] = self::TRADE_STATUS_NO_PAY;//支付状态
        $arrOrder['order_subject'] = '';//支付主体
        
        $arrOrder = array_merge($arrOrder, $arrInputOrder);
        
        $res = $this->add($arrOrder);
        if($res !== false)
        {
            $arrOrder['order_id'] = $res;
            return $arrOrder;
        }
        return false;
    }
    
    //生成订单号
    public function generateOrderNo()
    {
        $orderNo = date('YmdHis') . mt_rand(1000, 9999);
        $res = $this->getOrderByNo($orderNo, null);
        if(!empty($res))
        {
            return $this->generateOrderNo();
        }
        return $orderNo;
    }
    
    //订单支付成功
    public function orderPaySuccess($orderNo, $thirdOrderNo, $payType, $payInfo)
    {
        M()->startTrans();
        try{
            $data = [];
            $data['pay_type'] = $payType;
            $data['third_order_no'] = $thirdOrderNo;
            $data['trade_status'] = self::TRADE_STATUS_PAY_SUCCESS;
            $data['pay_time'] = time();
            $data['pay_amount'] = $payInfo['pay_amount'];
            $data['received_amount'] = $payInfo['received_amount'];
            $data['buyer_id'] = $payInfo['buyer_id'];
            $data['buyer_name'] = $payInfo['buyer_name'];
            $data['buyer_nickname'] = $payInfo['buyer_nickname'];
            $res = $this->where(['order_no'=>':order_no'])->bind([':order_no'=>$orderNo])->save($data);
            if($res === false)
            {
                E('订单修改失败');
            }
            $orderData = $this->getOrderByNo($orderNo);
            $orderFlow = [];
            $orderFlow['user_id'] = $orderData['user_id'];
            $orderFlow['store_id'] = $orderData['store_id'];
            $orderFlow['merchant_id'] = $orderData['merchant_id'];
            $orderFlow['order_id'] = $orderData['id'];
            $orderFlow['order_no'] = $orderNo;
            $orderFlow['flow_amount'] = $orderData['total_amount'];
            $orderFlow['flow_status'] = OrderFlowModel::ORDER_FLOW_INCOME;
            $orderFlow['pay_type'] = $data['pay_type'];
            $orderFlow['create_time'] = $data['pay_time'];
            //$orderFlow['buyer_name'] = $data['buyer_name'];
            $res = D('OrderFlow')->add($orderFlow);
            if($res === false)
            {
                E('订单流水记录失败');
            }
        }catch (Exception $e){
            M()->rollback();
            return false;
        }
        M()->commit();
        return array_merge($orderData, $data);
    }
    
    //订单退款成功
    public function orderRefundSuccess($orderId, $refundAmount = 0)
    {
        M()->startTrans();
        try{
            $data['refund_status'] = self::REFUND_STATUS_COMPLETE;
            $data['refund_amount'] = $refundAmount;
            $res = $this->where("id=$orderId")->save($data);
            if($res === false)
            {
                E('退款表修改失败');
            }
            $orderData = $this->getById($orderId);
            
            /* //增加退款中判断，看是否修改状态还是直接新增
            $where = [];
            $where['order_id'] = $orderData['id'];
            $where['flow_status'] = OrderFlowModel::ORDER_FLOW_PROCESSING;
            $orderFlowData = D('OrderFlow')->where($where)->find();
            if($orderFlowData)
            {
                //如果查到退款中，则直接修改状态
                $orderFlow = [];
                $orderFlow['flow_status'] = OrderFlowModel::ORDER_FLOW_OUTCOME;
                $res = D('OrderFlow')->where($where)->save($orderFlow);
            }
            else
            { */
            //如果没有查到之前的，则增加新记录
            $orderFlow = [];
            $orderFlow['user_id'] = $orderData['user_id'];
            $orderFlow['store_id'] = $orderData['store_id'];
            $orderFlow['merchant_id'] = $orderData['merchant_id'];
            $orderFlow['order_id'] = $orderData['id'];
            $orderFlow['order_no'] = $orderData['order_no'];
            $orderFlow['flow_amount'] = $orderData['total_amount'];
            $orderFlow['flow_status'] = OrderFlowModel::ORDER_FLOW_OUTCOME;
            $orderFlow['pay_type'] = $orderData['pay_type'];
            $orderFlow['create_time'] = time();
            $res = D('OrderFlow')->add($orderFlow);
           
            if($res === false)
            {
                E('退款流水新增或修改失败');
            }
        }catch (Exception $e){
            M()->rollback();
            return false;
        }
        M()->commit();
        return true;
    }
    
    //订单退款失败
    public function orderRefundFail($orderId)
    {
        M()->startTrans();
        try {
            $orderData = $this->getById($orderId);
            $orderRefundWhere = [];
            $orderRefundWhere['order_id'] = $orderId;
            $orderRefundWhere['refund_state'] = OrderRefundModel::REFUND_STATE_WAIT;
            $orderRefundWaitData = D('OrderRefund')->where($orderRefundWhere)->find();
            //同时满足，不存在退款等待中的退款单，有订单记录，订单状态不为已退款的就可以更新退款状态
            if(!$orderRefundWaitData && $orderData && $orderData['refund_status'] != OrderModel::REFUND_STATUS_COMPLETE)
            {
                $data['refund_status'] = self::REFUND_STATUS_FAIL;
                $res = $this->where("id=$orderId")->save($data);
                if($res === false)
                {
                    E('退款处理修改失败');
                }
            }
        }catch (Exception $e){
            M()->rollback();
            return false;
        }
        M()->commit();
        return true;
    }
    
    //订单退款已申请成功，具体到账情况还未知
    public function orderRefundSubmit($orderId, $refundAmount = 0)
    {
        M()->startTrans();
        try {
            $data['refund_status'] = self::REFUND_STATUS_HANDING;
            $data['refund_amount'] = $refundAmount;
            $res = $this->where("id=$orderId")->save($data);
            if($res === false)
            {
                E('退款处理修改失败');
            }
            /* $orderData = $this->getById($orderId);
            $orderFlow = [];
            $orderFlow['user_id'] = $orderData['user_id'];
            $orderFlow['store_id'] = $orderData['store_id'];
            $orderFlow['merchant_id'] = $orderData['merchant_id'];
            $orderFlow['order_id'] = $orderData['id'];
            $orderFlow['order_no'] = $orderData['order_no'];
            $orderFlow['flow_amount'] = $orderData['total_amount'];
            $orderFlow['flow_status'] = OrderFlowModel::ORDER_FLOW_PROCESSING;
            $orderFlow['pay_type'] = $orderData['pay_type'];
            $orderFlow['create_time'] = time();
            //$orderFlow['buyer_name'] = $orderData['buyer_name'];
            $res = D('OrderFlow')->add($orderFlow);
            if($res === false)
            {
                E('退款流水新增失败');
            } */
        }catch (Exception $e){
            M()->rollback();
            return false;
        }
        M()->commit();
        return true;
    }
    
    //根据商户单号或者第三发单号查询订单信息
    public function getOrderByNo($orderNo = null, $thirdOrderNo = null)
    {
        $where = $bind = [];
        if($orderNo)
        {
            $where['order_no'] = ':order_no';
            $bind[':order_no'] = $orderNo;
        }
        if($thirdOrderNo)
        {
            $where['third_order_no'] = ':third_order_no';
            $bind[':third_order_no'] = $thirdOrderNo;
        }
        if( !$where || !$bind)
        {
            E('缺少订单号');
        }
        return $this->where($where)->bind($bind)->find();
    }
    
    //根据店铺获取日，周，月统计金额信息
    public function getStatByStoreId($storeId)
    {
        
        $where = [];
        $where['store_id'] = ':store_id';
        $bind = [':store_id'=>$storeId];
        $where['trade_status'] = OrderModel::TRADE_STATUS_PAY_SUCCESS;
        $where['refund_status'] = ['NEQ', OrderModel::REFUND_STATUS_COMPLETE];//不等于退款成功
        $endTime = time();
        try{
            //周
            $where['create_time'] = ['between',[strtotime(date('Y-m-d 00:00:00'))-((date('w')==0?7:date('w'))-1)*24*3600, $endTime]];
            $weekStat = $this->where($where)->bind($bind)->sum('total_amount');
            //月
            $where['create_time'] = ['between',[strtotime(date('Y-m').'-01 00:00:00'), $endTime]];
            $monStat = $this->where($where)->bind($bind)->sum('total_amount');
            //日
            $where['create_time'] = ['between',[strtotime(date('Y-m-d').' 00:00:00'), $endTime]];
            $dayStat = $this->where($where)->bind($bind)->sum('total_amount');
        }catch (Exception $e){
            $dayStat = $weekStat = $monStat = 0;//初始化统计信息
        }
        $arrRtn = [$dayStat,$weekStat,$monStat];
        return array_map(function($value){
        	if(!$value)
        	{
        	    $value = 0;
        	}
        	return $value;
        }, $arrRtn);
        
    }
}
