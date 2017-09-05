<?php

namespace Besearcher;

/**
 * Parse the output produced by a task to find Besearcher tags and
 * collect special info, e.g. task progress.
 */
class ResultOutputParser {
	private $mResult;
	private $mTags;

	public function __construct($theResult) {
		$this->mResult = $theResult;
		$this->run();
	}

	public function calculateTaskProgress() {
		if(count($this->mTags) == 0) {
			return 0;
		}

	    $aProgresses = $this->findTagsByType(BESEARCHER_TAG_TYPE_PROGRESS);
	    $aCount = count($aProgresses);

	    $aProgress =  $aCount > 0 ? $aProgresses[$aCount - 1]['data'] : -1;
	    return $aProgress;
	}

	private function run() {
		if(!file_exists($this->mResult['log_file'])) {
			throw new \Exception('Log file does not exists:' . $this->mResult['log_file']);
		}

        // Find special marks in the log file that inform
        // Besearcher about things
        $this->mTags = $this->handleBesearcherLogTags($this->mResult);
	}

	private function isResultFinished($theResult) {
		$aFinished = $theResult['running'] == 0 && $theResult['exec_time_end'] != 0;
		return $aFinished;
	}

	private function handleBesearcherLogTags($theTaskInfo, $theUseCache = true) {
		$aTags = array();

		if($this->isResultFinished($theTaskInfo) && $theUseCache) {
			// Task has finished, it might be a cached version of the log file.
			$aCacheFilePath = $theTaskInfo['log_file'] . BESEARCHER_CACHE_FILE_EXT;

			if(file_exists($aCacheFilePath)) {
				$aTags = unserialize(file_get_contents($aCacheFilePath));
			} else {
				$aTags = $this->findBesearcherLogTags($theTaskInfo['log_file']);
				file_put_contents($aCacheFilePath, serialize($aTags));
			}
		} else {
			$aTags = $this->findBesearcherLogTags($theTaskInfo['log_file']);
		}

		return $aTags;
	}

	private function findBesearcherLogTags($theLogFilePath) {
	    $aRet = array();
	    $aFile = @fopen($theLogFilePath, 'r');

	    if (!$aFile) {
	        return $aRet;
	    }

	    $aLimit = strlen(BESEARCHER_TAG);

	    while (($aLine = fgets($aFile)) !== false) {
	        if(!empty($aLine) && $aLine != '') {
	            $aMarker = substr($aLine, 0, $aLimit);

	            if($aMarker == BESEARCHER_TAG) {
	                $aText = substr($aLine, $aLimit);
	                $aRet[] = json_decode(trim($aText), true);
	            }
	        }
	    }

	    fclose($aFile);
	    return $aRet;
	}

	private function findTagsByType($theType) {
	    $aRet = array();

	    foreach($this->mTags as $aItem) {
	        if($aItem['type'] == $theType) {
	            $aRet[] = $aItem;
	        }
	    }

	    return $aRet;
	}

	public function getTags() {
		return $this->mTags;
	}
}

?>
