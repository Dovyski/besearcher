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
		$aUser = WebApp::users()->getByLogin($theUser);
		$aValid = $aUser !== false && password_verify($thePassword, $aUser['password']);

		return $aValid;
	}

	public static function login($theUserLogin) {
		$aUser = WebApp::users()->getByLogin($theUserLogin);

		if($aUser == false) {
			throw new \Exception('Unable to authenticate invalid user.');
		}

		$_SESSION['authenticaded'] = true;
		$_SESSION['user'] = $aUser['id'];
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
		$aUser = false;

		if(isset($_SESSION['user'])) {
			$aUser = WebApp::users()->getById($_SESSION['user']);
		}

		return $aUser;
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
