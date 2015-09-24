<?php
date_default_timezone_set('Europe/Moscow');

define('MSG_ERR_EXCEPTION_UNDEF', 1024);
define('MSG_ERR_NOCONN', 10);
define('MSG_ERR_FILE_MAKE', 20);
define('MSG_ERR_FILE_OPEN', 22);
define('MSG_ERR_FILE_RENAME', 24);
define('MSG_ERR_INTERFACE', 30);
define('MSG_ERR_FILE_PATH', 40);
define('MSG_ERR_FILE_REM', 50);
define('MSG_ERR_OPT_REQ', 60);
define('MSG_OK_CONN', 100);
define('MSG_ERR_CONN_DENIED', 110);
define('MSG_ERR_CONN_NOHOST', 120);
define('MSG_OK_CONN_EXIST', 130);
define('MSG_EVENT_CONN', 140);
define('MSG_OK_DISCONNECT', 200);
define('MSG_ERR_DISCONN', 210);
define('MSG_OK_CHDIR', 300);
define('MSG_ERR_CHDIR', 310);
define('MSG_OK_FILE_WRITE', 400);
define('MSG_EVENT_DOWNLOAD', 410);
define('MSG_ERR_DOWNLOAD', 420);
define('MSG_OK_DOWNLOAD', 500);
define('MSG_EVENT_UPLOAD', 510);
define('MSG_ERR_UPLOAD', 520);
define('MSG_OK_GETTREE', 600);
define('MSG_ERR_GETTREE', 610);

define('FILENAME_INDEX', 'index.csv');

define('ARR_RESULT', 0);
define('ARR_FIELDS', 1);
define('ARR_COUNT_TOTAL', 2);
define('ARR_COUNT_COMPLETE', 3);

define('ARR_INDEX_FILE_ORIGINAL', 4);
define('ARR_INDEX_FILE_NEW', 5);
define('ARR_INDEX_FILE_BACK', 6);

define('FTP_INTERFACE', 'ftp');
define('FTP_PORT', 21);
define('FTP_LISTING_ARG', "-A");
define('SSH_INTERFACE', 'ssh');
define('SSH_PORT', 22);

require "system/classes/system.class.php";
require "system/classes/procureur.class.php";
require "system/classes/transport.class.php";

$date = date("Ymd_His");
$user = System::getUsername();


/* *********************************************************************************** */


// Connect to remote host
$con = new Transport();
$con->setRemoteHomeDir("demo.dev-hosting.ru/public_html/about/");
$con->connect("demo.dev-hosting.ru", "***************", "***************");

// Download files and create an index file
$localDir = "user_$user/downloads/run".$date."/";
$remoteDir = "";
$indexFile = "user_$user/tmp/run".$date."/index.csv";
$arResult = $con->getTree($localDir, $remoteDir, $indexFile);
System::showMessage(
	$arResult[ARR_RESULT],
	array_merge(
		['COUNT_TOTAL'		=> $arResult[ARR_COUNT_TOTAL],
		'COUNT_COMPLETE'	=> $arResult[ARR_COUNT_COMPLETE]],
		$arResult[ARR_FIELDS]));
		



// Close the connection to remote host
$con->disconnect();


?>
