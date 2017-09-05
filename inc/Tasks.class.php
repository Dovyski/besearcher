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

	public function createResultEntryFromTask($theTask) {
		$aSql =
		"INSERT INTO
			results (
				id,
				cmd,
				cmd_return_code,
				log_file,
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

		$aStmt->bindParam(':id', $theTask['id']);
		$aStmt->bindParam(':cmd', $theTask['cmd']);
		$aStmt->bindParam(':cmd_return_code', $theTask['cmd_return_code']);
		$aStmt->bindParam(':log_file', $theTask['log_file']);
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
				creation_time
			)
		VALUES (
			:cmd,
			:log_file,
			:working_dir,
			:experiment_hash,
			:permutation_hash,
			:params,
			:creation_time
		)";

		$aStmt = $this->mDb->getPDO()->prepare($aSql);
		$aNow = time();

		$aStmt->bindParam(':cmd', $theTask['cmd']);
		$aStmt->bindParam(':log_file', $theTask['log_file']);
		$aStmt->bindParam(':working_dir', $theTask['working_dir']);
		$aStmt->bindParam(':experiment_hash', $theTask['experiment_hash']);
		$aStmt->bindParam(':permutation_hash', $theTask['permutation_hash']);
		$aStmt->bindParam(':params', $theTask['params']);
		$aStmt->bindParam(':creation_time', $aNow);

		$aOk = $aStmt->execute();
		return $aOk;
	}

	public function dequeueTask() {
		// Get a task from DB
		$aStmt = $this->mDb->getPDO()->prepare("SELECT * FROM tasks WHERE 1 ORDER BY creation_time ASC LIMIT 1");
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

	public function isResultFinished($theResultId) {
		$aStmt = $this->mDb->getPDO()->prepare("SELECT running,exec_time_end FROM results WHERE id = :id");
		$aStmt->bindParam(':id', $theResultId);
		$aStmt->execute();

		$aResult = $aStmt->fetch(\PDO::FETCH_ASSOC);

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

	public function markResultAsFinished($theId, $theCmdReturnCode, $theExecTimeEnd) {
		$aStmt = $this->mDb->getPDO()->prepare("UPDATE results SET running = 0, progress = 1.0, cmd_return_code = :cmd_return_code, exec_time_end = :exec_time_end WHERE id = :id");
		$aStmt->bindParam(':cmd_return_code', $theCmdReturnCode);
		$aStmt->bindParam(':exec_time_end', $theExecTimeEnd);
	    $aStmt->bindParam(':id', $theId);

		$aOk = $aStmt->execute();
		return $aOk;
	}

	public function markResultAsRunning($theId, $theExecTimeStart) {
		$aStmt = $this->mDb->getPDO()->prepare("UPDATE results SET running = 1, exec_time_start = :exec_time_start WHERE id = :id");
		$aStmt->bindParam(':exec_time_start', $theExecTimeStart);
	    $aStmt->bindParam(':id', $theId);

		$aOk = $aStmt->execute();
		return $aOk;
	}

	function calculateTaskProgressFromTags(array $theBesearcherLogTags) {
	    $aProgresses = $this->findTagsByType($theBesearcherLogTags, BESEARCHER_TAG_TYPE_PROGRESS);
	    $aCount = count($aProgresses);

	    $aProgress =  $aCount > 0 ? $aProgresses[$aCount - 1]['data'] : -1;
	    return $aProgress;
	}

	public function aggredateTaskInfos($theTaskJsonFiles) {
	    $aInfos = array();

	    foreach($theTaskJsonFiles as $aFile) {
	        $aInfo = $this->loadTask($aFile);
	        $aPermutation = $aInfo['permutation'];

	        // Find special marks in the log file that inform
	        // Besearcher about things
	        $aTags = $this->handleBesearcherLogTags($aInfo);

	        $aInfos[$aPermutation] = array(
	            'commit'          => $aInfo['hash'],
	            'commit_message'  => @$aInfo['message'],
	            'permutation'     => $aPermutation,
	            'creation_time'   => @$aInfo['creation_time'],
	            'exec_time_start' => @$aInfo['exec_time_start'],
	            'exec_time_end'   => @$aInfo['exec_time_end'],
	            'params'          => $aInfo['params'],
	            'cmd'             => $aInfo['cmd'],
	            'progress'        => $this->calculateTaskProgressFromTags($aTags),
	            'meta'            => $aTags,
	            'raw'             => $aInfo
	        );
	    }

	    return $aInfos;
	}

	public function findTasksInfos($theDataDir) {
	    $aData = array();
	    $aTasks = scandir($theDataDir);

	    foreach($aTasks as $aItem) {
	        $aPath = $theDataDir . DIRECTORY_SEPARATOR . $aItem;

	        if($aItem[0] != '.' && is_dir($aPath)) {
	            $aFiles = glob($aPath . DIRECTORY_SEPARATOR . '*.json');
	            $aData[$aItem] = $this->aggredateTaskInfos($aFiles);
	        }
	    }

	    return $aData;
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
	public function prioritizeTasksInQueue($theTaskIds) {
		if(count($theTaskIds) == 0) {
			throw new Exception('Nothing has been selected to prioritize.');
		}

	    $aIds = $this->castArrayEntriesToInt($theTaskIds);
	    $aStmt = $this->mDb->getPDO()->prepare("UPDATE tasks SET creation_time = 0 WHERE id IN (".implode(',', $aIds).")");
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

	public function handleBesearcherLogTags($theTaskInfo, $theUseCache = true) {
		$aTags = array();

		if($this->isTaskFinished($theTaskInfo) && $theUseCache) {
			// Task has finished, it might be a cached version of the log file.
			$aCacheFilePath = $theTaskInfo['log_file'] . BESEARCHER_CACHE_FILE_EXT;

			if(file_exists($aCacheFilePath)) {
				$aTags = unserialize(file_get_contents($aCacheFilePath));
			} else {
				$aTags = $this->findBesearcherLogTags($theTaskInfo['log_file']);
				file_put_contents($aCacheFilePath, serialize($aTags));
			}
		} else {
			$aTags = $this->findBesearcherLogTags($theTaskInfo['log_file']);
		}

		return $aTags;
	}

	function findBesearcherLogTags($theLogFilePath) {
	    $aRet = array();
	    $aFile = @fopen($theLogFilePath, 'r');

	    if (!$aFile) {
	        return $aRet;
	    }

	    $aLimit = strlen(BESEARCHER_TAG);

	    while (($aLine = fgets($aFile)) !== false) {
	        if(!empty($aLine) && $aLine != '') {
	            $aMarker = substr($aLine, 0, $aLimit);

	            if($aMarker == BESEARCHER_TAG) {
	                $aText = substr($aLine, $aLimit);
	                $aRet[] = json_decode(trim($aText), true);
	            }
	        }
	    }

	    fclose($aFile);
	    return $aRet;
	}

	function findTagsByType(array $theBesearcherLogTags, $theType) {
	    $aRet = array();

	    foreach($theBesearcherLogTags as $aItem) {
	        if($aItem['type'] == $theType) {
	            $aRet[] = $aItem;
	        }
	    }

	    return $aRet;
	}

}

?>
