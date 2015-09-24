<?php

class Procureur
{

    public function setWorkspace($pathDir)
    {
        $this->workspace = $pathDir;
    }
    
    public function setWorkspaceCache($pathFile)
    {
		$this->workspaceCache = $pathFile;
	}

    public function setRegexp($pattern, $replacement)
    {
        $this->regexpPattern = $pattern;
        $this->regexpReplacement = $replacement;
    }

    public function add($filePath)
    {
        if ($localFile = $this->getRemoteFile($filePath))
        {
            $this->arItems[] = [
                ARR_INDEX_FILE_ORIGINAL => $filePath,
                ARR_INDEX_FILE_NEW => $localFile,
                ARR_INDEX_FILE_BACK => "$localFile.bak"
            ];
        }
        else
        	return false;
    }

    public function replacement()
    {
        $iterItems = new ArrayIterator($this->arItems);
        foreach ($iterItems as $arItem)
        {
            $arFields['FILE'] = $arItem[ARR_INDEX_FILE_NEW];
            if (copy($arItem[ARR_INDEX_FILE_NEW], $arItem[ARR_INDEX_FILE_BACK]))
            {
                if ($fp = @fopen($arItem[ARR_INDEX_FILE_NEW], "r"))
                {
                    $tempFile = $arItem[ARR_INDEX_FILE_NEW] . ".tmp";
                    $fpw      = @fopen($tempFile, "w");
                    while (($buffer = fgets($fp, 4096)) !== false)
                    {
                        $buffer = preg_replace_callback(
                            $this->regexpPattern,
                            function ($matches)
                            {
                                return $this->regexpReplacement;
                            }, $buffer
                        );
                        fputs($fpw, $buffer);
                    }
                    fclose($fp);
                    fclose($fpw);

                    if (unlink($arItem[ARR_INDEX_FILE_NEW]))
                    {
                        if (rename($tempFile, $arItem[ARR_INDEX_FILE_NEW]))
                        	$result = MSG_OK_FILE_REPLACE;
                        else
                        	$result = MSG_ERR_FILE_RENAME;
                    }
                    else
                    {
                        $arFields['FILE'] = $tempFile;
                        $result = MSG_ERR_FILE_REM;
                    }
                }
                else
                {
                    $arFields['FILE'] = $arItem[ARR_INDEX_FILE_BACK];
                    $result = MSG_ERR_FILE_OPEN;
                }
            }
            else
            	$result = MSG_ERR_FILE_MAKE;

            System::checkResult($result);
            System::showMessage($result, $arFields);

            // Upload file
            if ($result == MSG_OK_FILE_REPLACE)
            	$this->putRemoteFile($arItem[ARR_INDEX_FILE_NEW], $arItem[ARR_INDEX_FILE_ORIGINAL]);
        }
    }

    function __construct()
    {
		$date = date("Ymd_His");
		$this->userName = System::getUsername();
		
		$this->pathHome = $this->userName;
		$this->pathScripts = $this->pathHome."/scripts/run$date/";
		$this->pathBackups = $this->pathBackups."/backups/run$date/";
		$this->pathTmp = $this->pathTmp."/tmp/run$date/";
    }
    
    private $userName = false;
    private $workspace = false;
    private $workspaceCache = false;
    
    private $pathHome = false;
	private $pathScripts = false;
	private $pathBackups = false;
	private $pathTmp = false;
	
}

?>