<?php
    $aData = Besearcher\View::data();
?>

<?php require_once(dirname(__FILE__) . '/header.php'); ?>

<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <div class="login-panel panel panel-danger">
                <div class="panel-heading"><strong>Oops!</strong></div>
                <div class="panel-body">
                    <?php echo $aData['error']; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once(dirname(__FILE__) . '/footer.php'); ?>
