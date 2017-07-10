<?php
    require_once(dirname(__FILE__) . '/inc/globals.php');

    Besearcher\Auth::allowAuthenticated();
    Besearcher\Auth::logout();

    // Take the user to the index page
    header('Location: login.php');
	exit();
?>
