<?php

	require_once __DIR__."/../TestBase.php";

	/**
	 * Test basic connectivity.
	 */
	class ConnectionTest extends TestBase {

		/**
		 * Test basic connection.
		 */
		function testBasic() {
			$res=$this->createRequest("list")->exec();
			$this->assertFalse(array_key_exists("error",$res),"list should not return error");
		}

		/**
		 * Test bad credentials.
		 */
		/*function testBadCredentials() {
			$res=$this->fetchJson($this->api."/list?password=".$this->settings["walletpassword"]."xyz");
			$this->assertEquals($res["error"],"Wrong password.");

			$wrongApi="http://".
				$this->settings["wallethost"].":".
				$this->settings["walletport"]."/".
				$this->settings["walletguid"]."xyz";

			$res=$this->fetchJson($wrongApi."/list?password=".$this->settings["walletpassword"]);
			$this->assertEquals($res["error"],"Wrong guid.");
		}*/

		/**
		 * Test strange call.
		 */
		/*function testStrangeCall() {
			$res=$this->fetchJson($this->api."/hello?password=".$this->settings["walletpassword"]);
			$this->assertEquals($res["error"],"Unknown method.");

			$res=$this->fetchJson($this->api."?password=".$this->settings["walletpassword"]);
			$this->assertEquals($res["error"],"Unknown method.");
		}*/
	}