<?php

namespace Besearcher;

class WebApp {
	private static $mInstance;
	private static $mUsers;

	public static function init($theINIPath) {
		$aINI = @parse_ini_file($theINIPath);

		if($aINI === false) {
			throw new \Exception('There is a syntax error in the web dashboard configuration file or it does not exist. Path to the file is: <code>'.$theINIPath.'</code>');
		}

		if(!isset($aINI['besearcher_ini_file'])) {
			throw new \Exception('Unable to find the <code>besearcher_ini_file</code> directive in the web dashboard configuration file <code>'.$theINIPath.'</code>. Please check the content of this file is correct.');
		}

		try {
			self::$mInstance = new App();
			self::$mInstance->init($aINI['besearcher_ini_file'], '', true);
			self::$mUsers = new Users(self::$mInstance->getDb());

		} catch (\Exception $e) {
			throw new \Exception('Unable to initialize web dashboard. ' . $e->getMessage() . '. <pre style="margin-top:10px;">' . $e->getTraceAsString() . '</pre>');
		}
	}

	public static function instance() {
		return self::$mInstance;
	}

	public static function users() {
		return self::$mUsers;
	}
}

?>
