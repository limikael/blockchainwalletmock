<?php

	require_once __DIR__."/../TestBase.php";

	/**
	 * Test address creation and list.
	 */
	class AddressTest extends TestBase {

		/**
		 * Test address creation and list.
		 */
		function testCreateAddress() {
			$this->createRequest("debug_clear")->setParam("x","y")->exec();
			$r=$this->createRequest("new_address")->exec();
			$address=$r["address"];

			$this->assertNotNull($address);

			$r=$this->createRequest("list")->exec();
			$this->assertEquals($r["addresses"][0]["address"],$address);

			$req=$this->createRequest("archive_address");
			$req->setParam("address",$address);
			$r=$req->exec();
			$this->assertEquals($r["archived"],$address);

			$r=$this->createRequest("list")->exec();
			$this->assertEquals(sizeof($r["addresses"]),0,"the should be no address left");
		}
	}