<?php

namespace Besearcher;

class Utils {
	public static function paginate($theArray, $thePageNum, $theSize) {
		$thePageNum += 0; // cast to int
		$theSize += 0; // cast to int

		$aTotal = count($theArray);
		$thePageNum = $thePageNum <= 0 ? 1 : $thePageNum;
		$theSize = $theSize > $aTotal ? $aTotal : $theSize;
		$theSize = $theSize <= 0 ? 1 : $theSize;
		$aPages = ceil($aTotal / $theSize);

		$aRet = array('page' => $thePageNum, 'size' => $theSize, 'pages' => $aPages, 'start' => 0, 'data' => array());

		if($aTotal == 0) {
			return $aRet;
		}

		$aStart = ($thePageNum - 1) * $theSize;
		$aRet['start'] = $aStart;
		$aRet['data'] = array_slice($theArray, $aStart, $theSize);

		return $aRet;
	}
}

?>
