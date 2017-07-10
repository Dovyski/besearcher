<?php
    require_once(dirname(__FILE__) . '/inc/globals.php');

    Besearcher\Auth::allowAuthenticated();

    $aError = Besearcher\Data::init();
    $aINI = Besearcher\Data::ini();
    $aTaskParams = isset($aINI['task_cmd_params']) ? $aINI['task_cmd_params'] : array();
    $aINIPath = PATH_BESERCHER_INI_FILE;

    Besearcher\View::render('index', array(
        'error' => $aError,
        'ini' => $aINI,
        'task_params' => $aTaskParams,
        'ini_path' => $aINIPath
    ));
?>
