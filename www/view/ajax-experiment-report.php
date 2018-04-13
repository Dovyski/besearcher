<?php
    $aData = Besearcher\View::data();
    $aFilter = $aData['filter'];
?>

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
