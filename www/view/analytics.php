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

    <?php if(count($aSummary) == 0) { ?>
    <div class="row">
        <div class="col-lg-12">
            There is not enough data to perform any analytics.
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <?php } else { ?>

    <div class="row">
        <div class="col-lg-12">
            <table width="100%" class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>Min</th>
                        <th>Max</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        foreach($aSummary as $aMetric => $aEntry) {
                            echo '<tr>';
                                echo '<td>'.$aMetric.'</td>';
                                echo '<td><a href="result.php?commit='.$aEntry['min']['commit'].'&permutation='.$aEntry['min']['permutation'].'" title="Click to view more information">'.$aEntry['min']['value'].'</a></td>';
                                echo '<td><a href="result.php?commit='.$aEntry['max']['commit'].'&permutation='.$aEntry['max']['permutation'].'" title="Click to view more information">'.$aEntry['max']['value'].'</a></td>';
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
