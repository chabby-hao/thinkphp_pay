<?php
namespace Api\Controller;
use Think\Controller;
use Api\Msg\ErrMsg;
use Think\Exception;
use Common\Model\UserModel;
use Common\Model\SelfUpdateModel;

class BaseController extends Controller {
    
    public $intPageLimit = 20;//默认分页数
    
    private static $postData = null;
    
    protected $_noUpdRoute = [
        '/Api/SelfUpdate/checkUpdate'=>1,
    ];
    
    
    //所有接口类的构造方法，会对post数据进行解密并保存到属性$postData中，同时检测是否需要强制更新
    public function __construct()
    {
        parent::__construct();
        
        //传输数据解密保存到静态属性中
        if(self::$postData === null)
        {
            $strPostData = file_get_contents('php://input');
            $decodeData = strCode($strPostData, 'DECODE', C('apihash'));
            self::$postData = json_decode($decodeData, true);   //先json格式请求解析，不行的话，用a=1&b=2这种格式解析
            if(!self::$postData)
            {
                parse_str($decodeData, self::$postData);
            }
        }
        
        $route = __ACTION__;
        if( !isset($this->$_noUpdRoute[$route]))
        {
            
            $vercode = $this->getPost('post.vercode', 1);
            
            if($vercode)
            {
                $arrData = D('SelfUpdate')->getSelfUpdateForApi();
                if($arrData['is_use'] == SelfUpdateModel::IS_USE_OPEN 
                && $arrData['update_type'] == SelfUpdateModel::UPDATE_TYPE_FORCE 
                && (intval($arrData['vercode'])-intval($vercode)) > 0 && file_exists(ROOT_PATH . '/Web' . $arrData['apk_path']) )
                {
                    outputErr(ErrMsg::$err['self.update.force']);
                }
            }
        }
        
    }
    
    //检查用户登录token是否有效，如果有效会直接返回用户信息数组(里面获取用户信息在程序中有缓存，不用担心多次调用此方法降低程序效率)，否则输出错误，退出程序
    public function checkUserToken($token)
    {
        $userInfo = D('User')->getUserByToken($token);
        if(!$userInfo)
        {
            outputErr(ErrMsg::$err['user.token.invalid']);
        }
        return $userInfo;
    }
    
    //接受客户端加密后的post参数
    public function getPost($name,$default='',$filter=null,$datas=null)
    {
        if(strpos($name,'/')){ // 指定修饰符
            list($name,$type) 	=	explode('/',$name,2);
        }elseif(C('VAR_AUTO_STRING')){ // 默认强制转换为字符串
            $type   =   's';
        }
        if(strpos($name,'.')) { // 指定参数来源
            list($method,$name) =   explode('.',$name,2);
        }else{ // 默认为自动判断
            $method =   'param';
        }
        switch(strtolower($method)) {
        	case 'post'    :
        	    $input =& self::$postData;
        	    break;
        	default:
        	    return null;
        }
        if(''==$name) { // 获取全部变量
            $data       =   $input;
            $filters    =   isset($filter)?$filter:C('DEFAULT_FILTER');
            if($filters) {
                if(is_string($filters)){
                    $filters    =   explode(',',$filters);
                }
                foreach($filters as $filter){
                    $data   =   array_map_recursive($filter,$data); // 参数过滤
                }
            }
        }elseif(isset($input[$name])) { // 取值操作
            $data       =   $input[$name];
            $filters    =   isset($filter)?$filter:C('DEFAULT_FILTER');
            if($filters) {
                if(is_string($filters)){
                    if(0 === strpos($filters,'/')){
                        if(1 !== preg_match($filters,(string)$data)){
                            // 支持正则验证
                            return   isset($default) ? $default : null;
                        }
                    }else{
                        $filters    =   explode(',',$filters);
                    }
                }elseif(is_int($filters)){
                    $filters    =   array($filters);
                }
        
                if(is_array($filters)){
                    foreach($filters as $filter){
                        if(function_exists($filter)) {
                            $data   =   is_array($data) ? array_map_recursive($filter,$data) : $filter($data); // 参数过滤
                        }else{
                            $data   =   filter_var($data,is_int($filter) ? $filter : filter_id($filter));
                            if(false === $data) {
                                return   isset($default) ? $default : null;
                            }
                        }
                    }
                }
            }
            if(!empty($type)){
                switch(strtolower($type)){
                	case 'a':	// 数组
                	    $data 	=	(array)$data;
                	    break;
                	case 'd':	// 数字
                	    $data 	=	(int)$data;
                	    break;
                	case 'f':	// 浮点
                	    $data 	=	(float)$data;
                	    break;
                	case 'b':	// 布尔
                	    $data 	=	(boolean)$data;
                	    break;
                	case 's':   // 字符串
                	default:
                	    $data   =   (string)$data;
                }
            }
        }else{ // 变量默认值
            $data       =    isset($default)?$default:null;
        }
        is_array($data) && array_walk_recursive($data,'think_filter');
        return $data;
        //return I($name,$default='',$filter=null,$datas=null);
    }
    
    //格式化输出订单
    protected function _formatOrder($orderData)
    {
        $data = [];
        $data['order_no'] = $orderData['order_no'];
        $data['total_amount'] = floatval($orderData['total_amount']);
        $data['third_order_no'] = $orderData['third_order_no'];
        $data['refund_amount'] = floatval($orderData['refund_amount']);
        $data['refund_status'] = intval($orderData['refund_status']);
        $data['pay_type'] = $orderData['pay_type'];
        $data['create_date'] = intval($orderData['create_time']);
        $data['order_status'] = intval($orderData['trade_status']); //订单状态，数字
        $data['pay_time'] = $orderData['pay_time'] ? intval($orderData['pay_time']) : null;
        $data['buyer_name'] = $orderData['buyer_nickname'] ? : $orderData['buyer_name'];
        $data['order_subject'] = $orderData['order_subject'];
        $data = array_filter($data);
        return $data;
    }
    
    //根据用户信息，返回用户查询权限条件，在OrderController.class.php里用到,$strPre为查询条件表前缀
    protected function _getWhereByUserInfo($userInfo, $strPre = '')
    {
        $userRole = $userInfo['user_role'];
        $where = [];        if ($userRole == UserModel::USER_ROLE_OPERATOR)        { // 如果是操作员            $where[$strPre . 'user_id'] = $userInfo['user_id'];        }        elseif ($userRole == UserModel::USER_ROLE_ACCOUNTANT)        { // 如果是财务            $where[$strPre . 'store_id'] = $userInfo['store_id'];        }        elseif ($userRole == UserModel::USER_ROLE_BOSS)        { // 如果是boss            $where[$strPre . 'merchant_id'] = $userInfo['merchant_id'];        }
        else
        { // 没匹配上角色，重新登录
            outputErr(ErrMsg::$err['user.token.invalid']);
        }
        return $where;
    }
    
    
}