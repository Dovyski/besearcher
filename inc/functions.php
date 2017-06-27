<?php
/*
 This file contains a set of functions that are common to all
 tools of besearcher.

 Author: Fernando Bevilacqua <fernando.bevilacqua@his.se>
 */

function aggredateTaskInfos($theTaskJsonFiles) {
    $aInfos = array();

    foreach($theTaskJsonFiles as $aFile) {
        $aInfo = json_decode(file_get_contents($aFile), true);
        $aPermutation = $aInfo['permutation'];

        // TODO: get progress and result data from log file
        $aInfos[$aPermutation] = array(
            'commit'        => $aInfo['hash'],
            'permutation'   => $aPermutation,
            'date'          => date('d-m-Y H:i:s', $aInfo['time']),
            'params'        => $aInfo['params'],
            'cmd'           => $aInfo['cmd'],
            'progress'      => 0,
            'results'       => array(),
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
