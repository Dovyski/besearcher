<?php
    require_once(dirname(__FILE__) . '/inc/globals.php');

    $aLoaded = Besearcher\Data::init();
    $aTasks = Besearcher\Data::tasks();

    Besearcher\View::render('tasks', array(
        'loaded' => $aLoaded,
        'tasks' => $aTasks
    ));
?>
