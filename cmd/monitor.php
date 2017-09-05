<?php

/**
 * Monitor and updates the progress of running results.
 *
 * Author: Fernando Bevilacqua <fernando.bevilacqua@his.se>
 */

require_once(dirname(__FILE__) . '/../inc/constants.php');
require_once(dirname(__FILE__) . '/../inc/Db.class.php');
require_once(dirname(__FILE__) . '/../inc/Context.class.php');
require_once(dirname(__FILE__) . '/../inc/Tasks.class.php');
require_once(dirname(__FILE__) . '/../inc/Log.class.php');
require_once(dirname(__FILE__) . '/../inc/App.class.php');
require_once(dirname(__FILE__) . '/../inc/ResultOutputParser.class.php');

$aOptions = array(
    "ini:"
);

$aArgs = getopt("", $aOptions);

if($argc <= 1) {
     echo "Usage: \n";
     echo " php ".basename($_SERVER['PHP_SELF']) . " [options]\n\n";
     echo "Options:\n";
     echo " --ini=<path>         Path to the INI file being used by Besearcher.\n";
     echo "\n";
     exit(1);
}

$aIniPath = isset($aArgs['ini']) ? $aArgs['ini'] : '';

$aApp = new Besearcher\App();
$aApp->init($aIniPath, '', true);

$aResults = $aApp->getData()->findRunningTasks();
$aSize = count($aResults);

if($aSize > 0) {
    echo 'Running results: ' . count($aResults) . "\n";

    foreach($aResults as $aResult) {
        $aParser = new Besearcher\ResultOutputParser($aResult);

        echo ' result ' . $aResult['id'] . ' progress: ';

        $aTags = $aParser->getTags();
        $aProgress = $aParser->calculateTaskProgress();

        $aApp->getData()->updateResult($aResult['id'], array(
            'progress' => $aProgress,
            'log_file_tags' => serialize($aTags)
        ));

        $aParser = null;
        echo sprintf('%.2f%%', $aProgress * 100) . "\n";
    }
    echo 'Finished!' . "\n";
} else {
    echo 'No results are currently running.' . "\n";
}

exit(0);

?>
