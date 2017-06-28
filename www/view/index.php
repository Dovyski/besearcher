<?php
    $aData = Besearcher\View::data();
?>

<?php require_once(dirname(__FILE__) . '/header.php'); ?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Welcome!</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->

    <?php if(!$aData['loaded']) { ?>
        <div class="row">
            <div class="col-lg-12">
                <div class="alert alert-danger" role="alert"><strong>Unable to load data!</strong> Check the <em>config.php</em> file and make sure <em>PATH_BESERCHER_INI_FILE</em> contains the right path to the INI file being used by Besearcher.</div>
            </div>
        </div>
    <?php } ?>

    <div class="row">
        <div class="col-lg-12">
            How about some content here?
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->

<?php require_once(dirname(__FILE__) . '/footer.php'); ?>
