<?php

namespace Besearcher;

class WebApp {
	private static $mInstance;
	private static $mUsers;
	private static $mINI;
	private static $mActiveBesearcherINIPath;

	public static function bootstrap($theINIPath) {
		self::loadWebAppINI($theINIPath);
	}

	public static function init() {
		try {
			self::initBesearcherINIPaths();

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
	}

	private static function initBesearcherINIPaths() {
		// besearcher_ini_file must be an array of possible besearcher INI files.
		// If a single entry was informed in the web app INI, we convert it to
		// an array (containing a single element)
		if(!is_array(self::$mINI['besearcher_ini_file'])) {
			self::$mINI['besearcher_ini_file'] = array(self::$mINI['besearcher_ini_file']);
		}

		// If the user has selected a particular experiment for visualization,
		// we ensure that experiment is the one active now.
		$aExperimentNum = isset($_SESSION['experiment']) ? $_SESSION['experiment'] : 0;
		self::setActiveBesearcherINIPath($aExperimentNum);
	}

	public static function setActiveBesearcherINIPath($thePathNum) {
		if($thePathNum < 0 || $thePathNum >= count(self::$mINI['besearcher_ini_file'])) {
			throw new \Exception('Informed Besearcher INI path entry <code>'.$thePathNum.'</code> is invalid.');
		}

		if(self::$mActiveBesearcherINIPath != $thePathNum) {
			self::$mActiveBesearcherINIPath = $thePathNum;
			$_SESSION['experiment'] = $thePathNum;
		}
	}

	public static function getBesearcherINIPath() {
		return self::$mINI['besearcher_ini_file'][self::$mActiveBesearcherINIPath];
	}

	public static function findBesearcherINIPaths() {
		return self::$mINI['besearcher_ini_file'];
	}
}

?>
