<?php

require_once(dirname(__FILE__) . '/../../inc/constants.php');
require_once(dirname(__FILE__) . '/../../inc/Db.class.php');
require_once(dirname(__FILE__) . '/../../inc/Context.class.php');
require_once(dirname(__FILE__) . '/../../inc/AppControl.class.php');
require_once(dirname(__FILE__) . '/../../inc/Users.class.php');
require_once(dirname(__FILE__) . '/../../inc/Tasks.class.php');
require_once(dirname(__FILE__) . '/../../inc/Analytics.class.php');
require_once(dirname(__FILE__) . '/../../inc/Log.class.php');
require_once(dirname(__FILE__) . '/../../inc/ResultOutputParser.class.php');
require_once(dirname(__FILE__) . '/../../inc/App.class.php');
require_once(dirname(__FILE__) . '/WebApp.class.php');
require_once(dirname(__FILE__) . '/view.php');
require_once(dirname(__FILE__) . '/auth.php');
require_once(dirname(__FILE__) . '/utils.php');

// Mark the time this script started
define('BESERCHER_WEB_SCRIPT_START_TIME', microtime(true));

$aINIPath = dirname(__FILE__) . '/../config.ini';

try {
	// Load essential web app configuration info
	Besearcher\WebApp::bootstrap($aINIPath);

	// Start the authentication mechanism
	$aRequireAuth = Besearcher\WebApp::config('require_auth', false);
	Besearcher\Auth::init('besearchersid', $aRequireAuth);

	// Init everything else
	Besearcher\WebApp::init();

} catch(Exception $e) {
	Besearcher\View::render('error', array(
		'error'      => $e->getMessage(),
		'ini'        => $aINIPath,
		'hideNavbar' => true
	));
	exit();
}

?>
