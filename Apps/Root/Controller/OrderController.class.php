<?php
namespace Root\Controller;
use Think\Controller;
use Common\Model\OrderModel;
use Common\Model\OrderRefundModel;
class OrderController extends BaseController {
    
    //订单流水列表
    public function index(){
        $startData = I('get.start_date', 0);
        $endDate = I('get.end_date', date('Y-m-d 23:59:59'));
        $endDate .= ' 23:59:59';
        $startTime = intval(strtotime($startData));
        $endTime = intval(strtotime($endDate));
        $payType = I('get.pay_type', null);
        $tradeStatus = I('get.trade_status', null);
        $merchantId = I('get.merchant_id', null);
        $where = $bind = [];
        if($startData && $endTime)
        {
            $where['order.create_time'] = ['between',[$startTime, $endTime]];
        }
        if($payType)
        {
            $where['order.pay_type'] = ':pay_type';
            $bind[':pay_type'] = $payType;
        }
        if($tradeStatus && $tradeStatus == 100)
        {
            $where['refund_status'] = ':refund_status';
            $bind[':refund_status'] = OrderModel::REFUND_STATUS_COMPLETE;//退款
        }
        elseif($tradeStatus && $tradeStatus == 200)
        {
            $where['refund_status'] = ':refund_status';
            $bind[':refund_status'] = OrderModel::REFUND_STATUS_HANDING;//退款
        }
        elseif($tradeStatus)
        {
            $where['order.trade_status'] = ':trade_status';
            $bind[':trade_status'] = $tradeStatus;
            $where['order.refund_status'] = OrderModel::REFUND_STATUS_NONE;
        }
        if($merchantId)
        {
            $where['order.merchant_id'] = ':merchant_id';
            $bind[':merchant_id'] = $merchantId;
        }
        
        $intPage = I('get.page', 1);
        $arrOrderList = D('Order')->field('`order`.*,`store`.store_name')->join('store on store.id=order.store_id')->where( $where)->bind($bind)->order('order.create_time desc')->page($intPage, $this->intPageLimit)->select();
        $intRowsCount = D('Order')->join('store on store.id=order.store_id')->where( $where)->bind($bind)->count();
        $this->assign('merchantMap',$this->getMerchantMap());//将所有商户map输出到视图
        $this->assign('list', $arrOrderList);
        $pageNav = $this->_getPageNav($intRowsCount, $intPage);
        $this->assign('pageNav',$pageNav);
        $this->_setPageHeaderAction('流水');
        $this->display();
    }
    
    protected function _setPageHeaderAction($action)
    {
        $this->_setPageHeader($action, '交易');
    }
    
    
    
}