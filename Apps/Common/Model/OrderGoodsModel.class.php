<?php 
//订单商品
namespace Common\Model;
use Think\Model;
class OrderGoodsModel extends Model {
    
    
    protected $tableName = 'order_goods';//表名
    
    //新增订单商品
    public function addOrderGoods($goodsDetail, $orderId)
    {
        //追加id
        array_walk($goodsDetail, function(&$v, $k, $orderId){
        	$v['order_id'] = $orderId;
        }, $orderId);
        return $this->addAll($goodsDetail);
    }
    
}
