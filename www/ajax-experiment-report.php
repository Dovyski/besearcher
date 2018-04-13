
<?php
    require_once(dirname(__FILE__) . '/inc/globals.php');

    Besearcher\Auth::allowAuthenticated();

    $aApp = Besearcher\WebApp::instance();
    $aTaskCmdParams = $aApp->config('task_cmd_params');
    $aFilter = array();

    foreach($aTaskCmdParams as $aParam => $aValues) {
        if(isset($_REQUEST[$aParam]) && !empty($_REQUEST[$aParam])) {
            $aFilter[$aParam] = $_REQUEST[$aParam];
        }
    }

    $aResults = $aApp->getData()->findResults();
    $aEntries = array();

    foreach($aResults as $aResult) {
        $aResultParams = array();

        if(!empty($aResult['params'])) {
            $aResultParams = Besearcher\Utils::splitParamsString($aResult['params']);
        }

        if(count($aFilter) > 0) {
            foreach($aFilter as $aName => $aValue) {
                $aResultMatchesFilter = $aResultParams[$aName] == $aValue;
                if(!$aResultMatchesFilter) {
                    continue;
                }
            }
        }

        $aMeta = array();
        $aTags = @unserialize($aResult['log_file_tags']);

        if($aTags !== false) {
            foreach($aTags as $aItem) {
                if($aItem['type'] != BESEARCHER_TAG_TYPE_PROGRESS) {
                    $aMeta[] = $aItem;
                }
            }
        }

        $aEntry['result'] = $aResult;
        $aEntry['meta'] = $aMeta;
        $aEntry['params'] = $aResultParams;

        $aEntries[] = $aEntry;
    }

    Besearcher\View::render('ajax-experiment-report', array(
        'filter' => $aFilter,
        'entries' => $aEntries
    ));
?>
