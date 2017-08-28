<?php
/*
 This file contains a set of functions that are common to all
 tools of besearcher.

 Author: Fernando Bevilacqua <fernando.bevilacqua@his.se>
 */

define('BESEARCHER_TAG',                    '[BSR]');
define('BESEARCHER_TAG_TYPE_RESULT',        'result');
define('BESEARCHER_TAG_TYPE_PROGRESS',      'progress');

define('BESEARCHER_STATUS_INITING',          'INITING');
define('BESEARCHER_STATUS_RUNNING',          'RUNNING');
define('BESEARCHER_STATUS_PAUSED',           'PAUSED');
define('BESEARCHER_STATUS_WAITING_PRETASK',  'WAITING_PRETASK');

define('BESEARCHER_CACHE_FILE_EXT',          '.besearcher-cache');
define('BESEARCHER_PREPARE_FILE',            'besearcher.task_prepare_cmd-result');
define('BESEARCHER_PREPARE_TASK_LOG_FILE',   'task_prepare_cmd.log');
define('BESEARCHER_LAST_COMMIT_FILE',        'beseacher.last-commit');
define('BESEARCHER_WEB_CACHE_FILE',          'beseacher.web-cache');
define('BESEARCHER_CONTEXT_FILE',            'beseacher.context');
define('BESEARCHER_CONTEXT_OVERRIDE_FILE',   'beseacher.context-override');

define('BESEARCHER_COMMIT_SKIP_TOKEN',       '/\[(skip-ci|skip|skip-ic|skip-besearcher)\]/');

// Below are the definitions of the expressions that are
// expandable in the INI file.

// E.g. 0..10:1, which generates 0,1,2,...,10
define('INI_EXP_START_END_INC', '/(\d*[.]?\d*)[[:blank:]]*\.\.[[:blank:]]*(\d*[.]?\d*)[[:blank:]]*:[[:blank:]]*(\d*[.]?\d*)/i');

// E.g. 0..10:1, which generates 0,1,2,...,10
define('INI_PERM', '/perm[[:blank:]]*:[[:blank:]]*(\d+)[[:blank:]]*:[[:blank:]]*(.*)/i');

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

function calculateTaskProgressFromTags(array $theBesearcherLogTags) {
    $aProgresses = findTagsByType($theBesearcherLogTags, BESEARCHER_TAG_TYPE_PROGRESS);
    $aCount = count($aProgresses);

    $aProgress =  $aCount > 0 ? $aProgresses[$aCount - 1]['data'] : -1;
    return $aProgress;
}

function isTaskFinished($theTaskInfo) {
    $aTime = $theTaskInfo['exec_time_end'];
    return $aTime != 0;
}

function handleBesearcherLogTags($theTaskInfo, $theUseCache = true) {
    $aTags = array();

    if(isTaskFinished($theTaskInfo) && $theUseCache) {
        // Task has finished, it might be a cached version of the log file.
        $aCacheFilePath = $theTaskInfo['log_file'] . BESEARCHER_CACHE_FILE_EXT;

        if(file_exists($aCacheFilePath)) {
            $aTags = unserialize(file_get_contents($aCacheFilePath));
        } else {
            $aTags = findBesearcherLogTags($theTaskInfo['log_file']);
            file_put_contents($aCacheFilePath, serialize($aTags));
        }
    } else {
        $aTags = findBesearcherLogTags($theTaskInfo['log_file']);
    }

    return $aTags;
}

function aggredateTaskInfos($theTaskJsonFiles) {
    $aInfos = array();

    foreach($theTaskJsonFiles as $aFile) {
        $aInfo = json_decode(file_get_contents($aFile), true);
        $aPermutation = $aInfo['permutation'];

        // Find special marks in the log file that inform
        // Besearcher about things
        $aTags = handleBesearcherLogTags($aInfo);

        $aInfos[$aPermutation] = array(
            'commit'          => $aInfo['hash'],
            'commit_message'  => @$aInfo['message'],
            'permutation'     => $aPermutation,
            'creation_time'   => @$aInfo['creation_time'],
            'exec_time_start' => @$aInfo['exec_time_start'],
            'exec_time_end'   => @$aInfo['exec_time_end'],
            'params'          => $aInfo['params'],
            'cmd'             => $aInfo['cmd'],
            'progress'        => calculateTaskProgressFromTags($aTags),
            'meta'            => $aTags,
            'raw'             => $aInfo
        );
    }

    return $aInfos;
}

function findTasksInfos($theDataDir) {
    $aData = array();
    $aTasks = scandir($theDataDir);

    foreach($aTasks as $aItem) {
        $aPath = $theDataDir . DIRECTORY_SEPARATOR . $aItem;

        if($aItem[0] != '.' && is_dir($aPath)) {
            $aFiles = glob($aPath . DIRECTORY_SEPARATOR . '*.json');
            $aData[$aItem] = aggredateTaskInfos($aFiles);
        }
    }

    return $aData;
}

/**
 * [loadContextFromDisk description]
 * @param  [type]  $theDataDir         [description]
 * @param  boolean $theIncludeOverride [description]
 * @return [type]                      [description]
 */
function loadContextFromDisk($theDataDir) {
    $aContextPath = $theDataDir . DIRECTORY_SEPARATOR . BESEARCHER_CONTEXT_FILE;

    $aContext = false;
    $aContextRaw = @file_get_contents($aContextPath);

    if($aContextRaw !== false) {
        $aContext = unserialize($aContextRaw);
    }

    return $aContext;
}

/**
 * [loadContextFromDisk description]
 * @param  [type]  $theDataDir         [description]
 * @param  boolean $theIncludeOverride [description]
 * @return [type]                      [description]
 */
function loadOverrideContextFromDisk($theDataDir) {
    $aOverridePath = $theDataDir . DIRECTORY_SEPARATOR . BESEARCHER_CONTEXT_OVERRIDE_FILE;
    $aOverrideRaw = @file_get_contents($aOverridePath);
    $aContext = false;

    if($aOverrideRaw !== false) {
        // We have a new context to use. Let's return this one then.
        $aContext = unserialize($aOverrideRaw);

        // Get rid of override file
        unlink($aOverridePath);
    }

    return $aContext;
}

function hasOverrideContextInDisk($theDataDir) {
    $aOverridePath = $theDataDir . DIRECTORY_SEPARATOR . BESEARCHER_CONTEXT_OVERRIDE_FILE;
    return file_exists($aOverridePath);
}

function writeContextToDisk(& $theContext) {
    $theDataDir = get_ini('data_dir', $theContext, '');
    $aContextPath = $theDataDir . DIRECTORY_SEPARATOR . BESEARCHER_CONTEXT_FILE;
    $aSerializedContext = serialize($theContext);
    $aRet = @file_put_contents($aContextPath, $aSerializedContext);

    return $aRet;
}

function writeContextOverrideToDisk($theDataDir, $theDiff) {
    $aOverridePath = $theDataDir . DIRECTORY_SEPARATOR . BESEARCHER_CONTEXT_OVERRIDE_FILE;
    $aSerializedOverride = serialize($theDiff);
    $aRet = @file_put_contents($aOverridePath, $aSerializedOverride);

    return $aRet;
}


/**
 * Change the order of some tasks in the queue of tasks. The informed
 * taskes will be moved to the begining of the queue, so they are more likely
 * to be executed by Besearcher.
 *
 * @param  array $theTasks array with the tasks to be prioritized. Each entry in the array is an associtive array with the fields 'hash' and 'permutation'.
 * @param  array $theQueue queue of tasks
  */
function prioritizeTasksInQueue($theTasks, $theQueue) {
    $aExistingTasks = array();
    $aRelocatedTasks = array();

    if(count($theTasks) == 0) {
        throw new Exception('Unable to prioritize the selected elements.');
    }

    foreach($theTasks as $aItem) {
        foreach($theQueue as $aQueueItem) {
            if($aQueueItem['hash'] == $aItem['hash'] && $aQueueItem['permutation'] == $aItem['permutation']) {
                $aRelocatedTasks[] = $aQueueItem;
            }
        }
    }

    foreach($theQueue as $aQueueItem) {
        $aWasRelocatedBefore = false;
        foreach($aRelocatedTasks as $aItem) {
            if($aQueueItem['hash'] == $aItem['hash'] && $aQueueItem['permutation'] == $aItem['permutation']) {
                $aWasRelocatedBefore = true;
                break;
            }
        }

        if(!$aWasRelocatedBefore) {
            $aRelocatedTasks[] = $aQueueItem;
        }
    }

    return $aRelocatedTasks;
}

function removeTasksFromQueue($theTasks, $theQueue) {
    $aFilteredQueue = array();

    if(count($theTasks) == 0) {
        throw new Exception('Nothing has been selected for removal.');
    }

    foreach($theQueue as $aQueueItem) {
        $aFound = false;

        foreach($theTasks as $aItem) {
            if($aQueueItem['hash'] == $aItem['hash'] && $aQueueItem['permutation'] == $aItem['permutation']) {
                $aFound = true;
                break;
            }
        }

        if(!$aFound) {
            $aFilteredQueue[] = $aQueueItem;
        }
    }

    return $aFilteredQueue;
}

?>
