<?php

	require_once __DIR__."/utils/RewriteUtil.php";
	require_once __DIR__."/utils/ArrayUtil.php";
	require_once __DIR__."/handler/BlockchainWalletMockHandler.php";

	/**
	 * Mocked version of the blockchain.info wallet API.
	 */
	class BlockchainWalletMock {

		private $guid;
		private $password;
		private $dsn;
		private $logFile;
		private $db;
		private $handler;
		private $defaultFee;
		private $callbackUrl;

		/**
		 * Construct.
		 */
		public function BlockchainWalletMock() {
			date_default_timezone_set("UTC");

			$this->defaultFee=10000;
			$this->callbackUrl=NULL;
		}

		/**
		 * Set callback url.
		 */
		public function setCallbackUrl($value) {
			$this->callbackUrl=$value;
		}

		/**
		 * Get callback url.
		 */
		public function getCallbackUrl() {
			return $this->callbackUrl;
		}

		/**
		 * Set guid to expect.
		 */
		public function setGuid($value) {
			$this->guid=$value;
		}

		/**
		 * Set password to expect.
		 */
		public function setPassword($value) {
			$this->password=$value;
		}

		/**
		 * Set data service name.
		 */
		public function setDsn($value) {
			$this->dsn=$value;
		}

		/**
		 * Set log file.
		 */
		public function setLogFile($value) {
			$this->logFile=$value;
		}

		/**
		 * Authenticate.
		 */
		private function authenticate() {
			$components=RewriteUtil::getPathComponents();

			if ($this->password && 
					$this->password!=ArrayUtil::getIfExists($_REQUEST,"password"))
				$this->response(array("error"=>"Wrong password."));

			if ($this->guid &&
					$this->guid!=ArrayUtil::getIfExists($components,0)) {
				//$this->log("expected: ".$this->guid);
				$this->response(array("error"=>"Wrong guid."));
			}
		}

		/**
		 * Get database.
		 */
		public function getDatabase() {
			return $this->db;
		}

		/**
		 * Get default fee.
		 */
		public function getDefaultFee() {
			return $this->defaultFee;
		}

		/**
		 * Set default fee.
		 */
		public function setDefaultFee($value) {
			return $this->defaultFee=$value;
		}


		/**
		 * Log.
		 */
		public function log($message) {
			if (!$this->logFile)
				return;

			$data=array(
				"stamp"=>date("Y-m-d H:i:s"),
				"message"=>$message
			);

			$res=file_put_contents($this->logFile,json_encode($data)."\n",FILE_APPEND);
			if (!$res)
				$this->response(array("error"=>"Could not write log file."));
		}

		/**
		 * Reply and exit.
		 */
		private function response($response) {
			echo json_encode($response);
			exit();
		}

		/**
		 * Initialize database.
		 */
		private function initDatabase() {
			try {
				$this->db=new PDO($this->dsn);
				$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}

			catch (Exception $e) {
				$this->response(array("error"=>"Unable to connect to database: ".$e->getMessage()));
			}

			$this->db->exec(
				"CREATE TABLE IF NOT EXISTS addresses ( ".
				"  address TEXT PRIMARY KEY, ".
				"  label TEXT, ".
				"  archived INTEGER NOT NULL DEFAULT 0 ".
				")");

			$this->db->exec(
				"CREATE TABLE IF NOT EXISTS transactions ( ".
				"  hash TEXT NOT NULL, ".
				"  address TEXT NOT NULL, ".
				"  amount BIGINT NOT NULL, ".
				"  confirmations INTEGER NOT NULL, ".
				"  PRIMARY KEY (hash, address) ".
				")");
		}

		/**
		 * Dispatch call.
		 */
		public function dispatch() {
			$this->initDatabase();
			$this->log(RewriteUtil::getPathComponents());

			$this->authenticate();
			$components=RewriteUtil::getPathComponents();

			if (sizeof($components)<2)
				$this->response(array("error"=>"Unknown method."));

			$this->handler=new BlockchainWalletMockHandler($this);
			$method="serve_".$components[1];

			if (!method_exists($this->handler,$method))
				$this->response(array("error"=>"Unknown method."));

			$res=call_user_func(array($this->handler,$method));
			if ($res===NULL)
				$res=array("message"=>"ok");

			$this->response($res);
		}
	}