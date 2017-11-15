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
			throw new \Exception('Log file does not exists: ' . $this->mResult['log_file']);
		}

        // Find special marks in the log file that inform
        // Besearcher about things
        $this->mTags = $this->findBesearcherLogTags($this->mResult['log_file']);
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
