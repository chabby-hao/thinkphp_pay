<?php 
//自更新
namespace Common\Model;
use Think\Model;
class SelfUpdateModel extends Model {
    
    protected $tableName = 'self_update';//表名
    
    const IS_USE_CLOSE = 10;//停用
    const IS_USE_OPEN = 20;//启用
    
    const UPDATE_TYPE_NORMAL = 1;//普通更新
    const UPDATE_TYPE_FORCE = 2;//强制更新
    
    const NAME_MAJOR = 'major';//主键
    
    const CACHE_KEY = 'self_update:current_version';//缓存在redis中的键名
    
    //设置自更新数据到redis
    public function setSelfUpdateToRedis($arrData)
    {
        $expire = 3600 * 24 * 30;
        S(self::CACHE_KEY, $arrData, ['expire'=>$expire]);
    }
    
    //从redis获取自更新， 返回false 或者 自更新数组
    public function getSelfUpdateFromRedis()
    {
        return S(self::CACHE_KEY);
    }
    
    //清除redis中，自更新数据
    public function clearSelfUpdateFromRedis()
    {
        return S(self::CACHE_KEY, null);
    }
    
    //从mysql获取自更新
    public function getSelfUpdateFromMysql()
    {
        return $this->where(['name'=>SelfUpdateModel::NAME_MAJOR])->find();
    }
    
    //用于接口，优先从缓存读，其次从db中读
    public function getSelfUpdateForApi()
    {
        $arrData = S(self::CACHE_KEY);
        if(!$arrData)
        {
            $arrData = $this->getSelfUpdateFromMysql();
            $this->setSelfUpdateToRedis($arrData);
        }
        return $arrData;
    }
    
}
