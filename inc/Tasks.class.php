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

	public function writeTaskInfoFile($theTask) {
	    file_put_contents($theTask['info_file'], json_encode($theTask, JSON_PRETTY_PRINT));
	}

	public function enqueTask($theTask) {
		$aSql = "INSERT INTO tasks (creation_time, commit_hash, permutation_hash, data) VALUES (:creation_time, :commit_hash, :permutation_hash, :data)";
		$aStmt = $this->mDb->getPDO()->prepare($aSql);

		$aTaskSerialized = serialize($theTask);
		$aNow = time();

		$aStmt->bindParam(':creation_time', $aNow);
		$aStmt->bindParam(':commit_hash', $theTask['hash']);
		$aStmt->bindParam(':permutation_hash', $theTask['permutation']);
		$aStmt->bindParam(':data', $aTaskSerialized);

		$aOk = $aStmt->execute();
		return $aOk;
	}

	public function dequeueTask() {
		// Get a task from DB
		$aStmt = $this->mDb->getPDO()->prepare("SELECT * FROM tasks WHERE 1 ORDER BY creation_time ASC LIMIT 1");
		$aStmt->execute();
		$aDbTask = $aStmt->fetch(\PDO::FETCH_ASSOC);

		if($aDbTask === false) {
			throw new \Exception('Unable to dequeu task, tasks queue is probably empty.');
		}

		$aTask = @unserialize($aDbTask['data']);

		if($aTask === false) {
			throw new \Exception('Unable to unserialize dequeued task.');
		}

		// Remove that tasks from the queue
	    $aStmt = $this->mDb->getPDO()->prepare("DELETE FROM tasks WHERE id = :id");
	    $aStmt->bindParam(':id', $aDbTask['id']);
	    $aOk = $aStmt->execute();

		if(!$aOk) {
			throw new \Exception('Unable to delete task with id '.$aDbTask['id'].' from tasks queue.');
		}

		return $aTask;
	}

	public function queueSize() {
		$aStmt = $this->mDb->getPDO()->prepare("SELECT COUNT(*) AS num FROM tasks WHERE 1");
		$aStmt->execute();
		$aRow = $aStmt->fetch(\PDO::FETCH_ASSOC);

		return $aRow['num'];
	}

	public function isTaskFinished($theTaskInfo) {
	    $aTime = $theTaskInfo['exec_time_end'];
	    return $aTime != 0;
	}

	public function loadTask($theInfoFilePath) {
	    $aInfo = json_decode(file_get_contents($theInfoFilePath), true);
	    return $aInfo;
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
