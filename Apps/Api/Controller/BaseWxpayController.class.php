<?php
//微信支付基础类，微信支付相关接口可以继承此类
namespace Api\Controller;
use Think\Controller;
use Api\Msg\ErrMsg;
use Think\Exception;
use Common\Model\MerchantModel;
use Common\Extend\Wechat;
use Org\Net\Http;

class BaseWxpayController extends BasePayController {
    
    
    //微信支付sdk路径
    public $wxpaySdkpath;
    
    //初始化方法
    protected function _initialize()
    {
        //引入微信库
        $this->wxpaySdkpath = APP_PATH . 'Api/Extend/WxpaySdk';
        require_once $this->wxpaySdkpath . '/lib/WxPay.Api.php';
    }
    
    //根据openid，appid，appsecret获取微信昵称(前提需要用户关注该appid的公众号)，没有关注返回false
    public function getWxpayUserPickname($openId, $appid, $appsecret)
    {
        $objWechat = new Wechat();
        $arrWxUserInfo = $objWechat->getUserInfoForApi($appid, $appsecret, $openId);
        if($arrWxUserInfo)
        {
            D('WxOpenidMap')->replaceByWxUser($arrWxUserInfo, $appid, $appsecret);
            return $arrWxUserInfo['nickname'];
        }
        else
        {
            $where = [];
            $where['appid'] = $appid;
            $where['openid'] = $openId;
            $arrData = D('WxOpenidMap')->where($where)->find();
            if($arrData)
            {
                return $arrData['nickname'];
            }
        }
        
        return '';
    }
    
    //根据用户登录token，返回用户子商户的商户号（微信的商户号）
    public function getWxpaySubMchId($token)
    {
        $userInfo = $this->checkUserToken($token);
        return $userInfo['wechat_mch_id'];
        //根据token返回子商户号
        //return '1380385902';
    }
    
    //返回微信子商户公众号appid
    public function getWxpaySubAppid($token)
    {
        $userInfo = $this->checkUserToken($token);
        return $userInfo['wechat_app_id'];
    }
    
}