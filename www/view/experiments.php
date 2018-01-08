<?php
    $aData = Besearcher\View::data();
?>

<?php require_once(dirname(__FILE__) . '/header.php'); ?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Experiments</h1>
        </div>
    </div>

    <?php if(!empty($aData['message']['body'])) { ?>
        <div class="row">
            <div class="col-lg-12">
                <div class="alert alert-<?php echo $aData['message']['type']; ?>" role="alert"><strong><?php echo Besearcher\View::out($aData['message']['title']); ?></strong> <?php echo Besearcher\View::out($aData['message']['body']); ?></div>
            </div>
        </div>
    <?php } ?>
</div>
<!-- /#page-wrapper -->

<?php require_once(dirname(__FILE__) . '/footer.php'); ?>
