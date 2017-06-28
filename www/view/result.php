<?php
    $aData = Besearcher\View::data();
    $aInfo = $aData['info'];
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
            <h1 class="page-header">Result</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->

    <div class="row">
        <div class="col-lg-12">
            <table width="100%" class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Commit hash</th>
                        <th>Permutation hash</th>
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
                            echo '<td>'.Besearcher\View::prettyProgressValue($aInfo['progress']).'</td>';
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
            <strong>Meta</strong>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <?php if(count($aData['meta']) > 0) { ?>
                <table width="100%" class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Name</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            foreach($aData['meta'] as $aItem) {
                                echo '<tr>';
                                    echo '<td>'.$aItem['type'].'</td>';
                                    echo '<td>'.$aItem['name'].'</td>';
                                    echo '<td>'.print_r($aItem['data'], true).'</td>';
                                echo '</tr>';
                            }
                        ?>
                    </tbody>
                </table>
                <!-- /.table-responsive -->
            <?php } else { ?>
                <div class="alert alert-info" role="alert">No meta information was found in the log file. Check out <em><a href="#">Besearcher log marks</a></em> to learn how to generate meta information.</div>
            <?php } ?>
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
