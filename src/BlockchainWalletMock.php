<?php

	require_once __DIR__."/utils/RewriteUtil.php";
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

		/**
		 * Construct.
		 */
		public function BlockchainWalletMock() {

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

			if ($this->password && $this->password!=$_REQUEST["password"])
				$this->response(array("error"=>"Wrong password."));

			if ($this->guid && $this->guid!=$components[0]) {
				//$this->log("expected: ".$this->guid);
				$this->response(array("error"=>"Wrong guid."));
			}
		}

		/**
		 * Log.
		 */
		private function log($message) {
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
				"  label TEXT ".
				")");

			$this->db->exec(
				"CREATE TABLE IF NOT EXISTS transactions ( ".
				"  hash TEXT PRIMARY KEY, ".
				"  address TEXT NOT NULL, ".
				"  amount BIGINT NOT NULL, ".
				"  confirmations INTEGER NOT NULL ".
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

			$this->handler=new BlockchainWalletMockHandler();
			$method="serve_".$components[1];

			if (!method_exists($this->handler,$method))
				$this->response(array("error"=>"Unknown method."));

			$res=call_user_func(array($this->handler,$method));
			$this->response($res);

/*			switch ($method) {
				case "list":
					$this->handleList("hello");
					break;

				case "debug_clear":
					$this->response($this->handleDebugClear());
					break;

				default:
					break;
			}*/
		}
	}