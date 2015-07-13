<?php

	require_once __DIR__."/../vendor/autoload.php";
	require_once __DIR__."/../src/utils/CurlRequest.php";

	use blockchainwalletmock\BlockchainWalletMock;
	use blockchainwalletmock\CurlRequest;

	/**
	 * Base class for test cases.
	 */
	class TestBase extends PHPUnit_Framework_TestCase {

		protected $blockchainWalletMock;
		protected $callbackServerPid;

		protected function setUp() {
			$this->port=8910;

			$this->blockchainWalletMock=new BlockchainWalletMock();
			$this->blockchainWalletMock->setCallbackUrl("http://localhost:8911/");
			$this->blockchainWalletMock->setPort(8910);
			$this->blockchainWalletMock->setShowLog(FALSE);
			$this->blockchainWalletMock->runInBackground();

			$this->clearCallbackLog();
		}

		protected function startCallbackServer() {
			$this->clearCallbackLog();
			$pid=shell_exec("php -S localhost:8911 ".__DIR__."/callback/index.php > /dev/null & echo $!");

			usleep(100000);
			//sleep(1);

			$this->callbackServerPid=$pid;
		}

		protected function tearDown() {
			$this->clearCallbackLog();

			$this->blockchainWalletMock->stop();

			if ($this->callbackServerPid) {
				posix_kill($this->callbackServerPid,SIGKILL);
				pcntl_waitpid($this->callbackServerPid,$status);
			}
		}

		protected function createRequest($method) {
			$req=new CurlRequest("http://localhost:".$this->port."/".$method);
			$req->setResultProcessing(CurlRequest::JSON);
			return $req;
		}

		protected function getCallbackLog() {
			return json_decode(file_get_contents(__DIR__."/callback/callback.log"),TRUE);
		}

		protected function clearCallbackLog() {
			@unlink(__DIR__."/callback/callback.log");
		}
	}