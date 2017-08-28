<?php
    require_once(dirname(__FILE__) . '/inc/globals.php');

    Besearcher\Auth::allowAuthenticated();

    $aError = Besearcher\Data::init();
    $aINI = Besearcher\Data::ini();
    $aContext = loadContextFromDisk($aINI['data_dir'], false);

    $aTasksQueue = isset($aContext['tasks_queue']) ? $aContext['tasks_queue'] : array();

    Besearcher\View::render('control', array(
        'error' => $aError,
        'tasks_queue' => $aTasksQueue,
        'context' => $aContext
    ));
?>
