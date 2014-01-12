<?php

	/**
	 * Array utility.
	 */
	class ArrayUtil {

		/**
		 * Get element from array if it exists.
		 */
		public static function getIfExists($array, $key) {
			if (array_key_exists($key,$array))
				return $array[$key];

			return NULL;
		}
	}