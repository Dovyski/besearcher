<?php

namespace Besearcher;

class CmdUtils {
	public static function readInput($theScanfString = '%s') {
	    $aValue = null;

	    if($theScanfString == '%s') {
	        $aValue = trim(fgets(STDIN));
	    } else {
	        fscanf(STDIN, $theScanfString, $aValue);
	    }
	    return $aValue;
	}

	public static function confirmOperation($theText = "Operation can't be undone, proceed") {
	    echo $theText . " (y/n)? ";
	    $aAnswer = self::readInput();

	    if(strtolower($aAnswer) == 'n') {
	        exit(0);
	    }
	}
}

?>
