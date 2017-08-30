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

		$aParts = explode('--split', $aStructure);

		foreach($aParts as $aCommand) {
			self::$mDb->exec($aCommand);
		}
	}

	public static function getContext() {
		$aStmt = self::$mDb->prepare("SELECT * FROM context WHERE 1");
		$aStmt->execute();
		$aContext = $aStmt->fetch(\PDO::FETCH_ASSOC);

		return $aContext;
	}

	public static function saveContext($theContext) {
		$aSql =
			"UPDATE
				context
			SET
				ini_path = :ini_path,
				ini_hash = :ini_hash,
				last_commit = :last_commit,
				path_log_file = :path_log_file,
				time_last_pull = :time_last_pull,
				running_tasks = :running_tasks,
				status = :status
			WHERE
				id = 1";

		$aStmt = self::$mDb->prepare($aSql);

		$aStmt->bindParam(':ini_path', $theContext['ini_path']);
		$aStmt->bindParam(':ini_hash', $theContext['ini_hash']);
		$aStmt->bindParam(':last_commit', $theContext['last_commit']);
		$aStmt->bindParam(':path_log_file', $theContext['path_log_file']);
		$aStmt->bindParam(':time_last_pull', $theContext['time_last_pull']);
		$aStmt->bindParam(':running_tasks', $theContext['running_tasks']);
		$aStmt->bindParam(':status', $theContext['status']);

		$aStmt->execute();
	}

	public static function instance() {
		return self::$mDb;
	}
}

?>
