<?php

namespace Besearcher;

class Data {
	private static $mINI;
	private static $mData;
	private static $mLoaded;

	private static function load($theINIPath) {
		$aError = '';

		self::$mINI = parse_ini_file($theINIPath, true);
		$aDataDir = self::$mINI['data_dir'];

		if(file_exists($aDataDir)) {
			self::$mData = findTasksInfos($aDataDir);
			self::$mLoaded = true;
		} else {
			$aError = 'Informed data directory does not exist: ' . $aDataDir;
		}

		return $aError;
	}

	public static function init() {
		$aError = '';

		self::$mLoaded = false;
		self::$mData = array();

		if(file_exists(PATH_BESERCHER_INI_FILE)) {
			$aError = self::load(PATH_BESERCHER_INI_FILE);
		} else {
			$aError	= 'Unable to load config.ini file: ' . PATH_BESERCHER_INI_FILE;
		}

		return $aError;
	}

	public static function tasks() {
		return self::$mData;
	}

	public static function ini() {
		return self::$mINI;
	}

	public static function loaded() {
		return self::$mLoaded;
	}
}

?>
