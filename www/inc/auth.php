<?php

namespace Besearcher;

class Auth {
	private static $mSessionName;

	public static function init($theSessionName) {
		self::$mSessionName = $theSessionName;
		session_start();
		session_name(self::$mSessionName);
	}

	public static function credentialsMatch($theUser, $thePassword) {
		$aUsersManager = new Users(WebApp::instance()->getDb());
		$aUser = $aUsersManager->getByLogin($theUser);
		$aValid = $aUser !== false && password_verify($thePassword, $aUser['password']);

		return $aValid;
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

	public static function user() {
		return isset($_SESSION['user']) ? $_SESSION['user'] : '';
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
