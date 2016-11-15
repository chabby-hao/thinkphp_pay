<?php 
//用户
namespace Common\Model;
use Think\Model;
class UserModel extends Model {
    
    const USER_ROLE_OPERATOR = 'operator';//操作员级别，可看自己数据
    const USER_ROLE_ACCOUNTANT = 'accountant';//财务级别，可看本店铺数据
    const USER_ROLE_BOSS = 'boss'; // boss级别，可看所有店铺数据
    
    const TOKEN_KEY_PRE = 'login_token:';
        public static $userRoleMap = [        self::USER_ROLE_OPERATOR => '操作员',        self::USER_ROLE_ACCOUNTANT => '店长',        self::USER_ROLE_BOSS => '老板'    ];    public static $userPermisMap = [        self::USER_ROLE_OPERATOR => '单个账号的收银、退款',        self::USER_ROLE_ACCOUNTANT => '后台、本店全店的收银、退款',        self::USER_ROLE_BOSS => '所有权限',    ];
    
    protected $tableName = 'user';//表名
    
    protected static $arrToken = [];
    
    protected function _getLoginTokenRedisKey($token)
    {
        return self::TOKEN_KEY_PRE . $token;
    }
    
    public function addUser($data)
    {
        $user = [];
        $user['create_time'] = $user['update_time'] = time();
        $data = array_filter($data);
        $user = array_merge($user, $this->filterUserPwd($data));
        return $this->add($user);
    }
    
    public function editUser($data, $userId)
    {
        $user = [];
        $user['update_time'] = time();
        $data = array_filter($data);
        $user = array_merge($user, $this->filterUserPwd($data));
        return $this->where('id=:id')->bind([':id'=>$userId])->save($user);
    }
    
    public function filterUserPwd($data)
    {
        if($data)
        {
        	if(!empty($data['user_pwd']))
        	{
        		$data['user_pwd'] = $this->generateUserPwd($data['user_pwd']);
        	}
        }
        return $data;
    } 
    
    //检查用户名和密码是否正确
    public function getUser($userName, $strInputPwd)
    {
        $where = [];
        $where['user_name'] = ':user_name';
        $where['user_pwd'] = ':user_pwd';
        //$where['merchant_no'] = ':merchant_no';
        $bind = [];
        $bind[':user_name'] = $userName;
        $bind[':user_pwd'] = $this->generateUserPwd($strInputPwd);
        //$bind[':merchant_no'] = $merchantNo;
        
        $res = $this->field('user.*,store.alipay_store_id,merchant.merchant_name,merchant.alipay_token,merchant.wechat_mch_id,merchant.wechat_app_id,merchant.wechat_app_secret,store.store_name')->join('store on store.id=user.store_id','left')->join('merchant on merchant.id=user.merchant_id')->where($where)->bind($bind)->find();
        return empty($res) ? false : $res;
    }
    
    
    //获取用户详细信息，包括店铺名和商品名
    public function getUserDetailById($userId)
    {
        $where = ['user.id'=>':id'];
        $bind = [':id'=>$userId];
        $res = $this->field('user.*,store.store_name,merchant.merchant_name')->join('store on store.id=user.store_id','left')->join('merchant on merchant.id=user.merchant_id')->where($where)->bind($bind)->find();
        return empty($res) ? false : $res;
    }
    
    //重新生成token
    public function resetTokenForLogin($userInfo)
    {
        if($userInfo)
        {
            $token = $this->generateToken();
            $res = $this->setUserToken($token, $userInfo);
            if($res === true)
            {
                return $token;
            }
        }
        return false;
    }
    
    //生成token
    public function generateToken()
    {
        $token = md5(time().mt_rand(1000, 9999));
        if($this->checkTokenExists($token))
        {
            return $this->generateToken();
        }
        return $token;
    }
    
    //检测token是否存在
    public function checkTokenExists($token)
    {
        //获取token
        $res = S($this->_getLoginTokenRedisKey($token));
        return $res !== false ? true : false;
    }
    
    //设置用户的信息和token到缓存中去
    public function setUserToken($token, $userInfo)
    {
        $intLimitTime = strtotime('+1 days',strtotime(date('Y-m-d 00:00:00')));
        $expire = $intLimitTime - time();
        return S($this->_getLoginTokenRedisKey($token), $userInfo, ['expire'=>$expire]);
    }
    
    //根据token获取用户信息，如果没有或者失效则返回false
    public function getUserByToken($token)
    {
        //做个缓存
        if(!array_key_exists(self::$arrToken[$token]))
        {
            self::$arrToken[$token] = S($this->_getLoginTokenRedisKey($token));
        }
        return self::$arrToken[$token];
    }
    
    public function generateUserPwd($strInputPwd)
    {
        return md5(sha1($strInputPwd));
    }
    
}