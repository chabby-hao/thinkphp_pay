<?php
//用户控制器
namespace Api\Controller;
use Think\Controller;
use Api\Msg\ErrMsg;
use Common\Model\UserModel;
class UserController extends BaseController {
    
    //登录
    public function login()
    {
        $arrInput = checkFieldInArray('user_name,user_pwd', $this->getPost('post.'));
        $mixRes = D('User')->getUser($arrInput['user_name'], $arrInput['user_pwd']);
        if( $mixRes !== false)
        {
            //登录成功，token更换
            $userInfo = [];
            $userInfo['user_id'] = $mixRes['id'];
            $userInfo['store_id'] = $mixRes['store_id'];
            $userInfo['merchant_id'] = $mixRes['merchant_id'];
            $userInfo['user_role'] = $mixRes['user_role'];
            $userInfo['alipay_store_id'] = $mixRes['alipay_store_id'];//支付宝店铺id
            $userInfo['app_auth_token'] = $mixRes['alipay_token'];//支付宝的商户授权token
            $userInfo['wechat_mch_id'] = $mixRes['wechat_mch_id'];//微信子商户id
            $userInfo['wechat_app_id'] = $mixRes['wechat_app_id'];//微信子公众号appid
            $userInfo['wechat_app_secret'] = $mixRes['wechat_app_secret'];//微信子公众号appsecret
            $userInfo['store_name'] = $mixRes['store_name'];
            $userInfo['merchant_name'] = $mixRes['merchant_name'];
            $token = D('User')->resetTokenForLogin($userInfo);
            if( $token)
            {
                $data = ['token'=>$token];
                outputData($data);
                //输出token
            }
        }
        outputErr(ErrMsg::$err['user.invalid']);
        //登录失败，输出错误
    }
    
    //获取子账号列表
    public function getSubUser()
    {
        $arrInput = checkFieldInArray('token', $this->getPost('post.'));
        $token = $arrInput['token'];
        $userInfoFromToken = $this->checkUserToken($token);
        $userRole = $userInfoFromToken['user_role'];
        $merchantId = $userInfoFromToken['merchant_id'];
        $storeId = $userInfoFromToken['store_id'];
        $userId = $userInfoFromToken['user_id'];
        $map = [];
        $map['id'] = $userId;
        $map['_logic'] = 'or';
        $where = " (merchant_id=$merchantId AND store_id=$storeId ";
        if ($userRole == UserModel::USER_ROLE_BOSS)        { // 老板，有个特殊判断，如果老板只有1个店铺，则默认给操作员列表
            $intStoreCount = D('Store')->where("merchant_id=$merchantId")->count();
            if($intStoreCount == 1)
            {
                $where .= " AND user_role='" . UserModel::USER_ROLE_OPERATOR . "') ";
            }else {
                //这里加个特殊判断，如果是老板，并且有多个店铺，则返回用户id=>店铺名
                $storeWhere = [];
                $storeWhere['store.merchant_id'] = $merchantId;
                //$storeWhere['`user`.user_role'] = UserModel::USER_ROLE_ACCOUNTANT;
                $storeList = D('Store')->field('`user`.id AS user_id,`user`.user_role,store.store_name')->join('`user` on store.id=`user`.store_id', 'LEFT')->where($storeWhere)->select();
                if($storeList)
                {
                    $dataAll = $storeMap = [];
                    foreach ($storeList as $arrStore)
                    {
                        if(isset($storeMap[$arrStore['store_name']]))
                        {
                            //如果存在相同店铺，则跳过循环，过滤掉
                            continue;
                        }
                        //只有店长才可以输出
                        if($arrStore['user_role'] == UserModel::USER_ROLE_ACCOUNTANT)
                        {
                            $storeMap[] = $arrStore['store_name'];
                            $data = [];
                            $data['user_id'] = $arrStore['user_id'];
                            $data['user_name'] = $arrStore['store_name'];
                            $dataAll[] = $data;
                        }
                        elseif($arrStore['user_id'] === null)
                        {
                            $storeMap[] = $arrStore['store_name'];
                            $data = [];
                            $data['user_id'] = -1;//没有店长，返回-1
                            $data['user_name'] = $arrStore['store_name'];
                            $dataAll[] = $data;
                        }
                        else 
                        {
                            //其他的都是店员，不做任何处理
                        }
                    }
                    //过滤重复店铺，防止一个店铺有多个店长，导致输出多个相同店铺
                    outputData($dataAll);
                }
                
                //$where .= " AND user_role='" . UserModel::USER_ROLE_ACCOUNTANT . "') ";
            }        }        elseif ($userRole == UserModel::USER_ROLE_ACCOUNTANT)        { // 店长
            $where .= " AND user_role='" . UserModel::USER_ROLE_OPERATOR . "') ";        }        else        { // 操作员
            $where .= " AND id=" . $userId . ") ";        }
        $where .= " OR id=$userId";
        $userList = D('User')->where($where)->select();
        if($userList)
        {
            $dataAll = [];
            foreach ($userList as $userData)
            {
                $data = [];
                $data['user_id'] = $userData['id'];
                $data['user_name'] = $userData['user_pickname'] ? $userData['user_pickname'] : $userData['user_name'];
                $dataAll[] = $data;
            }
            outputData($dataAll);
        }
        outputErr(ErrMsg::$err['user.info.get.error']);
    }
    
    //获取用户信息
    public function userInfo()
    {
        $arrInput = checkFieldInArray('token', $this->getPost('post.'));
        $token = $arrInput['token'];
        $userInfoFromToken = $this->checkUserToken($token);
        $userInfo = D('User')->getUserDetailById($userInfoFromToken['user_id']);
        if(!$userInfo)
        {
            outputErr(ErrMsg::$err['user.info.get.error']);
        }
        $data = [];
        $data['user_name'] = $userInfo['user_pickname'] ? $userInfo['user_pickname'] : $userInfo['user_name'];
        $data['user_rolename'] = UserModel::$userRoleMap[$userInfo['user_role']];
        $data['user_permis'] = UserModel::$userPermisMap[$userInfo['user_role']];
        $data['store_name'] = $userInfo['store_name'];
        $data['merchant_name'] = $userInfo['merchant_name'];
        outputData($data);
    }

}