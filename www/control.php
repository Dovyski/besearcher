<?php
    require_once(dirname(__FILE__) . '/inc/globals.php');

    Besearcher\Auth::allowAuthenticated();

    $aError = Besearcher\Data::init();
    $aINI = Besearcher\Data::ini();

    $aMessage = array('title' => 'Oops!', 'body' => '', 'type' => 'warning');
    $aTasksQueue = array();
    $aSettings = array();

    try {
        $aContext = loadContextFromDisk($aINI['data_dir'], false);

        if($aContext === false) {
            throw new Exception('Unable to load Besearcher context.');
        }

        $aTasksQueue = isset($aContext['tasks_queue']) ? $aContext['tasks_queue'] : array();
        $aSettings = $aContext;

        if(isset($_REQUEST['action'])) {
            $aSelected = array();

            foreach($_REQUEST as $aKey => $aValue) {
                if(stripos($aKey, 'task_') !== false) {
                    $aParts = explode('-', $aValue);
                    $aSelected[] = array('hash' => $aParts[0], 'permutation' => $aParts[1]);
                }
            }

            if(count($aSelected) == 0) {
                throw new Exception('Nothing was selected.');
            }

            if($_REQUEST['action'] == 'move') {
                $aNewQueue = prioritizeTasks($aSelected, $aContext['tasks_queue']);
                $aOverride = array('tasks_queue' => $aNewQueue);

            } else if ($_REQUEST['action'] == 'delete') {

            }

            $aOk = writeContextOverrideToDisk($aINI['data_dir'], $aOverride);

            if($aOk) {
                $aMessage = array('title' => 'Success!', 'body' => 'The changes were sent to Besearcher. It will load them in a few seconds.', 'type' => 'success');
            }
        }
    } catch(Exception $e) {
        $aMessage['body'] = $e->getMessage();
    }

    Besearcher\View::render('control', array(
        'message' => $aMessage,
        'tasks_queue' => $aTasksQueue,
        'has_override' => hasOverrideContextInDisk($aINI['data_dir']),
        'settings' => $aSettings
    ));
?>
