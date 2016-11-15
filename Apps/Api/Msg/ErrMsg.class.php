<?phpnamespace Api\Msg;class ErrMsg{        // 本系统自定义错误    public static $err = [        '404' => [            'code' => 70002,            'msg' => '缺少必要参数'        ],        'user.invalid' => [            'code' => 70000,            'msg' => '用户名或者密码错误'        ],        'order.create.error' => [            'code' => 70003,            'msg' => '订单生成失败，请重试'        ],        'order.non.existend' => [            'code' => 70004,            'msg' => '订单不存在'        ],        'user.token.invalid' => [            'code' => 70005,            'msg' => '验证信息过期，请重新登录'        ],        'pay.type.invalid' => [            'code' => 70006,            'msg' => '无效的支付方式'        ],        'user.info.get.error' => [            'code' => 70007,            'msg' => '获取用户信息失败，请尝试重新登录'        ],        'order.modify.error' => [            'code' => 70008,            'msg' => '订单信息修改失败'        ],
        'self.update.error' => [
            'code' => 70009,
            'msg' => '不需要更新'
        ],
        'amount.input.invalid' => [
            'code' => 70010,
            'msg' => '输入金额有误，请确认是否金额超限'
        ],        'self.update.force' =>[	       'code'=> 70011,	       'msg'=>'有新的强更版本，请先更新到最新版本',        ]
    ];        // api接口返回的错误定义    public static $api = [        'trade.not.exists' => [            'code' => 40006,            'msg' => '交易不存在'        ],        'pay.token.invalid' => [            'code' => 40004,            'msg' => '支付失败，请重新扫描'        ],        'pay.password.wait' => [            'code' => 40002,            'msg' => '需要用户输入密码完成支付，请查询订单确认订单状态'        ],        'pay.exception' => [            'code' => '40000',            'msg' => '支付系统出现异常'        ],        'refund.api.fail' => [            'code' => '40007',            'msg' => '退款失败'
        ],
        'refund.not.enough' => [
            'code' => '40008',
            'msg' => '卖家余额不足'
        ],
        'refund.has.finished' => [
            'code' => '40009',
            'msg' => '交易已完结'
        ],
        'refund.api.processing' => [
            'code' => 40010,
            'msg' => '退款提交成功，正在处理中'
        ],
        'refund.api.nopermis' => [
            'code' => 40011,
            'msg' => '微信退款需要授权'
        ],
        'refund.trade.status.error' => [
            'code' => 40012,
            'msg' => '订单状态错误或已提交退款申请'
        ]
    ];}