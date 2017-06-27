<?php
    $aData = Besearcher\View::data();
    $aInfo = $aData['permutation'];
?>

<?php require_once(dirname(__FILE__) . '/header.php'); ?>

<div id="page-wrapper">
    <?php if(!empty($aData['error'])) { ?>
    <div class="row" style="padding-top: 20px;">
        <div class="col-lg-12">
            <div class="alert alert-warning" role="alert"><strong>Oops!</strong> <?php echo $aData['error']; ?></div>
        </div>
    </div>
    <?php } else { ?>

    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Permutation</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->

    <div class="row">
        <div class="col-lg-12">
            <table width="100%" class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Commit</th>
                        <th>Hash</th>
                        <th>Date</th>
                        <th>Progress</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        echo '<tr>';
                            echo '<td>'.$aInfo['commit'].'</td>';
                            echo '<td>'.$aInfo['permutation'].'</td>';
                            echo '<td>'.$aInfo['date'].'</td>';
                            echo '<td>'.$aInfo['progress'].'</td>';
                        echo '</tr>';
                    ?>
                </tbody>
            </table>
            <!-- /.table-responsive -->
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->

    <div class="row">
        <div class="col-lg-12">
            <table width="100%" class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Params</th>
                        <th>Command</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        echo '<tr>';
                            echo '<td>'.$aInfo['params'].'</td>';
                            echo '<td>'.$aInfo['cmd'].'</td>';
                        echo '</tr>';
                    ?>
                </tbody>
            </table>
            <!-- /.table-responsive -->
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->

    <div class="row">
        <div class="col-lg-12">
            <strong>Generated log</strong>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <?php echo '<pre>'.$aData['log_content'].'</pre>'; ?>
        </div>
    </div>

    <?php } ?>
</div>
<!-- /#page-wrapper -->

<?php
    require_once(dirname(__FILE__) . '/footer.php');
?>
