<?php 
namespace Common\Extend;

use Org\Net\Http;
class Wechat
{
    const REDIS_ACCESS_TOKEN_PRE = 'access_token:';
    
    public function __construct()
    {
        
    }
    
    public function getAccessTokenFromRedis($subAppid)
    {
        return S(self::REDIS_ACCESS_TOKEN_PRE . $subAppid);
    }
    
    public function setAccessToken($accessToken, $subAppid, $expire = 7000)
    {
        return S(self::REDIS_ACCESS_TOKEN_PRE . $subAppid, $accessToken, ['expire'=>$expire]);
    }
    
    public function getAccessTokenForApi($subAppid, $subAppsecret)
    {
        $accessToken = $this->getAccessTokenFromRedis($subAppid);
        if( !$accessToken)
        {
            $accessToken = $this->getAccessTokenFromWx($subAppid, $subAppsecret);
        }
        return $accessToken;
    }
    
    public function getAccessTokenFromWx($subAppid, $subAppsecret)
    {
        //$url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={APPID}&secret={APPSECRET}';
        $url = 'https://api.weixin.qq.com/cgi-bin/token';
        $params = [];
        $params['grant_type'] = 'client_credential';
        $params['appid'] = $subAppid;
        $params['secret'] = $subAppsecret;
        $json = Http::http($url, $params);
        $arr = json_decode($json, true);
        if(isset($arr['access_token']))
        {
            //延迟时间-100秒，防止刷新token时触发边界失效
            $this->setAccessToken($arr['access_token'], $subAppid, $arr['expires_in'] - 100);
            return $arr['access_token'];
        }
        return false;
    }
    
    public function getUserInfoFromWx($accessToken, $openid)
    {
        //$url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token={ACCESS_TOKEN}&openid={OPENID}&lang=zh_CN';
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info';
        $params = [
	       'access_token' => $accessToken,
	       'openid' => $openid,
	       'lang' => 'zh_CN',
        ];
        $json = Http::http($url, $params);
        $arr = json_decode($json, true);
        if (isset($arr['subscribe']) && $arr['subscribe'] == 1)
        {
            /*
             * { "subscribe": 1, "openid": "o6_bmjrPTlm6_2sgVt7hMZOPfL2M", "nickname": "Band", "sex": 1, "language": "zh_CN", "city": "广州", "province": "广东", "country": "中国", "headimgurl": "http://wx.qlogo.cn/mmopen/g3MonUZtNHkdmzicIlibx6iaFqAc56vxLSUfpb6n5WKSYVY0ChQKkiaJSgQ1dZuTOgvLLrhJbERQQ4eMsv84eavHiaiceqxibJxCfHe/0", "subscribe_time": 1382694957, "unionid": " o6_bmasdasdsad6_2sgVt7hMZOPfL" "remark": "", "groupid": 0 }
             */
            return $arr;
        }
        return false;
    }
    
    //返回用户基本信息{ "subscribe": 1, "openid": "o6_bmjrPTlm6_2sgVt7hMZOPfL2M", "nickname": "Band", "sex": 1, "language": "zh_CN", "city": "广州", "province": "广东", "country": "中国", "headimgurl": "http://wx.qlogo.cn/mmopen/g3MonUZtNHkdmzicIlibx6iaFqAc56vxLSUfpb6n5WKSYVY0ChQKkiaJSgQ1dZuTOgvLLrhJbERQQ4eMsv84eavHiaiceqxibJxCfHe/0", "subscribe_time": 1382694957, "unionid": " o6_bmasdasdsad6_2sgVt7hMZOPfL" "remark": "", "groupid": 0 }
    public function getUserInfoForApi($subAppid, $subAppsecret, $openid)
    {
        $accessToken = $this->getAccessTokenForApi($subAppid, $subAppsecret);
        if($accessToken)
        {
            return $this->getUserInfoFromWx($accessToken, $openid);
        }
        return false;
    }
    
}