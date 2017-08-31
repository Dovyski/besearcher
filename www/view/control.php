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
                        <tr><td>last_commit</td><td><?php echo $aData['context']['last_commit']; ?></td></tr>
                        <tr><td>running_tasks</td><td><?php echo $aData['context']['running_tasks']; ?></td></tr>
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
                            <th>#</th>
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
                                        echo '<input type="checkbox" name="task_'.$aNum.'" value="'.$aItem['id'].'" />';
                                    echo '</td>';
                                    echo '<td>'.($aData['start'] + $aNum + 1).'</td>';
                                    echo '<td><a href="result.php?commit='.$aItem['commit_hash'].'&permutation='.$aItem['permutation_hash'].'" title="Click to view more information">'.substr($aItem['commit_hash'], 0, 16).'-'.substr($aItem['permutation_hash'], 0, 16).'</a></td>';
                                    echo '<td>'.date('Y/m/d H:i:s', $aItem['creation_time']).'</td>';
                                    echo '<td>'.$aItem['data']['params'].'</td>';
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
                    echo '<li><a href="control.php?page=1&size='.$aData['size'].'" aria-label="First"><span aria-hidden="true">&laquo;</span></a></li>';

                    $aLength = 10;
                    $aStart = $aData['page'] - $aLength;
                    $aEnd = $aData['page'] + $aLength;

                    for($i = $aStart; $i <= $aEnd; $i++) {
                        if($i >= 1 && $i <= $aData['pages']) {
                            $aExtra = $aData['page'] == $i ? 'class="active"' : '';
                            echo '<li ' . $aExtra . '><a href="control.php?page='.$i.'&size='.$aData['size'].'">' . $i . '</a></li>';
                        }
                    }

                    echo '<li><a href="control.php?page='.$aData['pages'].'&size='.$aData['size'].'" aria-label="Last"><span aria-hidden="true">&raquo;</span></a></li>';
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
