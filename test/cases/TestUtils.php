<?php

	/**
	 * Test utilities.
	 */
	class TestUtils {

		/**
		 * Fetch json from url.
		 */
		public static function fetchJson($url) {
			//echo "Fetching: ".$url."\n";
			$curl=curl_init($url);
			curl_setopt($curl,CURLOPT_RETURNTRANSFER,TRUE);
			$raw=curl_exec($curl);
			//print_r($raw);

			$data=json_decode($raw,TRUE);
			if ($data===NULL)
				throw new Exception("Not json in response: ".$raw);

			return $data;
		}
	}