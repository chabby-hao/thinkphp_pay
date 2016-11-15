<?php
//商品控制器
namespace Api\Controller;
use Think\Controller;
use Api\Msg\ErrMsg;
class GoodsController extends BaseController {
    
    
    //获取商品列表
    public function goodsList()
    {
        $arrInput = checkFieldInArray('token', $this->getPost('post.'));
        $userInfo = $this->checkUserToken($arrInput['token']);
        $storeId = $userInfo['store_id'];
        $arrList = D('Goods')->getGoodsListByStoreIdForApi($storeId);
        outputData(['list'=>$arrList]);
        
    }
    
}