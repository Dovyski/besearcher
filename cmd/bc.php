<?php

/*
 Loads task result files and produces a summary of their data.

 Author: Fernando Bevilacqua <fernando.bevilacqua@his.se>
 */

require_once(dirname(__FILE__) . '/../inc/functions.php');
require_once(dirname(__FILE__) . '/../inc/Db.class.php');
require_once(dirname(__FILE__) . '/../inc/Context.class.php');

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
     echo " --reload             Clear the last known commit and task cache files on disk,\n";
     echo "                      forcing Besearcher to perform tasks since the last commit\n";
     echo "                      informed in the INI file (via start_commit_hash). If the\n";
     echo "                      directive skip_performed_tasks is true, already performed\n";
     echo "                      tasks will be skipped.\n";
     echo " --reset              Clear the context and the last known commit info on disk.\n";
     echo " --force, -f          Perform operations without asking for confirmation.\n";
     echo " --verbose, -v        Print extra info for performed actions.\n";
     echo " --help, -h           Show this help.\n";
     echo "\n";
     exit(1);
}

$aIniPath = isset($aArgs['ini']) ? $aArgs['ini'] : '';

if(empty($aIniPath)) {
    echo "Please provide the path to besearch's config INI file using --ini=<path>.\n";
    exit(1);
}

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

if(!$aDb->hasTables()) {
    echo "Besearcher database is not ready. Please, run besearcher at least once before using this \"bc\" command line tool.\n";
    exit(2);
}

$aContext = new Besearcher\Context($aDb);
$aContext->load();

$aIsVerbose = isset($aArgs['v']) || isset($aArgs['verbose']);
$aIsForce = isset($aArgs['f']) || isset($aArgs['force']);

if(isset($aArgs['status'])) {
    $aQueueSize = $aDb->tasksQueueSize();
    echo "Besearcher summary:\n";
    echo " Status: ".$aContext->get('status')."\n";
    echo " Last commit: ".$aContext->get('last_commit')."\n";
    echo " Tasks waiting in queue: ". $aQueueSize."\n";
    echo " Tasks running: ". $aContext->get('running_tasks')."\n";
}

if(isset($aArgs['pause']) || isset($aArgs['resume']) || isset($aArgs['stop'])) {
    $aStatus = '';
    $aText = '';

    if(isset($aArgs['pause'])) { $aStatus = BESEARCHER_STATUS_PAUSED; $aText = 'pause'; }
    if(isset($aArgs['resume'])) { $aStatus = BESEARCHER_STATUS_RUNNING; $aText = 'resume'; }

    if(isset($aArgs['stop'])) {
        if(!$aIsForce) {
            confirmOperation("Stop and lose data from unfinished tasks");
        }
        $aStatus = BESEARCHER_STATUS_STOPPING;
        $aText = 'stop';
    }

    $aContext->set('status', $aStatus);
    echo "Ok, besearcher will ".$aText.".\n";
}

if(isset($aArgs['reload'])) {
    if(!$aIsForce) {
        confirmOperation();
    }

    $aDeleteList = array();
    $aData = findTasksInfos($aDataDir);

    foreach($aData as $aCommitHash => $aTasks) {
        foreach($aTasks as $aPermutation => $aTask) {
            $aCachePath = $aTask['raw']['log_file'] . BESEARCHER_CACHE_FILE_EXT;
            $aDeleteList[] = $aCachePath;
        }
    }

    deleteFilesList($aDeleteList, 2, $aIsVerbose);
    $aContext->set('last_commit', '');

    echo "Ok, besearcher was reloaded successfully!\n";
}

if(isset($aArgs['reset'])) {
    if(!$aIsForce) {
        confirmOperation();
    }

    $aDb->destroy();
    echo "Ok, besearcher was reset successfully!\n";
}

exit(0);

?>
