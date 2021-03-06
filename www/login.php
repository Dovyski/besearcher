<?php
    require_once(dirname(__FILE__) . '/inc/globals.php');

    Besearcher\Auth::allowNonAuthenticated();

    $aError = '';

    if(isset($_REQUEST['user'])) {
        $aUserLogin = $_REQUEST['user'];

        // Performing login operation
        if(Besearcher\Auth::credentialsMatch($aUserLogin, @$_REQUEST['password'])) {
            Besearcher\Auth::login($aUserLogin);

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
