<?php
    require_once(dirname(__FILE__) . '/inc/globals.php');

    Besearcher\Auth::allowAuthenticated();

    $aCommit = isset($_REQUEST['commit']) ? $_REQUEST['commit'] : '';
    $aPermutation = isset($_REQUEST['permutation']) ? $_REQUEST['permutation'] : '';

    $aTasks = Besearcher\Data::tasks();
    $aError = '';
    $aData = array();

    if(isset($aTasks[$aCommit])) {
        if(isset($aTasks[$aCommit][$aPermutation])) {
            $aData = $aTasks[$aCommit][$aPermutation];
        } else {
            $aError = 'Unknown result with hash ' . Besearcher\view::out($aPermutation);
        }
    } else {
        $aError = 'Unknown task with commit ' . Besearcher\view::out($aCommit);
    }

    $aLogPath = @$aData['raw']['log_file'];
    $aLogContent = '';

    if(!empty($aLogPath)) {
        $aLogContent = @file_get_contents($aLogPath);
    }

    $aMeta = array();

    if(isset($aData['meta'])) {
        foreach($aData['meta'] as $aItem) {
            if($aItem['type'] != BESEARCHER_TAG_TYPE_PROGRESS) {
                $aMeta[] = $aItem;
            }
        }
    }

    Besearcher\View::render('result', array(
        'info' => $aData,
        'meta' => $aMeta,
        'log_content' => $aLogContent,
        'error' => $aError,
    ));
?>
