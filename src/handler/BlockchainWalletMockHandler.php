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
			$q=$this->db->query(
				"SELECT a.address AS address, ".
				"       SUM(t.amount) AS balance, ".
				"       SUM(CASE WHEN amount>0 THEN amount ELSE 0 END) as total_received ".
				"FROM addresses AS a ".
				"LEFT JOIN transactions AS t ON a.address=t.address ".
				"WHERE a.archived=0 ".
				"GROUP BY a.address");

			$a=[];

			foreach ($q->fetchAll() as $row) {
				$a[]=array(
					"address"=>$row["address"],
					"balance"=>$row["balance"],
					"total_received"=>$row["total_received"]
				);
			}

			return array("addresses"=>$a);
		}

		/**
		 * New address.
		 */
		function serve_new_address() {
			$address=md5(microtime());

			$q=$this->db->prepare(
				"INSERT INTO addresses (address) ".
				"VALUES (:address)");

			$q->bindValue("address",$address);
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
			$q=$this->db->prepare("UPDATE addresses SET archived=1 WHERE address=:address");
			$q->bindValue("address",$_REQUEST["address"]);
			$q->execute();

			return array("archived"=>$_REQUEST["address"]);
		}

		/**
		 * Get total balance.
		 */
		function serve_balance() {
			$q=$this->db->prepare("SELECT SUM(amount) AS balance FROM transactions");
			$q->execute();

			$row=$q->fetch();
			$balance=$row["balance"];

			return array(
				"balance"=>$balance
			);
		}

		/**
		 * Get balance of address.
		 */
		function serve_address_balance() {
			$confirmations=0;

			if (array_key_exists("confirmations",$_REQUEST))
				$confirmations=$_REQUEST["confirmations"];

			return $this->getAddressBalance($_REQUEST["address"],$confirmations);

		}

		/**
		 * Get address balance.
		 */
		function getAddressBalance($address, $confirmations) {
			$q=$this->db->prepare(
				"SELECT ".
				"SUM(amount) as balance, ".
				"SUM(CASE WHEN amount>0 THEN amount ELSE 0 END) as total_received ".
				"FROM transactions ".
				"WHERE address=:address ".
				"AND confirmations>=:confirmations"
			);

			$q->bindValue("address",$address);
			$q->bindValue("confirmations",$confirmations);
			$q->execute();
			$row=$q->fetch();
			$balance=$row["balance"];
			$total_received=$row["total_received"];

			return array(
				"balance"=>$balance,
				"total_received"=>$total_received,
				"address"=>$address,
			);
		}

		/**
		 * Make payment.
		 */
		function serve_payment() {
			if (!array_key_exists("from", $_REQUEST))
				return array("error"=>"From address needs to be specified.");

			if (array_key_exists("fee", $_REQUEST))
				$fee=$_REQUEST["fee"];

			else
				$fee=$this->walletMock->getDefaultFee();

			$totalAmount=$_REQUEST["amount"]+$fee;

			$balanceRes=$this->getAddressBalance($_REQUEST["from"],0);
			if ($totalAmount>$balanceRes["balance"])
				return array("error"=>"Insufficient balance.");

			$hash=md5(microtime());

			$q=$this->db->prepare(
				"INSERT INTO transactions (hash, address, amount, confirmations) ".
				"VALUES (:hash,:address,:amount,:confirmations)"
			);

			$q->bindValue("hash",$hash);
			$q->bindValue("address",$_REQUEST["from"]);
			$q->bindValue("amount",-$totalAmount);
			$q->bindValue("confirmations",1000);

			$q->execute();

			if ($this->isAddressLocal($_REQUEST["to"]))
				$this->createIncoming($hash,$_REQUEST["to"],$_REQUEST["amount"]);

			return array("tx_hash"=>$hash);
		}

		/**
		 * Is this a local address?
		 */
		private function isAddressLocal($address) {
			$q=$this->db->prepare(
				"SELECT * ".
				"FROM addresses ".
				"WHERE address=:address"
			);

			$q->bindValue("address",$address);
			$q->execute();

			$rows=$q->fetchAll();

			if (sizeof($rows))
				return TRUE;

			return FALSE;
		}

		/**
		 * Simulate incoming transaction.
		 */
		function serve_debug_incoming() {
			$hash=md5(microtime());

			$this->createIncoming($hash, $_REQUEST["address"], $_REQUEST["amount"]);
		}

		/**
		 * Create incoming transaction.
		 */
		private function createIncoming($hash, $address, $amount) {
			$q=$this->db->prepare(
				"INSERT INTO transactions (hash, address, amount, confirmations) ".
				"VALUES (:hash,:address,:amount,:confirmations)"
			);

			$q->bindValue("hash",$hash);
			$q->bindValue("address",$address);
			$q->bindValue("amount",$amount);
			$q->bindValue("confirmations",0);

			$q->execute();

			$this->invokeTransactionCallback($hash);
		}

		/**
		 * Simulate confirmation.
		 */
		function serve_debug_confirmation() {
			$confirmations=1;
			if (array_key_exists("confirmations",$_REQUEST))
				$confirmations=$_REQUEST["confirmations"];

			$s="SELECT * FROM transactions ";
			$bindValue=NULL;

			if (array_key_exists("transaction",$_REQUEST)) {
				$s+="WHERE id=?";
				$bindValue=$_REQUEST["transaction"];
			}

			else if (array_key_exists("address",$_REQUEST)) {
				$s+="WHERE address=?";
				$bindValue=$_REQUEST["address"];
			}

			$q=$this->db->prepare($s);
			if ($bindValue!==NULL)
				$q->bindValue(1,$bindValue);

			$q->execute();

			$r=$this->db->prepare(
				"UPDATE transactions ".
				"SET confirmations=confirmations+:confirmations ".
				"WHERE hash=:hash"
			);

			$r->bindValue("confirmations",$confirmations);

			foreach ($q->fetchAll() as $row) {
				$r->bindValue("hash",$row["hash"]);
				$r->execute();
				$this->invokeTransactionCallback($row["hash"]);
			}
		}

		/**
		 * Invoke transaction callback.
		 * Notifications look like:
		 * "anonymous": "false",
		 * "shared": "false",
		 * "destination_address": "172V4RT6jYtdN47kzY7poXdekZjeB4pd5H",
		 * "confirmations": "0",
		 * "address": "172V4RT6jYtdN47kzY7poXdekZjeB4pd5H",
		 * "value": "100000",
		 * "input_address": "172V4RT6jYtdN47kzY7poXdekZjeB4pd5H",
		 * "input_transaction_hash": "b27e56961c1b256203c4b7ecaa40e5445e504290fc03dd4b91f1ae12a86ca7a7",
		 * "transaction_hash": "b27e56961c1b256203c4b7ecaa40e5445e504290fc03dd4b91f1ae12a86ca7a7"
		 */
		private function invokeTransactionCallback($hash) {
			$url=$this->walletMock->getCallbackUrl();

			if (!$url)
				return;

			$q=$this->db->prepare("SELECT * FROM transactions WHERE hash=:hash");
			$q->bindValue("hash",$hash);
			$q->execute();
			$transaction=$q->fetch();

			if (strpos($url,"?")===FALSE)
				$url.="?";

			else
				$url.="&";

			$url.="value=".$transaction["amount"];
			$url.="&transaction_hash=".$hash;
			$url.="&input_address=".$transaction["address"];
			$url.="&confirmations=".$transaction["confirmations"];

			$c=curl_init($url);
			curl_setopt($c,CURLOPT_RETURNTRANSFER,TRUE);
			curl_exec($c);
		}
	}