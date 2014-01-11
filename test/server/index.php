<?php

	require_once __DIR__."/../../src/BlockchainWalletMock.php";
	require_once __DIR__."/../../src/utils/RewriteUtil.php";
	$settings=parse_ini_file(__DIR__."/../settings.ini");

	/*print_r(RewriteUtil::getPathComponents());*/

	$walletMock=new BlockchainWalletMock();
	$walletMock->setGuid($settings["walletguid"]);
	$walletMock->setPassword($settings["walletpassword"]);
	$walletMock->setDsn("sqlite:".__DIR__."/../data/walletmock.db3");
	$walletMock->setLogFile(__DIR__."/../data/access.log");
	$walletMock->dispatch();
