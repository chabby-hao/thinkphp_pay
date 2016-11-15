<?php 
//商品
namespace Common\Model;
use Think\Model;
class GoodsModel extends Model {
    
    protected $tableName = 'goods';//表名
    
    const GOODS_CACHE_KEY_PRE = 'goods_list:';
    
    //为接口获取商品列表，优先redis，其次mysql
    public function getGoodsListByStoreIdForApi($storeId)
    {
        $arrList = $this->getGoodsListByStoreIdFromRedis($storeId);
        if(!$arrList)
        {
            $arrList = $this->getGoodsListByStoreIdFromMysql($storeId);
            $this->setGoodsListToRedis($arrList, $storeId);
        }
        $arrClassList = $arrClassMap = $arrRtn = [];
        if($arrList)
        {
            foreach ($arrList as $arrData)
            {
                $tmp = [];
                $tmp['id'] = $arrData['id'];
                $tmp['goods_name'] = $arrData['goods_name'];
                $tmp['goods_price'] = $arrData['goods_price'];
                $arrClassList[$arrData['class_id']][] = $tmp;;
                if(!isset($arrClassMap[$arrData['class_id']]))
                {
                    $arrClassMap[$arrData['class_id']] = $arrData['class_name'];
                }
            }
            foreach ($arrClassList as $classId => $arrClass)
            {
                $tmp = [];
                $tmp['title'] = $arrClassMap[$classId];
                $tmp['goods_list'] = $arrClass;
                $arrRtn[] = $tmp;
            }
        }
        return $arrRtn;
    }
    
    //从mysql获取商品列表
    public function getGoodsListByStoreIdFromMysql($storeId)
    {
        $where = ['goods.store_id'=>':store_id'];
        $bind = [':store_id'=>$storeId];
        $arrList = $this->field('goods_class.class_name,goods.*')->join('goods_class on goods.class_id=goods_class.id')->where($where)->bind($bind)->order('goods_class.class_sort desc,goods.goods_sort desc')->select();
        return $arrList;
    }
    
    //从redis获取商品列表
    public function getGoodsListByStoreIdFromRedis($storeId)
    {
        $strKey = self::GOODS_CACHE_KEY_PRE . $storeId;
        return S($strKey);
    }
    
    public function setGoodsListToRedis($goodsList, $storeId)
    {
        $strKey = self::GOODS_CACHE_KEY_PRE . $storeId;
        $expire = 3600 * 24 * 30 * 12;
        return S($strKey, $goodsList, ['expire'=>$expire]);
    }
}
