<?php
//支付接口类的基类，支付类的接口可以继承此类
namespace Api\Controller;
use Think\Controller;
use Think\Exception;
use Api\Msg\ErrMsg;
class BasePayController extends BaseController {
    
    /* public $alipayTradeStatus = [
	   'WAIT_BUYER_PAY'=>10,//待支付
	   'TRADE_CLOSED'=>30,//订单超期未支付，或者取消
	   'TRADE_SUCCESS'=>20,//支付成功
	   'TRADE_FINISHED'=>20,//支付成功且不能退款
    ]; */

    //获取商品信息，如果获取不到，就返回泛商品
    public function getGoodsDetail()
    {
        if( !$this->getPost('post.goods_detail'))
        {
        	$goodsDetail = [[                'goods_id' => -1,                'goods_name' => '泛商品',                'quantity' => 1,                'price' => floatval($this->getPost('post.total_amount'))            ]];
        }
        else
        {
        	$goodsDetail = json_decode(htmlspecialchars_decode($this->getPost('post.goods_detail')), true);//商品详情传的是json字符串
        }
        return $goodsDetail;
    }
    
    //获取支付标题
    public function getSubject($token = null)
    {
        if($token === null)
        {
            //如果没传token，则从post参数里取token
            $token = $this->getPost('post.token',null);
        }
        $userInfo = D('User')->getUserByToken($token);
        $subject = $userInfo['merchant_name'] . '(' . $userInfo['store_name'] . ')';
        return $subject ? : '当面付';
    }
    
    //生成新订单，默认返回订单号
    //$boolOrderData 为true，返回整个订单记录，为fasle，返回订单号
    protected function _createNewOrder($arrInput, $boolOrderData = false)
    {
    	$arrInputOrderMap = ['subject'=>'order_subject'];
    	$arrInputOrderDb = mapToArray($arrInputOrderMap, $arrInput);
    	try
    	{
    		M()->startTrans();
    		$objOrder = D('Order');
    		$token = $arrInput['token'] ? : null;
    		$arrOrder = $objOrder->createOrder($arrInputOrderDb, $token);//新建订单，返回订单号
    
    		if($arrOrder === false)
    		{
    			//新增订单表出错
    			E('table order error');
    		}
    		$arrInputGoodsMap = ['price'=>'goods_price','quantity'=>'goods_quantity'];
    		array_walk($arrInput['goods_detail'], function(&$v, $k, $arrInputGoodsMap){
    			$v = mapToArray($arrInputGoodsMap, $v);
    		}, $arrInputGoodsMap);
    
			$objOrderGoods = D('OrderGoods');
			$res = $objOrderGoods->addOrderGoods($arrInput['goods_detail'], $arrOrder['order_id']);
			if($res === false)
			{
				//新增订单商品表出错
				E('table order_goods error');
			}
			$arrInputGoodsDb = mapToArray($arrInputGoodsMap, $arrInput['goods_detail']);
			$objOrderPay = D('OrderPay');
			//新建订单支付记录,order_pay
			if($objOrderPay->addApiPayState($arrOrder['order_no']) === false)
			{
				//出现错误
				E('table order_pay error');
			}
    	}
    	catch (Exception $e)
    	{
    		M()->rollback();
    		outputErr(ErrMsg::$err['order.create.error']);
    	}
    	M()->commit();
    	return $boolOrderData ? $arrOrder : $arrOrder['order_no'];
    }
}