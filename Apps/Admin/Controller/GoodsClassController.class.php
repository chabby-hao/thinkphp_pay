<?php
namespace Admin\Controller;
use Think\Controller;
class GoodsClassController extends BaseController {
    
    //商品列表
    public function index(){
        $permisWhere = $this->getWhereByUserRole(session('user_role'), false, 'store.');
        $goodsClassList = D('Store')->field('store.store_name,store.id as s_id,goods_class.*')->join('goods_class on store.id=goods_class.store_id','left')->where($permisWhere)->order('store.id desc,class_sort desc')->select();
        $storeGoodsClassList = [];
        foreach ($goodsClassList as $goods)
        {
            $storeGoodsClassList[$goods['s_id']][] = $goods;
        }
        $storeMap = $this->getStoreMap();
        $this->assign('storeMap', $storeMap);
        $this->assign('list', $storeGoodsClassList);
        $this->_setPageHeaderAction('列表');
        $this->display();
    }
    
    
    public function addHandler()
    {
        $arrInput = I('post.');
        if($arrInput)
        {
            $arrInput['merchant_id'] = session('merchant_id');
            $res = D('GoodsClass')->add($arrInput);
            if($res !== false)
            {
                $storeId = session('store_id');
                $goodsList = D('Goods')->getGoodsListByStoreIdFromMysql($storeId);
                D('Goods')->setGoodsListToRedis($goodsList, $storeId);
                ajaxRedirect('index');
            }
        }
        adminOutputErr('失败');
    }
    
    public function editHandler()
    {
        $arrInput = I('post.');
        $id = $arrInput['id'];
        unset($arrInput['id']);
        if($id)
        {
            $where = ['id'=>':id'];
            $bind = [':id'=>$id];
            $res = D('GoodsClass')->where($where)->bind($bind)->save($arrInput);
            if( $res !== false)
            {
                $storeId = session('store_id');
                $goodsList = D('Goods')->getGoodsListByStoreIdFromMysql($storeId);
                D('Goods')->setGoodsListToRedis($goodsList, $storeId);
                adminOutputData('success');
            }
        }
        adminOutputErr('失败!');
    }
    
    public function delHandler()
    {
        $id = I('post.id');
        if($id)
        {
            $where = $this->getWhereByUserRole(session('user_role'));
            $classWhere = array_merge($where, ['class_id'=>':class_id']);
            $res = D('Goods')->where($classWhere)->bind([':class_id'=>$id])->find();
            if($res)
            {
                adminOutputErr('这个分类下面还有商品，请先删除商品');
            }
            $goodsWhere = array_merge($where, ['id'=>':id']);
            $res = D('GoodsClass')->where($goodsWhere)->bind([':id'=>$id])->delete();
            if($res !== false)
            {
                $storeId = session('store_id');
                $goodsList = D('Goods')->getGoodsListByStoreIdFromMysql($storeId);
                D('Goods')->setGoodsListToRedis($goodsList, $storeId);
                ajaxRedirect('index');
            }
        }
        adminOutputErr('失败');
    }
    
    protected function _setPageHeaderAction($action)
    {
        $this->_setPageHeader($action, '商品类目');
    }
    
}