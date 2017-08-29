<?php
    $aData = Besearcher\View::data();

    header('Content-Type: application/json');
    echo json_encode($aData, JSON_NUMERIC_CHECK);
?>
