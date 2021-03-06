<?php
/*
 This file has a set of constants that guide the behavior of functions

 Author: Fernando Bevilacqua <fernando.bevilacqua@his.se>
 */

define('BESEARCHER_HOME', dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
define('BESEARCHER_CMD_DIR', BESEARCHER_HOME . 'cmd' . DIRECTORY_SEPARATOR);
define('BESEARCHER_RUNNER_CMD', 'php "' . BESEARCHER_CMD_DIR . 'runner.php"');

define('BESEARCHER_TAG',                    '[BSR]');
define('BESEARCHER_TAG_TYPE_RESULT',        'result');
define('BESEARCHER_TAG_TYPE_PROGRESS',      'progress');

define('BESEARCHER_STATUS_INITING',          'INITING');
define('BESEARCHER_STATUS_RUNNING',          'RUNNING');
define('BESEARCHER_STATUS_PAUSED',           'PAUSED');
define('BESEARCHER_STATUS_WAITING_SETUP',    'WAITING_SETUP');
define('BESEARCHER_STATUS_STOPPING',         'STOPPING');
define('BESEARCHER_STATUS_STOPED',           'STOPED');

define('BESEARCHER_DB_FILE',                 'besearcher.sqlite');
define('BESEARCHER_SETEUP_FILE',             'besearcher.setup_cmd-result');
define('BESEARCHER_SETUP_LOG_FILE',          'setup_cmd.log');

// Below are the definitions of the expressions that are
// expandable in the INI file.

// E.g. 0..10:1, which generates 0,1,2,...,10
define('INI_EXP_START_END_INC', '/(\d*[.]?\d*)[[:blank:]]*\.\.[[:blank:]]*(\d*[.]?\d*)[[:blank:]]*:[[:blank:]]*(\d*[.]?\d*)/i');

// E.g. 0..10:1, which generates 0,1,2,...,10
define('INI_PERM', '/perm[[:blank:]]*:[[:blank:]]*(\d+)[[:blank:]]*:[[:blank:]]*(.*)/i');

?>
