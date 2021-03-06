<?php
    $aData = Besearcher\View::data();
    $aResult = $aData['result'];
?>

<?php require_once(dirname(__FILE__) . '/header.php'); ?>

<div id="page-wrapper">
    <?php if(!empty($aData['invalid'])) { ?>
    <div class="row" style="padding-top: 20px;">
        <div class="col-lg-12">
            <div class="alert alert-warning" role="alert"><strong>Oops!</strong> <?php echo $aData['invalid']; ?></div>
        </div>
    </div>
    <?php } else { ?>

    <?php if(!empty($aData['message'])) { ?>
        <div class="row" style="padding-top: 20px;">
            <div class="col-lg-12">
                <div class="alert alert-<?php echo $aData['message_type']; ?>" role="alert"><?php echo $aData['message']; ?></div>
            </div>
        </div>
    <?php } ?>

    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Result</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <table width="100%" class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Creation</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Total time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        echo '<tr>';
                            echo '<td>'.date('Y/m/d H:i:s', $aResult['creation_time']).'</td>';
                            echo '<td>'.date('Y/m/d H:i:s', $aResult['exec_time_start']).'</td>';
                            echo '<td>'.($aData['finished'] ? date('Y/m/d H:i:s', $aResult['exec_time_end']) : '<span class="status-running"><i class="fa fa-clock-o"></i> ~ ' . Besearcher\Utils::humanReadableTime($aData['completion_time']) . '</span>').'</td>';
                            echo '<td><i class="fa fa-clock-o"></i> ' . Besearcher\Utils::humanReadableTime($aData['elapsed_time']) .'</td>';
                            echo '<td>'.Besearcher\View::prettyStatusName($aResult, true).'</td>';
                            echo '<td><a href="javascript:void(0);" id="rerun-action" data-rerun-url="result.php?experiment_hash='.$aResult['experiment_hash'].'&permutation_hash='.$aResult['permutation_hash'].'&rerun=1"><i class="fa fa-refresh"></i> Re-run</a></td>';
                        echo '</tr>';
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <table width="100%" class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Id</th>
                        <th>Experiment hash</th>
                        <th>Permutation hash</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        echo '<tr>';
                            echo '<td>'.$aResult['id'].'</td>';
                            echo '<td>'.$aResult['experiment_hash'].'</td>';
                            echo '<td>'.$aResult['permutation_hash'].'</td>';
                        echo '</tr>';
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <strong>Summary</strong> <i class="fa fa-question-circle" title="This table shows a summary of the meta information found in the command output and the params used in such command."></i>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <table width="100%" class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th style="width: 10%;">Type</th>
                        <th style="width: 10%;">Name</th>
                        <th style="width: 80%;">Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        if(count($aData['meta']) > 0) {
                            foreach($aData['meta'] as $aItem) {
                                echo '<tr>';
                                    echo '<td><i class="fa fa-tag" title="This is a meta information found in the command output."></i> <code>'.$aItem['type'].'</code></td>';
                                    echo '<td>'.$aItem['name'].'</td>';
                                    echo '<td>'.print_r($aItem['data'], true).'</td>';
                                echo '</tr>';
                            }
                        }
                    ?>
                    <tr><td colspan="3"></td></tr>
                    <?php
                        if(count($aData['params']) > 0) {
                            foreach($aData['params'] as $aName => $aValue) {
                                echo '<tr>';
                                    echo '<td><i class="fa fa-sliders" title="This is a parameter used in the command that produced this result."></i> <em>param</em></td>';
                                    echo '<td>'.$aName.'</td>';
                                    echo '<td>'.str_replace(',', ', ', $aValue).'</td>';
                                echo '</tr>';
                            }
                        }

                        if(count($aData['meta']) == 0) {
                            echo '<tr><td colspan="3" class="warning"><i class="fa fa-warning"></i> No meta information was found in the command output. Check out <em><a href="#">Besearcher log marks</a></em> to learn how to generate meta information.</td></tr>';
                        }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

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
                            echo '<td>'.$aResult['cmd'].'</td>';
                            echo '<td>'.$aResult['cmd_return_code'].'</td>';
                        echo '</tr>';
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <table width="100%" class="table table-striped table-bordered table-hover">
                <thead>
                    <tr><th>Params string</th></tr>
                </thead>
                <tbody>
                    <tr><td><?php echo $aResult['params']; ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>

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

<script>
    $(document).ready(function() {
        $('#rerun-action').click(function() {
            if(!confirm('Re-running this result will remove all its existing data files. Proceed?')) {
                return;
            }
            window.location.href = $(this).data('rerun-url');
        });
    });
</script>

<?php
    require_once(dirname(__FILE__) . '/footer.php');
?>
