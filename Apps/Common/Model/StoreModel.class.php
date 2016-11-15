<?php 
//店铺
namespace Common\Model;
use Think\Model;
class StoreModel extends Model {
    
    const REDIS_STORE_INFO_PRE = 'store:';
    
    const AUDIT_STATUS_WAIT = 1;//商户未提交审核
    const AUDIT_STATUS_SUBMIT = 2;//商户已提交审核
    const AUDIT_STATUS_SUCCESS = 3;//支付宝审核通过
    const AUDIT_STATUS_MODIFY = 4;//已审核店铺重新修改信息
    
    //审核状态map映射
    public static $auditStatusMap =[
	   self::AUDIT_STATUS_WAIT => '商户未提交审核',
	   self::AUDIT_STATUS_SUBMIT => '商户已提交审核',
	   self::AUDIT_STATUS_SUCCESS => '支付宝审核通过',
	   self::AUDIT_STATUS_MODIFY => '已审核店铺重新修改信息',
    ];
    
    protected $tableName = 'store';//表名
    
    
    
    //获取店铺信息
    public function getStoreList($where = [], $bind = [])
    {
        return $this->where()->bind()->select();
    }
    
    //根据商户id返回店铺列表
    public function getStoreListByMerchantId($merchantId)
    {
        $where = [];
        $where['merchant_id'] = ':merchant_id';
        $bind = [];
        $bind[':merchant_id'] = $merchantId;
        return $this->where($where)->bind($bind)->select();
    }
    
    //检测制定商户下是否存在门店
    public function checkStoreExistsByMerchantId($merchantId)
    {
        $where = ['merchant_id'=>':merchant_id'];
        $bind = [':merchant_id'=>$merchantId];
        $res = $this->where($where)->bind($bind)->find();
        return empty($res) ? false : true;
    }
    
    //增加店铺
    public function addStore($data)
    {
        $store = [];
        $store['store_name'] = '默认店铺';
        $store['create_time'] = $store['update_time'] = time();
        $store['audit_status'] = self::AUDIT_STATUS_WAIT;
        if(is_array($data))
        {
            $store = array_merge($store, $data);
        }
        return $this->add($store);
    }
    
    public function editStore($data, $id)
    {
        if(is_array($data))
        {
            unset($data['id']);
            return $this->where('id=:id')->bind([':id'=>$id])->save($data);
        }
        return false;
    }
    
    //优先从redis获取店铺，没有则拿数据库的同时保存到redis
    public function getStoreInfoByIdForApi($storeId)
    {
        $storeInfo = $this->getStoreInfoByIdFromRedis($storeId);
        if(!$storeInfo)
        {
            $storeInfo = $this->getStoreByIdFromMysql($storeId);
            $this->setStoreInfoToRedis($storeInfo, $storeId);
        }
        return $storeInfo;
    }
    
    
    //从redis获取店铺信息
    public function getStoreInfoByIdFromRedis($storeId)
    {
        $strKey = self::REDIS_STORE_INFO_PRE . $storeId;
        return S($strKey);
    }
    
    //设置店铺信息到redis
    public function setStoreInfoToRedis($storeInfo, $storeId)
    {
        $strKey = self::REDIS_STORE_INFO_PRE . $storeId;
        $expire = 3600*24*30;
        return S($strKey, $storeInfo, ['expire'=>$expire]);
    }
    
    //从mysql获取店铺
    public function getStoreByIdFromMysql($storeId)
    {
        $where = [];
        $where['store.id'] = ':id';
        $bind = [];
        $bind[':id'] = $storeId;
        return $this->field('merchant.merchant_name,merchant.alipay_token,merchant.wechat_mch_id,merchant.wechat_app_id,merchant.wechat_app_secret,store.*')->join('merchant on merchant.id=store.merchant_id')->where($where)->bind($bind)->find();
    }
    
    //清除店铺在redis数据
    public function clearStoreByIdFromRedis($storeId)
    {
        $strKey = self::REDIS_STORE_INFO_PRE . $storeId;
        return S($strKey, null);
    }
    
}
