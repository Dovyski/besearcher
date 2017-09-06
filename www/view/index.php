<?php
    $aData = Besearcher\View::data();
?>

<?php require_once(dirname(__FILE__) . '/header.php'); ?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Dashboard</h1>
        </div>
    </div>
    <!-- /.row -->

    <div class="row">
        <div class="col-lg-12">
            <h3>Overview</h3>
            <?php if(count($aData['context']) == 0) { ?>
                <p>There are no context information available at moment.</p>

            <?php } else { ?>
                <table width="100%" class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Entry</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Status</td><td><?php echo $aData['context']['status']; ?></td></tr>
                        <tr><td>Experiment hash</td><td><?php echo $aData['context']['experiment_hash']; ?></td></tr>
                        <tr><td><a href="queue.php">Tasks in queue</a></td><td><?php echo $aData['context']['queue_size']; ?></td></tr>
                        <tr><td><a href="results.php">Results in the making</a></td><td><?php echo $aData['context']['tasks_running']; ?></td></tr>
                    </tbody>
                </table>
            <?php } ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-2">
            <h3>Configuration</h3>
        </div>
        <div class="col-lg-10" style="padding-top: 25px; text-align: right;">
            <p><code><?php echo Besearcher\View::out($aData['ini_path']); ?></code></p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <table width="100%" class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Directive</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        foreach($aData['ini'] as $aName => $aValue) {
                            if($aName != 'task_cmd_params') {
                                echo '<tr>';
                                    echo '<td>'.Besearcher\View::out($aName).'</td>';
                                    echo '<td>'.(is_array($aValue) ? '<pre>'.print_r($aValue, true).'</pre>' : Besearcher\View::out($aValue)).'</td>';
                                echo '</tr>';
                            }
                        }

                        echo '<tr><td colspan="2" style="text-align: center; font-weight: bold;">Task params</td></tr>';

                        foreach($aData['task_params'] as $aName => $aValue) {
                            echo '<tr>';
                                echo '<td>'.Besearcher\View::out($aName).'</td>';
                                echo '<td>'.(is_array($aValue) ? '<pre>'.print_r($aValue, true).'</pre>' : Besearcher\View::out($aValue)).'</td>';
                            echo '</tr>';
                        }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- /#page-wrapper -->

<?php require_once(dirname(__FILE__) . '/footer.php'); ?>
