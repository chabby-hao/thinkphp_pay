<?php 
//支付宝店铺申请记录表
namespace Common\Model;
use Think\Model;
class StoreApiModel extends Model {
    
    protected $tableName = 'store_api';//表名
    
    //门店审核状态，对于开发者而言，只有三个状态。AUDITING：审核中,AUDIT_FAILED：审核驳回,AUDIT_SUCCESS：审核通过,第一次审核通过会触发门店上架

    const AUDIT_STATUS_AUDITING = 'AUDITING';
    const AUDIT_STATUS_AUDIT_FAILED = 'AUDIT_FAILED';
    const AUDIT_STATUS_AUDIT_SUCCESS = 'AUDIT_SUCCESS';
    
    const API_STATUS_WAIT = 10;//未收到通知
    const API_STATUS_RECEIVED = 20;//已收到通知
    
    public static $auditStatusMap = [
	   self::AUDIT_STATUS_AUDITING => '审核中',
	   self::AUDIT_STATUS_AUDIT_FAILED => '审核失败',
	   self::AUDIT_STATUS_AUDIT_SUCCESS => '审核成功',
    ];
    
    //获取门店最后一条支付宝申请店铺审核记录
    public function getLastData($storeId)
    {
        return $this->where('store_id=:store_id')->bind([":store_id"=>$storeId])->order('id desc')->find();
    }
    
}