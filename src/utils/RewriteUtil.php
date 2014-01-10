<?php

	/**
	 * Util for use with apache mod rewrite.
	 */
	class RewriteUtil {

		/**
		 * Get component of the path that comes after the location
		 * of the script.
		 */
		public static function getBase() {
			$pathinfo=pathinfo($_SERVER["SCRIPT_NAME"]);
			$dirname=$pathinfo["dirname"];
			$url=$_SERVER["REQUEST_URI"];

			//Logger::debug("url: ".$url);

			if (strpos($url,"?")!==FALSE)
				$url=substr($url,0,strpos($url,"?"));

			//Logger::debug("url: ".$url);

			if (substr($url,0,strlen($dirname))!=$dirname)
				throw new Exception("Somthing is malformed.");

			$s=substr($url,0,strlen($dirname))."/";

			return str_replace("//","/",$s);
		}

		/**
		 * Get base including host and protocol.
		 */
		public static function getBaseUrl() {
			$url="http://";

			if (isset($_SERVER['HTTPS']))
				$url="https://";

			$url.=$_SERVER["SERVER_NAME"];

			if ($_SERVER["SERVER_PORT"]!=80)
				$url.=":".$_SERVER["SERVER_PORT"];

			$url.=self::getBase();

			return $url;
		}

		/**
		 * Get component of the path that comes after the location
		 * of the script.
		 */
		public static function getPath() {
			$pathinfo=pathinfo($_SERVER["SCRIPT_NAME"]);
			$dirname=$pathinfo["dirname"];
			$url=$_SERVER["REQUEST_URI"];

			if (strpos($url,"?")!==FALSE)
				$url=substr($url,0,strpos($url,"?"));

			if (substr($url,0,strlen($dirname))!=$dirname)
				throw new Exception("Somthing is malformed.");

			return substr($url,strlen($dirname));
		}

		/**
		 * Get path components.
		 */
		public static function getPathComponents() {
			return self::splitUrlPath(self::getPath());
		}

		/**
		 * Split url path.
		 */
		public static function splitUrlPath($path) {
			$components=explode("/",$path);
			$res=array();

			foreach ($components as $component)
				if ($component)
					$res[]=$component;

			return $res;
		}
	}