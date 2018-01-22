<?php
    require_once(dirname(__FILE__) . '/inc/globals.php');

    Besearcher\Auth::allowAuthenticated();

    $aExperimentHash = isset($_REQUEST['experiment_hash']) ? $_REQUEST['experiment_hash'] : '';
    $aPermutationHash = isset($_REQUEST['permutation_hash']) ? $_REQUEST['permutation_hash'] : '';

    $aApp = Besearcher\WebApp::instance();
    $aResult = $aApp->getData()->getResultByHashes($aExperimentHash, $aPermutationHash);
    $aInvalid = '';

    if($aResult == false) {
        $aInvalid = 'Unknown result with experiment hash <code>' . Besearcher\view::out($aExperimentHash) . '</code> and permutation hash <code>' . $aPermutationHash . '</code>. The task associated with this result is probably still in the <a href="queue.php">queue</a> waiting to be processed.';

        Besearcher\View::render('result', array(
            'result' => null,
            'invalid' => $aInvalid,
        ));

        exit();
    }

    $aMessage = '';
    $aMessageType = 'success';

    $aShouldReRun = isset($_REQUEST['rerun']);

    if($aShouldReRun) {
        $aOk = $aApp->rerunResult($aResult['id']);
        $aMessageType = !$aOk ? 'danger' : 'success';
        $aMessage = $aOk ? '<strong>All good!</strong> Result will re-run in the next batch of tasks.' : '<strong>Oops!</strong> Unable to re-run result. Is it a valid one?';
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

    $aResultParams = array();

    if(!empty($aResult['params'])) {
        $aResultParams = Besearcher\Utils::splitParamsString($aResult['params']);
    }

    Besearcher\View::render('result', array(
        'result' => $aResult,
        'params' => $aResultParams,
        'meta' => $aMeta,
        'log_content' => $aLogContent,
        'message' => $aMessage,
        'message_type' => $aMessageType
    ));
?>
