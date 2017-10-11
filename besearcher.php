<?php

/*
 Main file for Besearcher. This script is the deamon that will periodically check
 the informed Git repository. It tracks changes in the repo and, for each new
 commit, runs a set of pre-defined commands.

 Author: Fernando Bevilacqua <fernando.bevilacqua@his.se>
 */

require_once(dirname(__FILE__) . '/inc/constants.php');
require_once(dirname(__FILE__) . '/inc/Db.class.php');
require_once(dirname(__FILE__) . '/inc/Log.class.php');
require_once(dirname(__FILE__) . '/inc/Context.class.php');
require_once(dirname(__FILE__) . '/inc/Tasks.class.php');
require_once(dirname(__FILE__) . '/inc/Analytics.class.php');
require_once(dirname(__FILE__) . '/inc/App.class.php');
require_once(dirname(__FILE__) . '/inc/ResultOutputParser.class.php');

$aOptions = array(
    "log:",
    "ini:",
);

$aArgs = getopt("", $aOptions);

if($argc <= 1) {
     echo "Usage: \n";
     echo " php ".basename($_SERVER['PHP_SELF']) . " [options]\n\n";
     echo "Options:\n";
     echo " --log=<path>     Path to the log file. If nothing is provided,\n";
     echo "                  log messages will be output to STDOUT.\n";
     echo "\n";
     echo " --ini=<path>     Path to the INI files used for configuration.\n";
     echo "\n";
     exit(1);
}

$aIniPath = isset($aArgs['ini']) ? $aArgs['ini'] : '';
$aLogPath = isset($aArgs['log']) ? $aArgs['log'] : '';

try {
    $aApp = new Besearcher\App();
    $aApp->init($aIniPath, $aLogPath);
    $aApp->run();
    $aApp->shutdown();
    exit(0);

} catch(Exception $e) {
    $aLogger = $aApp->getLogger();

    if($aLogger != null) {
        $aLogger->error($e->getMessage());
    }

    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(2);
}

?>
