<?php

/*
 Runs a command issued by Besearcher. This is a very nasty hack to provide
 forking capabilities to Besearcher on Windows, since pcntl_fork() is not available.

 Author: Fernando Bevilacqua <fernando.bevilacqua@his.se>
 */

 require_once(dirname(__FILE__) . '/../inc/constants.php');
 require_once(dirname(__FILE__) . '/../inc/Db.class.php');
 require_once(dirname(__FILE__) . '/../inc/Tasks.class.php');

$aIniPath = $argv[1];
$aTaskId = $argv[2];

if(!file_exists($aIniPath)) {
    echo "Unable to load INI file: " . $aIniPath . "\n";
    exit(1);
}

$aINI = parse_ini_file($aIniPath);
$aDataDir = @$aINI['data_dir'];

if(!file_exists($aDataDir)) {
    echo "Unable to access data directory informed in INI file: " . $aDataDir . "\n";
    exit(1);
}

$aDbPath = $aINI['data_dir'] . DIRECTORY_SEPARATOR . BESEARCHER_DB_FILE;

$aDb = new Besearcher\Db($aDbPath, false);
$aTasks = new Besearcher\Tasks($aDb);

$aResult = $aTasks->getResultById($aTaskId);

if($aResult === false) {
    echo "Unable to load result with task id=" . $aTaskId . ".\n";
    exit(2);
}

$aCmd = $aResult['cmd'];
$aPathLogFile = $aResult['log_file'];

$aTasks->markResultAsRunning($aResult['id'], time());

$aOutput = array();
$aReturnCode = -1;
$aLastLine = exec($aCmd . ' > "'.$aPathLogFile.'"', $aOutput, $aReturnCode);

// TODO: parse besearcher tags in the log file

$aTasks->markResultAsFinished($aResult['id'], $aReturnCode, time());

exit(0);
