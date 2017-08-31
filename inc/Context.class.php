<?php

namespace Besearcher;

class Context {
	private $mDb;
	private $mAutoSave;
	private $mValues = array(
		'ini_hash'         => '',
		'ini_values'       => '',
		'last_commit'      => '',
		'path_log_file'    => '',
		'time_last_pull'   => 0,
		'running_tasks'    => 0,
		'status'           => ''
	);

	private function ensureKeyExists($theKey) {
		if(!isset($this->mValues[$theKey])) {
			throw new \Exception('Unknown context key "' . $theKey . '".');
		}
	}

	private function ensureHasDb() {
		if($this->mDb == null) {
			throw new \Exception('No db instance to work with persitent data.');
		}
	}

	public function __construct(Db $theDb = null, array $theInitialValues = array()) {
		$this->mDb = $theDb;
		$this->mAutoSave = false;

		foreach($this->mValues as $aKey => $aValue) {
			if(isset($theInitialValues[$aKey])) {
				$this->mValues[$aKey] = $theInitialValues[$aKey];
			}
		}
	}

	public function save() {
		$this->ensureHasDb();

		$aSql =
	        "UPDATE
	            context
	        SET
	            ini_hash = :ini_hash,
	            last_commit = :last_commit,
	            path_log_file = :path_log_file,
	            time_last_pull = :time_last_pull,
	            running_tasks = :running_tasks,
	            status = :status
	        WHERE
	            1";

	    $aStmt = $this->mDb->getPDO()->prepare($aSql);

	    $aStmt->bindParam(':ini_hash',       $this->mValues['ini_hash']);
	    $aStmt->bindParam(':last_commit',    $this->mValues['last_commit']);
	    $aStmt->bindParam(':path_log_file',  $this->mValues['path_log_file']);
	    $aStmt->bindParam(':time_last_pull', $this->mValues['time_last_pull']);
	    $aStmt->bindParam(':running_tasks',  $this->mValues['running_tasks']);
	    $aStmt->bindParam(':status',         $this->mValues['status']);

	    $aOk = $aStmt->execute();
	    return $aOk;
	}

	private function getValuesFromDisk() {
		$this->ensureHasDb();

		$aStmt = $this->mDb->getPDO()->prepare("SELECT * FROM context WHERE 1");
		$aStmt->execute();
		$aValues = $aStmt->fetch(\PDO::FETCH_ASSOC);

		return $aValues;
	}

	public function sync() {
		$aDiskValues = $this->getValuesFromDisk();

		$aIsDiskValid = !empty($aDiskValues['ini_hash']);
		$aAreValuesDifferent = serialize($this->mValues) != serialize($aDiskValues);

		if($aAreValuesDifferent) {
			if($aIsDiskValid) {
				// Disk has valid data, so it takes priority. Let's update
				// the in-memory values with data from the disk
				foreach($aDiskValues as $aKey => $aValue) {
					echo 'context.' . $aKey . ' = ' . (is_array($aValue) ? 'Array' : $aValue) . ' (old=' . (is_array($this->mValues[$aKey]) ? 'Array' : $this->mValues[$aKey]) . ')' . "\n";
					$this->mValues[$aKey] = $aValue;
				}
			} else {
				// Disk is invalid, so let's update it with what we have in memery
				$this->save();
			}
		}

		return $aAreValuesDifferent;
	}

	public function get($theKey) {
		$this->ensureKeyExists($theKey);
		return $this->mValues[$theKey];
	}

	public function set($theKey, $theValue) {
		$this->ensureKeyExists($theKey);
		$this->mValues[$theKey] = $theValue;

		if($this->mAutoSave) {
			$this->save();
		}
	}

	public function setAutoSave($theStatus) {
		$this->mAutoSave = $theStatus;
	}
}

?>
