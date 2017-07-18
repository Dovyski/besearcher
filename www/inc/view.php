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

	public static function prettyStatusName($theItem, $theShowText = false) {
		$aProgress 	 = $theItem['progress'];
		$aReturnCode = $theItem['raw']['cmd_return_code'];
		$aTimeEnd    = $theItem['raw']['exec_time_end'];
		$aRet        = '<i class="fa fa-question-circle-o"></i> Unknown';
		$aFinished   = $aTimeEnd != 0 || $aProgress >= 1.0;

		if(!$aFinished) {
			$aProgressText = sprintf('%.1f%%', $aProgress < 0 ? 0 : $aProgress * 100);
			$aText         = $theShowText ? 'Running (' . $aProgressText . ')' : '';
			$aRet          = '<span class="status-running" title="Running"><i class="fa fa-circle-o-notch fa-spin ""></i> ' . $aText . '</span>';

		} else if($aFinished && $aReturnCode == 0) {
			$aText         = $theShowText ? 'Complete' : '';
			$aRet          = '<span class="status-complete" title="Complete"><i class="fa fa-check-circle status-complete"></i> ' . $aText . '</span>';

		} else if($aFinished && $aReturnCode != 0) {
			$aText         = $theShowText ? 'Error' : '';
			$aRet          = '<span class="status-error" title="Error"><i class="fa fa-exclamation-circle status-error"></i> '.$aText.'</span>';
		}

		return $aRet;
	}
}

?>
