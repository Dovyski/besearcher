<?php

namespace Besearcher;

class Data {
	private static $mINI;
	private static $mDb;
	private static $mTasks;
	private static $mData;
	private static $mLoaded;

	private static function load($theINIPath) {
		$aError = '';

		self::$mINI = parse_ini_file($theINIPath, true);
		$aDataDir = self::$mINI['data_dir'];

		if(file_exists($aDataDir)) {
			$aDbPath = $aDataDir . DIRECTORY_SEPARATOR . BESEARCHER_DB_FILE;

			self::$mDb = new Db($aDbPath);
			self::$mTasks = new Tasks(self::$mDb);

			$aCacheFile = $aDataDir . DIRECTORY_SEPARATOR . BESEARCHER_WEB_CACHE_FILE;
			$aData = false;

			if(file_exists($aCacheFile)) {
				// There is a cache file for the aggradated content.
				// Let's use that instead
				$aData = @unserialize(file_get_contents($aCacheFile));
			}

			// If cache data is inexistent or invalid, load
			// raw data instead
			if($aData === false) {
				$aData = self::$mTasks->findTasksInfos($aDataDir);
			}

			self::$mData = $aData;
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

	public static function compileMetricStats($theTasks) {
		$aStats = array();

		foreach($theTasks as $aTask) {
			foreach($aTask as $aResult) {
				$aMeta = $aResult['meta'];

				foreach($aMeta as $aItem) {
					if($aItem['type'] != BESEARCHER_TAG_TYPE_PROGRESS) {
						$aMetric = $aItem['name'];
						$aData = $aItem['data'];

						if(is_array($aData)) {
							continue;
						}

						if(!isset($aStats[$aMetric])) {
							$aStats[$aMetric] = array();
						}

						$aStats[$aMetric][] = array(
							'commit' => $aResult['commit'],
							'permutation' => $aResult['permutation'],
							'value' => $aData
						);
					}
				}
			}
		}

		return $aStats;
	}

	public static function compileAnalyticsFromMetricStats($theStats) {
		$aAnalytics = array();

		foreach($theStats as $aMetric => $aItems) {
			if(count($aItems) == 0) {
				continue;
			}

			if(!isset($aAnalytics[$aMetric])) {
				$aAnalytics[$aMetric] = array(
					'min' => array('commit' => $aItems[0]['commit'], 'permutation' => $aItems[0]['permutation'], 'value' => $aItems[0]['value']),
					'max' => array('commit' => $aItems[0]['commit'], 'permutation' => $aItems[0]['permutation'], 'value' => $aItems[0]['value']),
				);
			}

			foreach($aItems as $aEntry) {
				if($aEntry['value'] < $aAnalytics[$aMetric]['min']['value']) {
					$aAnalytics[$aMetric]['min'] = $aEntry;
				}

				if($aEntry['value'] > $aAnalytics[$aMetric]['max']['value']) {
					$aAnalytics[$aMetric]['max'] = $aEntry;
				}
			}
		}

		return $aAnalytics;
	}
}

?>
