#!/usr/bin/php
<?php

/*
 Loads task result files and produces a summary of their data.

 Author: Fernando Bevilacqua <fernando.bevilacqua@his.se>
 */

require_once(dirname(__FILE__) . '/../inc/constants.php');
require_once(dirname(__FILE__) . '/../inc/CmdUtils.class.php');
require_once(dirname(__FILE__) . '/../inc/Db.class.php');
require_once(dirname(__FILE__) . '/../inc/Context.class.php');
require_once(dirname(__FILE__) . '/../inc/Tasks.class.php');
require_once(dirname(__FILE__) . '/../inc/Log.class.php');
require_once(dirname(__FILE__) . '/../inc/App.class.php');

function deleteFilesList($theList, $theExitCode = 1, $theVerbose = false) {
    foreach($theList as $aPath) {
        if(file_exists($aPath)) {
            if($theVerbose) {
                echo " removing: ". $aPath . "\n";
            }

            $aOk = @unlink($aPath);

            if(!$aOk) {
                echo "Unable to remove file: \"" . $aPath . "\". Is it locked by another program?\n";
                exit($theExitCode);
            }
        }
    }
}

function migrateTaskResults($theDataDir, $theData, $theVerbose = true) {
    $aMigrated = 0;
    $aTasks = scandir($theDataDir);

    foreach($aTasks as $aItem) {
        $aPath = $theDataDir . DIRECTORY_SEPARATOR . $aItem;

        if($aItem[0] != '.' && is_dir($aPath)) {
            $aFiles = glob($aPath . DIRECTORY_SEPARATOR . '*.json');

            foreach($aFiles as $aFile) {
                $aTaskInfo = json_decode(file_get_contents($aFile), true);

                if($aTaskInfo === false) {
                    throw new \Exception('unable to decode task JSON in ' . $aFile);
                }

                $aHasTaskFinished = $aTaskInfo['exec_time_end'] > 0;

                if(!$aHasTaskFinished) {
                    // The task has not finished, so it will eventually be dequeued and processed
                    // by Besearcher in the future. Let's skip its migration then.
                    continue;
                }

                $aTaskInfo['experiment_hash'] = $aTaskInfo['hash'];
                $aTaskInfo['permutation_hash'] = $aTaskInfo['permutation'];

                $aQueueTask = $theData->getTaskByHashes($aTaskInfo['experiment_hash'], $aTaskInfo['permutation_hash']);

                if($aQueueTask === false) {
                    // We can't locate the task in the queue. There is nothing we can do to guess its id.
                    // End of the game.
                    throw new \Exception('unable to locate task with experiment_hash='.$aTaskInfo['experiment_hash'].' and permutation_hash=' . $aTaskInfo['permutation_hash']);
                }

                // Bind the file content with the db content
                $aTaskInfo['id'] = $aQueueTask['id'];

                if($theVerbose) {
                    echo ' adding task ' . $aTaskInfo['id'] . "\n";
                }

                // Remove the task from the queue because it was found on the disk, which
                // means it was already executed.
                $aOk1 = $theData->removeTask($aTaskInfo['id']);

                // Create the result entry in the DB from the result we found on disk.
                // This step is the actual migration from one format to another.
                $aOk2 = $theData->createResultEntryFromTask($aTaskInfo);

                if(!$aOk1 || !$aOk2) {
                    throw new \Exception('unable to create result entry for the task below.' . "\n" . print_r($aTaskInfo, true));
                }

                $aSerializedTags = @file_get_contents($aPath . DIRECTORY_SEPARATOR . $aTaskInfo['experiment_hash'] . '-' . $aTaskInfo['permutation_hash'] . '.log.besearcher-cache');

                if($aSerializedTags === false) {
                    $aSerializedTags = serialize(array());
                }

                $theData->updateResult($aTaskInfo['id'], array(
                    'running' => 0,
                    'progress' => $aTaskInfo['exec_time_end'] > 0 ? 1.0 : 0,
                    'exec_time_start' => $aTaskInfo['exec_time_start'],
                    'exec_time_end' => $aTaskInfo['exec_time_end'],
                    'log_file_tags' => $aSerializedTags
                ));

                $aMigrated++;
            }
        }
    }

    return $aMigrated;
}

$aOptions = array(
    "ini:",
    "status",
    "pause",
    "stop",
    "resume",
    "reload",
    "reset",
    "migrate",
    "test-email",
    "verbose",
    "force",
    "help"
);

$aArgs = getopt("hvf", $aOptions);

if(isset($aArgs['h']) || isset($aArgs['help']) || $argc == 1) {
     echo "Usage: \n";
     echo " php ".basename($_SERVER['PHP_SELF']) . " [options]\n\n";
     echo "Options:\n";
     echo " --ini=<path>         Path to the INI file being used by Besearcher.\n";
     echo " --status             Show a few info about the running instance of Besearcher.\n";
     echo " --pause              Pause Besearcher. Currently running tasks will\n";
     echo "                      continue to run, but new tasks will not be created.\n";
     echo " --stop               Stop Besearcher, but wait for all running tasks to\n";
     echo "                      finish first.";
     echo " --resume             Make Besearcher resume its operation, if it is paused.\n";
     echo " --reload             Clear cache files on disk forcing Besearcher to create\n";
     echo "                      and perform experiment tasks. If the directive \n";
     echo "                      skip_performed_tasks is true, already performed tasks\n";
     echo "                      will be skipped.\n";
     echo " --reset              Delete *ALL* control settings, like records of enqued\n";
     echo "                      tasks. Result files created by previous completed tasks\n";
     echo "                      are preserved.\n";
     echo " --test-email         Send a test e-mail to check if e-mail messages are working.\n";
     echo " --force, -f          Perform operations without asking for confirmation.\n";
     echo " --verbose, -v        Print extra info for performed actions.\n";
     echo " --migrate            Migrate data files generated by previous versions of\n";
     echo "                      Besearcher to its most recent architecture.\n";
     echo " --help, -h           Show this help.\n";
     echo "\n";
     exit(1);
}

$aIniPath = isset($aArgs['ini']) ? $aArgs['ini'] : '';

$aApp = new Besearcher\App();
$aApp->init($aIniPath, '', true);

$aContext = $aApp->getContext();
$aData = $aApp->getData();

$aIsVerbose = isset($aArgs['v']) || isset($aArgs['verbose']);
$aIsForce = isset($aArgs['f']) || isset($aArgs['force']);

if(isset($aArgs['status'])) {
    $aQueueSize = $aData->queueSize();
    $aRunningTasks = count($aData->findRunningTasks());
    $aStatus = $aContext->get('status');

    echo "Besearcher summary:\n";
    echo " Status: ".(empty($aStatus) ? "***UNKNOWN***" : $aStatus)."\n";
    echo " INI file: ".$aIniPath."\n";
    echo " Experiment hash: ".$aContext->get('experiment_hash')."\n";
    echo " Experiment description: ".trim($aApp->config('experiment_description'))."\n";
    echo " Experiment ready: ".$aApp->config('experiment_ready')."\n";
    echo " Tasks waiting in queue: ". $aQueueSize."\n";
    echo " Tasks running: ". $aRunningTasks."\n";

    if(empty($aStatus)) {
        echo "WARNING: Besearcher has never run using the provided INI file." . "\n";
    }
}

if(isset($aArgs['migrate'])) {
    try {
        $aDataDir = $aApp->config('data_dir');

        echo "Atempting to migrate data from an old version of Besearcher.\n";
        echo "  Data directory: ".$aApp->config('data_dir')."\n";
        echo "  Detected Besearcher version: 1.0.0"."\n";

        echo "Updating context info... ";
        $aExperimentHash = $aApp->config('experiment_hash');

        if(empty($aExperimentHash)) {
            throw new \Exception("entry 'experiment_hash' in provided INI file '".$aIniPath."' is empty.");
        }

        $aContext->set('experiment_hash', $aExperimentHash);
        $aContext->set('status', BESEARCHER_STATUS_STOPED);
        echo "[OK]" . "\n";

        echo "Creating setup_task files... ";
        $aBasePath = $aDataDir . DIRECTORY_SEPARATOR;
        $aTaskCmdLogFile = $aBasePath . 'task_prepare_cmd.log';
        $aTaskCmdResultFile = $aBasePath . 'besearcher.task_prepare_cmd-result';
        $aNewTaskCmdLogFile = $aBasePath . BESEARCHER_SETUP_LOG_FILE;
        $aNewTaskCmdResultFile = $aBasePath . BESEARCHER_SETEUP_FILE;

        system("cp " . $aTaskCmdLogFile . " " . $aNewTaskCmdLogFile);
        system("cp " . $aTaskCmdResultFile . " " . $aNewTaskCmdResultFile);

        echo "[OK]" . "\n";

        echo "Setting up experiment... ";
        // We need to setup the experiment to perform a migration, otherwise there is
        // no way to tasks that will be performed.
        $aApp->setupExperiment();
        echo "[OK]" . "\n";

        echo "Adding results to database:" . "\n";
        $aFilesMigrated = migrateTaskResults($aDataDir, $aData);

        echo "\n";
        echo "Migration finished successfully! Entries processed: " . $aFilesMigrated . ".\n";

    } catch (\Exception $e) {
        echo "[ERROR]\n\n";
        echo "Migration was interrupted: " . $e->getMessage() . "\n";
        exit(3);
    }
}

if(isset($aArgs['test-email'])) {
    $aINI = $aApp->getINIValues();

    echo "E-mail sending options:" . "\n";
    echo ' - REST API: ' . ($aINI['email']['use_email_api'] ? 'in use' : 'disabled') . ' (endpoint=' . $aINI['email']['email_api_endpoint'] . ")\n";
    echo ' - SMTP: ' . ($aINI['email']['use_smtp'] ? 'in use' : 'disabled') . ' (host=' . $aINI['email']['smtp_host'] . ', user='.$aINI['email']['smtp_user'] . ")\n";

    echo "Please input the test e-mail details:" . "\n";

    echo "To (e.g. test@domain.com): "; fscanf(STDIN, "%s", $aTo);
    echo "Subject: "; fscanf(STDIN, "%s", $aSubject);
    echo "Message: "; fscanf(STDIN, "%s", $aMessage);

    echo "\n";
    echo "Trying to send a test e-mail... ";

    $aApp->sendEmail($aTo, $aSubject, $aMessage);

    echo 'Ok, sent!' . "\n";
    echo "You should receive the e-mail in a few minutes. If you don't, something is not working." . "\n";
}

if(isset($aArgs['pause']) || isset($aArgs['resume']) || isset($aArgs['stop'])) {
    $aStatus = '';
    $aText = '';
    $aRunningTasks = count($aData->findRunningTasks());

    if(isset($aArgs['pause'])) { $aStatus = BESEARCHER_STATUS_PAUSED; $aText = 'pause'; }
    if(isset($aArgs['resume'])) { $aStatus = BESEARCHER_STATUS_RUNNING; $aText = 'resume'; }

    if(isset($aArgs['stop'])) {
        if(!$aIsForce && $aRunningTasks > 0) {
            Besearcher\CmdUtils::confirmOperation('Stop and cancel '.$aRunningTasks.' unfinished tasks');
        }
        // TODO: cancel running tasks
        $aStatus = BESEARCHER_STATUS_STOPPING;
        $aText = 'stop';
    }

    $aApp->setStatus($aStatus);
    echo "Ok, besearcher will ".$aText.".\n";
}

if(isset($aArgs['reload'])) {
    if(!$aIsForce) {
        Besearcher\CmdUtils::confirmOperation();
    }

    $aContext->set('experiment_hash', '');
    $aContext->set('experiment_ready', 0);

    echo "Ok, besearcher was reloaded successfully!\n";
}

if(isset($aArgs['reset'])) {
    if(!$aIsForce) {
        Besearcher\CmdUtils::confirmOperation();
    }

    // TODO: re-work this. Should destroy all but the "results" table.
    $aApp->getDb()->destroy();
    echo "Ok, besearcher was reset successfully!\n";
}

exit(0);

?>
