<?php
    require_once(dirname(__FILE__) . '/inc/globals.php');

    Besearcher\Auth::allowAuthenticated();

    $aApp = Besearcher\WebApp::instance();

    $aTaskParams = $aApp->config('task_cmd_params', array());
    $aINIPath = $aApp->getINIPath();
    $aINIValues = $aApp->getINIValues();

    $aAppContext = $aApp->getContext()->values();
    $aContext = array(
        'status' => $aAppContext['status'],
        'experiment_hash' => $aAppContext['experiment_hash'],
        'experiment_description' => $aINIValues['experiment_description'],
        'queue_size' => $aApp->getData()->queueSize(),
        'tasks_running' => count($aApp->getData()->findRunningTasks()),
        'completion_time' => Besearcher\WebApp::calculateEstimatedTimeFinishExperiment()
    );

    Besearcher\View::render('index', array(
        'task_params' => $aTaskParams,
        'ini_path' => $aINIPath,
        'ini' => $aINIValues,
        'context' => $aContext
    ));
?>
