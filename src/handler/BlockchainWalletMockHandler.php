<?php

	/**
	 * Handle api calls.
	 */
	class BlockchainWalletMockHandler {

		private $walletMock;
		private $db;

		/**
		 * Construct.
		 */
		public function BlockchainWalletMockHandler($walletMock) {
			$this->walletMock=$walletMock;
			$this->db=$this->walletMock->getDatabase();
		}

		/**
		 * List addresses.
		 */
		function serve_list() {
			$q=$this->db->query("SELECT * FROM addresses WHERE archived=0");
			$a=[];

			foreach ($q->fetchAll() as $row) {
				$a[]=array(
					"address"=>$row["address"]
				);
			}

			return $a;
		}

		/**
		 * New address.
		 */
		function serve_new_address() {
			$address=md5(microtime());

			$q=$this->db->prepare(
				"INSERT INTO addresses (address) ".
				"VALUES (:address)");

			$q->bindParam("address",$address);
			$q->execute();

			return array(
				"address"=>$address
			);
		}

		/**
		 * Clear.
		 */
		function serve_debug_clear() {
			$this->db->query("DELETE FROM addresses");
			$this->db->query("DELETE FROM transactions");
		}

		/**
		 * Archive.
		 */
		function serve_archive_address() {
			$q=$this->db->query("UPDATE addresses SET archived=1 WHERE address=:address");
			$q->bindParam("address",$_REQUEST["address"]);
			$q->execute();

			return array("archived"=>$_REQUEST["address"]);
		}
	}