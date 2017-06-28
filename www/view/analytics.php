<?php
    $aData = Besearcher\View::data();
    $aSummary = $aData['summary'];
?>

<?php require_once(dirname(__FILE__) . '/header.php'); ?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Analytics</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->

    <?php foreach($aSummary as $aMetric => $aEntry) { ?>
        <div class="row">
            <div class="col-lg-12" style="text-align: center;">
                <strong><?php echo $aMetric; ?></strong>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <div class="row">
            <div class="col-lg-12">
                <table width="100%" class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Analysis</th>
                            <th>Value</th>
                            <th>Commit-permutation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            foreach($aEntry as $aName => $aData) {
                                echo '<tr>';
                                    echo '<td>'.$aName.'</td>';
                                    echo '<td>'.$aData['value'].'</td>';
                                    echo '<td><a href="result.php?commit='.$aData['commit'].'&permutation='.$aData['permutation'].'" title="Click to view more information">'.$aData['commit'].'-'.$aData['permutation'].'</a></td>';
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
    <?php } ?>
</div>
<!-- /#page-wrapper -->

<?php
    require_once(dirname(__FILE__) . '/footer.php');
?>
