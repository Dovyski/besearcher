<?php

namespace Besearcher;

class Db {
	private static $mDb;

	public static function init($theDatabasePath) {
		self::$mDb = new \PDO('sqlite:' . $theDatabasePath);
		self::$mDb->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
	}

	public static function hasTables() {
		$aStmt = self::$mDb->prepare("SELECT COUNT(*) AS num FROM sqlite_master WHERE type='table' AND name='context'");
		$aStmt->execute();
		$aRows = array();
		$aRow = $aStmt->fetch(\PDO::FETCH_ASSOC);

		return $aRow['num'] > 0;
	}

	public static function createTables() {
		$aSqlFile = dirname(__FILE__) . '/resources/db.sql';
		$aStructure = file_get_contents($aSqlFile);

		if($aStructure === false) {
			throw new \Exception('Unable to load SQL file: ' . $aSqlFile);
		}

		$aStmt = self::$mDb->prepare($aStructure);
		$aStmt->execute();
	}

	public static function instance() {
		return self::$mDb;
	}
}

?>
