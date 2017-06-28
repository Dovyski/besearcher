
<?php
    require_once(dirname(__FILE__) . '/inc/globals.php');

    Besearcher\Data::init();

    $aTasks = Besearcher\Data::tasks();
    $aStats = array();

    foreach($aTasks as $aTask) {
        foreach($aTask as $aResult) {
            $aMeta = $aResult['meta'];

            foreach($aMeta as $aItem) {
                if($aItem['type'] != BESEARCHER_TAG_TYPE_PROGRESS) {
                    $aMetric = $aItem['name'];
                    $aData = $aItem['data'];

                    if(is_array($aData)) {
                        continue;
                    }

                    if(!isset($aStats[$aMetric])) {
                        $aStats[$aMetric] = array();
                    }

                    $aStats[$aMetric][] = array(
                        'commit' => $aResult['commit'],
                        'permutation' => $aResult['permutation'],
                        'value' => $aData
                    );
                }
            }
        }
    }

    $aAnalytics = array();

    foreach($aStats as $aMetric => $aItems) {
        if(count($aItems) == 0) {
            continue;
        }

        if(!isset($aAnalytics[$aMetric])) {
            $aAnalytics[$aMetric] = array(
                'min' => array('commit' => $aItems[0]['commit'], 'permutation' => $aItems[0]['permutation'], 'value' => $aItems[0]['value']),
                'max' => array('commit' => $aItems[0]['commit'], 'permutation' => $aItems[0]['permutation'], 'value' => $aItems[0]['value']),
            );
        }

        foreach($aItems as $aEntry) {
            if($aEntry['value'] < $aAnalytics[$aMetric]['min']['value']) {
                $aAnalytics[$aMetric]['min'] = $aEntry;
            }

            if($aEntry['value'] > $aAnalytics[$aMetric]['max']['value']) {
                $aAnalytics[$aMetric]['max'] = $aEntry;
            }
        }
    }

    Besearcher\View::render('analytics', array(
        'summary' => $aAnalytics
    ));
?>
