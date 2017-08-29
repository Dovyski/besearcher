<?php

/*
 Loads task result files and produces a summary of their data.

 Author: Fernando Bevilacqua <fernando.bevilacqua@his.se>
 */

require_once(dirname(__FILE__) . '/../inc/functions.php');

function deleteFilesList($theList, $theExitCode = 1, $theVerbose = false) {
    foreach($theList as $aPath) {
        if(file_exists($aPath)) {
            if($theVerbose) {
                echo " removing: ". $aPath . "\n";
            }

            $aOk = unlink($aPath);

            if(!$aOk) {
                echo "Unable to remove file: " . $aPath . ". Is it locked by another program?\n";
                exit($theExitCode);
            }
        }
    }
}

function confirmOperation() {
    echo "Operation can't be undone, proceed (y/n)? ";
    fscanf(STDIN, "%s", $aAnswer);

    if(strtolower($aAnswer) == 'n') {
        exit(0);
    }
}

$aOptions = array(
    "ini:",
    "status",
    "pause",
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
$aRefreshInterval = @$aINI['refresh_interval'];

if(!file_exists($aDataDir)) {
    echo "Unable to access data directory informed in INI file: " . $aDataDir . "\n";
    exit(1);
}

$aIsVerbose = isset($aArgs['v']) || isset($aArgs['verbose']);
$aIsForce = isset($aArgs['f']) || isset($aArgs['force']);

if(isset($aArgs['status'])) {
    $aContext = loadContextFromDisk($aDataDir);
    echo "Besearcher summary:\n";
    echo "Status: ".$aContext['status']."\n";
    echo "Last commit: ".$aContext['last_commit']."\n";
    echo "Tasks waiting in queue: ". count($aContext['tasks_queue'])."\n";
    echo "Tasks running: ". $aContext['running_tasks']."\n";
    echo "Pending changes: ". (hasOverrideContextInDisk($aDataDir) ? "YES" : "no")."\n";
}

if(isset($aArgs['pause']) || isset($aArgs['resume'])) {
    $aOverride = array();
    $aStatus = isset($aArgs['pause']) ? BESEARCHER_STATUS_PAUSED : BESEARCHER_STATUS_RUNNING;
    $aText = isset($aArgs['pause']) ? "pause" : "resume";

    $aOverride['status'] = $aStatus;
    $aOk = writeContextOverrideToDisk($aDataDir, $aOverride);

    if(!$aOk) {
        echo "Unable to ".$aText." besearcher!\n";
        exit(1);
    } else {
        echo "Ok, besearcher will ".$aText." in ".$aRefreshInterval." seconds!\n";
    }
}

if(isset($aArgs['reload'])) {
    if(!$aIsForce) {
        confirmOperation();
    }

    $aDeleteList = array();
    $aDeleteList[] = $aDataDir . DIRECTORY_SEPARATOR . BESEARCHER_LAST_COMMIT_FILE;

    $aData = findTasksInfos($aDataDir);

    foreach($aData as $aCommitHash => $aTasks) {
        foreach($aTasks as $aPermutation => $aTask) {
            $aCachePath = $aTask['raw']['log_file'] . BESEARCHER_CACHE_FILE_EXT;
            $aDeleteList[] = $aCachePath;
        }
    }

    deleteFilesList($aDeleteList, 2, $aIsVerbose);
    echo "Ok, besearcher was reloaded successfully!\n";
}

if(isset($aArgs['reset'])) {
    if(!$aIsForce) {
        confirmOperation();
    }

    $aBasePath = $aDataDir . DIRECTORY_SEPARATOR;
    $aFiles = array(
        $aBasePath . BESEARCHER_LAST_COMMIT_FILE,
        $aBasePath . BESEARCHER_CONTEXT_FILE
    );

    deleteFilesList($aFiles, 3, $aIsVerbose);
    echo "Ok, besearcher was reset successfully!\n";
}

exit(0);

?>
