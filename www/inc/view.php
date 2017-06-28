<?php

namespace Besearcher;

class View {
	private static $mData;

	public static function render($theName, array $theData = array()) {
		self::$mData = $theData;
		require_once(dirname(__FILE__) . '/../view/' . $theName . '.php');
	}

	public static function data() {
		return self::$mData;
	}

	public static function out($theText) {
		return htmlspecialchars($theText);
	}

	public static function prettyProgressName($theProgressValue) {
		$aRet = '<i class="fa fa-question-circle-o"></i> Unknown';

		if($theProgressValue >= 0. && $theProgressValue < 1.0) {
			$aRet = '<i class="fa fa-circle-o-notch fa-spin"></i> Running';
		} else if($theProgressValue >= 1.0) {
			$aRet = '<i class="fa fa-check-circle"></i> Complete';
		}

		return $aRet;
	}

	public static function prettyProgressValue($theProgressValue) {
		$aRet = '<i class="fa fa-question-circle-o"></i> Unknown';

		if($theProgressValue >= 0. && $theProgressValue < 1.0) {
			$aRet = '<i class="fa fa-circle-o-notch fa-spin"></i> '.sprintf('%.1f%%', $theProgressValue * 100);
		} else if($theProgressValue >= 1.0) {
			$aRet = '<i class="fa fa-check-circle"></i> '.sprintf('%.0f%%', $theProgressValue * 100);
		}

		return $aRet;
	}
}

?>
