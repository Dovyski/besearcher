<?php

/*
 Runs a command issued by Besearcher. This is a very nasty hack to provide
 forking capabilities to Besearcher on Windows, since pcntl_fork() is not available.

 Author: Fernando Bevilacqua <fernando.bevilacqua@his.se>
 */

require_once(dirname(__FILE__) . '/../inc/constants.php');
require_once(dirname(__FILE__) . '/../inc/Db.class.php');
require_once(dirname(__FILE__) . '/../inc/Context.class.php');
require_once(dirname(__FILE__) . '/../inc/AppControl.class.php');
require_once(dirname(__FILE__) . '/../inc/Tasks.class.php');
require_once(dirname(__FILE__) . '/../inc/Log.class.php');
require_once(dirname(__FILE__) . '/../inc/App.class.php');
require_once(dirname(__FILE__) . '/../inc/ResultOutputParser.class.php');

function instantiateApp($theIniPath) {
    $aApp = new Besearcher\App();
    $aApp->init($theIniPath, '', true);

    return $aApp;
}

$aIniPath = $argv[1];
$aTaskId = $argv[2];

$aApp = instantiateApp($aIniPath);
$aResult = $aApp->getData()->getResultById($aTaskId);

if($aResult === false) {
    echo "Unable to load result with task id=" . $aTaskId . ".\n";
    exit(2);
}

$aResultId = $aResult['id'];
$aCmd = $aResult['cmd'];
$aPathLogFile = $aResult['log_file'];

$aApp->getData()->updateResult($aResultId, array(
    'running' => 1,
    'exec_time_start' => time()
));

// We are about to start the execution of the task, which could take quite some time
// to finish running. We don't need to keep database connections and app context active
// during that time. For that reason, let's disconnect everything.
$aApp->shutdown();
unset($aApp);

// Run the command
$aOutput = array();
$aReturnCode = -1;
$aLastLine = exec($aCmd . ' > "'.$aPathLogFile.'"', $aOutput, $aReturnCode);

// From this point on, the result finished executing.
// Let's wake app up again.
$aApp = instantiateApp($aIniPath);

// Let's parse its output to find special Besearcher tags
$aParser = new Besearcher\ResultOutputParser($aResult);
$aTags = $aParser->getTags();

$aApp->getData()->updateResult($aResultId, array(
    'running' => 0,
    'progress' => 1.0,
    'cmd_return_code' => $aReturnCode,
    'exec_time_end' => time(),
    'log_file_tags' => serialize($aTags)
));

// Finish everything once and for all
$aApp->shutdown();

exit(0);
