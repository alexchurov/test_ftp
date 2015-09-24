<?php

class Transport
{
    public function setRemoteHomeDir($pathDir)
    {
        $this->remoteHomeDir = $pathDir;
        if ($this->checkConnection)
        	$this->changeDir($pathDir);
    }

    public function connect($host, $login, $password, $port = FTP_PORT, $protocol = FTP_INTERFACE)
    {
        if (!$this->connection)
        {
            $arFields = ["PROTOCOL" => $protocol, "HOST" => $host, "PORT" => $port, "LOGIN" => $login, "PASSWORD" => $password];
            System::showMessage(MSG_EVENT_CONN, $arFields);
            // FTP
            if (strcasecmp($protocol, FTP_INTERFACE) == 0)
            {
                if ($this->connection = ftp_connect($host, $port, $this->connectionTimeout))
                {
                    if (@ftp_login($this->connection, $login, $password))
                    {
                        $this->connectionType = $protocol;
                        $this->connectionPort = $port;
                        $result = MSG_OK_CONN;
                    }
                    else
                    	$result = MSG_ERR_CONN_DENIED;
                }
                else
                	$result = MSG_ERR_CONN_NOHOST;
            }
            // SSH
            elseif (strcasecmp($protocol, SSH_INTERFACE) == 0)
            	$result = MSG_ERR_INTERFACE;
            else
            	$result = MSG_ERR_INTERFACE;
        }
        else
        	$result = MSG_OK_CONN_EXIST;

        System::checkResult($result);
        System::showMessage($result, $arFields);
        $this->changeDir($this->remoteHomeDir); // Change the starting directory

        return ($result == MSG_OK_CONN ? $this->connection : false);
    }

    public function disconnect()
    {
        if ($this->connection)
        {
            // FTP
            if (strcasecmp($this->connectionType, FTP_INTERFACE) == 0)
            {
                if (ftp_close($this->connection))
                	$result = MSG_OK_DISCONNECT;
                else
                	$result = MSG_ERR_DISCONN;
            }
            else
            	$result = MSG_ERR_INTERFACE;
        }
        else
        	$result = MSG_ERR_NOCONN;

        System::checkResult($result);
        System::showMessage($result);

        return ($result == MSG_OK_DISCONNECT ? true : false);
    }

    public function checkConnection()
    {
        return ($this->connection ? true : false);
    }

    public function getTree($localPath = false, $remotePath, $indexFile = false, $depth = 0)
    {
    	$countComplete = 0;
    	$countTotal = 0;
    	
    	if (!$indexFile)
    		$indexFile = $localPath."/".FILENAME_INDEX;
    	System::checkPath($indexFile);
    	
	       	if ($this->connection)
    	    {
   	    	    // FTP
       	    	if (strcasecmp($this->connectionType, FTP_INTERFACE) == 0)
	           	{
   					$arFTPTypes = ["-" => "file", "d" => "directory", "l" => "simlink"];
    				if ($remotePath)
    					$this->changeDir($remotePath);
				
    	    		$arBuffer = ftp_rawlist($this->connection, FTP_LISTING_ARG);
       				$depth++;
       				if (!empty($arBuffer))
       				{
           				foreach ($arBuffer as $line)
            			{
   	            			$arLine = preg_split("/[\s]+/", $line, 9);
       	        			$hour  = $minute= 0;
            	    		$year  = $arLine[7];
                			if (strpos($arLine[7], ":"))
		            	    {
        		        	   	list($hour, $minute) = explode(":", $arLine[7]);
                		    	$year = date("Y");
			                }
    	    		        $arFile = array(
        	        		   	"name"       => $arLine[8],
            	        		"path"       => System::checkPath($remotePath . "/" . $arLine[8],false),
		        	           	"type"       => $arFTPTypes[$arLine[0]{0}],
        		    	       	"permissions"=> System::ConvertPermissions($arLine[0]),
                    			"owner"      => $arLine[2],
		                    	"usergroup"  => $arLine[3],
	        		           	"size"       => System::convertSize($arLine[4]),
    	            		   	"date"       => date("D, d M Y H:i:s", mktime($hour, $minute, 0, $this->months[$arLine[5]], $arLine[6], $year)),
        	            		//"raw"        => $line
		    	            );
		    	            $remoteFilePath = $arFile["name"];
		    	            
        			        if ($arFile["type"] == "directory")
                			{
                				$arFile["path"] = preg_replace('/^\//','',$arFile["path"]);
                    			$arFile["path"] .= "/";
		                    	if (($this->level == 0) || ($this->level > 0 && $this->level > $depth))
		                    	{
        		               		$arr = $this->getTree($localPath, $arFile["path"], $indexFile, $depth);
        		               		$this->changeDir("..");
        		               		
        		               		$result = $arr[ARR_RESULT];
        		               		$countComplete += $arr[ARR_COUNT_COMPLETE];
        		               		$countTotal += $arr[ARR_COUNT_COMPLETE];
        		                }
	                		}
        	        		elseif ($arFile["type"] == "file")
            	    		{
            	    			$countTotal++;
            	    			if ($this->getRemoteFile($localPath."/".$remotePath, $remoteFilePath))
            	    			{
    								if ($fp = fopen($indexFile,a))
    								{
        	    	    				fputcsv($fp, $arFile);
        	    	    				fclose($fp);
        	    	    				$countComplete++;
      								}
      								else
            							$result = MSG_ERR_FILE_OPEN;
        	    	    		}
        	    	    		else
        	    	    			$result = MSG_ERR_GETTREE;
							}
		            	} // foreach $arBuffer
	        		}
        	    }
        	    
            	// Unknown interface
	            else
    	        	$result = MSG_ERR_INTERFACE;
        	}
        	else
        		$result = MSG_ERR_NOCONN;

        if (!$result) $result = MSG_OK_GETTREE;

        return [ARR_RESULT			=> $result,
        		ARR_FIELDS			=> ['FILE' => $arItem[ARR_INDEX_FILE_BACK]],
        		ARR_COUNT_TOTAL 	=> $countTotal,
        		ARR_COUNT_COMPLETE	=> $countComplete];
    }

    private function changeDir($path)
    {
        if ($this->connection)
        {
            // FTP
            if (strcasecmp($this->connectionType, FTP_INTERFACE) == 0)
            {
                if (ftp_chdir($this->connection, $path))
                {
                    $pwd    = ftp_pwd($this->connection);
                    $result = MSG_OK_CHDIR;
                }
                else
                $result = MSG_ERR_CHDIR;
            }
            else
            	$result = MSG_ERR_INTERFACE;
        }
        else
        	$result = MSG_ERR_NOCONN;

        System::checkResult($result);
        System::showMessage($result, ["PATH" => $path]);

        return ($result == MSG_OK_CHDIR ? $pwd : false);
    }

    private function getRemoteFile($localFile = false, $remoteFile)
    {
        if (!$localFile)
        	$localFile = $this->localFilesDir;

		$localFile = System::checkPath($localFile, false);
		$remoteFile = System::checkPath($remoteFile, false);

        $arFields  = ["REMOTE_FILE" => $remoteFile, "LOCAL_DIR" => $localFile];
        $message   = System::getMessage(MSG_EVENT_DOWNLOAD, $arFields);
        if ($this->connection)
        {
            if (is_string($localFile))
            {
                // Got a local file path
                $localFile = str_replace(["\\", "//"], "/", $localFile . "/" . $remoteFile);
                if (System::checkPath($localFile))
                {
                    if (!$handle = fopen($localFile, 'w'))
                    	$result = MSG_ERR_FILE_MAKE;
                }
                else
                {
                    $arFields['FILE'] = $localFile;
                    $result = MSG_ERR_FILE_PATH;
                }
            }
            elseif (is_resource($localFile)) // Got a pointer to the opened file
            	$handle = & $localFile;

            if (is_resource($handle) && !$result)
            {
                if (strcasecmp($this->connectionType, FTP_INTERFACE) == 0)
                {
                    // FTP
                    if (ftp_fget($this->connection, $handle, $remoteFile, FTP_BINARY, 0))
                    {
                        fclose($handle);
                        $arFields['LOCAL_FILE'] = $localFile;
                        $result = MSG_OK_FILE_WRITE;
                    }
                    else
                    {
                        fclose($handle);
                        unlink($localFile);
                        $result = MSG_ERR_DOWNLOAD;
                    }
                }
                // SSH
                elseif (strcasecmp($this->connectionType, SSH_INTERFACE) == 0)
                	$result = MSG_ERR_INTERFACE;
                else
                	$result = MSG_ERR_INTERFACE;
            }
            else
            	$result = MSG_ERR_DOWNLOAD;
        }
        else
        	$result = MSG_ERR_NOCONN;

        System::checkResult($result);
        System::showMessage($message . System::getMessage($result, $arFields));
        
        return ($result == MSG_OK_FILE_WRITE ? $localFile : false);
    }

    private function putRemoteFile($localFile, $remoteFile)
    {
        $arFields = ['LOCAL_FILE' => $localFile, 'REMOTE_FILE' => $remoteFile];
        $message  = System::getMessage(MSG_EVENT_UPLOAD, $arFields);
        if ($this->connection)
        {
            if (file_exists($localFile) && is_readable($localFile))
            {
                // Got a local file path
                if (!$handle = fopen($localFile, 'r'))
                	$result = MSG_ERR_FILE_MAKE;
            }
            elseif (is_resource($localFile))
            $handle = & $localFile;

            if (is_resource($handle) && !$result)
            {
                // Got a pointer to the opened file
                if (strcasecmp($this->connectionType, FTP_INTERFACE) == 0)
                {
                    // FTP
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
        }
        else
        	$result = MSG_ERR_NOCONN;

        System::checkResult($result);
        System::showMessage($message . System::getMessage($result, $arFields));
        return ($result == MSG_OK_DOWNLOAD ? true : false);
    }

	function __construct()
	{
		
	}

    private $remoteHomeDir = false;
    private $connection = false;
    private $connectionType = false;
    private $connectionPort = false;
    private $connectionTimeout = 10;

}

?>