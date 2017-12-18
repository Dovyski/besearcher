<?php

namespace Besearcher;

class App {
	private $mDb;
	private $mLog;
	private $mTasks;
	private $mContext;
	private $mControl;
	private $mINIPath;
	private $mINIValues;
	private $mRunningTasksCount;
	private $mActive;
	private $mNextCronJob;

	/**
	 * Invoked when a given task that was running finished.
	 */
	private function onTaskFinishedRunning() {
		$this->updateAnalytics();
	}

	/**
	 * Invoked when all running tasks finished running. More tasks could
	 * be on the queue, however the ones that were running finished.
	 */
	private function onAllRunningTasksFinished() {
	}

	private function performConfigHotReload() {
	    $aContentHash = md5(file_get_contents($this->mINIPath));

	    if($aContentHash != $this->mContext->get('ini_hash')) {
	        $this->mLog->info('Content of INI file has changed. Reloading it.');

	        $this->mContext->set('ini_hash', $aContentHash);
	        $this->loadINI($this->mINIPath);
			$this->mLog->setLevel($this->config('log_level'));

			$aConfigExperimentHash = $this->config('experiment_hash');
			if(!empty($aConfigExperimentHash) && $aConfigExperimentHash != $this->mContext->get('experiment_hash')) {
				$this->mLog->warn('It is not possible to change "experiment_hash" in the INI file while besearcher is running. Stop then start besearcher again to load the new experiment_hash value.');
			}
	    }
	}

	/**
	  * Get a config value from the INI file.
	  *
	  * @param  string $theKey      Key that represents an entry in the INI file.
	  * @param  mixed $theDefault   Value to be returned if nothing is found.
	  * @return mixed               Value of the informed key.
	  */
	public function config($theKey, $theDefault = null) {
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

		$aDataDir = @$this->mINIValues['data_dir'];

		if(!file_exists($aDataDir)) {
			throw new \Exception("Unable to access data directory informed in INI file: " . $aDataDir . "\n");
		}

	    // Interpret the special syntax in the INI file
	    // to expand expressions, e.g. 0..10:1 becomes 0,1,2,3,..,10
	    $this->mINIValues = $this->processSpecialExpressions($this->mINIValues);
	}

	public function init($thePathINIFile, $thePathLogFile, $theSimplified = false) {
		// Load INI file because we need the bare minimum to start everything up.
		$this->loadINI($thePathINIFile);

		$this->mLog = new Log($thePathLogFile, $theSimplified);
		$this->mLog->setLevel($this->config('log_level'));
		$this->mLog->info('Besearcher is starting up. What a great day for science!');

		// Get all life support components up and running
		$aDbPath = $this->config('data_dir') . DIRECTORY_SEPARATOR . BESEARCHER_DB_FILE;
		$this->mDb = new Db($aDbPath);
		$this->mContext = new Context($this->mDb, $this->mLog, array('status' => BESEARCHER_STATUS_STOPED));
		$this->mTasks = new Tasks($this->mDb);
		$this->mControl = new AppControl($this);

		$this->mRunningTasksCount = 0;
		$this->scheduleNextCronJob();

		if($theSimplified) {
			// Load context data from disk and don't make any changes to it.
			$this->mContext->load();
		} else {
			// Load context data from disk and merge it with in-memory data.
			$this->mContext->sync();

			$this->mActive = true;
			$this->ensureStatusHealth();
			$this->printSummary();
		}
	}

	private function ensureStatusHealth() {
		$aStatus = $this->mContext->get('status');

		if($aStatus == BESEARCHER_STATUS_STOPED) {
		   // App was shutdown correctly last time, so the stoped status was written
		   // to the context on disk. Let's continue with the init procedure.
		   $this->mLog->debug('Besearcher was shutdown correctly last time. Thank you!');
		   $this->setStatus(BESEARCHER_STATUS_INITING);

	   } else {
		   $this->mLog->warn('Besearcher was not shutdown correctly last time. Please check if everything is ok.');

		   if($aStatus == BESEARCHER_STATUS_STOPPING) {
			   // We cant have a stopping status during startup.
			   $this->setStatus(BESEARCHER_STATUS_INITING);

		   } else if($aStatus == BESEARCHER_STATUS_PAUSED) {
			   $this->mLog->info('Restoring previous PAUSED status, so the setup task will be skipped.');

		   } else if($aStatus == BESEARCHER_STATUS_INITING || $aStatus == BESEARCHER_STATUS_WAITING_SETUP || $aStatus == BESEARCHER_STATUS_RUNNING) {
			   $this->mLog->warn('Restoring previous status: ' . $aStatus);

		   } else {
			   // Ths status was really fucked up, we don't even recognize it.
			   $this->mLog->warn('Previous status found on the disk ('.$aStatus.') is unknown, so probably there is corrupted data. Proceed with a full inspection of your data. Status will be set to ' . BESEARCHER_STATUS_INITING . ' to ensure a normal start up.');
			   $this->setStatus(BESEARCHER_STATUS_INITING);
		   }
	   }
	}

	private function scheduleNextCronJob() {
		$this->mNextCronJob = time() + $this->config('cron_jobs_interval');
	}

	private function isTimeForCronJobs() {
		return time() >= $this->mNextCronJob;
	}

	private function handleCronJobs() {
		if($this->isTimeForCronJobs()) {
			$this->updateProgressRunningResults();
			$this->scheduleNextCronJob();
		}
	}

	private function createAnalyticsAlertEmailText($theMetric, $theType, $theValue, $theResult) {
		$aMessage =  "Hi!\n\n";
		$aMessage .= "A new value has been found:\n\n";
		$aMessage .= " - Metric: " . $theMetric . "\n";
		$aMessage .= " - Value (".$theType."): " . $theValue . "\n";
		$aMessage .= " - Experiment hash: " . $theResult['experiment_hash'] . "\n";
		$aMessage .= " - Permutation hash: " . $theResult['permutation_hash'] . "\n";
		$aMessage .= " - Params: " . $theResult['params'] . "\n";

		return $aMessage;
	}

	public function sendEmail($theTo, $theSubject, $theMessage) {
		$aMessagePath = $this->config('data_dir') . DIRECTORY_SEPARATOR . md5($theMessage) . '.email';
		file_put_contents($aMessagePath, $theMessage);

		$aCmd = 'php ' . BESEARCHER_CMD_DIR . 'mailer.php --ini="'.$this->mINIPath.'" --to="'.$theTo.'" --subject="'.$theSubject.'" --file="'.$aMessagePath.'"';
		$this->mLog->debug($aCmd);

		$this->asyncExec($aCmd);
	}

	private function handleAlertsAboutAnalytics($theMetric, $theChangedValues, $theAnalyticsItem) {
		$aAlertSettings = $this->mINIValues['alerts'];
		$aShouldAlert = isset($aAlertSettings['alert_when_analytics_change']) && $aAlertSettings['alert_when_analytics_change'];

		if(!$aShouldAlert) {
			return;
		}

		$aTo = $aAlertSettings['email'];

		if(empty($aTo)) {
			$this->mLog->warn('There is a new analytics alert to be sent by e-mail, but no e-mail was provided in the INI file. Please update the [alerts] section of configuration INI file.');
			return;
		}

		$aMonitors = array('min', 'max');

		foreach($aMonitors as $aType) {
			$aKey = 'analytics_monitor_' . $aType;

			if(isset($theChangedValues[$aType]) && isset($aAlertSettings[$aKey])) {
				if(in_array($theMetric, $aAlertSettings[$aKey])) {
					$aValue = $theChangedValues[$aType];
					$aResult = $this->getData()->getResultByHashes($theAnalyticsItem['experiment_hash'], $theAnalyticsItem['permutation_hash']);

					$aSubject = 'Yay, new value for ' . $theMetric . '!';
					$aMessage =  $this->createAnalyticsAlertEmailText($theMetric, $aType, $aValue, $aResult);

					$this->sendEmail($aTo, $aSubject, $aMessage);
				}
			}
		}
	}

	private function updateAnalytics() {
		$aAnalytics = new Analytics();

		$aResults = $this->getData()->findResults();
		$aAnalytics->process($aResults);
		$aReport = $aAnalytics->getReport();

		if(count($aReport) == 0) {
			return;
		}

		$aDiskAnalytics = $this->getData()->findAnalytics();

		foreach($aReport as $aMetric => $aItem) {
			$aDiskEntry = isset($aDiskAnalytics[$aMetric]) ? $aDiskAnalytics[$aMetric] : false;

			if($aDiskEntry === false) {
				$this->getData()->createAnalytics($aMetric, $aItem['min']['value'], $aItem['max']['value']);
				continue;
			}

			$aChangedValues = array();

			if($aItem['min']['value'] != $aDiskEntry['min']) {
				$aChangedValues['min'] = $aItem['min']['value'];
			}

			if($aItem['max']['value'] != $aDiskEntry['max']) {
				$aChangedValues['max'] = $aItem['max']['value'];
			}

			if(count($aChangedValues) > 0) {
				$this->getData()->updateAnalytics($aDiskEntry['id'], $aChangedValues);
				$this->handleAlertsAboutAnalytics($aMetric, $aChangedValues, $aItem['max']);
			}
		}
	}

	public function updateProgressRunningResults() {
		$aRunningResults = $this->mTasks->findRunningTasks();
		$aCount = count($aRunningResults);

		if($aCount == 0) {
			return 0;
		}

		$aUpdated = 0;
		$this->mLog->debug('Updating progress of '.$aCount.' running results.');

		// Use a transaction to speed things up and ensure a coherent update
		$this->mDb->begin();

	    foreach($aRunningResults as $aResult) {
			try {
		        $aParser = new ResultOutputParser($aResult);
				$aTags = $aParser->getTags();
		        $aProgress = $aParser->calculateTaskProgress();

				// Ensure we never face a negative progress.
				$aProgress = $aProgress < 0 ? 0 : $aProgress;

				// Let's get the most recent info from the disk to confirm
				// the result is still running. If it is not, i.e. it finished
				// while we parsed the log, let's just ignore it.
				if($this->mTasks->isResultFinished($aResult['id'])) {
					continue;
				}

		        $this->mTasks->updateResult($aResult['id'], array(
		            'progress' => $aProgress,
		            'log_file_tags' => serialize($aTags)
		        ));

		        $aParser = null;
				$this->mLog->debug('Result id='.$aResult['id'].' is '.sprintf('%.2f%%', $aProgress * 100).' complete.');
				$aUpdated++;
			} catch(\Exception $e)  {
				// Something wrong happened, but we are not running critial services here.
				// Let's just issue a warning and hope for the best.
				$this->mLog->warn('Unable to update progress of result with id=' . $aResult['id'] . '. ' . $e->getMessage());
			}
	    }

		$this->mDb->commit();

		return $aUpdated;
	}

	public function shutdown() {
		$this->mLog->info('Besearcher is done. Over and out!');

		$this->mLog->shutdown();
		$this->mDb->shutdown();

		$this->mContext = null;
		$this->mTasks = null;
		$this->mLog = null;
		$this->mDb = null;
	}

	public function run() {
		while($this->mActive) {
		    $this->mActive = $this->step();
		    $this->performHotReloadProcedures();
			$this->handleCronJobs();

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

	private function asyncExec($theCmd) {
		$aIsWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

		if($aIsWindows) {
			$aFinalCmd = 'start "Job" /b cmd.exe /c "'.$theCmd.'"';
		} else {
			$aFinalCmd = $theCmd.' &';
		}

		pclose(popen($aFinalCmd, 'r'));
	}

	private function execTaskCommand($theTask, $theParallel) {
	    $aId = $theTask['id'];
	    $this->mLog->debug($theTask['cmd'] . ' > ' . $theTask['log_file']);

        $aCmd = sprintf('%s "%s" %s', BESEARCHER_RUNNER_CMD, $this->mINIPath, $aId);
		$this->asyncExec($aCmd);
	}

	public function countRunningTasks() {
		$aRunningTasks = $this->mTasks->findRunningTasks();
		$aCount = count($aRunningTasks);
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

	private function processQueuedTasks() {
	    $aSpawnedNewTask = false;
	    $aMaxParallelJobs = $this->config('max_parallel_tasks', 1);
	    $aWait = $this->shouldWaitForUnfinishedTasks($aMaxParallelJobs);

	    if(!$aWait && $this->mTasks->queueSize() > 0) {
	        // There is room for another job. Let's spawn it.
	        $this->mLog->debug("Dequeueing task");
	        $aTask = $this->mTasks->dequeueTask();
	        $this->runTask($aTask, $aMaxParallelJobs);

	        $aSpawnedNewTask = true;
	    }

	    return $aSpawnedNewTask;
	}

	public function setExperimentHash($theHash) {
		if($theHash == $this->mContext->get('experiment_hash')) {
			return;
		}
	    $this->mContext->set('experiment_hash', $theHash);
	    $this->mLog->info("Experiment hash changed to " . $theHash);
	}

	public function rerunResult($theResultId) {
		$aResult = $this->getData()->getResultById($theResultId);

		if($aResult == false) {
			throw new \Exception('Unable to re-run result with id=' . $theResultId);
		}

		// Re-enqueue the result (turn it into a task)
		$aOk = $this->getData()->createTaskFromResultId($theResultId);

		// If everything went well, delete any existing log files associated
		// with this result
		if($aOk) {
			@unlink($aResult['log_file']);
		}

		return $aOk;
	}

	private function createTask($theExperimentHash, $thePermutation) {
	    $aUid = $theExperimentHash . '-' . $thePermutation['hash'];

	    $aDataDir = $this->config('data_dir');
	    $aTaskDir = $aDataDir . DIRECTORY_SEPARATOR . $theExperimentHash . DIRECTORY_SEPARATOR;
	    $aLogFile = $aTaskDir . $aUid . '.log';

	    $aTask = array(
	        'cmd' => $thePermutation['cmd'],
	        'log_file' => $aLogFile,
	        'working_dir' => $this->config('task_cmd_working_dir'),
	        'experiment_hash' => $theExperimentHash,
	        'permutation_hash' => $thePermutation['hash'],
	        'params' => $thePermutation['params'],
	        'creation_time' => time()
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

	private function replaceSpecialTokens($theString) {
		// Replace tokens
		$aSpecialTokens['{@besearcher_home}'] = BESEARCHER_HOME;
		$aSpecialTokens['{@besearcher_cmd_dir}'] = BESEARCHER_CMD_DIR;
		$aSpecialTokens['{@besearcher_time}'] = time();
		$aSpecialTokens['{@besearcher_os}'] = PHP_OS;
		$aSpecialTokens['{@ini_data_dir}'] = $this->config('data_dir');
		$aSpecialTokens['{@ini_experiment_hash}'] = $this->mContext->get('experiment_hash');
		$aSpecialTokens['{@ini_experiment_description}'] = $this->config('experiment_description');

		$aString = str_ireplace(array_keys($aSpecialTokens), array_values($aSpecialTokens), $theString);
		return $aString;
	}

	private function generateTaskCmdPermutations() {
	    $aPermutations = array();
	    $aTaskCmd = $this->config('task_cmd');

	    if(!isset($aTaskCmd)) {
	        throw new \Exception('Empty or invalid "task_cmd" directive provided in INI file.');
	    }

	    $aTaskCmdParams = isset($this->mINIValues['task_cmd_params']) ? $this->mINIValues['task_cmd_params'] : array();
		$aTaskCmd = $this->replaceSpecialTokens($aTaskCmd);

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

	private function createExperimentTasks($theHash) {
	    $aPermutations = $this->generateTaskCmdPermutations();

	    if(count($aPermutations) <= 0) {
			return;
		}

		// Use a transaction to speed up the enqueueing of several tasks
		$this->mDb->begin();

        foreach($aPermutations as $aPermutation) {
			$aTask = $this->createTask($theHash, $aPermutation);
            $aOk = $this->mTasks->enqueueTask($aTask);

			if(!$aOk) {
				$this->mDb->rollback();
				throw \Exception('Unable to enqueue task: ' . print_r($aTask, true));
			}
        }

		$this->mDb->commit();
	}

	private function runTask($theTask, $theMaxParallel) {
	    $aSkipPerformedTasks = $this->config('skip_performed_tasks', false);
	    $aTaskAlreadyPerformed = false;

		$aResult = $this->mTasks->getResultByHashes($theTask['experiment_hash'], $theTask['permutation_hash']);

	    if($aResult !== false) {
	        $aTaskAlreadyPerformed = $this->mTasks->isResultFinished($aResult);
	    }

	    if($aSkipPerformedTasks && $aTaskAlreadyPerformed) {
	        // It seems the task at hand already has already
	        // been executed in the past. Since we were instructed
	        // to skip already performed tasks, we stop here.
	        $this->mLog->warn('Skipping already performed task (hash=' . $theTask['experiment_hash'] . ', permutation=' . $theTask['permutation_hash'] . ')');
	        return;
	    }

	    // Update exec time
	    $theTask['exec_time_start'] = time();

	    $this->mLog->info('Running task (hash=' . $theTask['experiment_hash'] . ', permutation=' . $theTask['permutation_hash'] . ')');
	    $this->mTasks->createResultEntryFromTask($theTask);

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

	public function setupExperiment() {
		$aHash = $this->mContext->get('experiment_hash');
		$aMessage = $this->config('experiment_description', '');

		if($this->mContext->get('experiment_ready')) {
			$this->mLog->info("Skipping experiment setup, it was already performed (experiment_hash=" . $aHash . ", experiment_description=" . trim($aMessage) . ")");
			return true;
		}

		if(empty($aHash)) {
			throw new \Exception('Empty experiment hash. Check if your INI file has a valid "experiment_hash" entry.');
		}

		$this->mLog->info("Setting up experiment: experiment_hash=" . $aHash . ", experiment_description=" . trim($aMessage));
		$this->mLog->info("Creating experiment tasks");

	    $this->createExperimentTasks($aHash);

	    // Create a folder to house the results of the tasks
	    // originated from the present experiment
	    $this->createTaskResultsFolder($aHash);

		$this->mLog->info('Experiment "' . $aHash . '" setup up successfully!');
		$this->mContext->set('experiment_ready', 1);

		return true;
	}

	private function monitorRunningTasks() {
	    $aTasksNow = $this->countRunningTasks();

	    if($this->mRunningTasksCount != $aTasksNow) {
	        if($aTasksNow > 0) {
	            $this->mLog->info('Tasks running now: ' . $aTasksNow);
				$this->onTaskFinishedRunning();
	        }

	        if($aTasksNow == 0 && $this->mRunningTasksCount > 0) {
				$this->onAllRunningTasksFinished();
	            $this->mLog->info('All runnings tasks finished!');
	            $this->printSummary();
	        }

			$this->mRunningTasksCount = $aTasksNow;
	    }
	}

	private function step() {
		$aStatus = $this->mContext->get('status');

	    if($aStatus == BESEARCHER_STATUS_RUNNING) {
			$aExperimentReady = $this->mContext->get('experiment_ready');

			if(!$aExperimentReady) {
	        	$this->setupExperiment();
			}

			$aSpawendTaskProcess = $this->processQueuedTasks();

	    } else if($aStatus == BESEARCHER_STATUS_INITING || $aStatus == BESEARCHER_STATUS_WAITING_SETUP) {
			// App is starting up or it is running the setup task.
			// Let's wait until that is complete.
			$this->checkSetupTaskProcedures();
		}

		$this->monitorRunningTasks();
	    $this->getControl()->update();

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

	public function setStatus($theValue, $theLogMessage = '', $theLogType = Log::INFO) {
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

	private function checkSetupTaskProcedures() {
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

	private function ensureExperimentHashIsNotEmpty() {
	    $aExperimentHash = $this->mContext->get('experiment_hash');

	    // If we don't have any information regarding the experiment hash,
	    // we use a fake one
	    if(empty($aExperimentHash)) {
	        $aExperimentHash = $this->config('experiment_hash', '');

			if(empty($aExperimentHash)) {
				$aINIHash = $this->mContext->get('ini_hash');
	        	$this->mLog->warn("INI file has an empty \"experiment_hash\", so the hash from the INI file itself will be used (" . $aINIHash . "). Please edit your config INI file and provide a valid \"experiment_hash\" so you can better track your experiment.");
				$aExperimentHash = $aINIHash;
			}

			$this->setExperimentHash($aExperimentHash);
	    }
	}

	private function performHotReloadProcedures() {
	    $this->performConfigHotReload();
	    $this->ensureExperimentHashIsNotEmpty();
	}

	public function printSummary() {
		$aRunningTasksCount = $this->countRunningTasks();
	    $aQueueSize = $this->mTasks->queueSize();
		$aStatus = $this->mContext->get('status');

	    $this->mLog->info('Running tasks: '. $aRunningTasksCount . ', queued tasks: ' . $aQueueSize . ', status: ' . $aStatus);
	}

	public function getLogger() {
		return $this->mLog;
	}

	public function getContext() {
		return $this->mContext;
	}

	public function getDb() {
		return $this->mDb;
	}

	public function getData() {
		return $this->mTasks;
	}

	public function getControl() {
		return $this->mControl;
	}

	public function getINIPath() {
		return $this->mINIPath;
	}

	public function getINIValues() {
		return $this->mINIValues;
	}
}
?>
