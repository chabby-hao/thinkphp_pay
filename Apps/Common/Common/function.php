<?php 

use Common\Extend\TripleDES;
use Api\Msg\ErrMsg;
/**
 * 检查数组中是否存在不为空的字段
 * 参 数：$mixFields string|array 字段可以是数组或者是字符串，如果是字符串用','分开，$arr array 要检查的数组, $arrFilterField   array   过滤掉的，只检查是否存在不管值是否为空
 * 返 回： false 或者 以$mixFields为键值构造的新数组
 * 作 者： 王志浩
 * 修改日期： 2016-08-15
 */
function checkFieldInArray($mixFields, $arr, $arrFilterField = array())
{
    $arrFlip = array_flip($arrFilterField);
    if (! is_array($arr))
    {
        outputErr(ErrMsg::$err['404']);
        //return false;
    }
    if (is_string($mixFields))
    {
        $mixFields = explode(',', $mixFields);
    }
    $arrRtn = array();
    foreach ($mixFields as $field)
    {
        if (empty($arr[$field]))
        {
            if (array_key_exists($field, $arrFlip))
            {
                // 如果这个参数设置了过滤，就多判断一次
                if (isset($arr[$field]))
                {
                    $arrRtn[$field] = $arr[$field];
                }
                else
                {
                    $arrRtn[$field] = '';
                }
            }
            else
            {
                //缺少参数，直接输出错误码
                outputErr(ErrMsg::$err['404'], ['msg'=>'缺少参数为:' . $field]);
                //return false;
            }
        }
        else
        {
            $arrRtn[$field] = $arr[$field];
        }
    }
    return $arrRtn;
}

/**
 *
 * 将数组中NULL字符转为''字符串
 * @param unknown $arr
 */
function nullFilter( &$arr)
{
    if(is_array($arr))
    {
        foreach ($arr as $k => $row){
            if(is_array($row))
            {
                nullFilter($arr[$k]);
            }else
            {
                if( $row === NULL)
                {
                    unset($arr[$k]);
                    //$arr[$k] = '';
                }
            }
        }
    }
}

/**
 * 直接输出接口数据
 * 参    数：$data array 输出内容
 * 返    回： json编码输出信息
 * 作    者： 王志浩
 * 修改日期： 2016-08-08
 */
function outputData($data, $extendData = [])
{
    $datas = array();
    $datas['code'] = 10000;
    $datas['msg'] = 'success';
    if(!empty($data) || is_numeric($data))
    {
        if(defined('API_EXTEND_DATA') && is_array($data))
        {
            $apiExtendData = json_decode(API_EXTEND_DATA, true);
            $data = array_merge($data, $apiExtendData);
        }
        $datas['data'] = $data;
    }
    $datas = array_merge($datas, $extendData);

    $datas['code'] = intval($datas['code']);
    nullFilter($datas);//过滤掉null的字段
    //echo json_encode($datas);exit;
    echo strCode(json_encode($datas), 'ENCODE', C('apihash'));
    exit();
}

/**
 * 直接输出接口错误信息
 * 参 数：$arrErrMsg       array       错误信息配置数组
 *       $data            array       额外的数组输出
 * 返 回： json编码输出信息
 * 作 者： 王志浩
 * 修改日期： 2016-08-08
 */
function outputErr($arrErrMsg, $extendData=[], $data = [])
{
    $arrErrMsg = array_merge($arrErrMsg, $extendData);
    outputData($data, $arrErrMsg);
}


//分页函数，$pageCount是总页数，$curpage是当前页
function mobilePage($pageCount, $curpage = 1)
{
    $data = [];
    if($curpage <= 0)
    {
        $curpage = 1;
    }
    if($curpage >= $pageCount)
    {
        $data['hasmore'] = false;
    }
    else
    {
        $data['hasmore'] = true;
    }
    $data['page_total'] = $pageCount;
    return $data;
}



//把$arr数组根据map映射转为新键值
function mapToArray($arrMap, $arr)
{
    $arrRtn = [];
    foreach ($arr as $k => $v)
    {
        if(isset($arrMap[$k]))
        {
            $arrRtn[$arrMap[$k]] = $v;
        }
        else
        {
            $arrRtn[$k] = $v;
        }
    }
    return $arrRtn;
}

/**
 * strcode 加解密函数
 * 参    数：$strInput      string          需要处理的字符串
 *          $strAction      string          加密 - ENCODE/ 解密 - DECODE
 *          $hash        array          加解密的key
 * 返    回：无
 * 作    者：王雕
 * 功    能：strcode 加解密函数
 * 修改日期：2015-08-19
 */
function strCode($strInput, $strAction, $hash = [])
{
    $key = $hash['key'];
    $iv = $hash['iv'];
    switch ($strAction)
    {
    	case 'DECODE':
    	    //安卓64编码的特殊处理
    	    $strInput = strtr($strInput, ['@'=>'+','*'=>'/','-'=>'=']);
    	    return TripleDES::decryptText($strInput, $key, $iv);
    	case 'ENCODE':
    	    return TripleDES::encryptText($strInput, $key, $iv);
    	default:
    	    return $strInput;
    }
}

/**
 * 数组映射，把二维数组中，内部数组的键当做二维数组的键重新构造数组
 * 参 数： $arr array 二维数组或以上, $key string 新的键值
 * 返 回： $arr 新构造的数组
 * 作 者： 王志浩
 * 修改日期： 2016-08-08
 */
function arrayMap($arr, $key)
{
	if (empty($arr) || !is_array($arr))
	{
		return array();
	}
	$arrBtn = array();
	// 遍历数组，查找$key
	foreach ($arr as $k => $val)
	{
		if (!is_array($val)) // 容错处理，只能是数组才可以
		{
			continue;
		}
		if (array_key_exists($key, $val))
		{
			$arrBtn[$val[$key]] = $val;
			unset($arr[$k]);
		}
	}
	return $arrBtn;
}

function adminCheckPost($mixFields, $arrFilterField = array())
{
    $arr = I('post.');
	$arrFlip = array_flip($arrFilterField);
	if (! is_array($arr))
	{
	    adminOutputErr('请确认信息是否完整');
		//return false;
	}
	if (is_string($mixFields))
	{
		$mixFields = explode(',', $mixFields);
	}
	$arrRtn = array();
	foreach ($mixFields as $field)
	{
		if (empty($arr[$field]))
		{
			if (array_key_exists($field, $arrFlip))
			{
				// 如果这个参数设置了过滤，就多判断一次
				if (isset($arr[$field]))
				{
					$arrRtn[$field] = $arr[$field];
				}
				else
				{
					$arrRtn[$field] = '';
				}
			}
			else
			{
				//缺少参数，直接输出错误码
				//outputErr(ErrMsg::$err['404'], ['msg'=>'缺少参数为:' . $field]);
				adminOutputErr('请确认信息是否完整,缺少字段:'.$field);
				//return false;
			}
		}
		else
		{
			$arrRtn[$field] = $arr[$field];
		}
	}
	$arrRtn = array_merge($arrRtn, $arr);
	return $arrRtn;
}


/**
 * URL组装 支持不同URL模式
 * @param string $url URL表达式，格式：'[模块/控制器/操作#锚点@域名]?参数1=值1&参数2=值2...'
 * @param string|array $vars 传入的参数，支持数组和字符串
 * @param string|boolean $suffix 伪静态后缀，默认为true表示获取配置值
 * @param boolean $domain 是否显示域名
 * @return string
 */
function ajaxRedirect($url='',$vars='',$suffix=true,$domain=false)
{
    $url = U($url, $vars, $suffix, $domain);
    adminOutputData('302', ['url'=>$url]);
}

/**
 * 后台输出ajax信息
 * 参    数：$data array|string 输出内容， $extendData array 附加信息
 * 返    回： json编码输出信息
 * 作    者： 王志浩
 * 修改日期： 2016-08-08
 */
function adminOutputData($data, $extendData = array())
{
	if (empty($data) && !is_numeric($data))
	{
		return adminOutputErr('信息为空', 404);
	}
	$datas = array();
	$datas['code'] = 200;
	$datas['status'] = 200;
	$datas['data'] = $data;
	$datas = array_merge($datas, $extendData);
	echo json_encode($datas);
	exit();
}

/**
 * 后台输出ajax错误信息
 * 参 数：$errMessage string 错误信息描述，$errCode int 错误信息编码
 * 返 回： json编码输出信息
 * 作 者： 王志浩
 * 修改日期： 2016-08-08
 */
function adminOutputErr($errMessage, $errCode = 0)
{
	$extendData = array();
	$extendData['status'] = 500;
	$data = array();
	$data['errCode'] = $errCode;
	$data['errMessage'] = $errMessage;
	echo adminOutputData($data, $extendData);
	exit();
}