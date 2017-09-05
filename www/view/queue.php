<?php
    $aData = Besearcher\View::data();
?>

<?php require_once(dirname(__FILE__) . '/header.php'); ?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Queue <i class="fa fa-question-circle" title="This page shows the internal data that Besearcher is using to process the tasks. You can change or cancel tasks, for instance."></i></h1>
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

    <div class="row">
        <div class="col-lg-12">
            <h3>Besearcher context</h3>
            <?php if(count($aData['context']) == 0) { ?>
                <p>There are no settings available to edit.</p>

            <?php } else { ?>
                <table width="100%" class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Entry</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>status</td><td><?php echo $aData['context']['status']; ?></td></tr>
                        <tr><td>experiment_hash</td><td><?php echo $aData['context']['experiment_hash']; ?></td></tr>
                    </tbody>
                </table>
            <?php } ?>
        </div>
    </div>


    <form action="queue.php" method="post">

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
                            <th>Id</th>
                            <th>Hash-permutation</th>
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
                                        echo '<input type="checkbox" name="task_'.$aNum.'" value="'.$aItem['id'].'" />';
                                    echo '</td>';
                                    echo '<td>'.$aItem['id'].'</td>';
                                    echo '<td><a href="result.php?experiment_hash='.$aItem['experiment_hash'].'&permutation_hash='.$aItem['permutation_hash'].'" title="Click to view more information">'.substr($aItem['experiment_hash'], 0, 16).'-'.substr($aItem['permutation_hash'], 0, 16).'</a></td>';
                                    echo '<td>'.date('Y/m/d H:i:s', $aItem['creation_time']).'</td>';
                                    echo '<td>'.$aItem['params'].'</td>';
                                echo '</tr>';
                                $aNum++;
                            }
                        ?>
                    </tbody>
                </table>
            <?php } ?>
        </div>
    </div>

    <?php if(count($aData['tasks_queue']) != 0) { ?>
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php
                    echo '<li style="margin: 10px;">Showing '.$aData['pagination']['size'].' of '.$aData['pagination']['total'].'</li>';
                    echo '<li><a href="queue.php?page=1&size='.$aData['pagination']['size'].'" aria-label="First" title="Jump to first page"><i class="fa fa-step-backward"></i></a></li>';

                    $aLength = 10;
                    $aStart = $aData['pagination']['page'] - $aLength;
                    $aEnd = $aData['pagination']['page'] + $aLength;

                    for($i = $aStart; $i <= $aEnd; $i++) {
                        if($i >= 1 && $i <= $aData['pagination']['pages']) {
                            $aExtra = $aData['pagination']['page'] == $i ? 'class="active"' : '';
                            echo '<li ' . $aExtra . '><a href="queue.php?page='.$i.'&size='.$aData['pagination']['size'].'">' . $i . '</a></li>';
                        }
                    }

                    echo '<li><a href="queue.php?page='.$aData['pagination']['pages'].'&size='.$aData['pagination']['size'].'" aria-label="Last" title="Jump to last page"><i class="fa fa-step-forward"></i></a></li>';
                ?>
            </ul>
        </nav>

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
