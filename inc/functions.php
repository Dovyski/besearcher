<?php
/*
 This file contains a set of functions that are common to all
 tools of besearcher.

 Author: Fernando Bevilacqua <fernando.bevilacqua@his.se>
 */

require_once(dirname(__FILE__) . '/constants.php');

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

function loadTask($theInfoFilePath) {
    $aInfo = json_decode(file_get_contents($theInfoFilePath), true);
    return $aInfo;
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
        $aInfo = loadTask($aFile);
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
function loadOverrideContextFromDisk($theDataDir) {
    $aOverridePath = $theDataDir . DIRECTORY_SEPARATOR . BESEARCHER_CONTEXT_OVERRIDE_FILE;
    $aOverrideRaw = @file_get_contents($aOverridePath);
    $aContext = false;

    if($aOverrideRaw !== false) {
        // We have a new context to use. Let's return this one then.
        $aContext = @unserialize($aOverrideRaw);

        // Get rid of override file
        if($aContext !== false) {
            unlink($aOverridePath);
        }
    }

    return $aContext;
}

/**
 * Change the order of some tasks in the queue of tasks. The informed
 * taskes will be moved to the begining of the queue, so they are more likely
 * to be executed by Besearcher.
 *
 * @param  array $theTasks array with the tasks to be prioritized. Each entry in the array is an associtive array with the fields 'hash' and 'permutation'.
 * @param  array $theQueue queue of tasks
  */
function prioritizeTasksInQueue($theTaskIds, Besearcher\Db $theDb) {
    $aCastedIds = array();

    foreach($theTaskIds as $aId) {
        $aCastedIds[] = $aId + 0;
    }

    if(count($aCastedIds) == 0) {
        throw new Exception('Nothing has been selected for removal.');
    }

    $aStmt = $theDb->getPDO()->prepare("UPDATE tasks SET creation_time = 0 WHERE id IN (".implode(',', $aCastedIds).")");
    $aStmt->execute();
}

function removeTasksFromQueue($theTaskIds, Besearcher\Db $theDb) {
    $aCastedIds = array();

    foreach($theTaskIds as $aId) {
        $aCastedIds[] = $aId + 0;
    }

    if(count($aCastedIds) == 0) {
        throw new Exception('Nothing has been selected for removal.');
    }

    $aStmt = $theDb->getPDO()->prepare("DELETE FROM tasks WHERE id IN (".implode(',', $aCastedIds).")");
    $aOk = $aStmt->execute();
}
?>
