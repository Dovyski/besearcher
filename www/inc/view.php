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
}

?>
