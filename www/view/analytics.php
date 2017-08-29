<?php
    $aData = Besearcher\View::data();
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
                            echo '<tr>';
                                echo '<td>'.$aMetric.'</td>';
                                echo '<td><a href="result.php?commit='.$aEntry['min']['commit'].'&permutation='.$aEntry['min']['permutation'].'" title="Click to view more information">'.$aEntry['min']['value'].'</a></td>';
                                echo '<td><a href="result.php?commit='.$aEntry['max']['commit'].'&permutation='.$aEntry['max']['permutation'].'" title="Click to view more information">'.$aEntry['max']['value'].'</a></td>';
                                echo '<td><a href="#" title="Click to view statistics"><i class="fa fa-bar-chart"></i></a></td>';
                            echo '</tr>';
                            echo '<tr><td colspan="4"><div id="chart_div'.$aNum++.'"></div></td></tr>';
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

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
    // Load the Visualization API and the corechart package.
    google.charts.load('current', {'packages':['corechart']});

    // Set a callback to run when the Google Visualization API is loaded.
    google.charts.setOnLoadCallback(drawChart);

    // Callback that creates and populates a data table,
    // instantiates the pie chart, passes in the data and
    // draws it.
    function drawChart() {
        var data = google.visualization.arrayToDataTable([
                  ['Dinosaur', 'Length'],
                  ['Acrocanthosaurus (top-spined lizard)', 12.2],
                  ['Albertosaurus (Alberta lizard)', 9.1],
                  ['Allosaurus (other lizard)', 12.2],
                  ['Apatosaurus (deceptive lizard)', 22.9],
                  ['Archaeopteryx (ancient wing)', 0.9],
                  ['Argentinosaurus (Argentina lizard)', 36.6],
                  ['Baryonyx (heavy claws)', 9.1],
                  ['Brachiosaurus (arm lizard)', 30.5],
                  ['Ceratosaurus (horned lizard)', 6.1],
                  ['Coelophysis (hollow form)', 2.7],
                  ['Compsognathus (elegant jaw)', 0.9],
                  ['Deinonychus (terrible claw)', 2.7],
                  ['Diplodocus (double beam)', 27.1],
                  ['Dromicelomimus (emu mimic)', 3.4],
                  ['Gallimimus (fowl mimic)', 5.5],
                  ['Mamenchisaurus (Mamenchi lizard)', 21.0],
                  ['Megalosaurus (big lizard)', 7.9],
                  ['Microvenator (small hunter)', 1.2],
                  ['Ornithomimus (bird mimic)', 4.6],
                  ['Oviraptor (egg robber)', 1.5],
                  ['Plateosaurus (flat lizard)', 7.9],
                  ['Sauronithoides (narrow-clawed lizard)', 2.0],
                  ['Seismosaurus (tremor lizard)', 45.7],
                  ['Spinosaurus (spiny lizard)', 12.2],
                  ['Supersaurus (super lizard)', 30.5],
                  ['Tyrannosaurus (tyrant lizard)', 15.2],
                  ['Ultrasaurus (ultra lizard)', 30.5],
                  ['Velociraptor (swift robber)', 1.8]]
             );

        var options = {
          title: 'Lengths of dinosaurs, in meters',
          legend: { position: 'none' },
        };

        var chart = new google.visualization.Histogram(document.getElementById('chart_div0'));
        chart.draw(data, options);
    }
</script>

<?php
    require_once(dirname(__FILE__) . '/footer.php');
?>
