<?php

namespace Besearcher;

class Analytics {
	public static function compileMetricStats($theResults) {
		$aStats = array();

		foreach($theResults as $aResult) {
			$aMeta = @unserialize($aResult['log_file_tags']);

			if($aMeta === false) {
				continue;
			}

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
						'experiment_hash' => $aResult['experiment_hash'],
						'permutation_hash' => $aResult['permutation_hash'],
						'value' => $aData
					);
				}
			}
		}

		return $aStats;
	}

	public static function compileAnalyticsFromMetricStats($theStats) {
		$aAnalytics = array();

		foreach($theStats as $aMetric => $aItems) {
			if(count($aItems) == 0) {
				continue;
			}

			if(!isset($aAnalytics[$aMetric])) {
				$aAnalytics[$aMetric] = array(
					'min' => array('experiment_hash' => $aItems[0]['experiment_hash'], 'permutation_hash' => $aItems[0]['permutation_hash'], 'value' => $aItems[0]['value']),
					'max' => array('experiment_hash' => $aItems[0]['experiment_hash'], 'permutation_hash' => $aItems[0]['permutation_hash'], 'value' => $aItems[0]['value']),
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

		return $aAnalytics;
	}
}

?>
