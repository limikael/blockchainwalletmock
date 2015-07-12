<?php

	require_once __DIR__."/../TestBase.php";

	/**
	 * Test address creation and list.
	 */
	class CallbackTest extends TestBase {

		/**
		 * Set up.
		 */
		function setUp() {
			parent::setUp();
			$this->startCallbackServer();
		}

		/**
		 * Test invoking the callback not through api.
		 */
		function testInvoke() {
			$c=curl_init("http://localhost:8911/?hello=world");
			curl_exec($c);

			$log=$this->getCallbackLog();
			$this->assertEquals($log["hello"],"world");
		}

		/**
		 * Test incoming payment callback.
		 */
		function testIncomingPaymentCallback() {
			$res=$this->createRequest("new_address")->exec();
			$address=$res["address"];

			$this->createRequest("debug_incoming")
				->setParam("address",$address)
				->setParam("amount",1000)
				->setParam("input_transaction_hash","testinputhash")
				->exec();

			$log=$this->getCallbackLog();
			$this->assertEquals($log["input_address"],$address);
			$this->assertEquals($log["input_transaction_hash"],"testinputhash");
			$this->assertEquals(0,$log["confirmations"]);
			$this->assertEquals(1000,$log["value"]);
			$this->assertEquals($address,$log["input_address"]);
			$this->assertNotNull($log["transaction_hash"]);
		}

		/**
		 * Test confirmation callback.
		 */
		function testConfirmationCallback() {
			$res=$this->createRequest("new_address")->exec();
			$address=$res["address"];

			$this->createRequest("debug_incoming")
				->setParam("address",$address)
				->setParam("amount",1000)
				->exec();

			$log=$this->getCallbackLog();
			$this->assertEquals($log["input_address"],$address);

			$this->assertEquals(0,$log["confirmations"]);
			$this->assertEquals(1000,$log["value"]);
			$this->assertEquals($address,$log["input_address"]);
			$this->assertNotNull($log["transaction_hash"]);

			$this->clearCallbackLog();
			$this->createRequest("debug_confirmation")->exec();
			$log=$this->getCallbackLog();
			$this->assertEquals($log["input_address"],$address);
			$this->assertEquals(1,$log["confirmations"]);
		}
	}