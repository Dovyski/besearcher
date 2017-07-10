<?php
    require_once(dirname(__FILE__) . '/inc/globals.php');

    Besearcher\Auth::allowAuthenticated();

    $aLoaded = Besearcher\Data::init();
    $aTasks = Besearcher\Data::tasks();

    Besearcher\View::render('tasks', array(
        'tasks' => $aTasks
    ));
?>
