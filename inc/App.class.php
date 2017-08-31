<?php

namespace Besearcher;

class App {
	private $mDb;
	private $mLog;
	private $mTasks;
	private $mContext;
	private $mINIPath;
	private $mINIValues;
	private $mActive;

	private function performConfigHotReload() {
	    $aContentHash = md5(file_get_contents($this->mINIPath));

	    if($aContentHash != $this->mContext->get('ini_hash')) {
	        $this->mLog->info('Content of INI file has changed. Reloading it.');

	        $this->mContext->set('ini_hash', $aContentHash);
	        $this->loadINI($this->mINIPath);
	    }
	}

	/**
	  * Get a config value from the INI file.
	  *
	  * @param  string $theKey      Key that represents an entry in the INI file.
	  * @param  mixed $theDefault   Value to be returned if nothing is found.
	  * @return mixed               Value of the informed key.
	  */
	private function config($theKey, $theDefault = null) {
	    $aINI = $this->mINIValues;
	    $aRet = $theDefault;

	    if(isset($aINI[$theKey])) {
	        $aRet = $aINI[$theKey];
	    }

	    return $aRet;
	}

	private function loadINI($theINIPath) {
		if(!file_exists($theINIPath)) {
			throw new \Exception("Informed INI file is invalid: '" . $theINIPath . "'");
		}

		$this->mINIPath = $theINIPath;
	    $this->mINIValues = parse_ini_file($this->mINIPath, true);

	    // Interpret the special syntax in the INI file
	    // to expand expressions, e.g. 0..10:1 becomes 0,1,2,3,..,10
	    $this->mINIValues = $this->processSpecialExpressions($this->mINIValues);
	}

	public function init($thePathINIFile, $thePathLogFile) {
		// Load INI file because we need the bare minimum to start everything up.
		$this->loadINI($thePathINIFile);

		$this->mLog = new Log($thePathLogFile);
		$this->mLog->setLevel($this->config('log_level'));
		$this->mLog->info('Besearcher starting up. What a great day for science!');

		// Get all life support components up and running
		$aDbPath = $this->config('data_dir') . DIRECTORY_SEPARATOR . BESEARCHER_DB_FILE;
		$this->mDb = new Db($aDbPath);
		$this->mContext = new Context($this->mDb, $this->mLog, array('status' => BESEARCHER_STATUS_INITING));
		$this->mTasks = new Tasks($this->mDb);

		// Load context data from disk and merge it with in-memory data
		$this->mContext->sync();

		// It is time to proceed with the initialization of everything else.
		$this->performHotReloadProcedures();
		$this->mActive = true;

		$this->printSummary();
	}

	public function shutdown() {
		$this->mLog->info('Besearcher is done. Over and out!');
		$this->mLog->shutdown();
		$this->mDb->shutdown();
	}

	public function run() {
		while($this->mActive) {
		    $this->mActive = $this->step();
		    $this->performHotReloadProcedures();

		    // Wait for the next check
		    $aWaitTime = $this->config('refresh_interval', 1);
		    sleep($aWaitTime);

		    if($this->mContext->get('status') == BESEARCHER_STATUS_STOPPING && $this->countRunningTasks() == 0) {
		        $this->mLog->info('All running tasks finnished, proceeding with requested shutdown.');

		        // Reset the status of the context in the disk
		        $this->mContext->set('status', BESEARCHER_STATUS_STOPED);
		        $this->mContext->save();

		        // Terminate the party
		        $this->mActive = false;
		    }
		}
	}

	private function execTaskCommand($theTask, $theParallel) {
	    $aCmd = $theTask['cmd'];
	    $aLogFile = $theTask['log_file'];
	    $aInfoFile = $theTask['info_file'];

	    $aCmdTemplate = '%s > "%s"';
	    $aFinalCmd = sprintf($aCmdTemplate, $aCmd, $aLogFile);

	    $this->mLog->debug($aFinalCmd);

	    if($theParallel) {
	        $aFinalCmd = sprintf('start "Job" /b cmd.exe /c "%s "%s" "%s" "%s""', RUNNER_CMD, $aCmd, $aLogFile, $aInfoFile);
	    }

	    pclose(popen($aFinalCmd, 'r'));
	}

	private function countRunningTasks() {
	    $aTaskCmdApp = $this->config('task_cmd_list_name', '');
	    $aCount = 0;
	    $aLines = array();
	    $aProcesses = exec("tasklist", $aLines);

	    foreach($aLines as $aProcess) {
	        if(stripos($aProcess, $aTaskCmdApp) !== FALSE) {
	            $aCount++;
	        }
	    }

	    return $aCount;
	}

	private function shouldWaitForUnfinishedTasks($theMaxParallel) {
	    $aShouldWait = false;
	    $aCount = $this->countRunningTasks();

	    // If there are tasks running, we must wait if we are at full capacity
	    if($aCount != 0 && $aCount >= $theMaxParallel) {
	        $aShouldWait = true;
	    }

	    return $aShouldWait;
	}

	private function countEnquedTasks() {
	    $aStmt = $this->mDb->getPDO()->prepare("SELECT COUNT(*) AS num FROM tasks WHERE 1");
	    $aStmt->execute();
	    $aRow = $aStmt->fetch(\PDO::FETCH_ASSOC);

	    return $aRow['num'];
	}

	private function processQueuedTasks() {
	    $aSpawnedNewTask = false;

	    $aCmdName = $this->config('task_cmd_list_name', '');
	    $aMaxParallelJobs = $this->config('max_parallel_tasks', 1);

	    $aWait = $this->shouldWaitForUnfinishedTasks($aMaxParallelJobs);

	    if(!$aWait && $this->countEnquedTasks() > 0) {
	        // There is room for another job. Let's spawn it.
	        $this->mLog->debug("Dequeueing task");
	        $aTask = $this->mTasks->dequeueTask();
	        $this->runTask($aTask, $aMaxParallelJobs);

	        $aSpawnedNewTask = true;
	    }

	    return $aSpawnedNewTask;
	}

	private function setLastKnownCommit($theHash) {
		if($theHash == $this->mContext->get('last_commit')) {
			return;
		}
	    $this->mContext->set('last_commit', $theHash);
	    $this->mLog->info("Last known commit changed to " . $theHash);
	}

	private function performGitPull($theWatchDir, $theGitExe) {
	    $aEntries = array();

	    $this->mLog->debug("Updating repo with git pull");
	    $aOutput = exec('cd ' . $theWatchDir . ' & ' . $theGitExe . ' pull', $aEntries);
	    $this->mLog->debug(implode("\n", $aEntries));
	}

	private function findCommitsFromGitLog($theWatchDir, $theGitExe) {
	    $aCommits = array();
	    $aPrettyFormat = '--pretty=format:"%HBESEARCHER-CONTENT-BREAK%BBESEARCHER-CONTENT-END"';

	    $aLines = array();
	    $aGitLog = exec('cd ' . $theWatchDir . ' & ' . $theGitExe . ' --no-pager log ' . $aPrettyFormat, $aLines);
	    $aContent = '';

	    foreach($aLines as $aLine) {
	        if(stripos($aLine, 'BESEARCHER-CONTENT-END') !== false) {
	            // We have enough data for a single commit
	            $aParts = explode('BESEARCHER-CONTENT-BREAK', $aContent);
	            if(count($aParts) == 2) {
	                $aCommits[] = array('hash' => $aParts[0], 'message' => $aParts[1]);
	            }
	            $aContent = '';
	        } else {
	            $aContent .= $aLine . ' ';
	        }
	    }

	    return $aCommits;
	}

	private function findNewCommits($theWatchDir, $theGitExe, $theLastCommitHash) {
	    $aNewCommits = array();
	    $aPerformPull = $this->config('perform_git_pull', false);

	    if($aPerformPull) {
	        $this->performGitPull($theWatchDir, $theGitExe);
	    }

	    $aCommits = $this->findCommitsFromGitLog($theWatchDir, $theGitExe);
	    $aShouldInclude = false;

	    for($i = count($aCommits) - 1; $i >= 0; $i--) {
	        $aCommit = $aCommits[$i];
	        $aHash = $aCommit['hash'];

	        if($aHash == $theLastCommitHash || $theLastCommitHash == '') {
	            $aShouldInclude = true;
	        }

	        if($aShouldInclude && $aHash != $theLastCommitHash) {
	            $aNewCommits[] = $aCommit;
	        }
	    }

	    return $aNewCommits;
	}

	private function createTask($theCommitHash, $theCommitMessage, $thePermutation) {
	    $aUid = $theCommitHash . '-' . $thePermutation['hash'];

	    $aDataDir = $this->config('data_dir');
	    $aTaskDir = $aDataDir . DIRECTORY_SEPARATOR . $theCommitHash . DIRECTORY_SEPARATOR;
	    $aLogFile = $aTaskDir . $aUid . '.log';
	    $aInfoFile = $aTaskDir . $aUid . '.json';

	    $aTask = array(
	        'cmd' => $thePermutation['cmd'],
	        'cmd_return_code' => -1,
	        'log_file' => $aLogFile,
	        'info_file' => $aInfoFile,
	        'working_dir' => $this->config('task_cmd_working_dir'),
	        'hash' => $theCommitHash,
	        'message' => $theCommitMessage,
	        'permutation' => $thePermutation['hash'],
	        'params' => $thePermutation['params'],
	        'creation_time' => time(),
	        'exec_time_start' => 0,
	        'exec_time_end' => 0
	    );

	    return $aTask;
	}

	private function replaceTolken($theString, $theSearches, $theReplaces, $theIdx, & $theOutputs, $theParamsString = '') {
	    if($theIdx >= count($theSearches)) {
	        // Nothing else to replace, we found the final string.
	        $theOutputs[] = array('text' => $theString, 'params' => substr($theParamsString, 0, strlen($theParamsString) - 2));
	    } else {
	        // Still work to do.
	        $aKey = $theSearches[$theIdx];
	        $aSearch = '{@' . $aKey . '}';
	        $aReplace = $theReplaces[$theIdx];

	        if(is_array($aReplace)) {
	            foreach($aReplace as $aReplacePiece) {
	                $aString = str_ireplace($aSearch, $aReplacePiece, $theString);
	                $aParamsString = $theParamsString . $aKey . '=' . $aReplacePiece . ', ';
	                $this->replaceTolken($aString, $theSearches, $theReplaces, $theIdx + 1, $theOutputs, $aParamsString);
	            }
	        } else {
	            $aString = str_ireplace($aSearch, $aReplace, $theString);
	            $aParamsString = $theParamsString . $aKey . '=' . $aReplace . ', ' ;
	            $this->replaceTolken($aString, $theSearches, $theReplaces, $theIdx + 1, $theOutputs, $aParamsString);
	        }
	    }
	}

	private function checkNonReplacedValues($thePermutations) {
	    if(count($thePermutations) > 0) {
	        foreach($thePermutations as $aItem) {
	            if(preg_match_all('/.*\{@.*\}/i', $aItem['cmd'])) {
	                throw new \Exception('Unreplaced value in command: ' . $aItem['cmd']);
	            }
	        }
	    }
	}

	private function generateTaskCmdPermutations() {
	    $aPermutations = array();
	    $aTaskCmd = $this->config('task_cmd');

	    if(!isset($aTaskCmd)) {
	        throw new \Exception('Empty or invalid "task_cmd" directive provided in INI file.');
	    }

	    $aTaskCmdParams = isset($this->mINIValues['task_cmd_params']) ? $this->mINIValues['task_cmd_params'] : array();

	    if(count($aTaskCmdParams) > 0) {
	        $aCmds = array();
	        $this->replaceTolken($aTaskCmd, array_keys($aTaskCmdParams), array_values($aTaskCmdParams), 0, $aCmds);

	        if(count($aCmds) > 0) {
	            foreach($aCmds as $aCmd) {
	                $aPermutations[] = array('cmd' => $aCmd['text'], 'hash' => md5($aCmd['text']), 'params' => $aCmd['params']);
	            }
	        }
	    } else {
	        $aPermutations[] = array('cmd' => $aTaskCmd, 'hash' => md5($aTaskCmd), 'params' => 'NONE');
	    }

	    $this->checkNonReplacedValues($aPermutations);
	    return $aPermutations;
	}

	private function createTasksFromCommit($theHash, $theMessage) {
	    $aTasks = array();
	    $aPermutations = $this->generateTaskCmdPermutations();

	    if(count($aPermutations) > 0) {
	        foreach($aPermutations as $aPermutation) {
	            $aTasks[] = $this->createTask($theHash, $theMessage, $aPermutation);
	        }
	    }

	    return $aTasks;
	}

	private function runTask($theTask, $theMaxParallel) {
	    $aSkipPerformedTasks = $this->config('skip_performed_tasks', false);
	    $aTaskAlreadyPerformed = false;

	    if(file_exists($theTask['info_file'])) {
	        $aTaskInfo = $this->mTasks->loadTask($theTask['info_file']);
	        $aTaskAlreadyPerformed = $this->mTasks->isTaskFinished($aTaskInfo);
	    }

	    if($aSkipPerformedTasks && $aTaskAlreadyPerformed) {
	        // It seems the task at hand already has already
	        // been executed in the past. Since we were instructed
	        // to skip already performed tasks, we stop here.
	        $this->mLog->warn('Skipping already performed task (hash=' . $theTask['hash'] . ', permutation=' . $theTask['permutation'] . ')');
	        return;
	    }

	    // Update exec time
	    $theTask['exec_time_start'] = time();

	    $this->mLog->info('Running task (hash=' . $theTask['hash'] . ', permutation=' . $theTask['permutation'] . ')');
	    $this->mTasks->writeTaskInfoFile($theTask);

	    $aParallel = $theMaxParallel > 1;
	    $this->execTaskCommand($theTask, $aParallel);
	}

	private function createTaskResultsFolder($theCommitHash) {
	    $aDataDir = $this->config('data_dir');
	    $aCommitDir = $aDataDir . DIRECTORY_SEPARATOR . $theCommitHash;

	    if(!file_exists($aCommitDir)) {
	        mkdir($aCommitDir);
	    }
	}

	private function handleNewCommit($theHash, $theMessage) {
	    $aTasks = $this->createTasksFromCommit($theHash, $theMessage);

	    // Create a folder to house the results of the tasks
	    // originated from the present commit
	    $this->createTaskResultsFolder($theHash);

	    if(count($aTasks) > 0) {
	        foreach($aTasks as $aTask) {
				$this->mLog->debug("Enqueing task " . $aTask['hash'] . '-' . $aTask['permutation']);
	            $this->mTasks->enqueTask($aTask);
	        }
	    }
	}

	private function textHasSkipToken($theMessage) {
	    $aMatches = array();
	    $aHits = preg_match_all(BESEARCHER_COMMIT_SKIP_TOKEN, $theMessage, $aMatches);

	    return $aHits !== false && $aHits > 0;
	}

	private function processNewCommits() {
	    $aWatchDir   = $this->config('watch_dir');
	    $aGitExe     = $this->config('git');

	    $aTasks      = $this->findNewCommits($aWatchDir, $aGitExe, $this->mContext->get('last_commit'));
	    $aLastHash   = '';
	    $aTasksCount = count($aTasks);

	    if($aTasksCount > 0) {
	        foreach($aTasks as $aCommit) {
	            $aHash     = $aCommit['hash'];
	            $aMessage  = $aCommit['message'];
	            $aLastHash = $aHash;

	            if($this->textHasSkipToken($aMessage)) {
	                $this->mLog->info("Skipping commit due to skip token (hash=" . $aHash . ", msg=" . trim($aMessage) . ")");
	                continue;
	            }

	            $this->mLog->info("Processing new commit (hash=" . $aHash . ", msg=" . trim($aMessage) . ")");
	            $this->handleNewCommit($aHash, $aMessage);
	        }

	        if($aLastHash != '') {
	            $this->setLastKnownCommit($aLastHash);
	        }
	    }

	    return $aTasksCount > 0;
	}

	private function monitorRunningTasks() {
		$aTasksBefore = $this->mContext->get('running_tasks');
	    $aTasksNow = $this->countRunningTasks();

	    if($aTasksBefore != $aTasksNow) {
	        if($aTasksNow > 0) {
	            $this->mLog->info('Tasks running now: ' . $aTasksNow);
	        }

	        $this->mContext->set('running_tasks', $aTasksNow);

	        if($aTasksNow == 0 && $aTasksBefore > 0) {
	            $this->mLog->info('All runnings tasks finished!');
	            $this->printSummary();
	        }
	    }
	}

	private function processGitCommits() {
	    $aPullInterval = $this->config('git_pull_interval', 10);
	    $aShouldPull = time() - $this->mContext->get('time_last_pull') >= $aPullInterval;

	    if($aShouldPull) {
	        $aAnyNewTask = $this->processNewCommits();
	        $this->mContext->set('time_last_pull', time());
	    }
	}

	private function step() {
	    if($this->mContext->get('status') == BESEARCHER_STATUS_RUNNING) {
	        $this->processGitCommits();
	        // The config INI file may have changed with the pull, so
	        // let's check current config params
	        $this->performConfigHotReload();

			$aProcessQueue = true;
			while($aProcessQueue) {
				$aProcessQueue = $this->processQueuedTasks();
			}
	    }

	    $this->monitorRunningTasks();
	    return true;
	}

	// License: https://stackoverflow.com/a/38871855/29827
	private function array_unique_combinations($in, $minLength = 1, $max = 2000) {
	    $count = count($in);
	    $members = pow(2, $count);
	    $return = array();
	    for($i = 0; $i < $members; $i ++) {
	        $b = sprintf("%0" . $count . "b", $i);
	        $out = array();
	        for($j = 0; $j < $count; $j ++) {
	            $b{$j} == '1' and $out[] = $in[$j];
	        }

	        count($out) >= $minLength && count($out) <= $max and $return[] = $out;
	    }
	    return $return;
	}

	private function expandPermExpression($theMatches) {
	    $aMinAmount = $theMatches[1][0] + 0;
	    $aElements = $theMatches[2][0];

	    // Ensure at least one element will be included in the result
	    $aMinAmount = $aMinAmount <= 0 ? 1 : $aMinAmount;
	    $aList = explode(',', $aElements);

	    if($aList === false || $aMinAmount < 1) {
	        // TODO: generate a log entry?
	        // Wrong "perm" expression in INI file.
	        exit(2);
	    }

	    $aCombinations = $this->array_unique_combinations($aList, $aMinAmount);
	    $aReturn = array();

	    foreach($aCombinations as $aItems) {
	        $aReturn[] = implode(',', $aItems);
	    }

	    return $aReturn;
	}

	private function expandStartEndIncExpression($theMatches) {
	    $aRet = array();

	    $aStart = $theMatches[1][0] + 0;
	    $aEnd = $theMatches[2][0] + 0;
	    $aInc = $theMatches[3][0] + 0;

	    // TODO: check for infinite loops
	    for($i = $aStart; $i <= $aEnd; $i += $aInc) {
	        $aRet[] = $i;

	        if($i + $aInc > $aEnd) {
	            break;
	        }
	    }

	    return $aRet;
	}

	private function processSpecialExpressions($theINIValues) {
	    $aCount = count($theINIValues);

	    if(!is_array($theINIValues) || $aCount == 0) {
	        return $theINIValues;
	    }

	    if($aCount == 1) {
	        // Arrays with a single elements are expandable
	        $aValue = reset($theINIValues);
	        $aMatches = array();

	        // Check for expressions like "0..10:1"
	        if(preg_match_all(INI_EXP_START_END_INC, $aValue, $aMatches)) {
	            $theINIValues = $this->expandStartEndIncExpression($aMatches);

	        } else if(preg_match_all(INI_PERM, $aValue, $aMatches)) {
	            $theINIValues = $this->expandPermExpression($aMatches);
	        }
	    } else {
	        // There is more than one element in the array,
	        // so we must check each one.
	        foreach($theINIValues as $aKey => $aValue) {
	            if(is_array($aValue)) {
	                $theINIValues[$aKey] = $this->processSpecialExpressions($aValue);
	            }
	        }
	    }

	    return $theINIValues;
	}

	private function prepareTaskCommandFileExists() {
	    $aResult = false;
	    $aPreTaskCmd = $this->config('setup_cmd', '');

	    if($aPreTaskCmd != '') {
	        $aDataDir = $this->config('data_dir');
	        $aPrepareFilePath = $aDataDir . DIRECTORY_SEPARATOR . BESEARCHER_SETEUP_FILE;
	        $aResult = file_exists($aPrepareFilePath);
	    }

	    return $aResult;
	}

	/**
	 * Informs about the result of running the prepare task command.
	 *
	 * @return mixed               return <code>false</code> if the command was not executed, or an integer containing the value the command returned.
	 */
	private function getPrepareTaskCommandResult() {
	    $aResult = false;
	    $aPreTaskCmd = $this->config('setup_cmd', '');

	    if($aPreTaskCmd != '') {
	        $aDataDir = $this->config('data_dir');
	        $aPrepareFilePath = $aDataDir . DIRECTORY_SEPARATOR . BESEARCHER_SETEUP_FILE;
	        $aResult = @file_get_contents($aPrepareFilePath);

	        $aResult = trim($aResult);
	        $aResult = $aResult == '' ? false : ($aResult + 0);
	    }

	    return $aResult;
	}

	private function setStatus($theValue, $theLogMessage = '', $theLogType = Log::INFO) {
	    if($this->mContext->get('status') == $theValue) {
	        return;
	    }

	    $this->mLog->say('App status changed to ' . $theValue . '. ' . ($theLogMessage != '' ? 'Note: ' . $theLogMessage : ''), $theLogType);
	    $this->mContext->set('status', $theValue);
	}

	private function runPrepareTaskCommand() {
	    $aDataDir = $this->config('data_dir');
	    $aPrepareFilePath = $aDataDir . DIRECTORY_SEPARATOR . BESEARCHER_SETEUP_FILE;
	    $aPrepareCmdLogPath = $aDataDir . DIRECTORY_SEPARATOR . BESEARCHER_SETUP_LOG_FILE;
	    $aPreTaskCmd = $this->config('setup_cmd', '');

	    $this->mLog->info('Command found in "setup_cmd", executing it. Command output will be in: ' . $aPrepareCmdLogPath);

	    if($aPreTaskCmd != '') {
	        // Create empty file to inform the command is running
	        file_put_contents($aPrepareFilePath, '');

	        $this->setStatus(BESEARCHER_STATUS_WAITING_SETUP);

	        $aCmd = $aPreTaskCmd . ' > "'.$aPrepareCmdLogPath.'"';
	        $aReturn = -1;
	        $this->mLog->debug('Running "setup_cmd": ' . $aCmd);
	        system($aCmd, $aReturn);

	        file_put_contents($aPrepareFilePath, $aReturn);
	        $this->mLog->debug('setup_cmd finished (returned '.$aReturn.')');
	    } else {
	        $this->mLog->error('Empty setup_cmd, unable to run it');
	    }
	}

	private function checkPrepareTaskCommandProcedures() {
	    $aHasPrepareCmd = $this->config('setup_cmd', '') != '';

	    if(!$aHasPrepareCmd) {
	        // Unpause the app in case it was paused and the INI file changed
	        // and is now configured to not have any pre task cmd.
	        $this->setStatus(BESEARCHER_STATUS_RUNNING);
	        return;
	    }

	    if($this->prepareTaskCommandFileExists()) {
	        // pre task command is running or have finished
	        $aPrepareResult = $this->getPrepareTaskCommandResult();

	        if($aPrepareResult === false) {
	            // We have a prepare task, but it is not finished yet. Halt
	            // all task commands.
	            $this->setStatus(BESEARCHER_STATUS_WAITING_SETUP);

	        } else if($aPrepareResult != 0) {
	            throw new \Exception('Command in "setup_cmd" returned error (return='.$aPrepareResult.')');

	        } else if($aPrepareResult == 0 && ($this->mContext->get('status') == BESEARCHER_STATUS_WAITING_SETUP || $this->mContext->get('status') == BESEARCHER_STATUS_INITING)) {
	            $this->setStatus(BESEARCHER_STATUS_RUNNING, 'command in "setup_cmd" finished successfully.');
	        }
	    } else {
	        // pre task command has not started yet.
	        $this->runPrepareTaskCommand();
	    }
	}

	private function ensureLastCommitIsNotEmpty() {
	    $aLastCommit = $this->mContext->get('last_commit');

	    // If we don't have any information regarding the last commit, we use
	    // the one provided in the ini file.
	    if(empty($aLastCommit)) {
	        $aLastCommit = $this->config('start_commit_hash', '');
	        $this->mLog->info("Replacing last known commit with info from INI: " . $aLastCommit);
			$this->setLastKnownCommit($aLastCommit);
	    }
	}

	private function performHotReloadProcedures() {
	    $this->performConfigHotReload();
	    $this->ensureLastCommitIsNotEmpty();
	    $this->checkPrepareTaskCommandProcedures();
	}

	private function printSummary() {
	    $aCountRunningTasks = $this->mContext->get('running_tasks');
	    $aCountEnquedTasks = $this->countEnquedTasks();
		$aStatus = $this->mContext->get('status');

	    $this->mLog->info('Running tasks: '. $aCountRunningTasks . ', queued tasks: ' . $aCountEnquedTasks . ', status: ' . $aStatus);
	}

	public function getLogger() {
		return $this->mLog;
	}
}
?>
