<?php
    require_once(dirname(__FILE__) . '/inc/globals.php');

    // TODO: load data here
    $aTasks = array();

    Besearcher\View::render('tasks', array(
        'tasks' => $aTasks
    ));
?>
