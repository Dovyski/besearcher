<?php
    require_once(dirname(__FILE__) . '/inc/globals.php');

    Besearcher\Auth::allowAuthenticated();
    $aMessage = array('title' => 'Oops!', 'body' => '', 'type' => 'danger');
    $aOnlyRedir = false;

    try {
        $aSelect = isset($_REQUEST['select']) ? $_REQUEST['select'] : '';

        if($aSelect != '') {
            Besearcher\WebApp::setActiveBesearcherINIPath($aSelect);
            $aOnlyRedir = true;
        }
    } catch(Exception $e) {
        $aMessage['error'] = $e->getMessage();
    }

    if($aOnlyRedir) {
        header('Location: index.php');
        exit();
    }

    Besearcher\View::render('experiments', array(
        'message' => $aMessage
    ));
?>
