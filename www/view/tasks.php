<?php
    $aData = Besearcher\View::data();
    $aTasks = $aData['tasks'];
?>

<?php require_once(dirname(__FILE__) . '/header.php'); ?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Tasks</h1>
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
                        <th>Commit-permutation</th>
                        <th>Date</th>
                        <th>Params</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $aNum = 0;
                        foreach($aTasks as $aTask) {
                            foreach($aTask as $aItem) {
                                echo '<tr class="'.($aNum++ % 2 == 0 ? 'even' : 'odd').'">';
                                    echo '<td>'.Besearcher\View::prettyStatusName($aItem, true).'</td>';
                                    echo '<td><a href="result.php?commit='.$aItem['commit'].'&permutation='.$aItem['permutation'].'" title="Click to view more information">'.substr($aItem['commit'], 0, 16).'-'.substr($aItem['permutation'], 0, 16).'</a></td>';
                                    echo '<td>'.$aItem['date'].'</td>';
                                    echo '<td>'.$aItem['params'].'</td>';
                                echo '</tr>';
                            }
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
