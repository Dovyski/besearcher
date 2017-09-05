<?php

namespace Besearcher;

class WebApp {
	private static $mInstance;

	public static function init($theINIPath) {
		$aINI = @parse_ini_file($theINIPath);

		if($aINI === false) {
			throw new Exception('There is a syntax error in config.ini or it does not exist.');
		}

		if(!isset($aINI['besearcher_ini_file'])) {
			throw new Exception('Unable to find "besearcher_ini_file" directive in config.ini. Please check if the file is correct.');
		}

		self::$mInstance = new App();
		self::$mInstance->init($aINI['besearcher_ini_file'], '', true);
	}

	public static function instance() {
		return self::$mInstance;
	}
}

?>
