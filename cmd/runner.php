<?php

/*
 Runs a command issued by Besearcher. This is a very nasty hack to provide
 forking capabilities to Besearcher on Windows, since pcntl_fork() is not available.

 Author: Fernando Bevilacqua <fernando.bevilacqua@his.se>
 */

 function writeDataToTaskInfoFile($thePathInfoFile, $theKeyValue) {
     $aContent = file_get_contents($thePathInfoFile);
     $aJson = json_decode($aContent, true);

     foreach($theKeyValue as $aKey => $aValue) {
         $aJson[$aKey] = $aValue;
     }

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

writeDataToTaskInfoFile($aPathInfoFile, array(
    'cmd_return_code' => $aReturnCode,
    'exec_time_end' => time()
));

exit(0);
