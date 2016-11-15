<?php
namespace Wap\Controller;
use Think\Controller;
use Api\Controller\AlipayController;
use Org\Net\Http;
use Api\Controller\WxpayController;
use Common\Model\OrderModel;
class IndexController extends BaseController {
    
    
    //引导页，判断支付宝和微信客户端
    public function index(){
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        
        $storeId = I('get.store_id');//店铺id
        if(!$storeId)
        {
            exit('缺少store_id');
        }
        $arrState = ['store_id'=>$storeId];
        
        if(strpos($userAgent, 'AlipayClient') !== false)
        {
            //支付宝
            //$url = "https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id=2016090901877426&scope=auth_base&redirect_uri={ENCODED_URL}";
            $redirectUrl = U('alipayInput', '', false, true);
            $url = "https://openauth.alipay.com/oauth2/publicAppAuthorize.htm";//支付宝用户授权地址
            $params = [];
            $params['app_id'] = '2016090901877426';
            $params['scope'] = 'auth_base';
            $params['redirect_uri'] = $redirectUrl;
            $params['state'] = $arrState;//可存店铺id
            $url .= '?' . http_build_query($params);
            header('location:' .$url);
            $this->assign('url', $url);
            $this->display();
        }
        else if(strpos($userAgent, 'MicroMessenger') !== false)
        {
            //微信
            //引入微信库
            $wxPayLibPath = APP_PATH . 'Api/Extend/WxpaySdk';
            require_once $wxPayLibPath . '/lib/WxPay.Api.php';
            require_once $wxPayLibPath . '/example/WxPay.JsApiPay.php';
            $storeInfo = D('Store')->getStoreInfoByIdForApi($storeId);
            //配置appid 和 appsecret来获取sub_openid，方便获取用户昵称
            /* defined('WX_SUB_APPID') or define('WX_SUB_APPID', $storeInfo['wechat_app_id']);
            defined('WX_SUB_APPSECRET') or define('WX_SUB_APPSECRET', $storeInfo['wechat_app_secret']); */
            //①、获取用户openid
            $tools = new \JsApiPay();
            $tools->redirectForCode($arrState);
        }
        else
        {
            return $this->display('clientError');
            /* echo "<meta charset='utf-8'/>";
            echo '<h1>请在支付宝或者微信客户端打开</h1>';exit; */
        }
        
        //var_dump($userAgent);exit;
    }
    
    //微信用户输入金额界面
    public function wxpayInput()
    {
        //引入微信库
        $wxPayLibPath = APP_PATH . 'Api/Extend/WxpaySdk';
        require_once $wxPayLibPath . '/lib/WxPay.Api.php';
        require_once $wxPayLibPath . '/example/WxPay.JsApiPay.php';
        $tools = new \JsApiPay();
        $openid = $tools->GetOpenid();
        $strState = I('get.state');
        $arrState = [];
        parse_str($strState, $arrState);
        $storeId = $arrState['store_id'];
        $storeInfo = D('Store')->getStoreInfoByIdForApi($storeId);
        $this->assign('openid', $openid);
        $this->assign('storeId', $storeId);
        $this->assign('storeInfo', $storeInfo);
        $this->display('wxpayInput');
    }
    
    //ajax微信创建订单
    public function wxpayCreateTrade()
    {
        $openid = I('post.openid');
        $storeId = I('post.store_id');
        $totalAmount = I('post.total_amount');
        $storeInfo = D('Store')->getStoreInfoByIdForApi($storeId);
        $data = (new WxpayController())->createTrade($openid, $storeInfo, $totalAmount);
        if($data)
        {
            //引入微信库
            $wxPayLibPath = APP_PATH . 'Api/Extend/WxpaySdk';
            require_once $wxPayLibPath . '/lib/WxPay.Api.php';
            require_once $wxPayLibPath . '/example/WxPay.JsApiPay.php';
            $tools = new \JsApiPay();
            $jsApiParameters = $tools->GetJsApiParameters($data['result']);
            adminOutputData(['result'=>$jsApiParameters,'order_no'=>$data['order_no']]);
        }
        else
        {
            adminOutputErr('create order error');
        }
        
    }
    
    //支付宝输入金额
    public function alipayInput()
    {
        //file_put_contents('authCode.log', json_encode($_REQUEST)."\r\n\r\n", FILE_APPEND);
        $authCode = I('get.auth_code');
        $state = I('get.state');
        $storeId = $state['store_id'];
        if($authCode && $storeId)
        {
            $storeInfo = D('Store')->getStoreInfoByIdForApi($storeId);
            
            $this->assign('authCode', $authCode);
            $this->assign('storeId', $storeId);
            $this->assign('storeInfo', $storeInfo);
            $this->display('alipayInput');
        }
        
    }
    
    //ajax支付宝创建订单
    public function alipayCreateTrade()
    {
        $authCode = I('post.auth_code');
        $storeId = I('post.store_id');
        $totalAmount = I('post.total_amount');
        $data = (new AlipayController())->createTrade($authCode, $storeId, $totalAmount);
        adminOutputData($data);
    }
    
    //展示支付结果页，（现在只展示成功的，不成功的是个空页面）
    public function showPayResult()
    {
        $orderNo = I('get.order_no', null);
        $thirdOrderNo = I('get.third_order_no', null);
        $orderData = D('Order')->getOrderByNo($orderNo, $thirdOrderNo);
        if($orderData && $orderData['trade_status'] == OrderModel::TRADE_STATUS_PAY_SUCCESS)
        {
            //支付成功展示成功页面
            $this->assign('orderData', $orderData);
            $this->display('showPayResult');
        }
    }
    
    
}