<?php

	require_once __DIR__."/../TestBase.php";

	/**
	 * Test transactions.
	 */
	class TransactionTest extends TestBase {

		private $address;

		/**
		 * Set up.
		 */
		function setUp() {
			parent::setUp();

			$this->createRequest("debug_clear")->exec();

			$r=$this->createRequest("new_address")->exec();
			$this->address=$r["address"];
		}

		/**
		 * Tear down.
		 */
		function tearDown() {
			$r=$this->createRequest("archive_address")
				->setParam("address",$this->address)
				->exec();
			$this->assertEquals($r["archived"],$this->address);

			parent::tearDown();
		}

		/**
		 * Test address creation and list.
		 */
		function testSimulateIncomming() {
			$res=$this->createRequest("debug_incoming")
				->setParam("address",$this->address)
				->setParam("amount",123)
				->exec();

			$this->assertEquals($res["message"],"ok");

			$res=$this->createRequest("address_balance")
				->setParam("address",$this->address)
				->exec();

			$this->assertEquals($res["address"],$this->address);
			$this->assertEquals($res["balance"],123);
		}

		/**
		 * Test making a payment.
		 */
		function testPayment() {
			$this->createRequest("debug_incoming")
				->setParam("address",$this->address)
				->setParam("amount",10000000)
				->exec();

			$res=$this->createRequest("payment")
				->setParam("to","some_random_place")
				->setParam("amount",1000000)
				->setParam("from",$this->address)
				->exec();

			$this->assertNotNull($res["tx_hash"]);
			$res=$this->createRequest("payment")
				->setParam("to","some_random_place")
				->setParam("amount",1000000000)
				->setParam("from",$this->address)
				->exec();

			$this->assertNotNull($res["error"]);

			$res=$this->createRequest("address_balance")
				->setParam("address",$this->address)
				->exec();

			$this->assertEquals($res["address"],$this->address);
			$this->assertEquals($res["balance"],9000000-10000);
			$this->assertEquals($res["total_received"],10000000);
		}

		/**
		 * Test confirmations.
		 */
		function testConfirmations() {
			$this->createRequest("debug_incoming")
				->setParam("address",$this->address)
				->setParam("amount",10000000)
				->exec();

			$res=$this->createRequest("address_balance")
				->setParam("address",$this->address)
				->exec();

			$this->assertEquals(10000000,$res["balance"]);

			$res=$this->createRequest("address_balance")
				->setParam("address",$this->address)
				->setParam("confirmations",1)
				->exec();

			$this->assertEquals($res["balance"],0);

			$this->createRequest("debug_confirmation")->exec();

			$res=$this->createRequest("address_balance")
				->setParam("address",$this->address)
				->setParam("confirmations",1)
				->exec();

			$this->assertEquals(10000000,$res["balance"]);
		}

		/**
		 * Test payment to local address.
		 */
		function testLocalTransaction() {
			$r=$this->createRequest("new_address")->exec();
			$this->address2=$r["address"];

			$this->createRequest("debug_incoming")
				->setParam("address",$this->address)
				->setParam("amount",10000000)
				->exec();

			$res=$this->createRequest("address_balance")
				->setParam("address",$this->address)
				->exec();

			$this->assertEquals(10000000,$res["balance"]);

			$res=$this->createRequest("payment")
				->setParam("to",$this->address2)
				->setParam("amount",1000000)
				->setParam("from",$this->address)
				->exec();

			$res=$this->createRequest("address_balance")
				->setParam("address",$this->address)
				->exec();

			$this->assertEquals(10000000-1000000-10000,$res["balance"]);

			$res=$this->createRequest("address_balance")
				->setParam("address",$this->address2)
				->exec();

			$this->assertEquals(1000000,$res["balance"]);
		}

		/**
		 * test payment without a from address.
		 */
		function testPaymentWithoutFrom() {
			$r=$this->createRequest("new_address")->exec();
			$address1=$r["address"];

			$r=$this->createRequest("new_address")->exec();
			$address2=$r["address"];

			$this->createRequest("debug_incoming")
				->setParam("address",$address1)
				->setParam("amount",100000)
				->exec();

			$this->createRequest("debug_incoming")
				->setParam("address",$address2)
				->setParam("amount",100000)
				->exec();

			$res=$this->createRequest("payment")
				->setParam("to","some_random_place")
				->setParam("amount",250000)
				->exec();

			$this->assertEquals("Insufficient balance.",$res["error"]);

			$res=$this->createRequest("payment")
				->setParam("to","some_random_place")
				->setParam("amount",150000)
				->exec();

			$res=$this->createRequest("balance")
				->exec();

			$this->assertEquals(40000,$res["balance"]);
		}
	}