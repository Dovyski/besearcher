<?php
    require_once(dirname(__FILE__) . '/inc/globals.php');

    Besearcher\Auth::allowAuthenticated();
    $aApp = Besearcher\WebApp::instance();

    $aMessage = array('title' => 'Oops!', 'body' => '', 'type' => 'danger');
    $aTasksQueue = array();
    $aContext = $aApp->getContext()->values();

    try {
        $aAction = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

        if($aAction == 'move' || $aAction == 'delete') {
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

            if($_REQUEST['action'] == 'move') {
                $aApp->getData()->prioritizeTasksInQueue($aSelected);

            } else if ($_REQUEST['action'] == 'delete') {
                $aApp->getData()->removeTasksFromQueue($aSelected);
            }

            $aMessage = array('title' => 'Success!', 'body' => 'The tasks queue has been updated.', 'type' => 'success');
        }

        // Get pagination info
        $aTasksCount = $aApp->getData()->queueSize();
        $aPage = isset($_REQUEST['page']) ? $_REQUEST['page'] + 0 : 1;
        $aSize = isset($_REQUEST['size']) ? $_REQUEST['size'] + 0 : 20;
        $aPagination = Besearcher\Utils::paginate($aPage, $aSize, $aTasksCount);

        $aTasksQueue = $aApp->getData()->findEnquedTasks($aPagination['start'], $aPagination['size']);

    } catch(Exception $e) {
        $aMessage['body'] = $e->getMessage();
    }

    Besearcher\View::render('queue', array(
        'message'       => $aMessage,
        'tasks_queue'   => $aTasksQueue,
        'pagination'    => $aPagination,
        'context'       => $aContext
    ));
?>
