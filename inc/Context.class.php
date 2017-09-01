<?php

namespace Besearcher;

class Context {
	private $mDb;
	private $mLog;
	private $mAutoSave;
	private $mValues = array(
		'ini_hash'         => '',
		'experiment_hash'  => '',
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

	public function __construct(Db $theDb = null, Log $theLog = null, array $theInitialValues = array()) {
		$this->mDb = $theDb;
		$this->mLog = $theLog;
		$this->mAutoSave = true;

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
	            experiment_hash = :experiment_hash,
	            running_tasks = :running_tasks,
	            status = :status
	        WHERE
	            1";

	    $aStmt = $this->mDb->getPDO()->prepare($aSql);

	    $aStmt->bindParam(':ini_hash',        $this->mValues['ini_hash']);
	    $aStmt->bindParam(':experiment_hash', $this->mValues['experiment_hash']);
	    $aStmt->bindParam(':running_tasks',   $this->mValues['running_tasks']);
	    $aStmt->bindParam(':status',          $this->mValues['status']);

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
					$this->logKeyChange($aKey, $aValue);
					$this->mValues[$aKey] = $aValue;
				}
			} else {
				// Disk is invalid, so let's update it with what we have in memory
				if($this->mLog != null) {
					$this->mLog->info('Context info from disk is invalid, replacing it with currently in use values.');
				}
				$this->save();
			}
		}

		return $aAreValuesDifferent;
	}

	private function logKeyChange($theKey, $theNewValue) {
		if($this->mLog != null) {
			$this->mLog->debug('context.' . $theKey . ' = ' . (is_array($theNewValue) ? 'Array' : $theNewValue) . ' (old=' . (is_array($this->mValues[$theKey]) ? 'Array' : $this->mValues[$theKey]) . ')');
		}
	}

	public function load() {
		$this->mValues = $this->getValuesFromDisk();
	}

	public function get($theKey) {
		$this->ensureKeyExists($theKey);
		$this->load();

		return $this->mValues[$theKey];
	}

	public function set($theKey, $theValue) {
		$this->ensureKeyExists($theKey);

		$this->logKeyChange($theKey, $theValue);
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
