<?php
namespace Admin\Controller;
use Think\Controller;
class GoodsController extends BaseController {
    
    //商品列表
    public function index(){
        $permisWhere = $this->getWhereByUserRole(session('user_role'), false, 'store.');
        //$goodsList = D('Store')->field('store.store_name,store.id as s_id,goods_class.class_name,goods.*')->join('goods_class on store.id=goods_class.store_id','left')->join('goods on goods_class.id=goods.class_id')->where($permisWhere)->order('store.id desc,class_sort desc')->select();
        array_walk($permisWhere, function (&$v, $k){            $v = $k . '=' . $v;        });
        $strWhere = implode(' AND ', $permisWhere);
        $goodsList = M()->query("select store.store_name,store.id as s_id,goods_class.class_name,goods.* from (store left join goods on store.id=goods.store_id) left join goods_class on goods.class_id=goods_class.id where $strWhere order by store.id desc, class_sort desc, goods_sort desc");
        $storeGoodsList = [];
        foreach ($goodsList as $goods)
        {
            $storeGoodsList[$goods['s_id']][] = $goods;
        }
        $storeMap = $this->getStoreMap();
        $this->assign('storeMap', $storeMap);
        $classWhere = $this->getWhereByUserRole(session('user_role'), false, 'goods_class.');
        $classList = D('GoodsClass')->where($classWhere)->select();
        if(!$classList)
        {
            //如果没有类目先添加类目
            $this->redirect('GoodsClass/index');
        }else{
            $classMap = [];
            foreach ($classList as $class)
            {
                $classMap[$class['store_id']][] = $class;
            }
        }
        //$classMap = arrayMap($classList, 'id');
        $this->assign('classMap', $classMap);
        $this->assign('list', $storeGoodsList);
        $this->_setPageHeaderAction('列表');
        $this->display();
    }
    
    public function addHandler()
    {
        $arrInput = I('post.');
        if($arrInput)
        {
            $arrInput['merchant_id'] = session('merchant_id');
            $res = D('Goods')->add($arrInput);
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
            $res = D('Goods')->where($where)->bind($bind)->save($arrInput);
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
            $goodsWhere = array_merge($where, ['id'=>':id']);
            $res = D('Goods')->where($goodsWhere)->bind([':id'=>$id])->delete();
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
        $this->_setPageHeader($action, '商品');
    }
    
}