<?php
    $aData = Besearcher\View::data();
    $aParams = $aData['params'];
    $aSummary = $aData['summary'];
    $aValues = $aData['values'];
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
            <h3 class="page-header"><i class="fa fa-list-ul"></i> Metrics ranking</h3>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <table width="100%" class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>Min</th>
                        <th>Max</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $aNum = 0;
                        foreach($aSummary as $aMetric => $aEntry) {
                            $aContainer = 'metric'.$aNum;
                            echo '<tr>';
                                echo '<td>'.$aMetric.'</td>';
                                echo '<td><a href="result.php?experiment_hash='.$aEntry['min']['experiment_hash'].'&permutation_hash='.$aEntry['min']['permutation_hash'].'" title="Click to view more information">'.$aEntry['min']['value'].'</a></td>';
                                echo '<td><a href="result.php?experiment_hash='.$aEntry['max']['experiment_hash'].'&permutation_hash='.$aEntry['max']['permutation_hash'].'" title="Click to view more information">'.$aEntry['max']['value'].'</a></td>';
                                echo '<td><a href="#" class="show-stats" data-container="'.$aContainer.'" data-metric="'.$aMetric.'" title="Click to view statistics" ><i class="fa fa-bar-chart"></i></a></td>';
                            echo '</tr>';
                            echo '<tr id="row'.$aContainer.'" style="display:none;" data-open="false"><td colspan="4"><div id="'.$aContainer.'"></div></td></tr>';
                            $aNum++;
                        }
                    ?>
                </tbody>
            </table>
            <!-- /.table-responsive -->
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->

    <div class="row">
        <div class="col-lg-12">
            <h3 class="page-header"><i class="fa fa-th"></i> Experiment report</h3>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <form id="form-experiment-report" method="get" action="analytics.php">
                <div class="form-row">
                    <?php
                        foreach($aParams as $aName) {
                            echo '<div class="form-group">';
                              echo '<label for="report-param-'.$aName.'"><i class="fa fa-sliders"></i> '.$aName.'</label>';
                              echo '<select id="report-param-'.$aName.'" class="form-control" name="'.$aName.'">';
                                    echo '<option value="__ANY__">*ANY*</option>';
                                    foreach($aParams as $aName) {
                                        echo '<option value="'.$aName.'">'.$aName.'</option>';
                                    }
                              echo '</select>';
                            echo '</div>';
                        }
                    ?>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary" id="experiment-report-submit">Apply</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row" style="padding-top: 30px;" >
        <div class="col-lg-12" id="experiment-report-table" style="display: none;">
        </div>
    </div>
    <!-- /.row -->
    <?php } ?>
</div>
<!-- /#page-wrapper -->

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript" src="./js/analytics.js"></script>

<?php
    require_once(dirname(__FILE__) . '/footer.php');
?>
