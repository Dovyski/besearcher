<?php
    require_once(dirname(__FILE__) . '/inc/globals.php');

    Besearcher\Auth::allowAuthenticated();

    $aError = Besearcher\Data::init();
    $aINI = Besearcher\Data::ini();

    $aMessage = array('title' => 'Oops!', 'body' => '', 'type' => 'danger');
    $aTasksQueue = array();
    $aContext = array();

    try {
        $aDbPath = $aINI['data_dir'] . DIRECTORY_SEPARATOR . BESEARCHER_DB_FILE;
        $aDb = new Besearcher\Db($aDbPath);

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
                prioritizeTasksInQueue($aSelected, $aDb);

            } else if ($_REQUEST['action'] == 'delete') {
                removeTasksFromQueue($aSelected, $aDb);
            }

            $aMessage = array('title' => 'Success!', 'body' => 'The tasks queue has been updated.', 'type' => 'success');
        }

        // Get pagination info
        $aTasksCount = $aDb->tasksCount();
        $aPage = isset($_REQUEST['page']) ? $_REQUEST['page'] + 0 : 1;
        $aSize = isset($_REQUEST['size']) ? $_REQUEST['size'] + 0 : 100;
        $aPagination = Besearcher\Utils::paginate($aPage, $aSize, $aTasksCount);

        // Fetch the tasks in the database
        $aStmt = $aDb->getPDO()->prepare('SELECT * FROM tasks WHERE 1 ORDER BY creation_time ASC LIMIT ' . $aPagination['start'] . ',' . $aPagination['size']);
        $aStmt->execute();

        while($aRow = $aStmt->fetch(\PDO::FETCH_ASSOC)) {
            $aRow['data'] = unserialize($aRow['data']);
            $aTasksQueue[] = $aRow;
        }

        $aStmt = $aDb->getPDO()->prepare('SELECT * FROM context WHERE 1');
        $aStmt->execute();
        $aContext = $aStmt->fetch(\PDO::FETCH_ASSOC);

        if($aContext === false) {
            throw new Exception('Unable to load Besearcher context.');
        }
    } catch(Exception $e) {
        $aMessage['body'] = $e->getMessage();
    }

    Besearcher\View::render('control', array(
        'message'       => $aMessage,
        'tasks_queue'   => $aTasksQueue,
        'pages'         => $aPagination['pages'],
        'page'          => $aPagination['page'],
        'size'          => $aPagination['size'],
        'start'         => $aPagination['start'],
        'context'       => $aContext
    ));
?>
