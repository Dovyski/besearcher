<?php

namespace Besearcher;

class Auth {
	public static function init() {
		session_start();
		session_name(BESERCHER_SESSION_NAME);
	}

	public static function isValidUser($theUser, $thePassword) {
		// TODO: implement this
		return true;
	}

	public static function login($theUserData) {
		$_SESSION['authenticaded'] = true;
		$_SESSION['user'] = $theUserData;
	}

	public static function allowAuthenticated() {
		if(!self::isAuthenticated()) {
			header('Location: login.php');
			exit();
		}
	}

	public static function allowNonAuthenticated() {
		if(self::isAuthenticated()) {
			header('Location: index.php');
			exit();
		}
	}

	public static function logout() {
		unset($_SESSION);
		session_destroy();
	}

	public static function isAuthenticated() {
		return isset($_SESSION['authenticaded']) && $_SESSION['authenticaded'];
	}
}

?>
