<?php
//支付类接口控制器
namespace Api\Controller;
use Think\Controller;
use Api\Msg\ErrMsg;
use Think\Exception;
use Common\Model\OrderModel;

class PayController extends BasePayController {
    
    protected $_payType;//支付渠道
    
    
    //动态二维码支付，可路由到支付宝或者微信对应的相关接口
    public function qrPay()
    {
        $this->_checkSetPayType();
        $this->_checkAmount();
        switch ($this->_payType)
        {
        	case 'alipay':
        	    (new AlipayController())->qrPay();
        	    break;
        	case 'wxpay':
        	    (new WxpayController())->qrPay();
        	    break;
        }
            
    }
    
    //条码支付，可路由到支付宝或者微信对应的相关接口
    public function barPay()
    {
        $this->_checkSetPayType();
        $this->_checkAmount();
        switch ($this->_payType)
        {
        	case 'alipay':
        	    (new AlipayController())->barPay();
        	    break;
        	case 'wxpay':
        	    (new WxpayController())->barPay();
        	    break;
        }
    }
    
    //退款，可路由到支付宝或者微信对应的相关接口
    public function refund()
    {
        $this->_checkSetPayType();
        switch ($this->_payType)
        {
        	case 'alipay':
        	    (new AlipayController())->refund();
        	    break;
        	case 'wxpay':
        	    (new WxpayController())->refund();
        	    break;
        }
    }
    
    //订单查询，可路由到支付宝或者微信对应的相关接口
    public function orderQuery()
    {
        $this->_checkSetPayType();
        switch ($this->_payType)
        {
        	case 'alipay':
        	    (new AlipayController())->orderQuery();
        	    break;
        	case 'wxpay':
        	    (new WxpayController())->orderQuery();
        	    break;
        }
    }
    
    //检测金额格式和数值
    protected function _checkAmount()
    {
        $totalAmount = $this->getPost('post.total_amount', 0);
        if($totalAmount)
        {
            if($totalAmount > 9999999 || $this->_getFloatLength($totalAmount) > 2)
            {
                outputErr(ErrMsg::$err['amount.input.invalid']);
            }
        }
    }
    
    //获取数字后的小数点点位数
    private function _getFloatLength($num) {
        $count = 0;
    
        $temp = explode ( '.', $num );
    
        if (sizeof ( $temp ) > 1) {
            $decimal = end ( $temp );
            $count = strlen ( $decimal );
        }
    
        return $count;
    }
    
    //检测并且设置支付方式
    protected function _checkSetPayType()
    {
        $arrInput = checkFieldInArray('pay_type', $this->getPost('post.'));
        $this->_payType = $arrInput['pay_type'];
        $payTypeMap = [OrderModel::PAY_TYPE_ALIPAY, OrderModel::PAY_TYPE_WXPAY];
        if( !in_array($this->_payType, $payTypeMap))
        {
            outputErr(ErrMsg::$err['pay.type.invalid']);
        }
        $extendData = [
	       'pay_type'=>$this->_payType,
        ];
        $strExtendData = json_encode($extendData);
        define('API_EXTEND_DATA', $strExtendData);
    }

}