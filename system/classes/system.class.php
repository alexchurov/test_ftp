<?php

class System {

    public static function checkPath($path, $mkdir = true) {
    
        $path = str_replace(["\\", "//"], "/", $path);
        if (!$mkdir) return $path;
        
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
            MSG_ERR_OPT_REQ => " - ERROR: Please specify required options. For more detailed information please specify: \"php procureor.php --help\"",
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
            MSG_EVENT_DOWNLOAD => "  Download file \"#REMOTE_FILE#\" (local path \"#LOCAL_DIR#\")",
            MSG_ERR_DOWNLOAD => " - ERROR: failed to download the file",
            MSG_OK_DOWNLOAD => " - file is successfully downloaded",
            MSG_EVENT_UPLOAD => "  Upload file \"#LOCAL_FILE#\" on server (remote path \"#REMOTE_FILE#\")",
            MSG_ERR_UPLOAD => " - error when uploading file",
            MSG_OK_GETTREE => "The directory tree created successfully, downloaded #COUNT_COMPLETE# from #COUNT_TOTAL# files",
            MSG_ERR_GETTREE => "WARNING: The directory tree created with errors, downloaded #COUNT_COMPLETE# from #COUNT_TOTAL# files",
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

    public static function getOptions($options = "", $longopts = array())
    {
        if (PHP_SAPI === 'cli' || empty($_SERVER['REMOTE_ADDR']))
        {
            $arOptions = getopt($options, $longopts);
            if (empty($arOptions))
                return false;
            return $arOptions;
        }
    }

    public static function convertPermissions($str)
    {
        $mode = 0;
        if ($str[1] == "r") $mode += 0400;
        if ($str[2] == "w") $mode += 0200;
        if ($str[3] == "x") $mode += 0100;
        else if ($str[3] == "s") $mode += 04100;
        else if ($str[3] == "S") $mode += 04000;

        if ($str[4] == "r") $mode += 040;
        if ($str[5] == "w") $mode += 020;
        if ($str[6] == "x") $mode += 010;
        else if ($str[6] == "s") $mode += 02010;
        else if ($str[6] == "S") $mode += 02000;

        if ($str[7] == "r") $mode += 04;
        if ($str[8] == "w") $mode += 02;
        if ($str[9] == "x") $mode += 01;
        else if ($str[9] == "t") $mode += 01001;
        else if ($str[9] == "T") $mode += 01000;
 
        return sprintf("%04o", $mode); 
    }

    public static function convertSize($bytes)
    {
        $size = ["bytes", "Kb", "Mb", "Gb", "Tb"];
        return $bytes ? round($bytes / pow(1024, ($i = floor(log($bytes, 1024)))), 2) 
            . " " . $size[$i] : "0 bytes";
    }
    
    public static function checkResult( & $result)
    {
        $result = ($result ? $result : MSG_ERR_EXCEPTION_UNDEF);
    }
    
    public static function getUsername()
    {
		return strlen(get_current_user()) > 0 ? get_current_user() : "unknown_".rand();
	}
}