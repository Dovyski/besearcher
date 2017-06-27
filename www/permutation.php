<?php
    require_once(dirname(__FILE__) . '/inc/globals.php');

    $aCommit = isset($_REQUEST['commit']) ? $_REQUEST['commit'] : '';
    $aPermutation = isset($_REQUEST['permutation']) ? $_REQUEST['permutation'] : '';

    Besearcher\Data::init();

    $aTasks = Besearcher\Data::tasks();
    $aError = '';
    $aData = array();

    if(isset($aTasks[$aCommit])) {
        if(isset($aTasks[$aCommit][$aPermutation])) {
            $aData = $aTasks[$aCommit][$aPermutation];
        } else {
            $aError = 'Unknown permutation with hash ' . Besearcher\view::out($aPermutation);
        }
    } else {
        $aError = 'Unknown task with commit ' . Besearcher\view::out($aCommit);
    }

    $aLogPath = @$aData['raw']['log_file'];
    $aLogContent = '';

    if(!empty($aLogPath)) {
        $aLogContent = file_get_contents($aLogPath);
    }

    Besearcher\View::render('permutation', array(
        'permutation' => $aData,
        'log_content' => $aLogContent,
        'error' => $aError,
    ));
?>
