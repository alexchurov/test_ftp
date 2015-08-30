<?php

define('MSG_ERR_EXCEPTION_UNDEF', 1024);
define('MSG_ERR_NOCONN', 10);
define('MSG_ERR_FILE_MAKE', 20);
define('MSG_ERR_FILE_OPEN', 22);
define('MSG_ERR_FILE_RENAME', 24);
define('MSG_ERR_INTERFACE', 30);
define('MSG_ERR_FILE_PATH', 40);
define('MSG_ERR_FILE_REM', 50);
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
define('MSG_OK_FILE_REPLACE', 600);

define('ARR_INDEX_FILE_ORIGINAL', 0);
define('ARR_INDEX_FILE_NEW', 1);
define('ARR_INDEX_FILE_BACK', 2);

define('FTP_INTERFACE', 'ftp');
define('FTP_PORT', 21);
define('SSH_INTERFACE', 'ssh');
define('SSH_PORT', 22);

class System {

    public static function checkPath($path) {
        $path = str_replace(["\\", "//"], "/", $path);
        if (substr($path, - 1) != "/") {
            $p = strrpos($path, "/");
            $path = substr($path, 0, $p);
        }

        $path = rtrim($path, "/");

        if (!file_exists($path))
            return mkdir($path, 0755, true);
        else
            return is_dir($path);
    }

    public static function getMessage($message = false, $arFields = false) {
        $arMessages = [
            MSG_ERR_EXCEPTION_UNDEF => " - Have any uncertain exception, please contact the developer: alexchurov@gmail.com",
            MSG_ERR_NOCONN => " - ERROR: remote connection not established",
            MSG_ERR_FILE_MAKE => " - ERROR: failed to create local file \"#FILE#\"",
            MSG_ERR_FILE_OPEN => " - ERROR: unable to open local file \"#FILE#\"",
            MSG_ERR_FILE_RENAME => " - ERROR: failed to rename local file \"#FILE#\"",
            MSG_ERR_CONN_UNDEF => " - ERROR: unable to connect - an unknown protocol \"#PROTOCOL#\"",
            MSG_ERR_FILE_PATH => " - ERROR: cannot create the file - path does not exist \"#FILE#\"",
            MSG_ERR_FILE_REM => " - ERROR: failed to remove the file \"#FILE#\"",
            MSG_OK_CONN => " + Connection to the remote server successfully",
            MSG_ERR_CONN_DENIED => " - ERROR: connection denied - invalid username or password",
            MSG_ERR_CONN_NOHOST => " - ERROR: connection failed - server not found",
            MSG_OK_CONN_EXIST => " + Connection to the remote server already exists",
            MSG_EVENT_CONN => " Connection to remote server: protocol \"#PROTOCOL#\", host \"#HOST#\", port \"#PORT#\", login \"#LOGIN#\", password \"#PASSWORD#\"",
            MSG_OK_DISCONNECT => "Closed connection with the remote server is successfully",
            MSG_ERR_DISCONN => "ERROR: failed to close the connection with the remote server or the connection does not exist",
            MSG_OK_CHDIR => "Change current directory: \"#PATH#\"",
            MSG_ERR_CHDIR => "ERROR: failed to change directory \"#PATH#\"",
            MSG_OK_FILE_WRITE => " - write to \"#LOCAL_FILE#\"",
            MSG_EVENT_DOWNLOAD => " ∨ Download file \"#REMOTE_FILE#\" (local path \"#LOCAL_DIR#\")",
            MSG_ERR_DOWNLOAD => " - ERROR: failed to download the file",
            MSG_OK_DOWNLOAD => " - file is successfully downloaded",
            MSG_EVENT_UPLOAD => " ∧ File upload \"#LOCAL_FILE#\" on server (remote path \"#REMOTE_FILE#\")",
            MSG_ERR_UPLOAD => " - error when uploading file",
            MSG_OK_FILE_REPLACE => "The backup is successfully created and the file is processed successfully #FILE#",
        ];

        $message = (array_key_exists($message, $arMessages) ? $arMessages[$message] : $message);

        if (is_array($arFields))
            foreach ($arFields as $k => $v)
                $message = str_replace("#$k#", $v, $message);

        return $message;
    }

    public static function showMessage($message = false, $arFields = false) {
        if (!$message)
            return;
        echo "[" . date("Y-m-d H:i:s") . "] " . System::getMessage($message, $arFields) . "\n";
    }
}

/**
 *
 * Class Task
 * 		This class represents a set of methods and properties to perform
 * 		actions on a list of files located on a remote computer.
 * 		Connection to the host is performed via FTP or SSH interface (in development).
 *  
 * 
 * Public methods:
 * 
 * setLocalDir ($path)
 * 		This method sets the home directory for temporary files
 * 		Downloaded files are stored under LocalDir/current-date-and-time/*
 *      @param string path - path to local directory, sample: "temp"
 *
 * setRemoteDir($path)
 * 		This method sets the home directory on the remote server
 *      @param string path - path at remote host, sample: "www/sample-site-dir.ru/"
 *
 * connection($host, $login, $password, $port, $protocol)
 * 		This method connects to a remote server via FTP or SSH
 *      @param string host - host of remote server, sample: "www.sample-site-dir.ru"
 *      @param string login - user name for authorization
 *      @param string password - user's password
 *      @param string port - remote port, default: 21
 *      @param string protocol - connection protocol, default: ftp
 *      @return handle of connection or false
 *
 * add($filePath)
 * 		This method adds a file to process
 * 		@param string filePath - path to the file on the remote machine, sample: "bitrix/php_inteface/init.php"
 * 		@return string local path to the downloaded files or false
 *
 * replacement()
 * 		This method performs processing of files that have been added
 * 		@return
 *
 * disconnect()
 * 		This method performs a disconnection from the remote server
 * 		@return true or false
 *
 * checkConnection()
 * 		This method checks status of connection to remote server
 * 		@return true or false
 *
 */
class Task {

    public function setLocalDir($path) {
        $this->localHomeDir = $path;
        $this->__construct();
    }

    public function setRemoteDir($path) {
        $this->remoteDir = $path;
        if ($this->checkConnection)
            $this->changeDir($path);
    }

    public function setRegexp($pattern, $replacement) {
        $this->regexpPattern = $pattern;
        $this->regexpReplacement = $replacement;
    }

    public function add($filePath) {
        if ($localFile = $this->getRemoteFile($filePath)) {
            $this->arItems[] = [
                ARR_INDEX_FILE_ORIGINAL => $filePath,
                ARR_INDEX_FILE_NEW => $localFile,
                ARR_INDEX_FILE_BACK => "$localFile.bak"
            ];
        } else
            return false;
    }

    public function replacement() {
        $iterItems = new ArrayIterator($this->arItems);
        foreach ($iterItems as $arItem) {
            $arFields['FILE'] = $arItem[ARR_INDEX_FILE_NEW];
            if (copy($arItem[ARR_INDEX_FILE_NEW], $arItem[ARR_INDEX_FILE_BACK])) {
                if ($fp = @fopen($arItem[ARR_INDEX_FILE_NEW], "r")) {
                    $tempFile = $arItem[ARR_INDEX_FILE_NEW] . ".tmp";
                    $fpw = @fopen($tempFile, "w");
                    while (($buffer = fgets($fp, 4096)) !== false) {
                        $buffer = preg_replace_callback(
                                $this->regexpPattern, function ($matches) {
                            return $this->regexpReplacement;
                        }, $buffer
                        );
                        fputs($fpw, $buffer);
                    }
                    fclose($fp);
                    fclose($fpw);

                    if (unlink($arItem[ARR_INDEX_FILE_NEW])) {
                        if (rename($tempFile, $arItem[ARR_INDEX_FILE_NEW]))
                            $result = MSG_OK_FILE_REPLACE;
                        else
                            $result = MSG_ERR_FILE_RENAME;
                    }
                    else {
                        $arFields['FILE'] = $tempFile;
                        $result = MSG_ERR_FILE_REM;
                    }
                } else {
                    $arFields['FILE'] = $arItem[ARR_INDEX_FILE_BACK];
                    $result = MSG_ERR_FILE_OPEN;
                }
            } else
                $result = MSG_ERR_FILE_MAKE;

            $this->checkResult($result);
            System::showMessage($result, $arFields);

            // Upload file
            if ($result == MSG_OK_FILE_REPLACE)
                $this->putRemoteFile($arItem[ARR_INDEX_FILE_NEW], $arItem[ARR_INDEX_FILE_ORIGINAL]);
        }
    }

    public function connect($host, $login, $password, $port = FTP_PORT, $protocol = FTP_INTERFACE) {
        if (!$this->connection) {
            $arFields = ["PROTOCOL" => $protocol, "HOST" => $host, "PORT" => $port, "LOGIN" => $login, "PASSWORD" => $password];
            System::showMessage(MSG_EVENT_CONN, $arFields);
            // FTP
            if (strcasecmp($protocol, FTP_INTERFACE) == 0) {
                if ($this->connection = ftp_connect($host, $port, $this->connectionTimeout)) {
                    if (@ftp_login($this->connection, $login, $password)) {
                        $this->connectionType = $protocol;
                        $this->connectionPort = $port;
                        $result = MSG_OK_CONN;
                    } else
                        $result = MSG_ERR_CONN_DENIED;
                } else
                    $result = MSG_ERR_CONN_NOHOST;
            }
            // SSH
            elseif (strcasecmp($protocol, SSH_INTERFACE) == 0)
                $result = MSG_ERR_INTERFACE;
            else
                $result = MSG_ERR_INTERFACE;
        } else
            $result = MSG_OK_CONN_EXIST;

        $this->checkResult($result);
        System::showMessage($result, $arFields);
        $this->changeDir($this->remoteDir); // Change the starting directory

        return ($result == MSG_OK_CONN ? $this->connection : false);
    }

    public function disconnect() {
        if ($this->connection) {
            // FTP
            if (strcasecmp($this->connectionType, FTP_INTERFACE) == 0) {
                if (ftp_close($this->connection))
                    $result = MSG_OK_DISCONNECT;
                else
                    $result = MSG_ERR_DISCONN;
            } else
                $result = MSG_ERR_INTERFACE;
        } else
            $result = MSG_ERR_NOCONN;

        $this->checkResult($result);
        System::showMessage($result);

        return ($result == MSG_OK_DISCONNECT ? true : false);
    }

    public function checkConnection() {
        return ($this->connection ? true : false);
    }

    private function changeDir($path) {
        if ($this->connection) {
            // FTP
            if (strcasecmp($this->connectionType, FTP_INTERFACE) == 0) {
                if (ftp_chdir($this->connection, $path)) {
                    $pwd = ftp_pwd($this->connection);
                    $result = MSG_OK_CHDIR;
                } else
                    $result = MSG_ERR_CHDIR;
            } else
                $result = MSG_ERR_INTERFACE;
        } else
            $result = MSG_ERR_NOCONN;

        $this->checkResult($result);
        System::showMessage($result, ["PATH" => $path]);

        return ($result == MSG_OK_CHDIR ? $pwd : false);
    }

    private function getRemoteFile($remoteFile, $localFile = false) {
        if (!$localFile)
            $localFile = $this->localFilesDir;

        $arFields = ["REMOTE_FILE" => $remoteFile, "LOCAL_DIR" => $localFile];
        $message = System::getMessage(MSG_EVENT_DOWNLOAD, $arFields);
        if ($this->connection) {
            if (is_string($localFile)) { // Got a local file path
                $localFile = str_replace(["\\", "//"], "/", $localFile . "/" . $remoteFile);
                if (System::checkPath($localFile)) {
                    if (!$handle = fopen($localFile, 'w'))
                        $result = MSG_ERR_FILE_MAKE;
                }
                else {
                    $arFields['FILE'] = $localFile;
                    $result = MSG_ERR_FILE_PATH;
                }
            } elseif (is_resource($localFile)) // Got a pointer to the opened file
                $handle = & $localFile;

            if (is_resource($handle) && !$result) {
                if (strcasecmp($this->connectionType, FTP_INTERFACE) == 0) { // FTP
                    if (ftp_fget($this->connection, $handle, $remoteFile, FTP_BINARY, 0)) {
                        fclose($handle);
                        $arFields['LOCAL_FILE'] = $localFile;
                        $result = MSG_OK_FILE_WRITE;
                    } else {
                        fclose($handle);
                        unlink($localFile);
                        $result = MSG_ERR_DOWNLOAD;
                    }
                } elseif (strcasecmp($this->connectionType, SSH_INTERFACE) == 0) // SSH
                    $result = MSG_ERR_INTERFACE;
                else
                    $result = MSG_ERR_INTERFACE;
            } else
                $result = MSG_ERR_DOWNLOAD;
        } else
            $result = MSG_ERR_NOCONN;


        $this->checkResult($result);
        System::showMessage($message . System::getMessage($result, $arFields));

        return ($result == MSG_OK_FILE_WRITE ? $localFile : false);
    }

    private function putRemoteFile($localFile, $remoteFile) {
        $arFields = ['LOCAL_FILE' => $localFile, 'REMOTE_FILE' => $remoteFile];
        $message = System::getMessage(MSG_EVENT_UPLOAD, $arFields);
        if ($this->connection) {
            if (file_exists($localFile) && is_readable($localFile)) { // Got a local file path
                if (!$handle = fopen($localFile, 'r'))
                    $result = MSG_ERR_FILE_MAKE;
            }
            elseif (is_resource($localFile))
                $handle = & $localFile;

            if (is_resource($handle) && !$result) { // Got a pointer to the opened file
                if (strcasecmp($this->connectionType, FTP_INTERFACE) == 0) { // FTP
                    if (ftp_fput($this->connection, $remoteFile, $handle, FTP_BINARY))
                        $result = MSG_OK_DOWNLOAD;
                    else
                        $result = MSG_ERR_UPLOAD;
                }
                // SSH
                elseif (strcasecmp($this->connectionType, SSH_INTERFACE) == 0)
                    $result = MSG_ERR_INTERFACE;
                else
                    $result = MSG_ERR_INTERFACE;
            }
        } else
            $result = MSG_ERR_NOCONN;

        $this->checkResult($result);
        System::showMessage($message . System::getMessage($result, $arFields));
        return ($result == MSG_OK_DOWNLOAD ? true : false);
    }

    protected function isDir() {
        
    }

    protected function checkResult(&$result) {
        $result = ($result ? $result : MSG_ERR_EXCEPTION_UNDEF);
    }

    function __construct() {
        $this->localFilesDir = $this->localHomeDir . "/" . date("Y-m-d_His");
    }

    private $localHomeDir = false;
    private $localFilesDir = false;
    private $remoteDir = false;
    private $regexpPattern = false;
    private $regexpReplacement = false;
    private $connection = false;
    private $connectionType = false;
    private $connectionPort = false;
    private $connectionTimeout = 10;
    private $arItems = array();

}

/* * ***************************************************************************** */

date_default_timezone_set('Europe/Moscow');
error_reporting(E_ERROR);

$config = [
  'indexFile' => 'files10.csv',
  'typeconn' => FTP_INTERFACE,
  'host' => 'demo.your-host.ru',
  'user' => 'demo',
  'password' => 'your-password',
  'start_dir' => "www/demo.your-host.ru"];

$task = new Task();
$task->setLocalDir('temp');
$task->setRemoteDir($config['start_dir']);
$task->connect($config['host'], $config['user'], $config['password'], FTP_PORT, FTP_INTERFACE);
$task->setRegexp('|[\s\n\r]+|', null);

if ($task->checkConnection()) {
    System::showMessage("\n\n * * *  Read the index file ($config[indexFile]) and downloadable files");
    $index = new LimitIterator(
            new SplFileObject($config["indexFile"]), 0, // Читаем с нулевой строки
            1  // Итерация 1 строка
    );
    while (!$index->eof()) {
        if ($remoteFile = $index->fgetcsv()[0])
            $localFile = $task->add($remoteFile);
    }

    System::showMessage("\n\n * * *  Create backups and perform replacing in files");
    $task->replacement();
}

$task->disconnect();

/* * ***************************************************************************** */

?>
