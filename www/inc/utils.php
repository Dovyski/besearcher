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
}

?>
