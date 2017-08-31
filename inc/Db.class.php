<?php

namespace Besearcher;

class Db {
	private $mPDO;

	public function __construct($theDatabasePath, $theCreateIfNonExistent = true) {
		$this->mPDO = new \PDO('sqlite:' . $theDatabasePath);
		$this->mPDO->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

		if($theCreateIfNonExistent && !$this->hasTables()) {
			$this->createTables();
		}
	}

	public function shutdown() {
	}

	public function hasTables() {
		$aStmt = $this->mPDO->prepare("SELECT COUNT(*) AS num FROM sqlite_master WHERE type='table' AND name='context'");
		$aStmt->execute();
		$aRow = $aStmt->fetch(\PDO::FETCH_ASSOC);

		return $aRow['num'] > 0;
	}

	public function tasksQueueSize() {
		$aStmt = $this->mPDO->prepare("SELECT COUNT(*) AS num FROM tasks WHERE 1");
		$aStmt->execute();
		$aRow = $aStmt->fetch(\PDO::FETCH_ASSOC);

		return $aRow['num'];
	}

	public function createTables() {
		$aSqlFile = dirname(__FILE__) . '/resources/db.sql';
		$aStructure = file_get_contents($aSqlFile);

		if($aStructure === false) {
			throw new \Exception('Unable to load SQL file: ' . $aSqlFile);
		}

		$aParts = explode('--split', $aStructure);

		foreach($aParts as $aCommand) {
			$this->mPDO->exec($aCommand);
		}
	}

	public function getPDO() {
		return $this->mPDO;
	}
}

?>
