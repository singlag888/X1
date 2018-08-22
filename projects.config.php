<?php
//开发环境
if (RUN_ENV == 1) {
	$GLOBALS['PROJECTS'] = array(
		'ssc' => array(
			'project_id' => 1,
			'project_description' => 'sss项目',
			'project_path' => '', //默认使用项目名作为项目目录
			'session' => 'session', // 留空表示使用PHP原生SESSION功能，否则为自己实现的session类名，而使用session统一用$GLOBALS['SESSION']['user_id']风格
			'template_engine' => '', //留空表示使用系统提供的模板，或者具体的模板名
			'template_postfix' => '.phtml', // 模板扩展名
			'locale' => 'zh_CN', // 使用的语言 en_US
			'theme_server' => '',
			'local_cache' => 'xcCache://127.0.0.1/ssc',
			'remote_cache' => 'mcCache://192.168.247.74:12000/xy00001',
			 'redis_cache' => 'redisCache://192.168.247.74:6379/xy00001',
			'swoole_server' => '0.0.0.0:9503',
			'swoole_client' => '127.0.0.1:9503',
			'db_host' => array(
	        '*' => 'mysqlpdo://dev:a123456@192.168.247.74:3306?localssc',
	        'share_host' => 'mysqlpdo://dev:a123456@192.168.247.74:3306?localssc',
	        'write_host'=> 'mysqlpdo://dev:a123456@192.168.247.74:3306?localssc'
	      ),
		),
	);

}
//测试环境
elseif (RUN_ENV == 2) {
	$GLOBALS['PROJECTS'] = array(
		'ssc' => array(
			'project_id' => 1,
			'project_description' => 'sss项目',
			'project_path' => '', //默认使用项目名作为项目目录
			'session' => 'session', // 留空表示使用PHP原生SESSION功能，否则为自己实现的session类名，而使用session统一用$GLOBALS['SESSION']['user_id']风格
			'template_engine' => '', //留空表示使用系统提供的模板，或者具体的模板名
			'template_postfix' => '.phtml', // 模板扩展名
			'locale' => 'zh_CN', // 使用的语言 en_US
			'theme_server' => '',
			'local_cache' => 'xcCache://localhost',
			'remote_cache' => 'mcCache://localhost:12000',
			//'db_driver' => 'mysqli', //下面已经指明了协议，不需要特别指定
			'db_host' => array(
				'*' => 'mysql://root:123456@localhost:3306?localssc', //暂只支持一个mysql账号
			),
		),
	);
}
//生产环境
elseif (RUN_ENV == 3) {
	$GLOBALS['PROJECTS'] = array(
		'ssc' => array(
			'project_id' => 1,
			'project_description' => 'sss项目',
			'project_path' => '', //默认使用项目名作为项目目录
			'session' => 'session', // 留空表示使用PHP原生SESSION功能，否则为自己实现的session类名，而使用session统一用$GLOBALS['SESSION']['user_id']风格
			'template_engine' => '', //留空表示使用系统提供的模板，或者具体的模板名
			'template_postfix' => '.phtml', // 模板扩展名
			'locale' => 'zh_CN', // 使用的语言 en_US
			'theme_server' => '',
			'local_cache' => 'xcCache://localhost',
			'remote_cache' => 'mcCache://localhost:11211',
			//'db_driver' => 'mysqli', //下面已经指明了协议，不需要特别指定
			'db_host' => array(
				'*' => 'mysqlpdo://root:root@localhost:3306?localssc',
				'write_host'=> 'mysqlpdo://root:a84drLkig3Qia98+Qg21pd+8wWJPzlZbZPkK4VlrNrJS@localhost:3306?localssc'
			),
		),
	);

}

