<?php 
//商户
namespace Common\Model;
use Think\Model;
class MerchantModel extends Model {
    
    const REDIS_MERCHANT_INFO_PRE = 'merchant:';
    
    protected $tableName = 'merchant';//表名
    
    public function getMerchantByStoreId($storeId)
    {
        return $this->field('merchant.*')->join('store on merchant.id=store.merchant_id')->where('store_id=:store_id')->bind([':store_id'=>$storeId])->find();
    }
    
    //获取微信子商户列表，返回[商户id=>[子商户号,子商户appid]]
    public function getWxMchIdMap()
    {
        $merchantList = D('merchant')->select();
        $mchIdMap = [];
        if($merchantList)
        {
            foreach ($merchantList as $merchantData)
            {
                if($merchantData['wechat_mch_id'])
                {
                    $mchIdMap[$merchantData['id']] = [$merchantData['wechat_mch_id'], $merchantData['wechat_app_id']];
                }
            }
        }
        return $mchIdMap;
        
    }
    
    //优先从redis中，其次从mysql中
    public function getMerchantInfoByIdForApi($merchantId)
    {
        $merchantInfo = $this->getMerchantInfoByIdFromRedis($merchantId);
        if(!$merchantInfo)
        {
            $merchantInfo = $this->getById($merchantId);
            $this->setMerchantInfoToRedis($merchantInfo, $merchantId);
        }
        return $merchantInfo;
        
    }
    
    //设置Redis
    public function setMerchantInfoToRedis($merchantInfo, $merchantId)
    {
        $strKey = self::REDIS_MERCHANT_INFO_PRE . $merchantId;
        $expire = 3600*24*30;//一个月
        return S($strKey, $merchantInfo, ['expire'=>$expire]);
    }
    
    //从redis取商户信息
    public function getMerchantInfoByIdFromRedis($merchantId)
    {
        $strKey = self::REDIS_MERCHANT_INFO_PRE . $merchantId;
        return S($strKey);
    }
    
    //从redis中删除商户信息
    public function clearMerchantInfoFromRedis($merchantId)
    {
        $strKey = self::REDIS_MERCHANT_INFO_PRE . $merchantId;
        return S($strKey, null);
    }
    
}
