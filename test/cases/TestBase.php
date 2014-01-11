<?php

	/**
	 * Base class for test cases.
	 */
	class TestBase extends PHPUnit_Framework_TestCase {

		protected $settings;
		protected $api;

		/**
		 * Set up for test.
		 */
		protected function setUp() {
			$this->settings=parse_ini_file(__DIR__."/../settings.ini");
			$this->api="http://".
				$this->settings["wallethost"].":".
				$this->settings["walletport"]."/".
				$this->settings["walletguid"];
		}

		/**
		 * Do call.
		 */
		protected function doCall($call, $params=array()) {
			if (!isset($params["password"]))
				$params["password"]=$this->settings["walletpassword"];

			$a=[];
			foreach ($params as $k=>$v)
				$a[]=$k."=".$v;

			return $this->fetchJson($this->api."/".$call."?".join("&",$a));
		}

		/**
		 * Fetch json from url.
		 */
		public function fetchJson($url) {
			//echo "Fetching: ".$url."\n";
			$curl=curl_init($url);
			curl_setopt($curl,CURLOPT_RETURNTRANSFER,TRUE);
			$raw=curl_exec($curl);
			//print_r($raw);

			$data=json_decode($raw,TRUE);
			if ($data===NULL)
				throw new Exception("Not json in response: ".$raw);

			return $data;
		}
	}