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

    <?php if(!empty($aData['message']['body'])) { ?>
        <div class="row">
            <div class="col-lg-12">
                <div class="alert alert-<?php echo $aData['message']['type']; ?>" role="alert"><strong><?php echo Besearcher\View::out($aData['message']['title']); ?></strong> <?php echo Besearcher\View::out($aData['message']['body']); ?></div>
            </div>
        </div>
    <?php } ?>

    <?php if($aData['has_override']) { ?>
        <div class="row">
            <div class="col-lg-12">
                <div class="alert alert-danger" role="alert"><i class="fa fa-warning fa-3x" style="float: left; padding: 1px 15px 5px 5px;"></i><strong>CHANGES PENDING!</strong> A few changes issued to Besearcher are still pending. As a consequence, the data displayed below is not accurate (it does not reflect the pending changes). Wait a few seconds then refresh this page to get the most recent (and accurate) data Besearcher is using.</div>
            </div>
        </div>
    <?php } ?>

    <div class="row">
        <div class="col-lg-12">
            <h3>Global settings</h3>
            <?php if(count($aData['settings']) == 0) { ?>
                <p>There are no settings available for edit.</p>

            <?php } else { ?>
                <table width="100%" class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Entry</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>status</td><td><?php echo $aData['settings']['status']; ?></td></tr>
                    </tbody>
                </table>
            <?php } ?>
        </div>
    </div>


    <form action="control.php" method="post">

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
    </div>

    <?php if(count($aData['tasks_queue']) != 0) { ?>
        <div class="row" style="padding-bottom: 20px;">
            <div class="col-lg-8">
                <button type="submit" class="btn btn-primary" name="action" value="move"><i class="fa fa-sort"></i> Move selected to the begining of queue</button>
                <button type="submit" class="btn btn-danger" name="action" value="delete"><i class="fa fa-trash"></i> Delete selected</button>
            </div>
        </div>
    <?php } ?>

    </form>
</div>
<!-- /#page-wrapper -->

<?php require_once(dirname(__FILE__) . '/footer.php'); ?>
