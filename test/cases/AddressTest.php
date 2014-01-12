<?php

	require_once __DIR__."/TestBase.php";

	/**
	 * Test address creation and list.
	 */
	class AddressTest extends TestBase {

		/**
		 * Test address creation and list.
		 */
		function testCreateAddress() {
			$r=$this->doCall("debug_clear");
			$r=$this->doCall("new_address");
			$address=$r["address"];

			$this->assertNotNull($address);

			$r=$this->doCall("list");
			$this->assertEquals($r["addresses"][0]["address"],$address);

			$r=$this->doCall("archive_address",array("address"=>$address));
			$this->assertEquals($r["archived"],$address);

			$r=$this->doCall("list");
			$this->assertEquals(sizeof($r["addresses"]),0);
		}

		
	}