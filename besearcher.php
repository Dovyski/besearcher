<?php

/*
 Main file for Besearcher. This script is the deamon that will periodically check
 the informed Git repository. It tracks changes in the repo and, for each new
 commit, runs a set of pre-defined commands.

 Author: Fernando Bevilacqua <fernando.bevilacqua@his.se>
 */

require_once(dirname(__FILE__) . '/inc/functions.php');

define('SAY_ERROR', 3);
define('SAY_WARN', 2);
define('SAY_INFO', 1);
define('SAY_DEBUG', 0);

define('RUNNER_CMD', 'php "' . dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cmd' . DIRECTORY_SEPARATOR . 'runner.php"');

$gSayStrings = array(
    SAY_ERROR => 'ERROR',
    SAY_WARN => 'WARN',
    SAY_INFO => 'INFO',
    SAY_DEBUG => 'DEBUG'
);

// Below are the definitions of the expressions that are
// expandable in the INI file.

// E.g. 0..10:1, which generates 0,1,2,...,10
define('INI_EXP_START_END_INC', '/(\d*[.]?\d*)[[:blank:]]*\.\.[[:blank:]]*(\d*[.]?\d*)[[:blank:]]*:[[:blank:]]*(\d*[.]?\d*)/i');

/**
  * Get a value from the INI file. The key is first looked up
  * at the action section. If nothing is found, the key is
  * looked up at the whole INI file scope.
  *
  * @param  string $theKey      Key that represents an entry in the INI file.
  * @param  array $theContext   Array containing informatio regarding the app context.
  * @param  mixed $theDefault   Value to be returned if nothing is found.
  * @return mixed               Value of the informed key.
  */
function get_ini($theKey, $theContext, $theDefault = null) {
    $aINI = $theContext['ini_values'];
    $aRet = $theDefault;

    if(isset($aINI[$theKey])) {
        $aRet = $aINI[$theKey];
    }

    return $aRet;
}

function execTaskCommand($theTask, $theParallel, $theContext) {
    $aCmd = $theTask['cmd'];
    $aLogFile = $theTask['log_file'];
    $aInfoFile = $theTask['info_file'];

    $aCmdTemplate = '%s > "%s"';
    $aFinalCmd = sprintf($aCmdTemplate, $aCmd, $aLogFile);

    say($aFinalCmd, SAY_DEBUG, $theContext);

    if($theParallel) {
        $aFinalCmd = sprintf('start "Job" /b cmd.exe /c "%s "%s" "%s" "%s""', RUNNER_CMD, $aCmd, $aLogFile, $aInfoFile);
    }

    pclose(popen($aFinalCmd, 'r'));
}

function countRunningTasks($theContext) {
    $aTaskCmdApp = get_ini('task_cmd_list_name', $theContext, '');
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

function shouldWaitForUnfinishedTasks($theMaxParallel, $theContext) {
    $aShouldWait = false;
    $aCount = countRunningTasks($theContext);

    // If there are tasks running, we must wait if we are at full capacity
    if($aCount != 0 && $aCount >= $theMaxParallel) {
        $aShouldWait = true;
    }

    return $aShouldWait;
}

function processQueuedTasks(& $theContext) {
    $aSpawnedNewTask = false;

    $aCmdName = get_ini('task_cmd_list_name', $theContext, '');
    $aMaxParallelJobs = get_ini('max_parallel_tasks', $theContext, 1);

    $aWait = shouldWaitForUnfinishedTasks($aMaxParallelJobs, $theContext);

    if(!$aWait && count($theContext['tasks_queue']) > 0) {
        // There is room for another job. Let's spawn it.
        $aTask = array_shift($theContext['tasks_queue']);
        runTask($aTask, $aMaxParallelJobs, $theContext);

        $aSpawnedNewTask = true;
    }

    return $aSpawnedNewTask;
}

function loadLastKnownCommitFromFile($theContext) {
    $aDataDir = get_ini('data_dir', $theContext);
    $aCommitFile = $aDataDir . DIRECTORY_SEPARATOR . BESEARCHER_LAST_COMMIT_FILE;
    $aFileContent = @file_get_contents($aCommitFile);
    $aValue = $aFileContent !== FALSE ? trim($aFileContent) : '';

    return $aValue;
}

function setLastKnownCommit(& $theContext, $theHash) {
    $theContext['last_commit'] = $theHash;

    say("Last known commit (on memory and on disk) changed to " . $theContext['last_commit'], SAY_INFO, $theContext);

    $aDataDir = get_ini('data_dir', $theContext);
    $aCommitFile = $aDataDir . DIRECTORY_SEPARATOR . 'beseacher.last-commit';
    file_put_contents($aCommitFile, $theContext['last_commit']);
}

function performGitPull($theWatchDir, $theGitExe, $theContext) {
    $aEntries = array();

    say("Updating repo with git pull", SAY_DEBUG, $theContext);
    $aOutput = exec('cd ' . $theWatchDir . ' & ' . $theGitExe . ' pull', $aEntries);
    say(implode("\n", $aEntries), SAY_DEBUG, $theContext);
}

function findCommitsFromGitLog($theWatchDir, $theGitExe) {
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

function findNewCommits($theWatchDir, $theGitExe, $theLastCommitHash, $theContext) {
    $aNewCommits = array();
    $aPerformPull = get_ini('perform_git_pull', $theContext, true);

    if($aPerformPull) {
        performGitPull($theWatchDir, $theGitExe, $theContext);
    }

    $aCommits = findCommitsFromGitLog($theWatchDir, $theGitExe);
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

function enqueTask($theTask, & $theContext) {
    array_push($theContext['tasks_queue'], $theTask);
    say("Enqueing task " . $theTask['hash'] . '-' . $theTask['permutation'], SAY_DEBUG, $theContext);
}

function createTask($theCommitHash, $theCommitMessage, $thePermutation, $theContext) {
    $aUid = $theCommitHash . '-' . $thePermutation['hash'];

    $aDataDir = get_ini('data_dir', $theContext);
    $aTaskDir = $aDataDir . DIRECTORY_SEPARATOR . $theCommitHash . DIRECTORY_SEPARATOR;
    $aLogFile = $aTaskDir . $aUid . '.log';
    $aInfoFile = $aTaskDir . $aUid . '.json';

    $aTask = array(
        'cmd' => $thePermutation['cmd'],
        'cmd_return_code' => -1,
        'log_file' => $aLogFile,
        'info_file' => $aInfoFile,
        'working_dir' => get_ini('task_cmd_working_dir', $theContext),
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

function replaceTolken($theString, $theSearches, $theReplaces, $theIdx, & $theOutputs, $theParamsString = '') {
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
                replaceTolken($aString, $theSearches, $theReplaces, $theIdx + 1, $theOutputs, $aParamsString);
            }
        } else {
            $aString = str_ireplace($aSearch, $aReplace, $theString);
            $aParamsString = $theParamsString . $aKey . '=' . $aReplace . ', ' ;
            replaceTolken($aString, $theSearches, $theReplaces, $theIdx + 1, $theOutputs, $aParamsString);
        }
    }
}

function checkNonReplacedValues($thePermutations, $theContext) {
    if(count($thePermutations) > 0) {
        foreach($thePermutations as $aItem) {
            if(preg_match_all('/.*\{@.*\}/i', $aItem['cmd'])) {
                say('Unreplaced value in command: ' . $aItem['cmd'], SAY_ERROR, $theContext);
                exit(4);
            }
        }
    }
}

function generateTaskCmdPermutations($theContext) {
    $aPermutations = array();
    $aTaskCmd = get_ini('task_cmd', $theContext);

    if(!isset($aTaskCmd)) {
        say('Empty or invalid "task_cmd" directive provided in INI file.', SAY_ERROR, $theContext);
        exit(4);
    }

    $aTaskCmdParams = isset($theContext['ini_values']['task_cmd_params']) ? $theContext['ini_values']['task_cmd_params'] : array();

    if(count($aTaskCmdParams) > 0) {
        $aCmds = array();
        replaceTolken($aTaskCmd, array_keys($aTaskCmdParams), array_values($aTaskCmdParams), 0, $aCmds);

        if(count($aCmds) > 0) {
            foreach($aCmds as $aCmd) {
                $aPermutations[] = array('cmd' => $aCmd['text'], 'hash' => md5($aCmd['text']), 'params' => $aCmd['params']);
            }
        }
    } else {
        $aPermutations[] = array('cmd' => $aTaskCmd, 'hash' => md5($aTaskCmd), 'params' => 'NONE');
    }

    checkNonReplacedValues($aPermutations, $theContext);
    return $aPermutations;
}

function createTasksFromCommit($theHash, $theMessage, $theContext) {
    $aTasks = array();
    $aPermutations = generateTaskCmdPermutations($theContext);

    if(count($aPermutations) > 0) {
        foreach($aPermutations as $aPermutation) {
            $aTasks[] = createTask($theHash, $theMessage, $aPermutation, $theContext);
        }
    }

    return $aTasks;
}

function writeTaskInfoFile($theTask) {
    file_put_contents($theTask['info_file'], json_encode($theTask, JSON_PRETTY_PRINT));
}

function runTask($theTask, $theMaxParallel, $theContext) {
    $aSkipPerformedTasks = get_ini('skip_performed_tasks', $theContext, false);
    $aTaskAlreadyPerformed = file_exists($theTask['info_file']);

    if($aSkipPerformedTasks && $aTaskAlreadyPerformed) {
        // It seems the task at hand already has already
        // been executed in the past. Since we were instructed
        // to skip already performed tasks, we stop here.
        say('Skipping already performed task (hash=' . $theTask['hash'] . ', permutation=' . $theTask['permutation'] . ')', SAY_WARN, $theContext);
        return;
    }

    // Update exec time
    $theTask['exec_time_start'] = time();

    say('Running task (hash=' . $theTask['hash'] . ', permutation=' . $theTask['permutation'] . ')', SAY_INFO, $theContext);
    writeTaskInfoFile($theTask);

    $aParallel = $theMaxParallel > 1;
    execTaskCommand($theTask, $aParallel, $theContext);
}

function createTaskResultsFolder($theCommitHash, $theContext) {
    $aDataDir = get_ini('data_dir', $theContext);
    $aCommitDir = $aDataDir . DIRECTORY_SEPARATOR . $theCommitHash;

    if(!file_exists($aCommitDir)) {
        mkdir($aCommitDir);
    }
}

function handleNewCommit($theHash, $theMessage, & $theContext) {
    $aTasks = createTasksFromCommit($theHash, $theMessage, $theContext);

    // Create a folder to house the results of the tasks
    // originated from the present commit
    createTaskResultsFolder($theHash, $theContext);

    if(count($aTasks) > 0) {
        foreach($aTasks as  $aTask) {
            enqueTask($aTask, $theContext);
        }
    }
}

function textHasSkipToken($theMessage) {
    $aMatches = array();
    $aHits = preg_match_all(BESEARCHER_COMMIT_SKIP_TOKEN, $theMessage, $aMatches);

    return $aHits !== false && $aHits > 0;
}

function processNewCommits(& $theContext) {
    $aWatchDir   = get_ini('watch_dir', $theContext);
    $aGitExe     = get_ini('git', $theContext);

    $aTasks      = findNewCommits($aWatchDir, $aGitExe, $theContext['last_commit'], $theContext);
    $aLastHash   = '';
    $aTasksCount = count($aTasks);

    if($aTasksCount > 0) {
        foreach($aTasks as $aCommit) {
            $aHash     = $aCommit['hash'];
            $aMessage  = $aCommit['message'];
            $aLastHash = $aHash;

            if(textHasSkipToken($aMessage)) {
                say("Skipping commit due to skip token (hash=" . $aHash . ", msg=" . trim($aMessage) . ")", SAY_INFO, $theContext);
                continue;
            }

            say("New commit (hash=" . $aHash . ", msg=" . trim($aMessage) . ")", SAY_INFO, $theContext);
            handleNewCommit($aHash, $aMessage, $theContext);
        }

        if($aLastHash != '') {
            setLastKnownCommit($theContext, $aLastHash);
        }
    }

    return $aTasksCount > 0;
}

function monitorRunningTasks(& $theContext) {
    $aTasksNow = countRunningTasks($theContext);

    if($theContext['running_tasks'] != $aTasksNow) {
        if($aTasksNow > 0) {
            say('Tasks running now: ' . $aTasksNow, SAY_INFO, $theContext);
        }

        if($aTasksNow == 0 && $theContext['running_tasks'] > 0) {
            say('All runnings tasks finished!', SAY_INFO, $theContext);
        }
        $theContext['running_tasks'] = $aTasksNow;
    }
}

function processGitPulls(& $theContext) {
    $aPullInterval = get_ini('git_pull_interval', $theContext, 10);
    $aShouldPull = time() - $theContext['time_last_pull'] >= $aPullInterval;

    if($aShouldPull) {
        $aAnyNewTask = processNewCommits($theContext);
        $theContext['time_last_pull'] = time();
    }
}

function run(& $theContext) {
    if($theContext['status'] == BESEARCHER_STATUS_RUNNING) {
        processGitPulls($theContext);

        // The config INI file may have changed with the pull, so
        // let's check current config params
        performConfigHotReload($theContext);

        $aProcessQueue = true;
        while($aProcessQueue) {
            $aProcessQueue = processQueuedTasks($theContext);
        }
    }

    monitorRunningTasks($theContext);

    // Wait for the next check
    $aWaitTime = get_ini('refresh_interval', $theContext, 1);
    sleep($aWaitTime);

    return true;
}

function expandStartEndIncExpression($theMatches) {
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

function expandExpressions($theINIValues) {
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
            $theINIValues = expandStartEndIncExpression($aMatches);
        }
    } else {
        // There is more than one element in the array,
        // so we must check each one.
        foreach($theINIValues as $aKey => $aValue) {
            if(is_array($aValue)) {
                $theINIValues[$aKey] = expandExpressions($aValue);
            }
        }
    }

    return $theINIValues;
}

function loadINI($theINIFilePath, & $theContext) {
    $theContext['ini_values'] = parse_ini_file($theINIFilePath, true);

    // Interpret the special syntax in the INI file
    // to expand expressions, e.g. 0..10:1 becomes 0,1,2,3,..,10
    $theContext['ini_values'] = expandExpressions($theContext['ini_values']);
}

function prepareTaskCommandFileExists(& $theContext) {
    $aResult = false;
    $aPreTaskCmd = get_ini('task_prepare_cmd', $theContext, '');

    if($aPreTaskCmd != '') {
        $aDataDir = get_ini('data_dir', $theContext);
        $aPrepareFilePath = $aDataDir . DIRECTORY_SEPARATOR . BESEARCHER_PREPARE_FILE;
        $aResult = file_exists($aPrepareFilePath);
    }

    return $aResult;
}

/**
 * Informs about the result of running the prepare task command.
 *
 * @param  $theContext         app context.
 * @return mixed               return <code>false</code> if the command was not executed, or an integer containing the value the command returned.
 */
function getPrepareTaskCommandResult(& $theContext) {
    $aResult = false;
    $aPreTaskCmd = get_ini('task_prepare_cmd', $theContext, '');

    if($aPreTaskCmd != '') {
        $aDataDir = get_ini('data_dir', $theContext);
        $aPrepareFilePath = $aDataDir . DIRECTORY_SEPARATOR . BESEARCHER_PREPARE_FILE;
        $aResult = @file_get_contents($aPrepareFilePath);

        $aResult = trim($aResult);
        $aResult = $aResult == '' ? false : ($aResult + 0);
    }

    return $aResult;
}

function setAppStatus(& $theContext, $theValue, $theLogMessage = '', $theLogType = SAY_INFO) {
    if($theContext['status'] != $theValue) {
        say('App status changed to ' . $theValue . '. ' . ($theLogMessage != '' ? 'Note: ' . $theLogMessage : ''), $theLogType, $theContext);
    }
    $theContext['status'] = $theValue;
}

function runPrepareTaskCommand(& $theContext) {
    $aDataDir = get_ini('data_dir', $theContext);
    $aPrepareFilePath = $aDataDir . DIRECTORY_SEPARATOR . BESEARCHER_PREPARE_FILE;
    $aPrepareCmdLogPath = $aDataDir . DIRECTORY_SEPARATOR . BESEARCHER_PREPARE_TASK_LOG_FILE;
    $aPreTaskCmd = get_ini('task_prepare_cmd', $theContext, '');

    say('Command found in "task_prepare_cmd", executing it. Command output will be in: ' . $aPrepareCmdLogPath, SAY_INFO, $theContext);

    if($aPreTaskCmd != '') {
        // Create empty file to inform the command is running
        file_put_contents($aPrepareFilePath, '');

        setAppStatus($theContext, BESEARCHER_STATUS_WAITING_PRETASK);

        $aCmd = $aPreTaskCmd . ' > "'.$aPrepareCmdLogPath.'"';
        $aReturn = -1;
        say('Running "task_prepare_cmd": ' . $aCmd, SAY_DEBUG, $theContext);
        system($aCmd, $aReturn);

        file_put_contents($aPrepareFilePath, $aReturn);
        say('task_prepare_cmd finished (returned '.$aReturn.')', SAY_DEBUG, $theContext);
    } else {
        say('Empty task_prepare_cmd, unable to run it', SAY_ERROR, $theContext);
    }
}

function checkPrepareTaskCommandProcedures(& $theContext) {
    $aHasPrepareCmd = get_ini('task_prepare_cmd', $theContext, '') != '';

    if(!$aHasPrepareCmd) {
        // Unpause the app in case it was paused and the INI file changed
        // and is now configured to not have any pre task cmd.
        setAppStatus($theContext, BESEARCHER_STATUS_RUNNING);
        return;
    }

    if(prepareTaskCommandFileExists($theContext)) {
        // pre task command is running or have finished
        $aPrepareResult = getPrepareTaskCommandResult($theContext);

        if($aPrepareResult === false) {
            // We have a prepare task, but it is not finished yet. Halt
            // all task commands.
            setAppStatus($theContext, BESEARCHER_STATUS_WAITING_PRETASK);

        } else if($aPrepareResult != 0) {
            setAppStatus($theContext, BESEARCHER_STATUS_PAUSED, 'command in "task_prepare_cmd" returned error (return='.$aPrepareResult.').', SAY_ERROR);

        } else {
            setAppStatus($theContext, BESEARCHER_STATUS_RUNNING, 'command in "task_prepare_cmd" finished successfully.');
        }
    } else {
        // pre task command has not started yet.
        runPrepareTaskCommand($theContext);
    }
}

function checkLastCommitDataFromDisk(& $theContext) {
    $aLastCommitDisk = loadLastKnownCommitFromFile($theContext);

    // If we don't have any information regarding the last commit, we use
    // the one provided in the ini file.
    if(empty($aLastCommitDisk)) {
        $aLastCommitDisk = get_ini('start_commit_hash', $theContext, '');
        say("No commit info found on disk, using info from INI: " . $aLastCommitDisk, SAY_INFO, $theContext);
    }

    if($aLastCommitDisk != $theContext['last_commit']) {
        say("Info regarding last commit has changed: old=" . $theContext['last_commit'] . ", new=" . $aLastCommitDisk, SAY_DEBUG, $theContext);
        setLastKnownCommit($theContext, $aLastCommitDisk);
    }
}

function performConfigHotReload(& $theContext) {
    $aPath = $theContext['ini_path'];

    if(!file_exists($aPath)) {
        say("Informed INI file is invalid: '" . $aPath . "'", SAY_ERROR, $theContext);
        exit(2);
    }

    $aContentHash = md5(file_get_contents($aPath));

    if($aContentHash != $theContext['ini_hash']) {
        say("Content of INI file has changed. Reloading it.", SAY_INFO, $theContext);

        $theContext['ini_hash'] = $aContentHash;
        loadINI($aPath, $theContext);
    }

    checkLastCommitDataFromDisk($theContext);
    checkPrepareTaskCommandProcedures($theContext);
}

function say($theMessage, $theType, $theContext) {
    global $gSayStrings;

    $aLogLevel = get_ini('log_level', $theContext, 0);
    $aLabel = isset($gSayStrings[$theType]) ? $gSayStrings[$theType] : 'UNKNOWN';
    $aMessage = date('[Y-m-d H:i:s]') . ' [' . $aLabel . '] ' . $theMessage . "\n";

    if($theType >= $aLogLevel) {
        fwrite($theContext['log_file_stream'] == null ? STDOUT : $theContext['log_file_stream'], $aMessage);
    }
}

function shutdown($theContext) {
    say('Abrupt shutdown. Please check if everything is OK.', SAY_WARN, $theContext);
}

$aOptions = array(
    "log:",
    "ini:",
);

$aArgs = getopt("", $aOptions);

if($argc <= 1) {
     echo "Usage: \n";
     echo " php ".basename($_SERVER['PHP_SELF']) . " [options]\n\n";
     echo "Options:\n";
     echo " --log=<path>     Path to the log file. If nothing is provided,\n";
     echo "                  log messages will be output to STDOUT.\n";
     echo "\n";
     echo " --ini=<path>     Path to the INI files used for configuration.\n";
     echo "\n";
     exit(1);
}

$aContext = array(
    'ini_path'         => isset($aArgs['ini']) ? $aArgs['ini'] : '',
    'ini_hash'         => '',
    'ini_values'       => '',
    'last_commit'      => '',
    'path_log_file'    => isset($aArgs['log']) ? $aArgs['log'] : '',
    'log_file_stream'  => null,
    'tasks_queue'      => array(),
    'time_last_pull'   => 0,
    'running_tasks'    => 0,
    'status'           => BESEARCHER_STATUS_INITING
);

// Register shutdown handler to deal with last minute stuff
register_shutdown_function('shutdown', $aContext);

// Open the log file. Program messages will be printed to stdout
// and to that file.
$aContext['log_file_stream'] = empty($aContext['path_log_file']) ? STDOUT : fopen($aContext['path_log_file'], 'a');

say('Besearcher starting up. What a great day for science!', SAY_INFO, $aContext);
performConfigHotReload($aContext);
$aActive = true;

while($aActive) {
    $aActive = run($aContext);
    performConfigHotReload($aContext);
}

if($aContext['log_file_stream'] != null) {
    fclose($aContext['log_file_stream']);
}

say('All done. Over and out!', SAY_INFO, $aContext);
exit(0);
?>
