
<?php
    require_once(dirname(__FILE__) . '/inc/globals.php');

    Besearcher\Auth::allowAuthenticated();

    $aApp = Besearcher\WebApp::instance();
    $aResults = $aApp->getData()->findResults();

    $aAnalytics = new Besearcher\Analytics();
    $aAnalytics->process($aResults);
    $aReport = $aAnalytics->getReport();
    $aStats = $aAnalytics->getStats();

    // Get a list of all available metrics
    $aMetrics = $aAnalytics->getMetrics();

    $aJson = isset($_REQUEST['json']);
    $aView = $aJson ? 'json' : 'analytics';

    // If an specific metric was requested, use only that metric
    $aSelectedMetric = isset($_REQUEST['metric']) ? $_REQUEST['metric'] : '';
    if($aSelectedMetric != '' && in_array($aSelectedMetric, $aMetrics)) {
        $aMetrics = $aSelectedMetric;
        $aReport = $aReport[$aSelectedMetric];
        $aStats = $aStats[$aSelectedMetric];
    }

    Besearcher\View::render($aView, array(
        'metrics' => $aMetrics,
        'summary' => $aReport,
        'values' => $aStats
    ));
?>
