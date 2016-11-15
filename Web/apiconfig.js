var config = [
	{
		"api_name":"barPay",//接口名
		"api_controller":"Pay",//接口控制器
		"api_explain":"支付宝微信条码支付",
		"data":{
			"auth_code":{"value":"289908501363494444", "require":true, "placeholder":"支付宝付款码"},//value:默认值，require为true表示必填，false为不必填，placeholder为输入提示
			//"subject":{"require":true,"value":"支付标题", "placeholder":"支付的标题"},
			//"goods_detail":{"value":"[{\"goods_id\":11,\"goods_name\":\"ipad\",\"quantity\":1,\"price\":0.01}]","placeholder":"Json数组，字段有goods_id-商品id,goods_name-商品名称,quantity-商品数量,price-商品单价（元）"},
			"total_amount":{"value":"0.01","placeholder":"订单价格,单位为元","require":true},
			"token":{"value":"3d30bd3ad14ff8e83777323cbaf343da","placeholder":"用户登录token","require":true},
			"pay_type":{"value":"alipay","placeholder":"支付方式,alipay-支付宝，wxpay-微信","require":true}
		},//接口需要的数据，值为默认值,
		"desc":["pay_type-支付方式，alipay-支付宝，wxpay-微信","create_time-订单创建时间戳","receipt_amount-商户收到的金额（订单金额－支付宝给的优惠）","total_amount－订单总额","trade_no－支付宝单号","out_trade_no－商户单号","gmt_payment－支付时间","buyer_logon_id-买家账号","------错误码————————-----------------------------------------------------------------------------------","40002-需要用户输入密码完成支付，请查询订单确认订单状态","40004-支付失败，获取顾客账户信息失败，请顾客刷新付款码后重新收款","40000-支付系统出现异常,请立即查询订单，确认订单交易状态","70005-用户token已经失效，请重新登录","70010-输入金额有误，请确认是否金额超限"]
	},
	{
		"api_name":"qrPay",//接口名
		"api_controller":"Pay",//接口控制器
		"api_explain":"支付宝微信扫码支付",
		"data":{
			//"subject":{"require":true,"value":"支付标题", "placeholder":"支付的标题"},
			//"goods_detail":{"value":"[{\"goods_id\":11,\"goods_name\":\"ipad\",\"quantity\":1,\"price\":0.01}]","placeholder":"Json数组，字段有goods_id-商品id,goods_name-商品名称,quantity-商品数量,price-商品单价（元）"},
			"total_amount":{"value":"0.01","placeholder":"订单价格,单位为元","require":true},
			"token":{"value":"caf1a3dfb505ffed0d024130f58c5cfa","placeholder":"用户登录token","require":true},
			"pay_type":{"value":"alipay","placeholder":"支付方式,alipay-支付宝，wxpay-微信","require":true}
		},//接口需要的数据，值为默认值
		"desc":["pay_type-支付方式，alipay-支付宝，wxpay-微信","create_time-订单创建时间戳","qr_img-支付二维码图片地址（考虑网络原因，最好客户端直接生成二维码）","qr_code-支付码，可以将此值生成二维码展示给顾客","out_trade_no－商户单号","------错误码————————-----------------------------------------------------------------------------------","40000-支付系统出现异常,请立即查询订单，确认订单交易状态","70010-输入金额有误，请确认是否金额超限"]
	},
	{
		"api_name":"orderQuery",//接口名
		"api_controller":"Pay",//接口控制器
		"api_explain":"支付宝微信订单查询",
		"data":{
			"token":{"value":"810a94c3bfaff435eac2966899ef3859","require":true,"placeholder":"用户登录token"},
			"trade_no":{"value":"201395434732424", "placeholder":"支付宝单号,和商户单号2选1"},
			"out_trade_no":{"value":"2014231423423", "placeholder":"商户单号，和支付宝单号2选1"},
			"pay_type":{"value":"alipay","placeholder":"支付方式,alipay-支付宝，wxpay-微信","require":true}
			
		},//接口需要的数据，值为默认值
		"desc":["pay_type-支付方式，alipay-支付宝，wxpay-微信","order_no-商户订单号","total_amount－订单总额","third_order_no-支付宝或微信单号","refund_amount-退款金额","refund_status-退款10：无退款，20：部分退款，30：全额退款","pay_type－支付类型，alipay-支付宝，wxpay-微信支付","create_date-创建日期","order_status-订单状态,10-待支付，20-支付成功","pay_time-支付时间，只有支付成功才有值","buyer_name-买家账号","order_subject-订单主题","------错误码————————-----------------------------------------------------------------------------------","40006-交易不存在","40000-支付系统出现异常,请立即查询订单，确认订单交易状态","70005-用户token已经失效，请重新登录","70008-支付成功，但是订单信息修改失败"]
	},
	{
		"api_name":"refund",//接口名
		"api_controller":"Pay",//接口控制器
		"api_explain":"支付宝微信退款",
		"data":{
			"token":{"value":"3d30bd3ad14ff8e83777323cbaf343da","require":true,"placeholder":"用户登录token"},
			"trade_no":{"value":"201395434732424", "placeholder":"支付宝单号,和商户单号2选1"},
			"out_trade_no":{"value":"2014231423423", "placeholder":"商户单号，和支付宝单号2选1"},
			"refund_reason":{"value":"正常退款","placeholder":"退款原因"},
			"pay_type":{"value":"alipay","placeholder":"支付方式,alipay-支付宝，wxpay-微信","require":true},
			
			
		},//接口需要的数据，值为默认值
		"desc":["pay_type-支付方式，alipay-支付宝，wxpay-微信","refund_fee-退款金额","total_amount－订单总额","trade_no－支付宝单号","out_trade_no－商户单号","gmt_refund_pay－支付时间","buyer_logon_id-买家账号","------错误码————————-----------------------------------------------------------------------------------","40007-退款失败","70005-用户token已经失效，请重新登录","40008-卖家余额不足","40009-交易已完结","40010-退款提交成功，正在处理中","40011-微信退款需要授权","40012-订单状态错误或已提交退款申请"]
	},
	{
		"api_name":"orderList",//接口名
		"api_controller":"Order",//接口控制器
		"api_explain":"订单列表(暂时先不用)",
		"data":{
			"token":{"value":"3d30bd3ad14ff8e83777323cbaf343da","require":true,"placeholder":"用户登录token"},
			"page":{"value":"1","placeholder":"当前页"},
			"trade_status":{"value":"10","placeholder":"10-待支付，20-支付成功"},
			"pay_type":{"value":"alipay","placeholder":"支付渠道:alipay-支付宝，wxpay-微信"},
			"refund_status":{"value":"10","placeholder":"退款状态,10-无退款，30-全额退"}
		},//接口需要的数据，值为默认值
		"desc":["order_no-商户订单号","total_amount－订单总额","third_order_no-支付宝或微信单号","refund_amount-退款金额","refund_status-退款10：无退款，20：部分退款，30：全额退款","pay_type－支付类型，alipay-支付宝，wxpay-微信支付","create_date-创建日期","order_status-订单状态,10-待支付，20-支付成功","pay_time-支付时间，只有支付成功才有值","buyer_name-买家账号","order_subject-订单主题","------错误码————————-----------------------------------------------------------------------------------","70005-用户token已经失效，请重新登录"]
	},
	{
		"api_name":"orderFlowList",//接口名
		"api_controller":"Order",//接口控制器
		"api_explain":"资金流水列表",
		"data":{
			"token":{"value":"d57c49780effb90a644b106894b23930","require":true,"placeholder":"用户登录token"},
			"page":{"value":"1","placeholder":"当前页"},
			"flow_status":{"value":"10","placeholder":"10-收入，20-支出"},
			"pay_type":{"value":"alipay","placeholder":"支付渠道:alipay-支付宝，wxpay-微信"},
			"user_id":{"value":"1","placeholder":"子账户id"},
		},//接口需要的数据，值为默认值
		"desc":["buyer_name-买家账号","order_no-商户订单号","total_amount－流水金额","pay_type－支付类型，alipay-支付宝，wxpay-微信支付","create_date-创建时间戳","flow_status-流水状态,10-收入，20-支出","------错误码————————-----------------------------------------------------------------------------------","70005-用户token已经失效，请重新登录"]
	},
	{
		"api_name":"orderDetail",//接口名
		"api_controller":"Order",//接口控制器
		"api_explain":"订单详情（列表页进入）",
		"data":{
			"token":{"value":"d57c49780effb90a644b106894b23930","require":true,"placeholder":"用户登录token"},
			"out_trade_no":{"require":true, "value":"201609281318211394", "placeholder":"商户单号，和第三方单号2选1"},
		},//接口需要的数据，值为默认值
		"desc":["order_no-商户订单号","total_amount－订单总额","third_order_no-支付宝或微信单号","refund_amount-退款金额","refund_status-退款状态，10：无退款，30：全额退款,40:退款中","pay_type－支付类型，alipay-支付宝，wxpay-微信支付","create_date-创建日期","order_status-订单状态,10-待支付，20-支付成功","pay_time-支付时间，只有支付成功才有值","buyer_name-买家账号","order_subject-订单主题","------错误码————————-----------------------------------------------------------------------------------","70005-用户token已经失效，请重新登录","70004-订单不存在"]
	},
	{

		"api_name":"login",//接口名
		"api_controller":"User",//接口控制器
		"api_explain":"用户登录",
		"data":{
			"user_name":{"value":"test","require":true,"placeholder":"用户名"},
			"user_pwd":{"value":"1","require":true,"placeholder":"用户密码"}
		},//接口需要的数据，值为默认值
		"desc":["token-用户登录token","------错误码————————-----------------------------------------------------------------------------------","70000-用户名密码错误"]
	},
	{

		"api_name":"goodsList",//接口名
		"api_controller":"Goods",//接口控制器
		"api_explain":"商品列表",
		"data":{
			"token":{"value":"123123125123","require":true,"placeholder":"用户token"},
		},//接口需要的数据，值为默认值
		"desc":["goods_name-商品名称","goods_price-商品价格","title-分类标题"]
	},
	// {

	// 	"api_name":"barPay",//接口名
	// 	"api_controller":"Wxpay",//接口控制器
	// 	"api_explain":"微信条码付",
	// 	"data":{
	// 		"auth_code":{"value":"289908501363494444", "require":true, "placeholder":"支付宝付款码"},//value:默认值，require为true表示必填，false为不必填，placeholder为输入提示
	// 		//"subject":{"require":true,"value":"支付标题", "placeholder":"支付的标题"},
	// 		//"goods_detail":{"value":"[{\"goods_id\":11,\"goods_name\":\"ipad\",\"quantity\":1,\"price\":0.01}]","placeholder":"Json数组，字段有goods_id-商品id,goods_name-商品名称,quantity-商品数量,price-商品单价（元）"},
	// 		"total_amount":{"value":"0.01","placeholder":"订单价格,单位为元","require":true},
	// 		"token":{"value":"3d30bd3ad14ff8e83777323cbaf343da","placeholder":"用户登录token","require":true}
	// 	},//接口需要的数据，值为默认值,
	// 	"desc":["receipt_amount-商户收到的金额（订单金额－支付宝给的优惠）","total_amount－订单总额","trade_no－支付宝单号","out_trade_no－商户单号","gmt_payment－支付时间","buyer_logon_id-买家账号","------错误码————————-----------------------------------------------------------------------------------","40002-需要用户输入密码完成支付，请查询订单确认订单状态","40004-支付失败，获取顾客账户信息失败，请顾客刷新付款码后重新收款","40000-支付系统出现异常,请立即查询订单，确认订单交易状态","70005-用户token已经失效，请重新登录"]
	// },
	{

		"api_name":"userInfo",//接口名
		"api_controller":"User",//接口控制器
		"api_explain":"用户信息",
		"data":{
			"token":{"value":"3d30bd3ad14ff8e83777323cbaf343da","placeholder":"用户登录token","require":true}
		},//接口需要的数据，值为默认值,
		"desc":["user_name-用户名昵称，如果没有设置昵称则为用户登录名","user_rolename－用户角色称谓","user_permis－用户拥有权限","store_name－店铺名","merchant_name－商户名","------错误码————————-----------------------------------------------------------------------------------","70007-获取用户信息失败，请尝试重新登录","70005-用户token已经失效，请重新登录"]
	},
	{

		"api_name":"getSubUser",//接口名
		"api_controller":"User",//接口控制器
		"api_explain":"获取用户子账号列表",
		"data":{
			"token":{"value":"3d30bd3ad14ff8e83777323cbaf343da","placeholder":"用户登录token","require":true}
		},//接口需要的数据，值为默认值,
		"desc":["user_name-用户名昵称，如果没有设置昵称则为用户登录名","user_id－用户id","------错误码————————-----------------------------------------------------------------------------------","70007-获取用户信息失败，请尝试重新登录","70005-用户token已经失效，请重新登录"]
	},
	{
		"api_name":"checkUpdate",//接口名
		"api_controller":"SelfUpdate",//接口控制器
		"api_explain":"自更新检测",
		"data":{
			"vercode":{"value":"1","placeholder":"当前版本号","require":true},
		},//接口需要的数据，值为默认值,
		"desc":["update_type-更新类型，1-普通更新，2-强制更新","apk_url-下载地址","new_vercode-新内部版本号，为数字","new_version-新外部版本号，为字符串","update_desc-更新说明","------错误码————————-----------------------------------------------------------------------------------","70009-不需要更新"]
	},
	{
		"api_name":"decode3",//接口名
		"api_controller":"Index",//接口控制器
		"api_explain":"解密",
		"data":{
			"data":{"value":"","placeholder":"解密原始数据","require":true},
		},//接口需要的数据，值为默认值,
		//"desc":["update_type-更新类型，1-普通更新，2-强制更新","apk_url-下载地址","new_vercode-新内部版本号，为数字","new_version-新外部版本号，为字符串","update_desc-更新说明","------错误码————————-----------------------------------------------------------------------------------","70009-不需要更新"]
	},



];
