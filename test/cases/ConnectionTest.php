<?php

	/**
	 * Test connectivity.
	 */
	class ConnectionTest extends PHPUnit_Framework_TestCase {

		private $settings;
		private $api;

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
		 * Fetch json from url.
		 */
		private static function fetchJson($url) {
			//echo "Fetching: ".$url."\n";
			$curl=curl_init($url);
			curl_setopt($curl,CURLOPT_RETURNTRANSFER,TRUE);
			$raw=curl_exec($curl);
			//print_r($raw);

			$data=json_decode($raw,TRUE);
			if ($data===NULL)
				throw new Exception("Not json in response.");

			return $data;
		}

		/**
		 * Test connect.
		 */
		function testList() {
			$res=self::fetchJson($this->api."/list?password=".$this->settings["walletpassword"]);
			$this->assertFalse(array_key_exists("error",$res),"list should not return error");
		}

		/**
		 * Test bad credentials.
		 */
		function testBadCredentials() {
			$res=self::fetchJson($this->api."/list?password=".$this->settings["walletpassword"]."xyz");
			$this->assertEquals($res["error"],"Wrong password.");

			$wrongApiapi="http://".
				$this->settings["wallethost"].":".
				$this->settings["walletport"]."/".
				$this->settings["walletguid"]."xyz";

			$res=self::fetchJson($wrongApi."/list?password=".$this->settings["walletpassword"]);
			$this->assertEquals($res["error"],"Wrong guid.");
		}

		/**
		 * Test strange call.
		 */
		function testStrangeCall() {
			$res=self::fetchJson($this->api."/hello?password=".$this->settings["walletpassword"]);
			$this->assertEquals($res["error"],"Unknown method.");

			$res=self::fetchJson($this->api."?password=".$this->settings["walletpassword"]);
			$this->assertEquals($res["error"],"Unknown method.");
		}

		/**
		 * Test address creation and list.
		 */
		function testCreateAddress() {
			$res=self::fetchJson($this->api."/new_address?password=".$this->settings["walletpassword"]);

		}
	}