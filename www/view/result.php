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
                        <th>Permutation hash</th>
                        <th>Creation</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        echo '<tr>';
                            echo '<td>'.$aInfo['permutation'].'</td>';
                            echo '<td>'.date('Y/m/d H:i:s', $aInfo['creation_time']).'</td>';
                            echo '<td>'.date('Y/m/d H:i:s', $aInfo['exec_time_start']).'</td>';
                            echo '<td>'.($aInfo['exec_time_end'] != 0 ? date('Y/m/d H:i:s', $aInfo['exec_time_end']) : '-').'</td>';
                            echo '<td>'.Besearcher\View::prettyStatusName($aInfo, true).'</td>';
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
                        <th>Commit hash</th>
                        <th>Commit message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        echo '<tr>';
                            echo '<td>'.$aInfo['commit'].'</td>';
                            echo '<td>'.Besearcher\View::out($aInfo['commit_message']).'</td>';
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
                        <th style="width: 95%;">Command</th>
                        <th style="width: 5%;">Return</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        echo '<tr>';
                            echo '<td>'.$aInfo['cmd'].'</td>';
                            echo '<td>'.$aInfo['raw']['cmd_return_code'].'</td>';
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
                    <tr><th>Params</th></tr>
                </thead>
                <tbody>
                    <tr><td><?php echo $aInfo['params']; ?></td></tr>
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
                <p>No meta information was found in the command output. Check out <em><a href="#">Besearcher log marks</a></em> to learn how to generate meta information.</p>
            <?php } ?>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->

    <div class="row">
        <div class="col-lg-12">
            <strong>Command output</strong>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <?php echo ($aData['log_content'] === false ? '<p>No output was produced.</p>' : '<pre>'.$aData['log_content'].'</pre>'); ?>
        </div>
    </div>

    <?php } ?>
</div>
<!-- /#page-wrapper -->

<?php
    require_once(dirname(__FILE__) . '/footer.php');
?>
