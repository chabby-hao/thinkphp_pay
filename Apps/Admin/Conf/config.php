<?php
return array(
	//'配置项'=>'配置值'
    'LAYOUT_ON'=>true,// 开启布局模板功能，默认不开启
    'LAYOUT_NAME'=>'layout',// 可以不配置，默认为layout
    
    'SESSION_PREFIX'=>'admin:',//本地化session支持开启后，生成的session数据格式由原来的 $_SESSION['name'] 变成 $_SESSION['前缀']['name']。
    
    'ALIPAY_REDIRECT_URI'=>'http%3A%2F%2Fwww.idealpayee.com%2FApi%2FAlipay%2FgetAppAuthToken',//支付宝授权回调地址
);