<?php

/**
 * This script send e-mails using some provided SMTP infrastructure.
 *
 * Author: Fernando Bevilacqua <fernando.bevilacqua@his.se>
 */

require_once(dirname(__FILE__) . '/../inc/constants.php');
require_once(dirname(__FILE__) . '/../inc/Db.class.php');
require_once(dirname(__FILE__) . '/../inc/Context.class.php');
require_once(dirname(__FILE__) . '/../inc/AppControl.class.php');
require_once(dirname(__FILE__) . '/../inc/Tasks.class.php');
require_once(dirname(__FILE__) . '/../inc/Log.class.php');
require_once(dirname(__FILE__) . '/../inc/App.class.php');
require_once(dirname(__FILE__) . '/../inc/ResultOutputParser.class.php');

@include_once(dirname(__FILE__) . '/../inc/vendor/autoload.php');

$aOptions = array(
    'ini:',
    'to:',
    'subject:',
    'text:',
    'file:',
    'verbose',
    'help'
);

$aArgs = getopt("vh", $aOptions);

if($argc <= 1 || isset($aArgs['h']) || isset($aArgs['help'])) {
     echo "Usage: \n";
     echo " php ".basename($_SERVER['PHP_SELF']) . " [options]\n\n";
     echo "Options:\n";
     echo " --ini=<path>         Path to the INI file being used by Besearcher.\n";
     echo " --to=<string>        Who will receive this e-mail.\n";
     echo " --subject=<string>   The message's subjectmail.\n";
     echo " --text=<string>      The e-mail's message.\n";
     echo " --file=<path>        Path to a file whose content will be used as text.\n";
     echo " --verbose, -v        Show verbose output.\n";
     echo " --help, -h           Show this help message.\n";
     echo "\n";
     exit(1);
}

$aIniPath = isset($aArgs['ini']) ? $aArgs['ini'] : '';

$aApp = new Besearcher\App();
$aApp->init($aIniPath, '', true);

$aTo = isset($aArgs['to']) ? $aArgs['to'] : '';
$aSubject = isset($aArgs['subject']) ? $aArgs['subject'] : '';
$aText = isset($aArgs['text']) ? $aArgs['text'] : '';
$aTextFile = isset($aArgs['file']) ? $aArgs['file'] : '';
$aVerbose = isset($aArgs['v']) || isset($aArgs['verbose']);

if(!empty($aTextFile)) {
    if(!file_exists($aTextFile)) {
        echo "Unable to load content of file: " . $aTextFile . "\n";
        exit(3);
    }
    $aText = file_get_contents($aTextFile);
    // TODO: delete the e-mail file?
}

if(empty($aTo) || empty($aText)) {
    echo "Neither e-mail destination nor text can be empty!" . "\n";
    exit(4);
}

$aINI = $aApp->getINIValues();
$aConfig = $aINI['email'];

if($aConfig['use_smtp']) {
    if($aVerbose) {
        echo 'Sending e-mail using SMTP (host=' . $aConfig['smtp_host'] . ', user='.$aConfig['smtp_user'] . ")\n";
    }

    if(!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo 'PHPMailer has not been installed. Run "composer install" in the folder "inc" of the project.';
        exit(2);
    }

    $aMailer = new PHPMailer\PHPMailer\PHPMailer();
    $aMailer->isSMTP();

    //Enable SMTP debugging
    // 0 = off (for production use)
    // 1 = client messages
    // 2 = client and server messages
    $aMailer->SMTPDebug = $aVerbose ? 2 : 0;

    $aMailer->Host = gethostbyname($aConfig['smtp_host']);
    $aMailer->Port = 587;
    $aMailer->SMTPSecure = 'tls';
    $aMailer->SMTPAuth = true;

    $aMailer->Username = $aConfig['smtp_user'];
    $aMailer->Password = $aConfig['smtp_password'];

    $aMailer->setFrom($aConfig['sender_email'], $aConfig['sender_name']);
    $aMailer->addAddress($aTo);

    $aSubjectTag = isset($aConfig['subject_tag']) ? $aConfig['subject_tag'] . ' ' : '';
    $aMailer->Subject = $aSubjectTag . $aSubject;

    $aFooter = isset($aConfig['text_footer']) ? $aConfig['text_footer'] : '';

    if($aFooter != '') {
        $aFooter = "\n\n" . str_replace('\\n', "\n", $aFooter);
    }

    $aMailer->Body = $aText . $aFooter;

    if (!$aMailer->send()) {
        echo "Something went wrong: " . $aMailer->ErrorInfo . "\n";
    } else {
        echo "Success, message sent!" . "\n";
    }
}

if($aConfig['use_email_api']) {
    if($aVerbose) {
        echo 'Sending e-mail using REST API (endpoint=' . $aConfig['email_api_endpoint'] . ")\n";
    }

    $aCh = curl_init();

    curl_setopt($aCh, CURLOPT_URL, $aConfig['email_api_endpoint']);
    curl_setopt($aCh, CURLOPT_POST, 1);
    curl_setopt($aCh, CURLOPT_POSTFIELDS, array('to' => $aTo, 'subject' => $aSubject, 'text' => $aText));
    curl_setopt($aCh, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($aCh, CURLOPT_SSL_VERIFYPEER, false); // TODO: fix this with https://github.com/paragonie/certainty

    $aJSONResponse = curl_exec($aCh);
    curl_close($aCh);

    $aResponse = json_decode($aJSONResponse);

    if($aResponse !== false && !empty($aResponse)) {
        if($aResponse->success) {
            echo "Success, message sent!" . "\n";
        } else {
            echo "Something went wrong: " . $aResponse->error . "\n";
        }
    } else {
        echo 'Unable to parse response' . "\n";
    }
}

exit(0);

?>
