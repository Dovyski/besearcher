<?php

namespace Besearcher;

/**
 * Control all things tasks, such as tasks queue, running tasks, etc.
 */
class Tasks {
	private $mDb;

	public function __construct(Db $theDb) {
		if($theDb == null) {
			throw new \Exception('A valid instance of DB class is required.');
		}
		$this->mDb = $theDb;
	}

	public function removeResult($theResultId) {
	    $aStmt = $this->mDb->getPDO()->prepare("DELETE FROM results WHERE id = :id");
		$aStmt->bindParam(':id', $theResultId);
	    $aOk = $aStmt->execute();

		return $aOk;
	}

	public function createResultEntryFromTask($theTask) {
		$aExistingResult = $this->getResultByHashes($theTask['experiment_hash'], $theTask['permutation_hash']);

		if($aExistingResult !== false) {
			// A result identical to this one already exists. Let's remove it to prevent
			// results with identical hashes.
			$aOk = $this->removeResult($aExistingResult['id']);
		}

		$aSql =
		"INSERT INTO
			results (
				id,
				cmd,
				cmd_return_code,
				log_file,
				log_file_tags,
				working_dir,
				experiment_hash,
				permutation_hash,
				params,
				creation_time,
				exec_time_start,
				exec_time_end,
				progress,
				running
			)
		VALUES (
			:id,
			:cmd,
			:cmd_return_code,
			:log_file,
			:log_file_tags,
			:working_dir,
			:experiment_hash,
			:permutation_hash,
			:params,
			:creation_time,
			:exec_time_start,
			:exec_time_end,
			:progress,
			:running
		)";

		$aStmt = $this->mDb->getPDO()->prepare($aSql);
		$aNow = time();
		$aZero = 0;
		$aSerializedTags = serialize(array());

		$aStmt->bindParam(':id', $theTask['id']);
		$aStmt->bindParam(':cmd', $theTask['cmd']);
		$aStmt->bindParam(':cmd_return_code', $theTask['cmd_return_code']);
		$aStmt->bindParam(':log_file', $theTask['log_file']);
		$aStmt->bindParam(':log_file_tags', $aSerializedTags);
		$aStmt->bindParam(':working_dir', $theTask['working_dir']);
		$aStmt->bindParam(':experiment_hash', $theTask['experiment_hash']);
		$aStmt->bindParam(':permutation_hash', $theTask['permutation_hash']);
		$aStmt->bindParam(':params', $theTask['params']);
		$aStmt->bindParam(':creation_time', $theTask['creation_time']);
		$aStmt->bindParam(':exec_time_start', $aNow);
		$aStmt->bindParam(':exec_time_end', $aZero);
		$aStmt->bindParam(':progress', $aZero);
		$aStmt->bindParam(':running', $aZero);

		$aOk = $aStmt->execute();
		return $aOk;
	}

	public function enqueueTask($theTask) {
		$aSql =
		"INSERT INTO
			tasks (
				cmd,
				log_file,
				working_dir,
				experiment_hash,
				permutation_hash,
				params,
				creation_time,
				priority
			)
		VALUES (
			:cmd,
			:log_file,
			:working_dir,
			:experiment_hash,
			:permutation_hash,
			:params,
			:creation_time,
			:priority
		)";

		$aStmt = $this->mDb->getPDO()->prepare($aSql);
		$aNow = time();
		$aPriority = 10;

		$aStmt->bindParam(':cmd', $theTask['cmd']);
		$aStmt->bindParam(':log_file', $theTask['log_file']);
		$aStmt->bindParam(':working_dir', $theTask['working_dir']);
		$aStmt->bindParam(':experiment_hash', $theTask['experiment_hash']);
		$aStmt->bindParam(':permutation_hash', $theTask['permutation_hash']);
		$aStmt->bindParam(':params', $theTask['params']);
		$aStmt->bindParam(':creation_time', $aNow);
		$aStmt->bindParam(':priority', $aPriority);

		$aOk = $aStmt->execute();
		return $aOk;
	}

	public function dequeueTask() {
		// Get a task from DB
		$aStmt = $this->mDb->getPDO()->prepare("SELECT * FROM tasks WHERE 1 ORDER BY priority ASC, creation_time ASC LIMIT 1");
		$aStmt->execute();
		$aTask = $aStmt->fetch(\PDO::FETCH_ASSOC);

		if($aTask === false) {
			throw new \Exception('Unable to dequeu task, tasks queue is probably empty.');
		}

		// Remove that tasks from the queue
	    $aStmt = $this->mDb->getPDO()->prepare("DELETE FROM tasks WHERE id = :id");
	    $aStmt->bindParam(':id', $aTask['id']);
	    $aOk = $aStmt->execute();

		if(!$aOk) {
			throw new \Exception('Unable to delete task with id '.$aTask['id'].' from tasks queue.');
		}

		return $aTask;
	}

	public function queueSize() {
		$aStmt = $this->mDb->getPDO()->prepare("SELECT COUNT(*) AS num FROM tasks WHERE 1");
		$aStmt->execute();
		$aRow = $aStmt->fetch(\PDO::FETCH_ASSOC);

		return $aRow['num'];
	}

	public function findEnquedTasks($theStart, $theHowMany) {
		$aTasksQueue = array();
		$theStart = $theStart + 0;
		$theHowMany = $theHowMany + 0;

		$aStmt = $this->mDb->getPDO()->prepare('SELECT * FROM tasks WHERE 1 ORDER BY priority ASC, creation_time ASC LIMIT ' . $theStart . ',' . $theHowMany);
        $aStmt->execute();

        while($aRow = $aStmt->fetch(\PDO::FETCH_ASSOC)) {
            $aTasksQueue[] = $aRow;
        }

		return $aTasksQueue;
	}

	public function findRunningTasks() {
		$aStmt = $this->mDb->getPDO()->prepare("SELECT * FROM results WHERE running = 1");
		$aStmt->execute();
		$aTasks = array();

		while($aRow = $aStmt->fetch(\PDO::FETCH_ASSOC)) {
			$aTasks[] = $aRow;
		}

		return $aTasks;
	}

	public function isResultFinished($theResult) {
		$aResult = null;

		if(is_array($theResult)) {
			$aResult = $theResult;
		} else {
			$aStmt = $this->mDb->getPDO()->prepare("SELECT running,exec_time_end FROM results WHERE id = :id");
			$aStmt->bindParam(':id', $theResult);
			$aStmt->execute();
			$aResult = $aStmt->fetch(\PDO::FETCH_ASSOC);
		}

	    $aFinished = $aResult['running'] == 0 && $aResult['exec_time_end'] != 0;
	    return $aFinished;
	}

	public function getResultById($theResultId) {
		$aStmt = $this->mDb->getPDO()->prepare("SELECT * FROM results WHERE id = :id");
		$aStmt->bindParam(':id', $theResultId);
		$aStmt->execute();

		$aResult = $aStmt->fetch(\PDO::FETCH_ASSOC);

	    return $aResult;
	}

	public function getResultByHashes($theExperimentHash, $thePermutationHash) {
		$aStmt = $this->mDb->getPDO()->prepare("SELECT * FROM results WHERE experiment_hash = :experiment_hash AND permutation_hash = :permutation_hash");
		$aStmt->bindParam(':experiment_hash', $theExperimentHash);
		$aStmt->bindParam(':permutation_hash', $thePermutationHash);
		$aStmt->execute();

		$aResult = $aStmt->fetch(\PDO::FETCH_ASSOC);

	    return $aResult;
	}

	public function findResults() {
		$aStmt = $this->mDb->getPDO()->prepare("SELECT * FROM results WHERE 1");
		$aStmt->execute();

		$aResults = array();

		while($aResult = $aStmt->fetch(\PDO::FETCH_ASSOC)) {
			$aResults[] = $aResult;
		}

	    return $aResults;
	}

	public function updateResult($theId, $theKeyValuePairs) {
		$aOk = $this->mDb->update('results', $theId, $theKeyValuePairs);
		return $aOk;
	}

	private function castArrayEntriesToInt($theArray) {
		$aCastedIds = array();

		foreach($theArray as $aId) {
			$aCastedIds[] = $aId + 0;
		}

		return $aCastedIds;
	}

	/**
	 * Change the order of some tasks in the queue of tasks. The informed
	 * taskes will be moved to the begining of the queue, so they are more likely
	 * to be executed by Besearcher.
	 *
	 * @param  array $theTaskIds array with the ids of the tasks to be prioritized.
	  */
	public function updateTasksPriority($theTaskIds, $thePriority = 0) {
		if(count($theTaskIds) == 0) {
			throw new Exception('Nothing has been selected for update.');
		}

	    $aIds = $this->castArrayEntriesToInt($theTaskIds);
	    $aStmt = $this->mDb->getPDO()->prepare("UPDATE tasks SET priority = :priority WHERE id IN (".implode(',', $aIds).")");
		$aStmt->bindParam(':priority', $thePriority);
	    $aStmt->execute();
	}

	public function removeTasksFromQueue($theTaskIds) {
		if(count($theTaskIds) == 0) {
			throw new Exception('Nothing has been selected for removal.');
		}

	    $aIds = $this->castArrayEntriesToInt($theTaskIds);
	    $aStmt = $this->mDb->getPDO()->prepare("DELETE FROM tasks WHERE id IN (".implode(',', $aIds).")");
	    $aStmt->execute();
	}
}

?>
