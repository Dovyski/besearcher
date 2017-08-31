
<?php
    require_once(dirname(__FILE__) . '/inc/globals.php');

    function compareStats($theA, $theB) {
        if ($theA['value'] == $theB['value']) {
            return 0;
        }
        return ($theA['value'] < $theB['value']) ? 1 : -1;
    }

    Besearcher\Auth::allowAuthenticated();

    $aTasks = Besearcher\Data::tasks();
    $aStats = Besearcher\Data::compileMetricStats($aTasks);
    $aAnalytics = Besearcher\Data::compileAnalyticsFromMetricStats($aStats);

    // Get a list of all available metrics
    $aMetrics = array_keys($aStats);

    // Sort everything from best to worst
    foreach($aMetrics as $aMetric) {
        usort($aStats[$aMetric], 'compareStats');
    }

    $aJson = isset($_REQUEST['json']);
    $aView = $aJson ? 'json' : 'analytics';

    // If an specific metric was requested, use only that metric
    $aSelectedMetric = isset($_REQUEST['metric']) ? $_REQUEST['metric'] : '';
    if($aSelectedMetric != '' && in_array($aSelectedMetric, $aMetrics)) {
        $aMetrics = $aSelectedMetric;
        $aAnalytics = $aAnalytics[$aSelectedMetric];
        $aStats = $aStats[$aSelectedMetric];
    }

    Besearcher\View::render($aView, array(
        'metrics' => $aMetrics,
        'summary' => $aAnalytics,
        'values' => $aStats
    ));
?>
