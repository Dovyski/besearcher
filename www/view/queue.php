<?php
    $aData = Besearcher\View::data();
?>

<?php require_once(dirname(__FILE__) . '/header.php'); ?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Queue <i class="fa fa-question-circle" title="These are the tasks that are scheduled to be executed in the near future, but are currently waiting CPU time due to the value of max_parallel_tasks."></i></h1>
        </div>
    </div>

    <?php if(!empty($aData['message']['body'])) { ?>
        <div class="row">
            <div class="col-lg-12">
                <div class="alert alert-<?php echo $aData['message']['type']; ?>" role="alert"><strong><?php echo Besearcher\View::out($aData['message']['title']); ?></strong> <?php echo Besearcher\View::out($aData['message']['body']); ?></div>
            </div>
        </div>
    <?php } ?>

    <form action="queue.php" method="post">

    <div class="row">
        <div class="col-lg-12">
            <?php if(count($aData['tasks_queue']) == 0) { ?>
                <p>There are no tasks queued for execution.</p>

            <?php } else { ?>
                <table width="100%" class="table table-striped table-bordered table-hover" id="queue-table">
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
                                    echo '<td>'.Besearcher\View::createResultLink($aItem['experiment_hash'], $aItem['permutation_hash']).'</td>';
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
        <div class="row">
            <div class="col-lg-5">
                <?php if($aData['pagination']['pages'] > 1) { ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <?php
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
                <?php } ?>
            </div>

            <div class="col-lg-2" style="padding-top: 25px; text-align: center;">
                <p>Showing <?php echo $aData['pagination']['size'] ?> of <?php echo $aData['pagination']['total']; ?></p>
            </div>

            <div class="col-lg-5" style="padding-top: 20px; text-align: right;">
                <button type="submit" class="btn btn-default" name="action" value="prioritize"><i class="fa fa-chevron-up"></i> Prioritize selected</button>
                <button type="submit" class="btn btn-default" name="action" value="deprioritize"><i class="fa fa-chevron-down"></i> Deprioritize selected</button>
                <button type="submit" class="btn btn-danger" name="action" value="delete"><i class="fa fa-trash"></i> Delete selected</button>
            </div>
        </div>
    <?php } ?>
    </form>
</div>
<!-- /#page-wrapper -->

<script>
    $(document).ready(function() {
        $('#queue-table').DataTable({
            responsive: true,
            paging: false,
            info: false,
            searching: true
        });
    });
</script>

<?php require_once(dirname(__FILE__) . '/footer.php'); ?>
