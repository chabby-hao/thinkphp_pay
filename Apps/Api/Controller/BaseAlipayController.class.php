<?php
/**
 * Copyright (c) 2016,上海诱梦网络
 * 文件名称：BaseAlipayController.class.php
 * 摘    要：支付宝的接口基础类，此类会初始化支付宝接口的各项参数
 * 作    者：王志浩
 * 修改日期：2016.10.17
 */
namespace Api\Controller;
use Think\Controller;
class BaseAlipayController extends BasePayController {
    
    const ALIPAY_APP_ID = '2016090901877426';//支付宝应用id
    
    const ALIPAY_PID = '2088421858275842';//支付宝PID，也是UID
    
    private $_aopClient;//aop对象
    
    private $_app_auth_token = null;//授权token
    
    /**
     * 初始化方法，首先调用的方法,此方法会示例化aopClient
     * 参   数：无
     * 返   回：无
     * 功   能：初始化方法，首先调用的方法,此方法会示例化aopClient
     * 作   者：王志浩
     * 修改日期： 2016-10-17
     */
    protected function _initialize()
    {
        $token = $this->getPost('post.token', null);
        $this->_app_auth_token = $this->_getAppAuthToken($token);//应用授权token，暂时写死，后期根据具体商户去拿
        require APP_PATH . 'Api/Extend/AlipaySdk/AopSdk.php';
        $this->_aopClient = new \AopClient();
        $this->_aopClient->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $this->_aopClient->appId = self::ALIPAY_APP_ID;//应用id
        $this->_aopClient->format = "json";
        $this->_aopClient->charset= "UTF-8";
        $this->_aopClient->rsaPrivateKeyFilePath = APP_PATH . 'Api/Extend/AlipaySdk/cert/rsa_private_key.pem';
        $this->_aopClient->alipayPublicKey=APP_PATH . 'Api/Extend/AlipaySdk/cert/rsa_public_key.pem';
    }
    
    /**
     * 设置授权token
     * 参   数：$alipayToken   string  授权token
     * 返   回：无
     * 功   能：设置授权token
     * 作   者：王志浩
     * 修改日期： 2016-10-17
     */
    public function setAppAuthToken($alipayToken)
    {
        $this->_app_auth_token = $alipayToken;
    }
    
    /**
     * 调用SDK服务发起接口请求,返回json格式相应
     * 参   数：$request   object  支付宝请求体对象
     * 返   回：json       支付宝响应
     * 功   能：调用SDK服务发起接口请求,返回json格式相应
     * 作   者：王志浩
     * 修改日期： 2016-10-17
     */
    public function execute($request)
    {
        $response = $this->_aopClient->execute($request, null, $this->_app_auth_token);
        return json_encode($response);
    }
    
    /**
     * 根据登录token获取支付宝的app_auth_token
     * 参   数：$token   string    用户登录token
     * 返   回：string   支付宝授权token
     * 功   能：根据登录token获取支付宝的app_auth_token
     * 作   者：王志浩
     * 修改日期： 2016-10-17
     */
    private function _getAppAuthToken($token)
    {
        if(empty($token))
        {
            return $token;
        }
        $userInfo = $this->checkUserToken($token);
        return $userInfo['app_auth_token'];
        //return '201609BB4aab7d95b4e44c25892d0f8c5a9b6X05';
    }
    
    /**
     * 根据登录token获取支付宝的app_auth_token
     * 参   数：$token   string    用户登录token
     * 返   回：string   支付宝授权token
     * 功   能：根据登录token获取支付宝的app_auth_token
     * 作   者：王志浩
     * 修改日期： 2016-10-17
     */
    public function getNotifyUrl()
    {
        return U('/Api/Alipay/notify', '', true, true);
    }
    
    /**
     * 验证支付宝异步通知签名
     * 参   数：无
     * 返   回：bool   验签成功true ，失败false
     * 功   能：验证支付宝异步通知签名
     * 作   者：王志浩
     * 修改日期： 2016-10-17
     */
    public function checkNotifySign()
    {
        return $this->_aopClient->rsaCheckV1($_POST, $this->_aopClient->alipayPublicKey);
    }
    
}