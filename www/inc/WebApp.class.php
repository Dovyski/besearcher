<?php

namespace Besearcher;

class WebApp {
	private static $mInstance;
	private static $mUsers;
	private static $mINI;
	private static $mActiveBesearcherINIPath;

	public static function init($theINIPath) {
		self::loadWebAppINI($theINIPath);

		try {
			self::$mInstance = new App();
			self::$mInstance->init(self::getBesearcherINIPath(), '', true);
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

	public static function config($theKey, $theDefault = null) {
		return isset(self::$mINI[$theKey]) ? self::$mINI[$theKey] : $theDefault;
	}

	private static function loadWebAppINI($theINIPath) {
		self::$mINI = @parse_ini_file($theINIPath);

		if(self::$mINI === false) {
			throw new \Exception('There is a syntax error in the web dashboard configuration file or it does not exist. Path to the file is: <code>'.$theINIPath.'</code>');
		}

		if(!isset(self::$mINI['besearcher_ini_file'])) {
			throw new \Exception('Unable to find the <code>besearcher_ini_file</code> directive in the web dashboard configuration file <code>'.$theINIPath.'</code>. Please check the content of this file is correct.');
		}

		self::initBesearcherINIPaths();
	}

	private static function initBesearcherINIPaths() {
		// besearcher_ini_file must be an array of possible besearcher INI files.
		// If a single entry was informed in the web app INI, we convert it to
		// an array (containing a single element)
		if(!is_array(self::$mINI['besearcher_ini_file'])) {
			self::$mINI['besearcher_ini_file'] = array(self::$mINI['besearcher_ini_file']);
		}

		// By default, the first entry in the array is the active besearcher INI file.
		self::$mActiveBesearcherINIPath = 0;
	}

	private static function getBesearcherINIPath() {
		return self::$mINI['besearcher_ini_file'][self::$mActiveBesearcherINIPath];
	}

	private static function findBesearcherINIPaths() {
		return self::$mINI['besearcher_ini_file'];
	}
}

?>
