<?php

	require_once __DIR__."/../src/BlockchainWalletMock.php";

	$walletMock=new BlockchainWalletMock();

	/**** Set the guid we expect client to use. ****/
	$walletMock->setGuid("testwallet");

	/**** Set password we expect client to use. ****/
	$walletMock->setPassword("testpassword");

	/**** Set database DSN to use. ****/
	$walletMock->setDsn("sqlite:".__DIR__."/mockwallet.db3");

	/**** Set file where to log calls. ****/
	//$walletMock->setLogFile("some_log_file.log");

	/**** Set url for callbacks, will be called in the same way as from blockchain.info. ****/
	//$walletMock->setCallback("http://example.com/transaction_notify");

	$walletMock->dispatch();