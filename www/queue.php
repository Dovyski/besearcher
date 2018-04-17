<?php
    require_once(dirname(__FILE__) . '/inc/globals.php');

    Besearcher\Auth::allowAuthenticated();
    $aApp = Besearcher\WebApp::instance();

    $aMessage = array('title' => 'Oops!', 'body' => '', 'type' => 'danger');

    try {
        $aAction = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

        if($aAction != '') {
            $aSelected = array();

            foreach($_REQUEST as $aKey => $aValue) {
                if(stripos($aKey, 'task_') !== false) {
                    $aValue = $aValue + 0; // cast id to int
                    $aSelected[] = $aValue;
                }
            }

            if(count($aSelected) == 0) {
                throw new Exception('Nothing was selected.');
            }

            if($aAction == 'prioritize' || $aAction == 'deprioritize') {
                $aPriority = $aAction == 'prioritize' ? 5 : 10;
                $aApp->getData()->updateTasksPriority($aSelected, $aPriority);

            } else if ($aAction == 'delete') {
                $aApp->getData()->removeTasksFromQueue($aSelected);
            }

            $aMessage = array('title' => 'Success!', 'body' => 'The queue has been updated.', 'type' => 'success');
        }
    } catch(Exception $e) {
        $aMessage['body'] = $e->getMessage();
    }

    // Get pagination info
    $aTasksCount = $aApp->getData()->queueSize();
    $aPage = isset($_REQUEST['page']) ? $_REQUEST['page'] + 0 : 1;
    $aSize = isset($_REQUEST['size']) ? $_REQUEST['size'] + 0 : 100;
    $aPagination = Besearcher\Utils::paginate($aPage, $aSize, $aTasksCount);

    $aTasksQueue = $aApp->getData()->findEnquedTasks($aPagination['start'], $aPagination['size']);
    $aExpectedTimeComplete = Besearcher\WebApp::calculateEstimatedTimeFinishExperiment(false);

    Besearcher\View::render('queue', array(
        'message'         => $aMessage,
        'tasks_queue'     => $aTasksQueue,
        'completion_time' => $aExpectedTimeComplete,
        'pagination'      => $aPagination
    ));
?>
