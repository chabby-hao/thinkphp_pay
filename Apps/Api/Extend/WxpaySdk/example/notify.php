<?php

class PayNotifyCallBack extends WxPayNotify
{
	//查询订单
	public function Queryorder($transaction_id, $subMchId = null, $subAppid = null)
	{
		$input = new WxPayOrderQuery();
		$input->SetTransaction_id($transaction_id);
		if($subMchId)
		{
		    $input->setSubMchId($subMchId);
		}
		if($subAppid)
		{
		    $input->setSubAppid($subAppid);
		}
		$result = WxPayApi::orderQuery($input);
		if(array_key_exists("return_code", $result)
			&& array_key_exists("result_code", $result)
			&& $result["return_code"] == "SUCCESS"
			&& $result["result_code"] == "SUCCESS")
		{
			return true;
		}
		return false;
	}
	
	//重写回调处理函数
	public function NotifyProcess($data, &$msg)
	{
		$notfiyOutput = array();
		//file_put_contents('ttt2.log', json_encode($data) ."\r\n", FILE_APPEND);
		if(!array_key_exists("transaction_id", $data)){
			$msg = "输入参数不正确";
			return false;
		}
		//查询订单，判断订单真实性
		if(!$this->Queryorder($data["transaction_id"], $data['sub_mch_id'], $data['sub_appid'])){
			$msg = "订单查询失败";
			return false;
		}
		$this->SetData('data', $data);
		return true;
	}
}

