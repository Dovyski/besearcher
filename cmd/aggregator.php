<?php

/*
 Loads task result files and produces a summary of their data.

 Author: Fernando Bevilacqua <fernando.bevilacqua@his.se>
 */

require_once(dirname(__FILE__) . '/../inc/functions.php');

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

// Load the data
$aData = findTasksInfos($aDataDir);

$aShouldCreateCache = isset($aArgs['create-web-cache']);

if($aCreateCache) {
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
