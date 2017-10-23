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

	public function begin() {
		// TODO: keep track of being in a transaction
		$this->mPDO->beginTransaction();
	}

	public function rollback() {
		$this->mPDO->rollBack();
	}

	public function commit() {
		$this->mPDO->commit();
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

	public function execute(\PDOStatement $theStmt, $theToleratedError = '10 disk I/O error', $theTries = 1) {
		try {
			$aOk = $theStmt->execute();
			return $aOk;
		} catch (\Exception $e) {
			if($theTries > 0 && stripos($e->getMessage(), $theToleratedError) !== false) {
				usleep(300000); // wait for 0.3 seconds
				return $this->execute($theStmt, $theToleratedError, $theTries - 1);
			} else {
				throw $e;
			}
		}
	}

	public function update($theTable, array $theKeyValuePairs, array $theIdInfo = array()) {
		$aFields = array_keys($theKeyValuePairs);
		$aParts = array();

		foreach($aFields as $aField) {
			$aParts[] = $aField . ' = ' . ':' . $aField;
		}

		$aWhere = "1";
		$aIdFieldName = null;
		$aIdFieldValue = null;
		$aHasIdValue = count($theIdInfo) == 1;

		if($aHasIdValue) {
			$aIdFieldName = array_keys($theIdInfo)[0];
			$aIdFieldValue = array_values($theIdInfo)[0];
			$aWhere = $aIdFieldName." = :" . $aIdFieldName;
		}

		$aSql = "UPDATE ".$theTable." SET ".implode(', ', $aParts)." WHERE ".$aWhere;
		$aStmt = $this->mPDO->prepare($aSql);

		foreach($aFields as $aField) {
			$aStmt->bindParam(':' . $aField, $theKeyValuePairs[$aField]);
		}

		if($aHasIdValue) {
			$aStmt->bindParam(':' . $aIdFieldName, $aIdFieldValue);
		}

		$aOk = $aStmt->execute();

		return $aOk;
	}

	public function getPDO() {
		return $this->mPDO;
	}
}

?>
