<?php
    require_once(dirname(__FILE__) . '/inc/globals.php');

    $aLoaded = Besearcher\Data::init();

    Besearcher\View::render('index', array(
        'loaded' => $aLoaded
    ));
?>
