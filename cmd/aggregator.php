<?php

/*
 Loads task result files and produces a summary of their data.

 Author: Fernando Bevilacqua <fernando.bevilacqua@his.se>
 */

 require_once(dirname(__FILE__) . '/../inc/constants.php');
 require_once(dirname(__FILE__) . '/../inc/Db.class.php');
 require_once(dirname(__FILE__) . '/../inc/Context.class.php');
 require_once(dirname(__FILE__) . '/../inc/Tasks.class.php');

$aOptions = array(
    "ini:",
    "create-web-cache"
);

$aArgs = getopt("", $aOptions);

if($argc <= 1) {
     echo "Usage: \n";
     echo " php ".basename($_SERVER['PHP_SELF']) . " [options]\n\n";
     echo "Options:\n";
     echo " --ini=<path>         Path to the INI file being used by Besearcher.\n";
     echo " --create-web-cache   If present, all task data will be loaded and used\n";
     echo "                      to create a cache for the web dashboard.\n";
     echo "\n";
     exit(1);
}

$aIniPath = isset($aArgs['ini']) ? $aArgs['ini'] : '';

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
    echo "Besearcher database is not ready. Please, run besearcher at least once before using this \"aggregator\" command line tool.\n";
    exit(2);
}

$aTasks = new Besearcher\Tasks($aDb);

// Load the data
$aData = $aTasks->findTasksInfos($aDataDir);

$aShouldCreateCache = isset($aArgs['create-web-cache']);

if($aShouldCreateCache) {
    $aCacheFile = $aDataDir . DIRECTORY_SEPARATOR . BESEARCHER_WEB_CACHE_FILE;
    $aSerializedData = serialize($aData);
    file_put_contents($aCacheFile, $aSerializedData);

    echo "Cache file successfully created at: " . $aCacheFile . "\n";
} else {
    // Just output the aggredated data
    echo json_encode($aData, JSON_PRETTY_PRINT);
}

exit(0);

?>
