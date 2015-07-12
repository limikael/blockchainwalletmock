<?php

	require_once __DIR__."/../TestBase.php";

	/**
	 * Test transactions.
	 */
	class BalanceTest extends TestBase {

		/**
		 * Test total balance.
		 */
		function testBalance() {
			$this->createRequest("debug_clear")->exec();

			$res=$this->createRequest("balance")->exec();

			$this->assertTrue(array_key_exists("balance",$res));
			$this->assertEquals(0,$res["balance"]);

			$r=$this->createRequest("new_address")->exec();
			$addess1=$r["address"];

			$r=$this->createRequest("new_address")->exec();
			$addess2=$r["address"];

			$res=$this->createRequest("debug_incoming")
				->setParam("address",$address1)
				->setParam("amount",200)
				->exec();

			$this->assertEquals($res["message"],"ok");

			$res=$this->createRequest("debug_incoming")
				->setParam("address",$address2)
				->setParam("amount",123)
				->exec();

			$this->assertEquals($res["message"],"ok");

			$res=$this->createRequest("balance")->exec();
			$this->assertEquals(323,$res["balance"]);
		}
	}