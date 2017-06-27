<?php

/*
 This script walk among the result files produced by command tasks
 producing a report.

 Author: Fernando Bevilacqua <fernando.bevilacqua@his.se>
 */

function aggredateTaskInfos($theTaskJsonFiles) {
    $aInfos = array();

    foreach($theTaskJsonFiles as $aFile) {
        $aInfo = json_decode(file_get_contents($aFile), true);

        // TODO: get progress and result data from log file
        $aInfos[] = array(
            'commit'        => $aInfo['hash'],
            'permutation'   => $aInfo['permutation'],
            'date'          => date('d-m-Y H:i:s', $aInfo['time']),
            'params'        => $aInfo['params'],
            'cmd'           => $aInfo['cmd'],
            'progress'      => 0,
            'results'       => array(),
            'raw'           => $aInfo
        );
    }

    return $aInfos;
}

$aOptions = array(
    "ini:"
);

$aArgs = getopt("", $aOptions);

if($argc <= 1) {
     echo "Usage: \n";
     echo " php ".basename($_SERVER['PHP_SELF']) . " [options]\n\n";
     echo "Options:\n";
     echo " --ini=<path>     Path to the INI files used for configuration.\n";
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

$aData = array();
$aTasks = scandir($aDataDir);

foreach($aTasks as $aItem) {
    $aPath = $aDataDir . DIRECTORY_SEPARATOR . $aItem;
    if($aItem[0] != '.' && is_dir($aPath)) {
        $aFiles = glob($aPath . DIRECTORY_SEPARATOR . '*.json');
        $aData[$aItem] = aggredateTaskInfos($aFiles);
    }
}

echo json_encode($aData, JSON_PRETTY_PRINT);
exit(0);

?>
