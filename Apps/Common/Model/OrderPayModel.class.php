<?php 
//订单支付接口记录表
namespace Common\Model;
use Think\Model;
class OrderPayModel extends Model {
    
    const API_PAY_STATE_WAIT = 10;//未收到通知
    const API_PAY_STATE_RECEIVED = 20;//已收到通知

    
    protected $tableName = 'order_pay';//表名
    
    //检查是否收到过通知，收到过返回ture，没有返回false
    public function checkApiPayState($orderNo)
    {
        $where = ['order_no'=>':order_no', 'api_pay_state'=>self::API_PAY_STATE_RECEIVED];
        $bind = [':order_no'=>$orderNo];
        $res = $this->where($where)->bind($bind)->find();
        if($res)
        {
            return true;
        }
        return false;
    }
    
    //保存新支付状态
    public function saveApiPayState($orderNo, $thirdOrderNo, $apiDesc='')
    {
        $data = [];
        $data['order_no'] = $orderNo;
        $data['third_order_no'] = $thirdOrderNo;
        $data['api_pay_state'] = self::API_PAY_STATE_RECEIVED;
        $data['api_desc'] = $apiDesc;
        return $this->where(['order_no'=>':order_no'])->bind([':order_no'=>$orderNo])->save($data);
    }
    
    public function addApiPayState($orderNo)
    {
        $data = [];
        $data['order_no'] = $orderNo;
        $data['api_pay_state'] = self::API_PAY_STATE_WAIT;
        
        return $this->add($data);
    }
    
}
