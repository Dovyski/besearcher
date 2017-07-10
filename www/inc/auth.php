<?php

namespace Besearcher;

class Auth {
	public static function init() {
		session_start();
		session_name(BESERCHER_SESSION_NAME);
	}

	public static function isValidUser($theUser, $thePassword) {
		// TODO: get this value from INI?
		$aPasswordFile = dirname(__FILE__) . '/../.htpasswd';
		$aValid = false;

		if(file_exists($aPasswordFile)) {
			$aValues = file($aPasswordFile);
			foreach($aValues as $aRow) {
				$aParts = explode(':', trim($aRow), 2);
				$aUser = @$aParts[0];
				$aHash = @$aParts[1];

				if($aUser == $theUser && password_verify($thePassword, $aHash)) {
					$aValid = true;
				}
			}
		} else {
			// If no password file is provided, allow anyone to login
			$aValid = true;
		}

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
