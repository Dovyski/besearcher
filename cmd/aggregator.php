<?php

/*
 Loads task result files and produces a summary of their data.

 Author: Fernando Bevilacqua <fernando.bevilacqua@his.se>
 */

require_once(dirname(__FILE__) . '/../inc/functions.php');

$aOptions = array(
    "ini:"
);

$aArgs = getopt("", $aOptions);

if($argc <= 1) {
     echo "Usage: \n";
     echo " php ".basename($_SERVER['PHP_SELF']) . " [options]\n\n";
     echo "Options:\n";
     echo " --ini=<path>     Path to the INI file being used by Besearcher.\n";
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

$aData = findTasksInfos($aDataDir);
echo json_encode($aData, JSON_PRETTY_PRINT);

exit(0);

?>
