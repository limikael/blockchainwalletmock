<?php

	require_once __DIR__."/TestBase.php";

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

			$r=$this->doCall("debug_clear");

			$r=$this->doCall("new_address");
			$this->address=$r["address"];
		}

		/**
		 * Tear down.
		 */
		function tearDown() {
			$r=$this->doCall("archive_address",array("address"=>$this->address));
			$this->assertEquals($r["archived"],$this->address);
		}

		/**
		 * Test address creation and list.
		 */
		function testSimulateIncomming() {
			$res=$this->doCall("debug_incoming",array(
				"address"=>$this->address,
				"amount"=>123
			));

			$this->assertEquals($res["message"],"ok");

			$res=$this->doCall("address_balance",array(
				"address"=>$this->address
			));

			$this->assertEquals($res["address"],$this->address);
			$this->assertEquals($res["balance"],123);
		}

		/**
		 * Test making a payment.
		 */
		function testPayment() {
			$this->doCall("debug_incoming",array(
				"address"=>$this->address,
				"amount"=>10000000
			));

			$res=$this->doCall("payment",array(
				"to"=>"some_random_place",
				"amount"=>1000000,
				"from"=>$this->address
			));

			$this->assertNotNull($res["tx_hash"]);
			$res=$this->doCall("payment",array(
				"to"=>"some_random_place",
				"amount"=>1000000000,
				"from"=>$this->address
			));

			$this->assertNotNull($res["error"]);

			$res=$this->doCall("address_balance",array(
				"address"=>$this->address
			));

			$this->assertEquals($res["address"],$this->address);
			$this->assertEquals($res["balance"],9000000-10000);
			$this->assertEquals($res["total_received"],10000000);
		}

		/**
		 * Test confirmations.
		 */
		function testConfirmations() {
			$this->doCall("debug_incoming",array(
				"address"=>$this->address,
				"amount"=>10000000
			));

			$res=$this->doCall("address_balance",array(
				"address"=>$this->address,
			));

			$this->assertEquals(10000000,$res["balance"]);

			$res=$this->doCall("address_balance",array(
				"address"=>$this->address,
				"confirmations"=>1
			));

			$this->assertEquals($res["balance"],0);

			$this->doCall("debug_confirmation");

			$res=$this->doCall("address_balance",array(
				"address"=>$this->address,
				"confirmations"=>1
			));

			$this->assertEquals(10000000,$res["balance"]);
		}
	}