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
            <h3><i class="fa fa-tachometer"></i> Overview</h3>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
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
                        <tr><td>Experiment description</td><td><?php echo $aData['context']['experiment_description']; ?></td></tr>
                        <tr><td><a href="queue.php">Tasks in queue</a></td><td><?php echo $aData['context']['queue_size']; ?></td></tr>
                        <tr><td><a href="results.php">Results in the making</a></td><td><?php echo $aData['context']['tasks_running']; ?></td></tr>
                        <tr><td>Estimated completion time</td><td><span class="status-running"><i class="fa fa-clock-o" title="Estimation is calculated based on the average time of completed results."></i> <?php echo $aData['context']['completion_time'] > 0 ? Besearcher\Utils::humanReadableTime($aData['context']['completion_time']) : 'unknown'; ?></span></td></tr>
                    </tbody>
                </table>
            <?php } ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-2">
            <h3><i class="fa fa-cogs"></i> Configuration</h3>
        </div>
        <div class="col-lg-10" style="padding-top: 25px; text-align: right;">
            <p><i class="fa fa-file-text-o"></i> <code><?php echo Besearcher\View::out($aData['ini_path']); ?></code></p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <table class="table table-striped table-bordered table-hover">
                <tr class="active"><td colspan="2" style="text-align: center; font-weight: bold;">General</td></tr>
                <?php
                    foreach($aData['ini'] as $aName => $aValue) {
                        if(is_array($aValue) && $aName != 'task_cmd_params') {
                            echo '<tr><td colspan="2" style="text-align: center; font-weight: bold;">'.Besearcher\View::out($aName).'</td></tr>';

                            foreach($aValue as $aSubName => $aSubValue) {
                                echo '<tr>';
                                    echo '<td>'.Besearcher\View::out($aSubName).'</td>';
                                    echo '<td>'.(is_array($aSubValue) ? '<pre style="width: 80%; overflow: scroll;">'.print_r($aSubValue, true).'</pre>' : '<p style="width: 80%; overflow-wrap: break-word;">'.Besearcher\View::out($aSubValue).'</p>').'</td>';
                                echo '</tr>';
                            }
                        } else if($aName != 'task_cmd_params') {
                            $aValueOut = '<p style="width: 80%; overflow-wrap: break-word;">' . Besearcher\View::out($aValue) . '</p>';
                            echo '<tr>';
                                echo '<td>'.Besearcher\View::out($aName).'</td>';
                                echo '<td>'.($aName == 'task_cmd' ? Besearcher\View::prettifyTaskCmd($aValueOut, $aData['task_params']) : $aValueOut).'</td>';
                            echo '</tr>';
                        }
                    }

                    echo '<tr class="active"><td colspan="2" style="text-align: center; font-weight: bold;">Task params</td></tr>';

                    foreach($aData['task_params'] as $aName => $aValue) {
                        echo '<tr>';
                            echo '<td><a name="task_param_'.Besearcher\View::out($aName).'"></a>'.Besearcher\View::out($aName).'</td>';
                            echo '<td>'.(is_array($aValue) ? '<pre>'.print_r($aValue, true).'</pre>' : '<p style="overflow-wrap: break-word;">'.Besearcher\View::out($aValue).'</p>').'</td>';
                        echo '</tr>';
                    }
                ?>
            </table>
        </div>
    </div>
</div>
<!-- /#page-wrapper -->

<?php require_once(dirname(__FILE__) . '/footer.php'); ?>
