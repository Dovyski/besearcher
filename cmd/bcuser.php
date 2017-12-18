#!/usr/bin/php
<?php

/*
 Manage web dashboard users, e.g. add, remove, edit.

 Author: Fernando Bevilacqua <fernando.bevilacqua@his.se>
 */

require_once(dirname(__FILE__) . '/../inc/constants.php');
require_once(dirname(__FILE__) . '/../inc/CmdUtils.class.php');
require_once(dirname(__FILE__) . '/../inc/Db.class.php');
require_once(dirname(__FILE__) . '/../inc/Context.class.php');
require_once(dirname(__FILE__) . '/../inc/AppControl.class.php');
require_once(dirname(__FILE__) . '/../inc/Tasks.class.php');
require_once(dirname(__FILE__) . '/../inc/Log.class.php');
require_once(dirname(__FILE__) . '/../inc/Users.class.php');
require_once(dirname(__FILE__) . '/../inc/App.class.php');

function readStringFromInput($theText = 'Input value: ', $theError = 'Invalid value!', $theAllowEmpty = false) {
    $aValue = null;
    do {
        echo $theText;
        $aValue = Besearcher\CmdUtils::readInput();
        $aInvalid = empty($aValue) && !$theAllowEmpty;

        if($aInvalid) {
            echo $theError . "\n";
        }
    } while($aInvalid);

    return $aValue;
}

function getExistingValue($theFieldName, array $theArray) {
    $aString = isset($theArray[$theFieldName]) ? (' [current: ' . $theArray[$theFieldName] . ']') : '';
    return $aString;
}

function collectUserInfoFromInput(array $theExistingValues = array(), $theAllowEmpty = false) {
    $aName = readStringFromInput('Name (e.g. John Doe)' . getExistingValue('name', $theExistingValues) . ': ', 'Invalid name!', $theAllowEmpty);

    do {
        echo 'Login (lower case, no spaces, e.g. johndoe)'.getExistingValue('login', $theExistingValues).': '; $aLogin = Besearcher\CmdUtils::readInput();
        $aInvalid = (empty($aLogin) && !$theAllowEmpty) || stripos($aLogin, ' ') !== false;

        if($aInvalid) {
            echo 'Invalid login!' . "\n";
        }
    } while($aInvalid);

    $aEmail = readStringFromInput('Email'.getExistingValue('email', $theExistingValues).': ', 'Invalid e-mail!', $theAllowEmpty);
    $aPassword = readStringFromInput('Password: ', 'Invalid password!', $theAllowEmpty);

    if($theAllowEmpty && !empty($aPassword) || !$theAllowEmpty) {
        do {
            echo 'Re-type password: '; $aPassword2 = Besearcher\CmdUtils::readInput();
            $aInvalid = $aPassword != $aPassword2;

            if($aInvalid) {
                echo 'Password don\'t match!' . "\n";
            }
        } while($aInvalid);
    }

    $aUser = array('name' => $aName, 'email' => $aEmail, 'login' => $aLogin, 'password' => $aPassword);
    return $aUser;
}

function fitString($theString, $theLength = 30, $thePaddingChar = ' ') {
    $aLength = strlen($theString);
    $aDiff = $theLength - $aLength;

    if($aDiff < 0) {
        $aOut = substr($theString, 0, $theLength - 1);

    } else {
        $aOut = $theString;

        for($i = 0; $i < $aDiff - 1; $i++) {
            $aOut .= $thePaddingChar;
        }
    }

    return $aOut;
}

function listUsers(Besearcher\Users & $theUserManager) {
    $aUsers = $theUserManager->findAll();

    if(count($aUsers) > 0) {
        echo fitString('Id', 4) . '  ' . fitString('Login', 20) . '  ' . fitString('Name', 20) . '  '  . fitString('E-mail', 45) . "\n";
        echo str_repeat('-', 85) . "\n";

        foreach($aUsers as $aUser) {
            echo fitString($aUser['id'], 4) . '  ' . fitString($aUser['login'], 20) . '  ' . fitString($aUser['name'], 20) . '  '  . fitString($aUser['email'], 45) . "\n";
        }
    } else {
        echo "You have no users yet. Add new users using --add." . "\n";
    }
}

function addUser(Besearcher\Users & $theUserManager) {
    echo 'Adding new user. Please inform user info below.' . "\n";

    $aUser = collectUserInfoFromInput();
    $aHashedPassword = password_hash($aUser['password'], PASSWORD_DEFAULT);

    $aOk = $theUserManager->create($aUser['name'], $aUser['email'], $aUser['login'], $aHashedPassword);

    echo "\n";

    if($aOk) {
        echo 'User created successfully!' . "\n";
    } else {
        echo 'Unable to create user. Please try again.' . "\n";
    }
}

function editUser(Besearcher\Users & $theUserManager, $theUserId) {
    $aUser = $theUserManager->getById($theUserId);

    if($aUser == false) {
        echo 'Unable to find user with id: ' . $theUserId . "\n";
        exit(3);
    } else {
        echo 'Editing user with id:' . $theUserId . '. Leave fields blank to keep its current value.'. "\n";

        $aUserInput = collectUserInfoFromInput($aUser, true);
        $aUpdate = array();

        foreach($aUser as $aField => $aValue) {
            if(isset($aUserInput[$aField]) && !empty($aUserInput[$aField])) {
                $aUpdate[$aField] = $aUserInput[$aField];

                if($aField == 'password') {
                    $aUpdate[$aField] = password_hash($aUpdate[$aField], PASSWORD_DEFAULT);
                }
            }
        }

        $aUpdateRequired = count($aUpdate) > 0;

        if($aUpdateRequired) {
            $aOk = $theUserManager->update($aUser['id'], $aUpdate);
        }

        echo "\n";

        if(!$aUpdateRequired) {
            echo 'No changes were provided. User info remains the same.' . "\n";
        } else if($aOk) {
            echo 'User updated successfully!' . "\n";
        } else {
            echo 'Unable to update user. Please try again.' . "\n";
        }
    }
}

function removeUser(Besearcher\Users & $theUserManager, $theUserId) {
    $aUser = $theUserManager->getById($theUserId);

    if($aUser == false) {
        echo 'Unable to find user with id: ' . $theUserId . "\n";
        exit(3);
    } else {
        echo 'Removing user:' . "\n";
        echo ' Name: ' . $aUser['name']. "\n";
        echo ' E-mail: ' . $aUser['email']. "\n";
        echo ' Login: ' . $aUser['login']. "\n";

        Besearcher\CmdUtils::confirmOperation();

        $aOk = $theUserManager->removeById($theUserId);

        if($aOk) {
            echo 'User removed successfully!' . "\n";
        } else {
            echo 'Unable to remove user. Please try again.' . "\n";
        }
    }
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

try {
    $aApp = new Besearcher\App();
    $aApp->init($aIniPath, '', true);
    $aUsersManager = new Besearcher\Users($aApp->getDb());

} catch (\Exception $e) {
    echo $e->getMessage();
    exit(1);
}

$aIsForce = isset($aArgs['f']) || isset($aArgs['force']);

if(isset($aArgs['list'])) {
    listUsers($aUsersManager);

} else if(isset($aArgs['add'])) {
    addUser($aUsersManager);

} else if(isset($aArgs['remove'])) {
    removeUser($aUsersManager, $aArgs['remove']);

} else if(isset($aArgs['edit'])) {
    editUser($aUsersManager, $aArgs['edit']);

} else {
    echo "Invalid command line parameters. Have you forgot to input any value?" . "\n";
    exit(2);
}

exit(0);

?>
