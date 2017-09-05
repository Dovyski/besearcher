<?php

/*
 Runs a command issued by Besearcher. This is a very nasty hack to provide
 forking capabilities to Besearcher on Windows, since pcntl_fork() is not available.

 Author: Fernando Bevilacqua <fernando.bevilacqua@his.se>
 */

 require_once(dirname(__FILE__) . '/../inc/constants.php');
 require_once(dirname(__FILE__) . '/../inc/Db.class.php');
 require_once(dirname(__FILE__) . '/../inc/Context.class.php');
 require_once(dirname(__FILE__) . '/../inc/Tasks.class.php');
 require_once(dirname(__FILE__) . '/../inc/Log.class.php');
 require_once(dirname(__FILE__) . '/../inc/App.class.php');

$aIniPath = $argv[1];
$aTaskId = $argv[2];

$aApp = new Besearcher\App();
$aApp->init($aIniPath, '', true);

$aData = $aApp->getData();
$aResult = $aData->getResultById($aTaskId);

if($aResult === false) {
    echo "Unable to load result with task id=" . $aTaskId . ".\n";
    exit(2);
}

$aCmd = $aResult['cmd'];
$aPathLogFile = $aResult['log_file'];

$aData->markResultAsRunning($aResult['id'], time());

$aOutput = array();
$aReturnCode = -1;
$aLastLine = exec($aCmd . ' > "'.$aPathLogFile.'"', $aOutput, $aReturnCode);

// TODO: parse besearcher tags in the log file

$aData->markResultAsFinished($aResult['id'], $aReturnCode, time());

exit(0);
