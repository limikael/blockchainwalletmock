<?php

	require_once __DIR__."/TestBase.php";

	/**
	 * Test address creation and list.
	 */
	class CallbackTest extends TestBase {

		/**
		 * Set up for test.
		 */
		protected function setUp() {
			parent::setUp();
			$this->doCall("debug_clear");
			$this->clearCallbackLog();
		}		

		/**
		 * Test invoking the callback not through api.
		 */
		function testInvoke() {
			$c=curl_init($this->settings["walletcallback"]."?hello=world");
			curl_exec($c);

			$log=$this->getCallbackLog();
			$this->assertEquals(sizeof($log),1);
			$this->assertEquals("world",$log[0]["hello"]);
		}

		/**
		 * Test incoming payment callback.
		 */
		function testIncomingPaymentCallback() {
			$res=$this->doCall("new_address");
			$address=$res["address"];
			$this->doCall("debug_incoming",array(
				"address"=>$address,
				"amount"=>1000,
				"input_transaction_hash"=>"testinputhash"
			));

			$log=$this->getCallbackLog();
			$this->assertEquals(sizeof($log),1);
			$this->assertEquals($log[0]["input_address"],$address);
			$this->assertEquals($log[0]["input_transaction_hash"],"testinputhash");

			$params=$log[0];
			$this->assertEquals(0,$params["confirmations"]);
			$this->assertEquals(1000,$params["value"]);
			$this->assertEquals($address,$params["input_address"]);
			$this->assertNotNull($params["transaction_hash"]);
		}

		/**
		 * Test confirmation callback.
		 */
		function testConfirmationCallback() {
			$res=$this->doCall("new_address");
			$address=$res["address"];
			$this->doCall("debug_incoming",array(
				"address"=>$address,
				"amount"=>1000
			));

			$log=$this->getCallbackLog();
			$this->assertEquals(sizeof($log),1);
			$this->assertEquals($log[0]["input_address"],$address);

			$params=$log[0];
			$this->assertEquals(0,$params["confirmations"]);
			$this->assertEquals(1000,$params["value"]);
			$this->assertEquals($address,$params["input_address"]);
			$this->assertNotNull($params["transaction_hash"]);

			$this->doCall("debug_confirmation");
			$log=$this->getCallbackLog();
			$this->assertEquals(sizeof($log),2);
			$this->assertEquals($log[0]["input_address"],$address);
			$this->assertEquals($log[1]["input_address"],$address);

			$params=$log[0];
			$this->assertEquals(0,$params["confirmations"]);

			$params=$log[1];
			$this->assertEquals(1,$params["confirmations"]);
		}
	}