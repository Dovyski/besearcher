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
		$mPDO = null;
	}

	public function destroy() {
		$this->mPDO->exec("DROP TABLE tasks");
		$this->mPDO->exec("DROP TABLE results");
		$this->mPDO->exec("DROP TABLE context");
		$this->mPDO->exec("DROP TABLE analytics");
		$this->createTables();
	}

	public function hasTables() {
		$aStmt = $this->mPDO->prepare("SELECT COUNT(*) AS num FROM sqlite_master WHERE type='table' AND name='context'");
		$aStmt->execute();
		$aRow = $aStmt->fetch(\PDO::FETCH_ASSOC);

		return $aRow['num'] > 0;
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

	public function update($theTable, $theId, $theKeyValuePairs) {
		$aFields = array_keys($theKeyValuePairs);
		$aParts = array();

		foreach($aFields as $aField) {
			$aParts[] = $aField . ' = ' . ':' . $aField;
		}

		$aStmt = $this->mPDO->prepare("UPDATE ".$theTable." SET ".implode(', ', $aParts)." WHERE id = :id");

		foreach($aFields as $aField) {
			$aStmt->bindParam(':' . $aField, $theKeyValuePairs[$aField]);
		}

		$aStmt->bindParam(':id', $theId);
		$aOk = $aStmt->execute();

		return $aOk;
	}

	public function getPDO() {
		return $this->mPDO;
	}
}

?>
