<?php
    require_once(dirname(__FILE__) . '/inc/globals.php');

    Besearcher\Auth::allowAuthenticated();

    $aApp = Besearcher\WebApp::instance();

    $aTaskParams = $aApp->config('task_cmd_params', array());
    $aINIPath = $aApp->getINIPath();
    $aINIValues = $aApp->getINIValues();

    Besearcher\View::render('index', array(
        'task_params' => $aTaskParams,
        'ini_path' => $aINIPath,
        'ini' => $aINIValues
    ));
?>
