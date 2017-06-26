<?php

/*
 This script will run a set of commands based on the changes of a
 Git repository. It tracks changes in the repo and, for each new
 commit, runs a set of pre-defined commands.

 Author: Fernando Bevilacqua <fernando.bevilacqua@his.se>
 */

define('SAY_ERROR', 'ERROR');
define('SAY_INFO', 'INFO');
define('SAY_WARN', 'WARN');

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

function execCommand($theCmd, $theLogFile, $theParallel) {
    $aCmdTemplate = $theParallel ? 'start "Job" /b cmd.exe /c "%s > "%s""' : '%s > "%s"';
    $aFinalCmd = sprintf($aCmdTemplate, $theCmd, $theLogFile);

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
    $aCommitFile = $aDataDir . DIRECTORY_SEPARATOR . 'beseacher.last-commit';
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

function findNewCommits($theWatchDir, $theGitExe, $theLastCommitHash) {
    $aNewCommits = array();
    $aEntries = array();

    exec('cd ' . $theWatchDir . ' & ' . $theGitExe . ' log --pretty=oneline', $aEntries);

    $aShouldInclude = false;

    for($i = count($aEntries) - 1; $i >= 0; $i--) {
        $aCommit = $aEntries[$i];
        $aParts = explode(' ', $aCommit);
        $aHash = $aParts[0];

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
    say("Enqueing task " . $theTask['hash'], SAY_INFO, $theContext);
}

function createTask($theCommitHash, $thePermutationHash, $theCmd, $theContext) {
    $aUid = $theCommitHash . '-' . $thePermutationHash;

    $aDataDir = get_ini('data_dir', $theContext);
    $aLogFile = $aDataDir . DIRECTORY_SEPARATOR . $aUid . '.log';

    $aTask = array(
        'cmd' => $theCmd,
        'log_file' => $aLogFile,
        'working_dir' => get_ini('task_cmd_working_dir', $theContext),
        'hash' => $theCommitHash,
        'permutation' => $thePermutationHash,
        'time' => time()
    );

    return $aTask;
}

function replaceTolken($theString, $theSearches, $theReplaces, $theIdx, & $theOutputs) {
    if($theIdx >= count($theSearches)) {
        // Nothing else to replace, we found the final string.
        $theOutputs[] = $theString;

    } else {
        // Still work to do.
        $aSearch = '{@' . $theSearches[$theIdx] . '}';
        $aReplace = $theReplaces[$theIdx];

        if(is_array($aReplace)) {
            foreach($aReplace as $aReplacePiece) {
                $aString = str_ireplace($aSearch, $aReplacePiece, $theString);
                replaceTolken($aString, $theSearches, $theReplaces, $theIdx + 1, $theOutputs);
            }
        } else {
            $aString = str_ireplace($aSearch, $aReplace, $theString);
            replaceTolken($aString, $theSearches, $theReplaces, $theIdx + 1, $theOutputs);
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
                $aPermutations[] = array('cmd' => $aCmd, 'hash' => md5($aCmd));
            }
        }

    } else {
        $aPermutations[] = array('cmd' => $aTaskCmd, 'hash' => md5($aTaskCmd));
    }

    checkNonReplacedValues($aPermutations, $theContext);
    return $aPermutations;
}

function createTasksFromCommit($theHash, $theContext) {
    $aTasks = array();
    $aPermutations = generateTaskCmdPermutations($theContext);

    if(count($aPermutations) > 0) {
        foreach($aPermutations as $aPermutation) {
            $aTasks[] = createTask($theHash, $aPermutation['hash'], $aPermutation['cmd'], $theContext);
        }
    }

    return $aTasks;
}

function runTask($theTask, $theMaxParallel, $theContext) {
    say('Running task (hash=' . $theTask['hash'] . ', permutation=' . $theTask['permutation'] . ')', SAY_INFO, $theContext);

    $aParallel = $theMaxParallel > 1;
    $aTaskCmd = $theTask['cmd'];
    $aTaskLogFile = $theTask['log_file'];

    if(get_ini('verbose', $theContext, false)) {
        say($aTaskCmd, SAY_INFO, $theContext);
    }

    execCommand($aTaskCmd, $aTaskLogFile, $aParallel);
}

function handleNewCommit($theHash, $theMessage, & $theContext) {
    $aTasks = createTasksFromCommit($theHash, $theContext);

    if(count($aTasks) > 0) {
        foreach($aTasks as  $aTask) {
            enqueTask($aTask, $theContext);
        }
    }
}

function processNewCommits(& $theContext) {
    $aWatchDir = get_ini('watch_dir', $theContext);
    $aGitExe = get_ini('git', $theContext);

    $aTasks = findNewCommits($aWatchDir, $aGitExe, $theContext['last_commit']);
    $aLastHash = '';
    $aTasksCount = count($aTasks);

    if($aTasksCount > 0) {
        foreach($aTasks as $aCommit) {
            $aDivider = strpos($aCommit, ' ');
            $aHash = substr($aCommit, 0, $aDivider);
            $aMessage = substr($aCommit, $aDivider);
            $aLastHash = $aHash;

            say("New commit (" . $aHash . "): " . $aMessage, SAY_INFO, $theContext);
            handleNewCommit($aHash, $aMessage, $theContext);
        }

        setLastKnownCommit($theContext, $aLastHash);
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
    processGitPulls($theContext);

    $aProcessQueue = true;
    while($aProcessQueue) {
        $aProcessQueue = processQueuedTasks($theContext);
    }

    monitorRunningTasks($theContext);

    // Wait for the next check
    $aWaitTime = get_ini('refresh_interval', $theContext, 1);
    sleep($aWaitTime);

    return true;
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

        $theContext['ini_values'] = parse_ini_file($aPath, true);
        $theContext['ini_hash'] = $aContentHash;
    }

    $aLastCommitDisk = loadLastKnownCommitFromFile($theContext);

    // If we don't have any information regarding the last commit, we use
    // the one provided in the ini file.
    if(empty($aLastCommitDisk)) {
        $aLastCommitDisk = get_ini('start_commit_hash', $theContext, '');
        say("No commit info found on disk, using info from INI: " . $aLastCommitDisk, SAY_INFO, $theContext);
    }

    if($aLastCommitDisk != $theContext['last_commit']) {
        say("Info regarding last commit has changed: old=" . $theContext['last_commit'] . ", new=" . $aLastCommitDisk, SAY_INFO, $theContext);
        setLastKnownCommit($theContext, $aLastCommitDisk);
    }
}

function say($theMessage, $theType, $theContext) {
    echo date('[Y-m-d H:i:s]') . ' [' . $theType . '] ' . $theMessage . "\n";
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
     echo " --log=<path>     Path to the log file.\n";
     echo "\n";
     echo " --ini=<path>     Path to the INI files used for configuration.\n";
     echo "\n";
     exit(1);
}

$aContext = array(
    'ini_path' => isset($aArgs['ini']) ? $aArgs['ini'] : '',
    'ini_hash' => '',
    'ini_values' => '',
    'last_commit' => '',
    'log_file' => isset($aArgs['log']) ? $aArgs['log'] : '',
    'tasks_queue' => array(),
    'time_last_pull' => 0,
    'running_tasks' => 0
);

performConfigHotReload($aContext);

$aActive = true;

while($aActive) {
    $aActive = run($aContext);
    performConfigHotReload($aContext);
}

exit(0);
?>
