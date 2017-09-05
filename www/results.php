<?php
    require_once(dirname(__FILE__) . '/inc/globals.php');

    Besearcher\Auth::allowAuthenticated();
    $aApp = Besearcher\WebApp::instance();

    Besearcher\View::render('results', array(
        'results' => $aApp->getData()->findResults()
    ));
?>
