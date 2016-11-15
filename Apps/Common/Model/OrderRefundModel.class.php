<?php 
//订单退款
namespace Common\Model;
use Think\Model;
class OrderRefundModel extends Model {
    
    const REFUND_STATE_WAIT = 10;//退款中
    const REFUND_STATE_SUCCESS = 20;//退款成功
    const REFUND_STATE_FAIL = 30;//退款失败

    
    protected $tableName = 'order_refund';//表名
    
    //新增退款订单
    public function createOrderRefund($arrOrderRefund)
    {
        if( !isset($arrOrderRefund['order_id']))
        {
            E('缺少订单id');
        }
        if( !isset($arrOrderRefund['refund_amount']))
        {
            E('缺少退款金额');
        }
        $data = [];
        $data['create_time'] = time();
        $data['refund_no'] = $this->generateRefundNo();
        $data['refund_state'] = self::REFUND_STATE_WAIT;
        $data['refund_reason'] = '正常退款';
        $data = array_merge($data, $arrOrderRefund);
        $res = $this->add($data);
        return $res !== false ? $data : false;
    }
    
    //退款成功
    public function orderRefundSuccess($orderId, $refundNo, $arrRefundData = [])
    {
        list($where, $bind) = $this->_getSelectCondition($orderId, $refundNo);
        $data = [];
        $data['refund_state'] = self::REFUND_STATE_SUCCESS;
        $data['refund_time'] = time();
        $data = array_merge($data, $arrRefundData);
        return $this->where($where)->bind($bind)->save($data);
    }
    
    //退款失败
    public function OrderRefundFail($orderId, $refundNo, $arrRefundData = [])
    {
        list($where, $bind) = $this->_getSelectCondition($orderId, $refundNo);
        $data = [];
        $data['refund_state'] = self::REFUND_STATE_FAIL;
        $data = array_merge($data, $arrRefundData);
        return $this->where($where)->bind($bind)->save($data);
    }
    
    //根据订单id，和退款单号获取退款查询条件
    private function _getSelectCondition($orderId, $refundNo)
    {
        $where = [            'order_id' => ':order_id',            'refund_no' => ':refund_no',            'refund_state' => self::REFUND_STATE_WAIT        ];        $bind = [            ':order_id' => $orderId,            ':refund_no' => $refundNo        ];
        return [$where, $bind];
    }
    
    public function generateRefundNo()
    {
        return date('YmdHis').mt_rand(1000, 9999);
    }
}
