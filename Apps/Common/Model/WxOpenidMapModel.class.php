<?php 
//微信openid和昵称映射表
namespace Common\Model;
use Think\Model;
class WxOpenidMapModel extends Model {
    
    protected $tableName = 'wx_openid_map';//表名
    
    //替换一条，会过滤相同appid,openid
    public function replaceByWxUser($wxUser, $appid, $appsecret)
    {
        $where = [];
        $where['appid'] = $appid;
        $where['openid'] = $wxUser['openid'];
        
        $arrData = $this->where($where)->find();
        if($arrData && $arrData['nickname'] != $wxUser['nickname'])
        {
            $data = [];
            $data['nickname'] = $wxUser['nickname'];
            $data['api_desc'] = json_encode($wxUser);
            //如果昵称更换了
            return $this->where($where)->save($data);
        }
        elseif($arrData)
        {
            //已经存在,什么也不用做
            return true;
        }
        else 
        {
            //库里没有，加进去
            $data = [];
            $data['appid'] = $appid;
            $data['appsecret'] = $appsecret;
            $data['openid'] = $wxUser['openid'];
            $data['nickname'] = $wxUser['nickname'];
            $data['api_desc'] = json_encode($wxUser);
            return $this->where($where)->add($data);
        }
        return false;
    }
    
}
