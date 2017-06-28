<?php

namespace Besearcher;

class Data {
	private static $mINI;
	private static $mData;
	private static $mLoaded;

	private static function load($theINIPath) {
		$aOk = false;

		self::$mINI = parse_ini_file($theINIPath);
		$aDataDir = self::$mINI['data_dir'];

		if(file_exists($aDataDir)) {
			self::$mData = findTasksInfos($aDataDir);
			self::$mLoaded = true;
			$aOk = true;
		}

		return $aOk;
	}

	public static function init() {
		$aOk = false;

		self::$mLoaded = false;
		self::$mData = array();

		if(file_exists(PATH_BESERCHER_INI_FILE)) {
			$aOk = self::load(PATH_BESERCHER_INI_FILE);
		}

		return $aOk;
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
