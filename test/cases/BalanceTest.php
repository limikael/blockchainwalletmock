<?php

	require_once __DIR__."/TestBase.php";

	/**
	 * Test transactions.
	 */
	class BalanceTest extends TestBase {

		/**
		 * Test total balance.
		 */
		function testBalance() {
			$this->doCall("debug_clear");

			$res=$this->doCall("balance");

			$this->assertTrue(array_key_exists("balance",$res));
			$this->assertEquals(0,$res["balance"]);

			$r=$this->doCall("new_address");
			$addess1=$r["address"];

			$r=$this->doCall("new_address");
			$addess2=$r["address"];

			$res=$this->doCall("debug_incoming",array(
				"address"=>$address1,
				"amount"=>200
			));

			$this->assertEquals($res["message"],"ok");

			$res=$this->doCall("debug_incoming",array(
				"address"=>$address2,
				"amount"=>123
			));

			$this->assertEquals($res["message"],"ok");

			$res=$this->doCall("balance");
			$this->assertEquals($res["balance"],323);
		}
	}