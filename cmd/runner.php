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
 require_once(dirname(__FILE__) . '/../inc/ResultOutputParser.class.php');

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

$aData->updateResult($aResult['id'], array(
    'running' => 1,
    'exec_time_start' => time()
));

$aOutput = array();
$aReturnCode = -1;
$aLastLine = exec($aCmd . ' > "'.$aPathLogFile.'"', $aOutput, $aReturnCode);

// From this point on, the result finished executing. Let's
// parse its output to find special Besearcher tags
$aParser = new Besearcher\ResultOutputParser($aResult);
$aTags = $aParser->getTags();

$aData->updateResult($aResult['id'], array(
    'running' => 0,
    'progress' => 1.0,
    'cmd_return_code' => $aReturnCode,
    'exec_time_end' => time(),
    'log_file_tags' => serialize($aTags)
));

exit(0);
