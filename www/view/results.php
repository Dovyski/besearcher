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
            <table width="100%" class="table table-striped table-bordered table-hover" id="dataTables-example">
                <thead>
                    <tr>
                        <th><i class="fa fa-info-circle"></i></th>
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
                                echo '<td><a href="result.php?experiment_hash='.$aResult['experiment_hash'].'&permutation_hash='.$aResult['permutation_hash'].'" title="Click to view more information">'.substr($aResult['experiment_hash'], 0, 16).'-'.substr($aResult['permutation_hash'], 0, 16).'</a></td>';
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
        $('#dataTables-example').DataTable({
            responsive: true,
            pageLength: 100,
            order: [[3, 'desc']]
        });
    });
</script>

<?php
    require_once(dirname(__FILE__) . '/footer.php');
?>
