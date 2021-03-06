<?php

namespace Besearcher;

/**
 * Perform some analytics on a set of results.
 */
class Analytics {
	private $mReport;
	private $mStats;

	public function __constructor() {
		$mReport = array();
	}

	public function process(array $theResults) {
		if(count($theResults) == 0) {
			return;
		}

		$this->mStats = $this->compileMetricStats($theResults);
		$this->mReport = $this->createReportFromMetricStats($this->mStats);

		$aMetrics = $this->getMetrics();

		// Sort everything from best to worst
	    foreach($aMetrics as $aMetric) {
	        usort($this->mStats[$aMetric], array($this, 'compareStats'));
	    }
	}

	public function compareStats($theA, $theB) {
        if ($theA['value'] == $theB['value']) {
            return 0;
        }
        return ($theA['value'] < $theB['value']) ? 1 : -1;
    }

	private function compileMetricStats($theResults) {
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

	private function createReportFromMetricStats($theStats) {
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

	public function getStats() {
		return $this->mStats;
	}

	public function getReport() {
		return $this->mReport;
	}

	public function getMetrics() {
		return !is_array($this->mStats) ? array() : array_keys($this->mStats);
	}
}

?>
