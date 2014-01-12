#!/usr/bin/env php
<?php

	$settings=parse_ini_file(__DIR__."/settings.ini");

	$descs=array(
		0=>array("file","php://stdin","r"),
		1=>array("file","php://stdout","w")
	);

	$cmd="php -S $settings[wallethost]:$settings[walletport] ".
		"-t ".__DIR__."/server/ ".
		__DIR__."/server/index.php";

	$serverProc=proc_open($cmd,$descs,$pipes);

	$cmd="php -S $settings[callbackhost]:$settings[callbackport] ".
		"-t ".__DIR__."/callback/ ".
		__DIR__."/callback/index.php";

	$callbackProc=proc_open($cmd,$descs,$pipes);

	echo "**** Waiting for server to come up...\n";
	sleep(1);

	system(__DIR__."/../vendor/bin/phpunit ".__DIR__."/cases");

	proc_terminate($serverProc);
	proc_terminate($callbackProc);