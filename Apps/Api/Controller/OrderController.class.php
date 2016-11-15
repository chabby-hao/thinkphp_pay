<?php
//订单接口（包含订单流水）
namespace Api\Controller;
use Think\Controller;
use Api\Msg\ErrMsg;
use Think\Exception;
use Common\Model\UserModel;
use Common\Model\OrderFlowModel;
use Common\Model\OrderModel;

class OrderController extends BaseController {
    
    //订单流水列表
    public function orderFlowList()
    {
        //参数过滤
        $arrInput = checkFieldInArray('token', $this->getPost('post.'));
        $intPage = $this->getPost('post.page', 1);
        $flowStatus = $this->getPost('post.flow_status', null);
        $payType = $this->getPost('post.pay_type', null);
        $userId = $this->getPost('post.user_id', null);
        $userInfo = $this->checkUserToken($arrInput['token']);
        $where = $bind = [];
        $permisWhere = $this->_getWhereByUserInfo($userInfo, '`order_flow`.');
        $where = array_merge($where, $permisWhere);
        
        if($userId)
        {
            $subSubInfo = D('User')->getById($userId); // 账户信息
            if ( !$subSubInfo)
            {
                //如果没有账户信息，则输出空数据
                outputData([
                    'list' => [],
                    'page_list' => mobilePage(1, $intPage),
                ]);
            }
            if($subSubInfo['user_role'] == (UserModel::USER_ROLE_ACCOUNTANT))
            {//店长
                $where['`order_flow`.store_id'] = ':store_id';
                $bind[':store_id'] = $subSubInfo['store_id'];
            }
            elseif($subSubInfo['user_role'] == (UserModel::USER_ROLE_OPERATOR))
            {
                //职员
                $where['`order_flow`.user_id'] = ':user_id';
                $bind[':user_id'] = $userId;
            }
        }
        if($flowStatus)
        {
            if($flowStatus == OrderFlowModel::ORDER_FLOW_INCOME)
            {//特殊判断，如果是传收入类型的状态，则过滤包含退款的流水
                $where['`order`.refund_status'] = ['NEQ', OrderModel::REFUND_STATUS_COMPLETE];//不等于退款成功
            }
        	//交易状态
        	$where['`order_flow`.flow_status'] = ':flow_status';
        	$bind[':flow_status'] = $flowStatus;
        }
        if($payType)
        {
        	//支付类型
        	$where['`order_flow`.pay_type'] = ':pay_type';
        	$bind[':pay_type'] = $payType;
        }
        $orderFlowList = D('OrderFlow')->field('order_flow.*,`order`.buyer_name,`order`.buyer_nickname')->join('`order` on `order`.id=order_flow.order_id')->where( $where)->bind($bind)->order('create_time desc')->page($intPage, $this->intPageLimit)->select();
        $intRowsCount = D('OrderFlow')->field('order_flow.*,`order`.buyer_name,`order`.buyer_nickname')->join('`order` on `order`.id=order_flow.order_id')->where( $where)->bind($bind)->count();
        $pageCount = ceil($intRowsCount/$this->intPageLimit);
        $dataAll = [];
        foreach ($orderFlowList as $orderFlowData)
        {
        	$data = [];
        	$data['order_no'] = $orderFlowData['order_no'];
        	$data['total_amount'] = floatval($orderFlowData['flow_amount']);
        	$data['pay_type'] = $orderFlowData['pay_type'];
        	$data['create_date'] = intval($orderFlowData['create_time']);
        	$data['flow_status'] = intval($orderFlowData['flow_status']);
        	$data['buyer_name'] = $orderFlowData['buyer_nickname'] ? : $orderFlowData['buyer_name'];
        	$data['buyer_name'] = $data['buyer_name'] ? : '匿名';
        	$dataAll[] = $data;
        }
        outputData([
            'list' => $dataAll,
            'page_list' => mobilePage($pageCount, $intPage),
        ]);
    }
    
    //订单详情
    public function orderDetail()
    {
        //参数过滤
        $arrInput = checkFieldInArray('token,out_trade_no', $this->getPost('post.'));
        $refundStatus = $this->getPost('post.refund_status', null);
        $userInfo = $this->checkUserToken($arrInput['token']);
        $where = $bind = [];
        $permisWhere = $this->_getWhereByUserInfo($userInfo);
        $where = array_merge($where, $permisWhere);
        $where['order_no'] = ':order_no';
        $bind[':order_no'] = $arrInput['out_trade_no'];
        $orderData = D('Order')->where($where)->bind($bind)->find();
        if(!$orderData)
        {
            outputErr(ErrMsg::$err['order.non.existend']);
        }
        if($orderData['refund_status'] == OrderModel::REFUND_STATUS_FAIL)
        {
            //如果订单退款状态为失败的话，返回给客户端还是未退款，允许其再次发送退款请求
            $orderData['refund_status'] = OrderModel::REFUND_STATUS_NONE;
        }
        $data = $this->_formatOrder($orderData);
        outputData($data);
    }
    
    //订单列表（第一个版本客户端不需要此接口，先放在这里）
    public function orderList()
    {
        //参数过滤
        $arrInput = checkFieldInArray('token', $this->getPost('post.'));
        $intPage = $this->getPost('post.page', 1);
        $tradeStatus = $this->getPost('post.trade_status', null);
        $payType = $this->getPost('post.pay_type', null);
        $refundStatus = $this->getPost('post.refund_status', null);
        $userInfo = $this->checkUserToken($arrInput['token']);
        $where = [];
        $userRole = $userInfo['user_role'];
        //根据角色判断条件
        if($userRole == UserModel::USER_ROLE_OPERATOR)
        {//如果是操作员
            $where['operator_id'] = $userInfo['operator_id'];
        }
        elseif ($userRole == UserModel::USER_ROLE_ACCOUNTANT)
        {//如果是财务
            $where['store_id'] = $userInfo['store_id'];
        }
        elseif($userRole == UserModel::USER_ROLE_BOSS)
        {//如果是boss
            $where['merchant_id'] = $userInfo['merchant_id'];
        }
        else 
        {//没匹配上角色，重新登录
            outputErr(ErrMsg::$err['user.token.invalid']);
        }
        
        if($tradeStatus)
        {
            //交易状态
            $where['trade_status'] = $tradeStatus;
        }
        if($payType)
        {
            //支付类型
            $where['pay_type'] = $payType;
        }
        if($refundStatus)
        {
            //退款状态
            $where['refund_status'] = $refundStatus;
        }
        $arrOrderList = D('Order')->where( $where)->order('create_time desc')->page($intPage, $this->intPageLimit)->select();
        $intRowsCount = D('Order')->where( $where)->count();
        $pageCount = ceil($intRowsCount/$this->intPageLimit);
        $this->_orderListOut($arrOrderList, $pageCount, $intPage);
    }
    
    //输出订单列表
    private function _orderListOut($arrOrderList, $pageCount, $intPage)
    {
        $dataAll = [];
        foreach ($arrOrderList as $arrOrder)
        {
            $data = $this->_formatOrder($arrOrder);
            $dataAll[] = $data;
        }
        outputData([            'list' => $dataAll,
            'page_list' => mobilePage($pageCount, $intPage),        ]);
    }
    
}