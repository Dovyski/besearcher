<?php

/*
 Manage web dashboard users, e.g. add, remove, edit.

 Author: Fernando Bevilacqua <fernando.bevilacqua@his.se>
 */

require_once(dirname(__FILE__) . '/../inc/constants.php');
require_once(dirname(__FILE__) . '/../inc/CmdUtils.class.php');
require_once(dirname(__FILE__) . '/../inc/Db.class.php');
require_once(dirname(__FILE__) . '/../inc/Context.class.php');
require_once(dirname(__FILE__) . '/../inc/Tasks.class.php');
require_once(dirname(__FILE__) . '/../inc/Log.class.php');
require_once(dirname(__FILE__) . '/../inc/Users.class.php');
require_once(dirname(__FILE__) . '/../inc/App.class.php');

function collectUserInfoFromInput() {
    do {
        echo 'Name (e.g. John Doe): '; $aName = readInput();
        $aInvalid = empty($aName);
        if($aInvalid) {
            echo 'Invalid name!' . "\n";
        }
    } while($aInvalid);

    do {
        echo 'Login (lower case, no spaces, e.g. johndoe): '; $aLogin = readInput();
        var_dump(stripos($aLogin, ' '));
        vaR_dump($aLogin);
        $aInvalid = empty($aLogin) || stripos($aLogin, ' ') !== false;
        if($aInvalid) {
            echo 'Invalid login!' . "\n";
        }
    } while($aInvalid);

    do {
        echo 'Password: '; $aPassword = readInput();
        $aInvalid = empty($aLogin);
        if($aInvalid) {
            echo 'Invalid password!' . "\n";
        }
    } while($aInvalid);

    do {
        echo 'Re-type password: '; $aPassword2 = readInput();
        $aInvalid = $aPassword != $aPassword2;

        if($aInvalid) {
            echo 'Password don\'t match!' . "\n";
        }
    } while($aInvalid);

    $aUser = array('name' => $aName, 'login' => $aLogin, 'password' => $aPassword);
    return $aUser;
}

$aOptions = array(
    "ini:",
    "list",
    "add",
    "remove:",
    "edit:",
    "force",
    "help"
);

$aArgs = getopt("hf", $aOptions);

if(isset($aArgs['h']) || isset($aArgs['help']) || $argc == 1) {
     echo "Usage: \n";
     echo " php ".basename($_SERVER['PHP_SELF']) . " [options]\n\n";
     echo "Options:\n";
     echo " --ini=<path>     Path to the INI file being used by Besearcher.\n";
     echo " --list           List all existing users able to acess the web dashboard.\n";
     echo " --add            Add a new user able to access the web dashboard.\n";
     echo " --remove=<id>    Remove dashboard user with id <id>.\n";
     echo " --edit=<id>      Edit infos of dashboard user with id <id>.\n";
     echo " --force, -f      Perform operations without asking for confirmation.\n";
     echo " --help, -h       Show this help.\n";
     echo "\n";
     exit(1);
}

$aIniPath = isset($aArgs['ini']) ? $aArgs['ini'] : '';

$aApp = new Besearcher\App();
$aApp->init($aIniPath, '', true);
$aUsersManager = new Besearcher\Users($aApp->getDb());

$aIsForce = isset($aArgs['f']) || isset($aArgs['force']);

if(isset($aArgs['list'])) {
    $aUsers = $aUsersManager->findAll();
    if(count($aUsers) > 0) {
        echo "Id | Name           | Login" . "\n";
        foreach($aUsers as $aUser) {
            echo "No users found. You can add new users by using the --user-add paramater." . "\n";
            echo $aUser['id'] . ' ' . $aUser['name'] . ' ' . $aUser['login'] . "\n";
        }
    } else {
        echo "You have no users yet. Add new users using --add." . "\n";
    }

} else if(isset($aArgs['add'])) {
    echo 'Adding new user. Please inform user info below.' . "\n";
    $aUser = collectUserInfoFromInput();
    var_dump($aUser);

    $aOk = true;

    if($aOk) {
        echo 'User created successfully!' . "\n";
    } else {
        echo 'Unable to create user. Please try again.' . "\n";
    }

} else if(isset($aArgs['remove'])) {

} else if(isset($aArgs['edit'])) {

} else {
    echo "Invalid command line parameters. Have you forgot to input any value?" . "\n";
    exit(2);
}

exit(0);

?>
