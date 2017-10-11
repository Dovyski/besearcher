<?php

/*
 Loads task result files and produces a summary of their data.

 Author: Fernando Bevilacqua <fernando.bevilacqua@his.se>
 */

require_once(dirname(__FILE__) . '/../inc/constants.php');
require_once(dirname(__FILE__) . '/../inc/Db.class.php');
require_once(dirname(__FILE__) . '/../inc/Context.class.php');
require_once(dirname(__FILE__) . '/../inc/Tasks.class.php');
require_once(dirname(__FILE__) . '/../inc/Log.class.php');
require_once(dirname(__FILE__) . '/../inc/App.class.php');

function deleteFilesList($theList, $theExitCode = 1, $theVerbose = false) {
    foreach($theList as $aPath) {
        if(file_exists($aPath)) {
            if($theVerbose) {
                echo " removing: ". $aPath . "\n";
            }

            $aOk = @unlink($aPath);

            if(!$aOk) {
                echo "Unable to remove file: \"" . $aPath . "\". Is it locked by another program?\n";
                exit($theExitCode);
            }
        }
    }
}

function confirmOperation($theText = "Operation can't be undone, proceed") {
    echo $theText . " (y/n)? ";
    fscanf(STDIN, "%s", $aAnswer);

    if(strtolower($aAnswer) == 'n') {
        exit(0);
    }
}

$aOptions = array(
    "ini:",
    "status",
    "pause",
    "stop",
    "resume",
    "reload",
    "reset",
    "verbose",
    "force",
    "help"
);

$aArgs = getopt("hvf", $aOptions);

if(isset($aArgs['h']) || isset($aArgs['help']) || $argc == 1) {
     echo "Usage: \n";
     echo " php ".basename($_SERVER['PHP_SELF']) . " [options]\n\n";
     echo "Options:\n";
     echo " --ini=<path>         Path to the INI file being used by Besearcher.\n";
     echo " --status             Show a few info about the running instance of Besearcher.\n";
     echo " --pause              Pause Besearcher. Currently running tasks will\n";
     echo "                      continue to run, but new tasks will not be created.\n";
     echo " --stop               Stop Besearcher, but wait for all running tasks to\n";
     echo "                      finish first.";
     echo " --resume             Make Besearcher resume its operation, if it is paused.\n";
     echo " --reload             Clear cache files on disk forcing Besearcher to create\n";
     echo "                      and perform experiment tasks. If the directive \n";
     echo "                      skip_performed_tasks is true, already performed tasks\n";
     echo "                      will be skipped.\n";
     echo " --reset              Delete *ALL* control settings, like records of enqued\n";
     echo "                      tasks. Result files created by previous completed tasks\n";
     echo "                      are preserved.\n";
     echo " --force, -f          Perform operations without asking for confirmation.\n";
     echo " --verbose, -v        Print extra info for performed actions.\n";
     echo " --help, -h           Show this help.\n";
     echo "\n";
     exit(1);
}

$aIniPath = isset($aArgs['ini']) ? $aArgs['ini'] : '';

$aApp = new Besearcher\App();
$aApp->init($aIniPath, '', true);

$aContext = $aApp->getContext();
$aData = $aApp->getData();

$aIsVerbose = isset($aArgs['v']) || isset($aArgs['verbose']);
$aIsForce = isset($aArgs['f']) || isset($aArgs['force']);

if(isset($aArgs['status'])) {
    $aQueueSize = $aData->queueSize();
    $aRunningTasks = count($aData->findRunningTasks());
    $aStatus = $aContext->get('status');

    echo "Besearcher summary:\n";
    echo " Status: ".(empty($aStatus) ? "***UNKNOWN***" : $aStatus)."\n";
    echo " INI file: ".$aIniPath."\n";
    echo " Experiment hash: ".$aContext->get('experiment_hash')."\n";
    echo " Experiment description: ".trim($aApp->config('experiment_description'))."\n";
    echo " Experiment ready: ".$aApp->config('experiment_ready')."\n";
    echo " Tasks waiting in queue: ". $aQueueSize."\n";
    echo " Tasks running: ". $aRunningTasks."\n";

    if(empty($aStatus)) {
        echo "WARNING: Besearcher has never run using the provided INI file." . "\n";
    }
}

if(isset($aArgs['pause']) || isset($aArgs['resume']) || isset($aArgs['stop'])) {
    $aStatus = '';
    $aText = '';
    $aRunningTasks = count($aData->findRunningTasks());

    if(isset($aArgs['pause'])) { $aStatus = BESEARCHER_STATUS_PAUSED; $aText = 'pause'; }
    if(isset($aArgs['resume'])) { $aStatus = BESEARCHER_STATUS_RUNNING; $aText = 'resume'; }

    if(isset($aArgs['stop'])) {
        if(!$aIsForce && $aRunningTasks > 0) {
            confirmOperation('Stop and cancel '.$aRunningTasks.' unfinished tasks');
        }
        // TODO: cancel running tasks
        $aStatus = BESEARCHER_STATUS_STOPPING;
        $aText = 'stop';
    }

    $aApp->setStatus($aStatus);
    echo "Ok, besearcher will ".$aText.".\n";
}

if(isset($aArgs['reload'])) {
    if(!$aIsForce) {
        confirmOperation();
    }

    $aContext->set('experiment_hash', '');
    $aContext->set('experiment_ready', 0);

    echo "Ok, besearcher was reloaded successfully!\n";
}

if(isset($aArgs['reset'])) {
    if(!$aIsForce) {
        confirmOperation();
    }

    $aApp->getDb()->destroy();
    echo "Ok, besearcher was reset successfully!\n";
}

exit(0);

?>
