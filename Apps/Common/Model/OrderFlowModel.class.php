<?php 
//订单流水
namespace Common\Model;
use Think\Model;
class OrderFlowModel extends Model {
    
    protected $tableName = 'order_flow';//表名

    const ORDER_FLOW_INCOME = 10;//收入
    const ORDER_FLOW_OUTCOME = 20;//支出(退款)
    const ORDER_FLOW_PROCESSING = 30;//退款处理中
    
    
}
