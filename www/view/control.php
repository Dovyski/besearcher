<?php
    $aData = Besearcher\View::data();
?>

<?php require_once(dirname(__FILE__) . '/header.php'); ?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Control <i class="fa fa-question-circle" title="This page shows the internal data that Besearcher is using to process the tasks. You can change or cancel tasks, for instance."></i></h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->

    <?php if(!empty($aData['error'])) { ?>
        <div class="row">
            <div class="col-lg-12">
                <div class="alert alert-danger" role="alert"><strong>Oops!</strong> <?php echo Besearcher\View::out($aData['error']); ?></div>
            </div>
        </div>
    <?php } else { ?>
        <div class="row">
            <div class="col-lg-12">
                <h3>Global settings</h3>
                <table width="100%" class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Entry</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>status</td><td><?php echo $aData['context']['status']; ?></td></tr>
                    </tbody>
                </table>
            </div>
            <!-- /.col-lg-12 -->
        </div>

        <div class="row">
            <div class="col-lg-12">
                <h3>Queued tasks <i class="fa fa-question-circle" title="Tasks that are scheduled to be executed in the near future, but are currently waiting CPU time due to the value of max_parallel_tasks."></i></h3>

                <?php if(count($aData['tasks_queue']) == 0) { ?>
                    <p>There are no tasks queued for execution.</p>

                <?php } else { ?>
                    <table width="100%" class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Commit-permutation</th>
                                <th>Creation</th>
                                <th>Params</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $aNum = 0;
                                foreach($aData['tasks_queue'] as $aItem) {
                                    echo '<tr>';
                                        echo '<td>';
                                            echo '<input type="checkbox" name="task_'.$aNum++.'" value="'.$aItem['hash'].'-'.$aItem['permutation'].'" />';
                                        echo '</td>';
                                        echo '<td><a href="result.php?commit='.$aItem['hash'].'&permutation='.$aItem['permutation'].'" title="Click to view more information">'.substr($aItem['hash'], 0, 16).'-'.substr($aItem['permutation'], 0, 16).'</a></td>';
                                        echo '<td>'.date('Y/m/d H:i:s', $aItem['creation_time']).'</td>';
                                        echo '<td>'.$aItem['params'].'</td>';
                                    echo '</tr>';
                                }
                            ?>
                        </tbody>
                    </table>
                <?php } ?>
            </div>
            <!-- /.col-lg-12 -->
        </div>
    <!-- /.row -->
    <?php } ?>
</div>
<!-- /#page-wrapper -->

<?php require_once(dirname(__FILE__) . '/footer.php'); ?>
