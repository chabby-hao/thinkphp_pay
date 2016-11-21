<?php
return array(
	//'配置项'=>'配置值'
	'URL_MODEL'=>2,  //0 (普通模式) 1 (PATHINFO 模式) 2 (REWRITE  模式) 3 (兼容模式) 
    //分布式数据库配置定义
    'DB_DEPLOY_TYPE'=> 1, // 设置分布式数据库支持
    'DB_TYPE'       => 'mysql', //分布式数据库类型必须相同
    'DB_HOST'       => '192.168.1.4,192.168.1.5',//','分隔多项host
    'DB_NAME'       => 'idealpayee', //如果相同可以不用定义多个
    'DB_USER'       => 'D_ym_idealpayee',
    'DB_PWD'        => 'Iasnw23@512093$^92[Asjda%912Oas',
    'DB_PORT'       => '3306',
    'DB_PREFIX'     => '',
    'DB_RW_SEPARATE'=> true, //是否读写分离，默认不分离
    //'DB_MASTER_NUM' => 1, //多少台主服务器写入
    
    'DATA_CACHE_TIME'       =>  0,      // 数据缓存有效期 0表示永久缓存
    'DATA_CACHE_COMPRESS'   =>  false,   // 数据缓存是否压缩缓存
    'DATA_CACHE_CHECK'      =>  false,   // 数据缓存是否校验缓存
    'DATA_CACHE_PREFIX'     =>  '',     // 缓存前缀
    'DATA_CACHE_TYPE'       =>  'Redis',  // 数据缓存类型,支持:File|Db|Apc|Memcache|Shmop|Sqlite|Xcache|Apachenote|Eaccelerator
    'DATA_CACHE_PATH'       =>  TEMP_PATH,// 缓存路径设置 (仅对File方式缓存有效)
    'DATA_CACHE_SUBDIR'     =>  false,    // 使用子目录缓存 (自动根据缓存标识的哈希创建子目录)
    'DATA_PATH_LEVEL'       =>  1,        // 子目录缓存级别
    'REDIS_RW_SEPARATE' => false, //Redis读写分离 true 开启
    'REDIS_HOST'=>'192.168.1.2', //redis服务器ip，多台用逗号隔开；读写分离开启时，第一台负责写，其它[随机]负责读；
    'REDIS_PORT'=>'6380',//端口号
    'REDIS_TIMEOUT'=>'5',//超时时间
    'REDIS_PERSISTENT'=>false,//是否长连接 false=短连接
    'REDIS_AUTH'=>'as51%9aw*)0as_sas1jslnBias24H)820&^#%a_qwxlzpqwjd',//AUTH认证密码
    
    //Redis Session配置
    'SESSION_AUTO_START'	=>  true,	// 是否自动开启Session
    'SESSION_TYPE'			=>  'Redis',	//session类型
    'SESSION_PERSISTENT'    =>  1,		//是否长连接(对于php来说0和1都一样)
    'SESSION_CACHE_TIME'	=>  5,		//连接超时时间(秒)
    'SESSION_EXPIRE'		=>  1800,		//session有效期(单位:秒) 0表示永久缓存
    'SESSION_PREFIX'		=>  'common:',		//session前缀
    'SESSION_REDIS_HOST'	=>  '192.168.1.2', //分布式Redis,默认第一个为主服务器
    'SESSION_REDIS_PORT'	=>  '6380',	       //端口,如果相同只填一个,用英文逗号分隔
    'SESSION_REDIS_AUTH'    =>  'as51%9aw*)0as_sas1jslnBias24H)820&^#%a_qwxlzpqwjd',    //Redis auth认证(密钥中不能有逗号),如果相同只填一个,用英文逗号分隔
    
);