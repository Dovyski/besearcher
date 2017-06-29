<?php

/*
 This script will run a command issued by besearcher. This is
 a very nasty hack to provide forking capabilities to besearcher
 on Windows, since pcntl_fork() is not available.

 Author: Fernando Bevilacqua <fernando.bevilacqua@his.se>
 */

 function writeDataToTaskInfoFile($thePathInfoFile, $theKey, $theValue) {
     $aContent = file_get_contents($thePathInfoFile);
     $aJson = json_decode($aContent, true);
     $aJson[$theKey] = $theValue;

     file_put_contents($thePathInfoFile, json_encode($aJson, JSON_PRETTY_PRINT));
 }

if($argc != 4) {
    echo "No params informed." . "\n";
    exit(1);
}

$aCmd = $argv[1];
$aPathLogFile = $argv[2];
$aPathInfoFile = $argv[3];

$aOutput = array();
$aReturnCode = -1;
$aLastLine = exec($aCmd . ' > "'.$aPathLogFile.'"', $aOutput, $aReturnCode);

writeDataToTaskInfoFile($aPathInfoFile, 'cmd_return_code', $aReturnCode);

exit(0);
