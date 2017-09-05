<?php
    require_once(dirname(__FILE__) . '/inc/globals.php');

    Besearcher\Auth::allowAuthenticated();

    $aExperimentHash = isset($_REQUEST['experiment_hash']) ? $_REQUEST['experiment_hash'] : '';
    $aPermutationHash = isset($_REQUEST['permutation_hash']) ? $_REQUEST['permutation_hash'] : '';

    $aApp = Besearcher\WebApp::instance();
    $aResult = $aApp->getData()->getResultByHashes($aExperimentHash, $aPermutationHash);
    $aError = '';

    if($aResult == false) {
        $aError = 'Unknown result with experiment hash ' . Besearcher\view::out($aExperimentHash) . ' and permutation hash ' . $aPermutationHash;
    }

    $aLogPath = @$aResult['log_file'];
    $aLogContent = '';

    if(!empty($aLogPath)) {
        $aLogContent = @file_get_contents($aLogPath);
    }

    $aMeta = array();
    $aTags = @unserialize($aResult['log_file_tags']);

    if($aTags !== false) {
        foreach($aTags as $aItem) {
            if($aItem['type'] != BESEARCHER_TAG_TYPE_PROGRESS) {
                $aMeta[] = $aItem;
            }
        }
    }

    Besearcher\View::render('result', array(
        'result' => $aResult,
        'meta' => $aMeta,
        'log_content' => $aLogContent,
        'error' => $aError,
    ));
?>
