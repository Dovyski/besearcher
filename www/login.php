<?php
    require_once(dirname(__FILE__) . '/inc/globals.php');

    $aError = '';

    if(isset($_REQUEST['user'])) {
        // Performing login operation
        if(Besearcher\Auth::isValidUser(@$_REQUEST['user'], @$_REQUEST['password'])) {
            Besearcher\Auth::login();

            // Take the user to the index page
            header('Location: index.php');
			exit();
        } else {
            $aError = 'Invalid user or password.';
        }
    }

    Besearcher\View::render('login', array(
        'error' => $aError,
        'hideNavbar' => true
    ));
?>
