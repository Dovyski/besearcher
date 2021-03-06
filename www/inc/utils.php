<?php

namespace Besearcher;

class Utils {
	public static function paginate($thePageNum, $thePageSize, $theTotal) {
		$thePageNum += 0; // cast to int
		$thePageSize += 0; // cast to int

		$thePageNum = $thePageNum <= 0 ? 1 : $thePageNum;
		$thePageSize = $thePageSize > $theTotal ? $theTotal : $thePageSize;
		$thePageSize = $thePageSize <= 0 ? 1 : $thePageSize;
		$aPages = ceil($theTotal / $thePageSize);

		$aRet = array('page' => $thePageNum, 'size' => $thePageSize, 'pages' => $aPages, 'start' => 0, 'total' => $theTotal);

		if($theTotal == 0) {
			return $aRet;
		}

		$aStart = ($thePageNum - 1) * $thePageSize;
		$aRet['start'] = $aStart;

		return $aRet;
	}

	public static function splitParamsString($theParamsString) {
		$aResult = array();
		$aNameValuePairs = explode(', ', $theParamsString);

		foreach($aNameValuePairs as $aEntry) {
			$aParts = explode('=', $aEntry);
			$aName = $aParts[0];
			$aValue = $aParts[1];

			$aResult[$aName] = $aValue;
		}

		return $aResult;
	}

	public static function humanReadableTime($theSeconds) {
		$aOut = array();
		$aParts = array();

		$s = $theSeconds % 60;
		$m = floor(($theSeconds % 3600) / 60);
		$h = floor(($theSeconds % 86400) / 3600);
		$d = floor(($theSeconds % 2592000) / 86400);
		$o = floor($theSeconds / 2592000);

		$aOut['months'] = $o;
		$aOut['days'] = $d;
		$aOut['h'] = $h;
		$aOut['min'] = $m;
		$aOut['sec'] = $s;

		foreach($aOut as $aName => $aValue) {
			if($aValue > 0) {
				$aParts[] = $aValue . ' ' . $aName;
			}
		}

		return implode(', ', $aParts);
	}
}

?>
