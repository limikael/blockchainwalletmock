<?php

	namespace blockchainwalletmock;

	require_once __DIR__."/utils/RewriteUtil.php";
	require_once __DIR__."/utils/ArrayUtil.php";
	require_once __DIR__."/handler/BlockchainWalletMockHandler.php";

	use HTTPServer;
	use \Exception;
	use \PDO;

	/**
	 * Server.
	 * Mainly just lets the main instance handle the request.
	 */
	class BlockchainWalletMockServer extends HTTPServer {

		/**
		 * Constructor.
		 */
		function __construct($blockchainWalletMock, $options) {
			$this->blockchainWalletMock=$blockchainWalletMock;

			parent::__construct($options);
		}

		/**
		 * Route request.
		 */
		function route_request($request) {
			$path=RewriteUtil::splitUrlPath($request->uri);
			parse_str($request->query_string,$params);

			$response=$this->blockchainWalletMock->handle($path,$params);

			return $this->text_response(200,$response);
		}
	}

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
		private $server;
		private $pid;
		private $port;
		private $showLog;
		private $tmpFile;

		/**
		 * Construct.
		 */
		public function __construct() {
			date_default_timezone_set("UTC");

			$this->defaultFee=10000;
			$this->callbackUrl=NULL;
			$this->port=8910;
			$this->showLog=TRUE;
			$this->tmpFile=NULL;
		}

		/**
		 * Set callback url.
		 */
		public function setCallbackUrl($value) {
			$this->callbackUrl=$value;
		}

		/**
		 * Set port.
		 */
		public function setPort($value) {
			$this->port=$value;
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
		 * Set log file.
		 */
		public function setShowLog($value) {
			$this->showLog=$value;
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
			if ($this->logFile) {
				$data=array(
					"stamp"=>date("Y-m-d H:i:s"),
					"message"=>$message
				);

				$res=file_put_contents($this->logFile,json_encode($data)."\n",FILE_APPEND);
			}

			if ($this->showLog) {
				$out=fopen("php://stdout","w");
				fwrite($out,$message."\n");
				fclose($out);
			}
		}

		/**
		 * Initialize database.
		 */
		private function initDatabase() {
			if (!$this->dsn) {
				$this->tmpFile=tempnam(sys_get_temp_dir(),"");
				$this->dsn="sqlite:".$this->tmpFile;
			}

			try {
				$this->db=new PDO($this->dsn);
				$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}

			catch (Exception $e) {
				throw new Exception("Unable to connect to database: ".$e->getMessage());
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
		 *
		 */
		private function init() {
			if (!$this->port)
				throw new Exception("no port");

			$this->initDatabase();
		}

		/**
		 * Run as a child process until stop is called.
		 */
		public function runInBackground() {
			$this->init();

			$this->pid=pcntl_fork();

			if ($this->pid<0)
				throw new Exception("Could not fork");

			// Parent.
			if ($this->pid) {
				register_shutdown_function(array($this,"stop"));
				usleep(100000);
			}

			// Child.
			if (!$this->pid) {
				$this->run();
			}
		}

		/**
		 * Stop.
		 */
		public function stop() {
			if ($this->pid) {
				posix_kill($this->pid,SIGINT);
				pcntl_waitpid($this->pid,$status);

				$this->pid=NULL;
			}

			if ($this->tmpFile) {
				unlink($this->tmpFile);
				$this->tmpFile=null;
			}
		}

		/**
		 * Run forever.
		 */
		public function runForever() {
			$this->init();
			$this->run();
		}

		/**
		 * Run server.
		 */
		private function run() {
			$server=new BlockchainWalletMockServer($this,array(
				"port"=>$this->port
			));

			$server->run_forever();
		}

		/**
		 * Handle a request.
		 */
		public function handle($components, $params) {
			$this->log("Req: ".$components[0]);

			if ($this->guid)
				$components=array($components[1]);

			if (sizeof($components)<1)
				return "Maformed url";

			$this->handler=new BlockchainWalletMockHandler($this);
			$method="serve_".$components[0];

			if (!method_exists($this->handler,$method))
				return "Unknown method: ".$components[0];

			$res=call_user_func(array($this->handler,$method),$params);
			if ($res===NULL)
				$res=array("message"=>"ok");

			return json_encode($res)."\n";
		}

		/**
		 * Dispatch call.
		 */
/*		public function dispatch() {
			$this->initDatabase();
			$this->log(RewriteUtil::getPathComponents());

			$this->authenticate();
			$components=RewriteUtil::getPathComponents();

			if ($this->guid)
				$components=array($components[1]);

			if (sizeof($components)<1)
				$this->response(array("error"=>"Maformed url"));

			$this->handler=new BlockchainWalletMockHandler($this);
			$method="serve_".$components[0];

			if (!method_exists($this->handler,$method))
				$this->response(array("error"=>"Unknown method: ".$components[0]));

			$res=call_user_func(array($this->handler,$method));
			if ($res===NULL)
				$res=array("message"=>"ok");

			$this->response($res);
		}*/
	}