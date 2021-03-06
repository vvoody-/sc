<?php

return array(
	'my site' => array(
		'remote' => 'ftp://' . $username . ':' . $password . '@' . $server,
		'passivemode' => TRUE,
		'local' => '..',
		'test' => FALSE,
		'ignore' => '
			.git*
			.composer*
			project.pp[jx]
			/nbproject
			/deployment
			log/*
			!log/.htaccess
			temp/*
			!temp/.htaccess
			tests/
			bin/
			www/webtemp/*
			!www/webtemp/.htaccess
			www/foto/*
			!www/foto/
			*.local.neon
			*.server.neon
			*.server_dev.neon
			*.server_test.neon
			*.server_ver21.neon
			*.server_ver22.neon
			*.local.example.neon
			composer.lock
			composer.json
			*.md
			.bowerrc
			/app/config/deployment.*
			/vendor/dg/ftp-deployment
			*.rst
		',
		'allowdelete' => TRUE,
		'after' => array(
			$domain . '/install?printHtml=0'
		),
		'purge' => array(
			'temp/cache',
			'temp/install',
			'temp/deployment',
			'tmp/'
		),
		'preprocess' => FALSE,
	),
	
	'tempdir' => __DIR__ . '/temp',
	'colors' => TRUE,
);
