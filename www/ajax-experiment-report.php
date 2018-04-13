
<?php
    require_once(dirname(__FILE__) . '/inc/globals.php');

    Besearcher\Auth::allowAuthenticated();

    $aApp = Besearcher\WebApp::instance();
    $aTaskCmdParams = $aApp->config('task_cmd_params');
    $aFilter = array();

    foreach($aTaskCmdParams as $aParam => $aValues) {
        if(isset($_REQUEST[$aParam]) && !empty($_REQUEST[$aParam])) {
            $aFilter[$aParam] = $_REQUEST[$aParam];
        }
    }

    Besearcher\View::render('ajax-experiment-report', array(
        'filter' => $aFilter
    ));
?>
