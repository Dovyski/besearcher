<?php

/*
 This script will run a set of commands based on the changes of a
 Git repository. It tracks changes in the repo and, for each new
 commit, runs a set of pre-defined commands.

 Author: Fernando Bevilacqua <fernando.bevilacqua@his.se>
 */

define('SAY_ERROR', 'ERROR');
define('SAY_INFO', 'INFO');
define('SAY_WARN', 'WARN');

/**
  * Get a value from the INI file. The key is first looked up
  * at the action section. If nothing is found, the key is
  * looked up at the whole INI file scope.
  *
  * @param  string $theKey      Key that represents an entry in the INI file.
  * @param  array $theContext   Array containing informatio regarding the app context.
  * @param  mixed $theDefault   Value to be returned if nothing is found.
  * @return mixed               Value of the informed key.
  */
function get_ini($theKey, $theContext, $theDefault = null) {
    $aINI = $theContext['ini_values'];
    $aRet = $theDefault;

    if(isset($aINI[$theKey])) {
        $aRet = $aINI[$theKey];
    }

    return $aRet;
}

function replaceConfigVars($theString, $theSubject, $theINIJob, $theINI) {
    $aSearch = array();
    $aReplaces = array();
    $aINI = $theINI;

    // Add meta keys
    foreach($theINI as $aKey => $aValue) {
        if(strpos($aKey, 'hcidb_') !== false) {
            $aINI[$aKey] = $aValue;
        }
    }

    // Add action keys
    foreach($theINIJob as $aKey => $aValue) {
        $aINI[$aKey] = $aValue;
    }

    unset($aINI['files']);
    unset($aINI['app']);

    addSearchAndReplaceEntries($aSearch, $aReplaces, $theSubject);
    addSearchAndReplaceEntries($aSearch, $aReplaces, $aINI);

    return str_replace($aSearch, $aReplaces, $theString);
}

function loadLastKnownCommitFromFile($theContext) {
    $aDataDir = get_ini('data_dir', $theContext);
    $aCommitFile = $aDataDir . DIRECTORY_SEPARATOR . 'beseacher.last-commit';
    $aFileContent = @file_get_contents($aCommitFile);
    $aValue = $aFileContent !== FALSE ? trim($aFileContent) : '';

    return $aValue;
}

function setLastKnownCommit(& $theContext, $theHash) {
    $theContext['last_commit'] = $theHash;

    say("Last known commit (on memory and on disk) changed to " . $theContext['last_commit'], SAY_INFO, $theContext);

    $aDataDir = get_ini('data_dir', $theContext);
    $aCommitFile = $aDataDir . DIRECTORY_SEPARATOR . 'beseacher.last-commit';
    file_put_contents($aCommitFile, $theContext['last_commit']);
}

function findNewCommits($theWatchDir, $theGitExe, $theLastCommitHash) {
    $aNewCommits = array();
    $aEntries = array();

    exec('cd ' . $theWatchDir . ' & ' . $theGitExe . ' log --pretty=oneline', $aEntries);

    $aShouldInclude = false;

    for($i = count($aEntries) - 1; $i >= 0; $i--) {
        $aCommit = $aEntries[$i];
        $aParts = explode(' ', $aCommit);
        $aHash = $aParts[0];

        if($aHash == $theLastCommitHash || $theLastCommitHash == '') {
            $aShouldInclude = true;
        }

        if($aShouldInclude && $aHash != $theLastCommitHash) {
            $aNewCommits[] = $aCommit;
        }
    }

    return $aNewCommits;
}

function execCommitTask($theWatchDir, $theHash, $theContext) {
    $aTaskCmd = get_ini('cmd', $theContext);
    $aDataDir = get_ini('data_dir', $theContext);
    $aLogFile = $aDataDir . DIRECTORY_SEPARATOR . $theHash . '.log';
    $aFinalCmd = 'cd ' . $theWatchDir . ' & ' . $aTaskCmd . ' > ' . $aLogFile;

    say("Issuing task: '" . $aFinalCmd . "'", SAY_INFO, $theContext);
    exec($aFinalCmd, $aOutput);
}

function run(& $theContext) {
    $aWatchDir = get_ini('watch_dir', $theContext);
    $aGitExe = get_ini('git', $theContext);

    $aTasks = findNewCommits($aWatchDir, $aGitExe, $theContext['last_commit']);
    $aLastHash = '';

    if(count($aTasks) > 0) {
        foreach($aTasks as $aCommit) {
            $aDivider = strpos($aCommit, ' ');
            $aHash = substr($aCommit, 0, $aDivider);
            $aMessage = substr($aCommit, $aDivider);
            $aLastHash = $aHash;

            say("New commit (" . $aHash . "): " . $aMessage, SAY_INFO, $theContext);
            execCommitTask($aWatchDir, $aHash, $theContext);
        }

        setLastKnownCommit($theContext, $aLastHash);
    }

    return true;
}

function performConfigHotReload(& $theContext) {
    $aPath = $theContext['ini_path'];

    if(!file_exists($aPath)) {
        say("Informed INI file is invalid: '" . $aPath . "'", SAY_ERROR, $theContext);
        exit(2);
    }

    $aContentHash = md5(file_get_contents($aPath));

    if($aContentHash != $theContext['ini_hash']) {
        say("Content of INI file has changed. Reloading it.", SAY_INFO, $theContext);

        $theContext['ini_values'] = parse_ini_file($aPath, true);
        $theContext['ini_hash'] = $aContentHash;
    }

    $aLastCommitDisk = loadLastKnownCommitFromFile($theContext);

    // If we don't have any information regarding the last commit, we use
    // the one provided in the ini file.
    if(empty($aLastCommitDisk)) {
        $aLastCommitDisk = get_ini('start_commit_hash', $theContext, '');
        say("No commit info found on disk, using info from INI: " . $aLastCommitDisk, SAY_INFO, $theContext);
    }

    if($aLastCommitDisk != $theContext['last_commit']) {
        say("Info regarding last commit has changed: old=" . $theContext['last_commit'] . ", new=" . $aLastCommitDisk, SAY_INFO, $theContext);
        setLastKnownCommit($theContext, $aLastCommitDisk);
    }
}

function say($theMessage, $theType, $theContext) {
    echo date('[Y-m-d H:i:s]') . ' [' . $theType . '] ' . $theMessage . "\n";
}

function rest($theContext) {
    $aDuration = get_ini('sleep_time', $theContext, 1);
    sleep($aDuration);
}

$aOptions = array(
    "log:",
    "ini:",
);

$aArgs = getopt("", $aOptions);

if($argc <= 1) {
     echo "Usage: \n";
     echo " php ".basename($_SERVER['PHP_SELF']) . " [options]\n\n";
     echo "Options:\n";
     echo " --log=<path>     Path to the log file.\n";
     echo "\n";
     echo " --ini=<path>     Path to the INI files used for configuration.\n";
     echo "\n";
     exit(1);
}

$aContext = array(
    'ini_path' => isset($aArgs['ini']) ? $aArgs['ini'] : '',
    'ini_hash' => '',
    'ini_values' => '',
    'last_commit' => '',
    'log_file' => isset($aArgs['log']) ? $aArgs['log'] : ''
);

performConfigHotReload($aContext);

$aActive = true;

while($aActive) {
    $aActive = run($aContext);
    rest($aContext);
    performConfigHotReload($aContext);
}

exit(0);
?>
