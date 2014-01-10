<?php

	require_once __DIR__."/../src/BlockchainWalletMock.php";

	$walletMock=new BlockchainWalletMock();
	$walletMock->setGuid("testwallet");
	$walletMock->setPassword("testpassword");
	$walletMock->setDsn("sqlite:".__DIR__."/../data/walletmock.db3");
	$walletMock->setLogFile(__DIR__."/../data/access.log");
	$walletMock->dispatch();
