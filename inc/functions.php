<?php
/*
 This file contains a set of functions that are common to all
 tools of besearcher.

 Author: Fernando Bevilacqua <fernando.bevilacqua@his.se>
 */

define('BESEARCHER_TAG',                    '[BSR]');
define('BESEARCHER_TAG_TYPE_RESULT',        'result');
define('BESEARCHER_TAG_TYPE_PROGRESS',      'progress');

define('BESEARCHER_CACHE_FILE',              '.besearcher-cache');

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
    return $theTaskInfo['time_end'] != 0;
}

function handleBesearcherLogTags($theTaskInfo, $theUseCache = true) {
    $aTags = array();

    if(isTaskFinished($theTaskInfo) && $theUseCache) {
        // Task has finished, it might be a cached version of the log file.
        $aCacheFilePath = $theTaskInfo['log_file'] . BESEARCHER_CACHE_FILE;

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
            'commit'        => $aInfo['hash'],
            'commit_message'=> @$aInfo['message'],
            'permutation'   => $aPermutation,
            'date'          => date('Y-m-d H:i:s', $aInfo['time']),
            'params'        => $aInfo['params'],
            'cmd'           => $aInfo['cmd'],
            'progress'      => calculateTaskProgressFromTags($aTags),
            'meta'          => $aTags,
            'raw'           => $aInfo
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
