<?php
    $aData = Besearcher\View::data();
    $aResults = $aData['results'];
?>

<?php require_once(dirname(__FILE__) . '/header.php'); ?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Results</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->

    <div class="row">
        <div class="col-lg-12">
            <table width="100%" class="table table-striped table-bordered table-hover" id="results-table">
                <thead>
                    <tr>
                        <th><i class="fa fa-info-circle"></i></th>
                        <th>Id</th>
                        <th>Hash-permutation</th>
                        <th>Creation</th>
                        <th>Start</th>
                        <th>Params</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $aNum = 0;
                        foreach($aResults as $aResult) {
                            echo '<tr>';
                                echo '<td>'.Besearcher\View::prettyStatusName($aResult, true).'</td>';
                                echo '<td>'.$aResult['id'].'</td>';
                                echo '<td>'.Besearcher\View::createResultLink($aResult['experiment_hash'], $aResult['permutation_hash']).'</td>';
                                echo '<td>'.date('Y/m/d H:i:s', $aResult['creation_time']).'</td>';
                                echo '<td>'.date('Y/m/d H:i:s', $aResult['exec_time_start']).'</td>';
                                echo '<td>'.$aResult['params'].'</td>';
                            echo '</tr>';
                        }
                    ?>
                </tbody>
            </table>
            <!-- /.table-responsive -->
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->

<script>
    $(document).ready(function() {
        $('#results-table').DataTable({
            responsive: true,
            pageLength: 100,
            order: [[4, 'desc']]
        });
    });
</script>

<?php
    require_once(dirname(__FILE__) . '/footer.php');
?>
