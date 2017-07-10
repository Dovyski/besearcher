<?php

$aINI = @parse_ini_file(dirname(__FILE__) . '/../config.ini');

if($aINI === false) {
	die('There is a syntax error in config.ini or it does not exist.');
}

if(!isset($aINI['besearcher_ini_file'])) {
	die('Unable to find "besearcher_ini_file" directive in config.ini. Please check if the file is correct.');
}

// Turn INI values into constants
define('PATH_BESERCHER_INI_FILE', @$aINI['besearcher_ini_file']);
define('BESERCHER_SESSION_NAME', 'besearchersid');

require_once(dirname(__FILE__) . '/../../inc/functions.php');
require_once(dirname(__FILE__) . '/view.php');
require_once(dirname(__FILE__) . '/data.php');
require_once(dirname(__FILE__) . '/auth.php');

// Mark the time this script started
define('BESERCHER_WEB_SCRIPT_START_TIME', microtime(true));

// Start the authentication mechanism
Besearcher\Auth::init();
?>
