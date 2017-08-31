<?php
    require_once(dirname(__FILE__) . '/inc/globals.php');

    Besearcher\Auth::allowAuthenticated();

    Besearcher\View::render('tasks', array(
        'tasks' => Besearcher\Data::tasks()
    ));
?>
