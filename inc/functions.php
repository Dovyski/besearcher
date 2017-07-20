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

define('BESEARCHER_COMMIT_SKIP_TOKEN',       '/\[(skip-ci|skip|skip-ic|skip-besearcher)\]/');

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

?>
