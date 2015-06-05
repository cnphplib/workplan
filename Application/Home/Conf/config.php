<?php
return array(
	// '配置项'=>'配置值'
		// 数据库配置信息
		'DB_TYPE' => 'mysql', // 数据库类型
		
		//服务器数据库本地开发配置
		'DB_HOST' => '192.168.16.253', // 服务器地址
		'DB_NAME' => 'workplan', // 数据库名
		'DB_USER' => 'admin', // 用户名
		'DB_PWD' => '', // 密码
		'DB_PORT' => 3306, // 端口
        'DB_PREFIX' => 'tb_',  // 数据库表前缀
		'DB_FIELDS_CACHE'=>true,
		'DB_SQL_BUILD_CACHE' => true,
		'DB_SQL_BUILD_CACHE_TIME' => 30,//前端接口缓存时间
		'DB_SQL_BUILD_QUEUE' => 30,
		'DB_SQL_BUILD_LENGTH' => 20,
		'DATA_CACHE_TYPE' => 'file',
		'DATA_CACHE_TIME' => 6000,
//         'SHOW_PAGE_TRACE' =>true,
        'LOG_RECORD' => true, // 开启日志记录
        'LOG_LEVEL'  =>'SQL,EMERG,ALERT,CRIT,ERR',
        'LOG_EXCEPTION_RECORD' => true,
        'URL_CASE_INSENSITIVE' =>true,
        'SESSION_TYPE'		=> 'DB',
        'SESSION_OPTIONS' => array(
            'expire'	=>	2592000,
        ),
);
