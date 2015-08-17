<?php
 
/**
* Проверяет путь и создаёт необходимое дерево директорий 
*
* @return  bool
*/
function checkPath($path)
{
	$path = str_replace(["\\", "//"], "/", $path);
	if(substr($path, - 1) != "/")
	{
		$p    = strrpos($path, "/");
		$path = substr($path, 0, $p);
	}

	$path = rtrim($path, "/");

	if(!file_exists($path))
	return mkdir($path, 0755, true);
	else
	return is_dir($path);
}

/**
* Возвращает заранее определенное сообщение или заданную строку
*
* @param string OR int $message     Произвольная строка или идентификатор сообщения из списка
* @param array $arFields            Массив значений шаблона замены #ID# -> 20, #SERVER# -> localhost
*
* @return string
*/
function getMessage($message = false, $arFields = false)
{
	$arMessages = [
		10 => " - Ошибка: соединение c удалённым сервером не установлено",
		20 => " - Ошибка: не удалось создать локальный файл #FILE#",
        22 => " - Ошибка: не удалось открыть локальный файл #FILE#",
        24 => " - Ошибка: не удалось переименовать локальный файл #FILE#",
		30 => " - Ошибка при подключении: неизвестный протокол \"#PROTOCOL#\"",
        40 => " - ошибка при создании файла: указанный путь не существует #FILE#",
        50 => " - ошибка: не удалось удалить файл #FILE#",
        
		100 => " + Соединение с удалённым сервером успешно установлено",
		110 => " - Ошибка при подключении: неверный логин или пароль",
		120 => " - Ошибка при подключении: сервер недоступен, некорректный порт или имя хоста",
		130 => " + Соединение с удалённым сервером уже существует",
        140 => "Подключение к удалённому серверу: протокол #PROTOCOL#, адрес #HOST#, порт #PORT#, логин #LOGIN#, #PASSWORD#",
        
        200 => "Закрыто подключение с удалённым сервером",
		210 => "Ошибка: не удалось закрыть соединение с удалённым сервером или соединение не существует",
		
        300 => "Новая текущая директория: #PATH#",
		310 => "Ошибка: не удалось сменить директорию: #PATH#",
        
        400 => " - произведена запись в \"#LOCAL_FILE#\"",
        410 => " ∨ Скачиваем файл \"#REMOTE_FILE#\"",
		420 => " - ошибка: не удалось скачать файл",
        
        500 => " - файл успешно загружен",
        510 => " ∧ Выгрузка файла (#LOCAL_FILE#) на сервер (#REMOTE_FILE#)",
        520 => " - при закачке произошла проблема",
        
        600 => "Резервная копия создана, и файл успешно изменён #FILE#",

        -1  => " - Have any uncertain exception, please contact the developer: alexchurov@gmail.com"
	];

    $message = (array_key_exists($message, $arMessages)
           ? $arMessages[$message]
           : $message);

    if (is_array($arFields))
        foreach ($arFields as $k => $v) $message = str_replace("#$k#", $v, $message);
    
    return $message;
}

function showMessage($message = false, $arFields = false)
{
    if (!$message)
        return;
	echo "[".date("Y-m-d H:i:s")."] ".getMessage($message, $arFields)."\n";
}

/**
* Класс Task - представляет собой набор методов и свойств для выполнения действий над списком файлов
* 
* Основные методы:
* 
* connection - выполняет подключение к удалённому серверу
* add - добавляет файл для обработки
* replacement - выполнить обработку, создав предварительно резервные копии
* 
* По-умолчанию файлы сохраняются в разделе /temp/текущая-дата-и-время/*
* 
*/
class Task
{
	public  $localHomeDir = false;
	public  $localFilesDir = false;
	public  $remoteHomeDir = false;

    public  $regexpPattern = false;
    public  $regexpReplacement = false;
    
	private $connection = false;
	private $connectionType = false;
	private $connectionPort = false;
	private $connectionTimeout = 10;
    
    private $arItems = array();

    // Метод устанавливает локальную директорию для файлов
    public function setLocalHomeDir($path)
    {
        $this->localHomeDir = $path;
        $this->__construct();
    }
    
    // Метод устанавливает шаблон регулярного выражения, который будет применятся при обработке удалённых файлов
    public function setRegexp($pattern, $replacement)
    {
        $this->regexpPattern = $pattern;
        $this->regexpReplacement = $replacement;
    }

    // Метод добавляет файл к списку, над которым будут произведены изменения
    public function add($filePath)
    {
        if ($localFile = $this->getRemoteFile($filePath))
            $this->arItems[] = [
                'FILE_ORIGINAL' => $filePath,
                'FILE_NEW'     => $localFile,
                'FILE_BACK'   => "$localFile.bak"
            ];
    }
    
    public function replacement()
    {
        $iterItems = new ArrayIterator($this->arItems);
        foreach ($iterItems as $arItem)
        {
            $arFields['FILE'] = $arItem['FILE_NEW'];
            if (copy($arItem['FILE_NEW'], $arItem['FILE_BACK']))
            {
                if ($fp = @fopen($arItem['FILE_NEW'], "r"))
                {
                    $tempFile = $arItem['FILE_NEW'].".tmp";
                    $fpw = @fopen($tempFile, "w");
                    while (($buffer = fgets($fp, 4096)) !== false)
                    {
                        $buffer = preg_replace_callback(
                            $this->regexpPattern,
                            function ($matches)
                            {
                                return $this->regexpReplacement;
                            },
                            $buffer
                        );
                        fputs($fpw, $buffer);
                    }
                    fclose($fp);
                    fclose($fpw);
                
                    if (unlink($arItem['FILE_NEW']))
                    {
                        if (rename($tempFile, $arItem['FILE_NEW']))
                            $result = 600;
                        else
                            $result = 24;
                    }
                    else
                    {
                        $arFields['FILE'] = $tempFile;
                        $result = 50;
                    }
                }
                else
                {
                    $arFields['FILE'] = $arItem['FILE_BACK'];
                    $result = 22;
                }
            }
            else
                $result = 20;
            
            $this->checkResult($result);
            showMessage($result, $arFields);
            
            // Выгружаем файл
            if ($result == 600)
                $this->putRemoteFile($arItem['FILE_NEW'], $arItem['FILE_ORIGINAL']);
        }
    }

	// Метод выполняет подключение к удалённому серверу и возвращает дескриптор соединения
	public function connect($host, $login, $password, $port = "21", $type = "ftp")
	{
		if(!$this->connection)
		{
            $arFields = ["PROTOCOL" => $type, "HOST" => $host, "PORT" => $port, "LOGIN" => $login, "PASSWORD" => $password];
			showMessage(140, $arFields);
			// FTP
			if(strcasecmp($type, 'ftp') == 0)
			{
				if($this->connection = ftp_connect($host, $port, $this->connectionTimeout))
				{
					if(@ftp_login($this->connection, $login, $password))
					{
                        $this->connectionType = $type;
                        $this->connectionPort = $port;
						$result = 100;
					}
					else
					    $result = 110;
				}
				else
				    $result = 120;
			}
			// SSH
			elseif(strcasecmp($type, 'ssh') == 0)
			    $result = 30;
			else
			    $result = 30;
		}
		else
		    $result = 130;

        $this->checkResult($result);
		showMessage($result, $arFields);
		$this->changeDir($this->remoteHomeDir); // Change the starting directory

		return ($result == 100 ? $this->connection : false);
	}

	public function disconnect()
	{
		if ($this->connection)
		{
			// FTP
			if(strcasecmp($this->connectionType, 'ftp') == 0)
			{
			    if(ftp_close($this->connection))
	    	    	$result = 200;
			    else
			        $result = 210;
            }
			else
			    $result = 30;
		}
		else
            $result = 10;
        
        $this->checkResult($result);
        showMessage($result);
        
        return ($result == 200 ? true : false);
	}

	public function checkConnection()
	{
		return ($this->connection ? true : false);
	}

	public function changeDir($path)
	{
		if($this->connection)
		{
			// FTP соединение
			if(strcasecmp($this->connectionType, 'ftp') == 0)
			{
				if (ftp_chdir($this->connection, $path))
                {
                    $pwd = ftp_pwd($this->connection);
	                $result = 300;
                }
				else
	    			$result = 310;
			}
			else
	    		$result = 30;
		}
		else
	    	$result = 10;

        $this->checkResult($result);
        showMessage($result, ["PATH" => $path]);

		return ($result == 300 ? $pwd : false);
	}
    
	/**
	* Скачивает удалённый файл и сохраняет локально в домашнем каталоге $localHomeDir
	* Рекурсивно создаём дерево директорий,  тем самым копирую удалённую структуру разделов
    * 
	* @param string $remoteFile        Удалённый файл
	* @param string || resourse $localFile Локальный каталог или указатель на открытый файл
	*
    * Возвращет путь к созданному файлу или отрицание
	* @return string || false            
	*/
	public function getRemoteFile($remoteFile, $localFile = false)
	{
        if (!$localFile) $localFile = $this->localFilesDir;
        
        $arFields = ["REMOTE_FILE" => $remoteFile, "LOCAL_DIR" => $localFile];
		$message = getMessage(410, $arFields);
		if($this->connection)
		{
			if (is_string($localFile)) // Получили путь к локальному файлу
			{
				$localFile = str_replace(["\\", "//"], "/", $localFile."/".$remoteFile);
				if(checkPath($localFile))
				{
					if(!$handle = fopen($localFile, 'w'))
					    $result = 20;
				}
				else
                {
                    $arFields['FILE'] = $localFile;
                    $result = 40;
                }
				
			}
			elseif (is_resource($localFile)) // Получили указатель на открытый файл
			    $handle = & $localFile;

			if(is_resource($handle) && !$result)
			{
				if(strcasecmp($this->connectionType, 'ftp') == 0) // FTP соединение
				{
					if(ftp_fget($this->connection, $handle, $remoteFile, FTP_BINARY, 0))
					{
						fclose($handle);
                        $arFields['LOCAL_FILE'] = $localFile;
						$result = 400;
					}
					else
					{
						fclose($handle);
						unlink($localFile);
						$result = 420;
					}
				}
				elseif(strcasecmp($this->connectionType, 'ssh') == 0) // SSH соединение
					$result = 30;
				else
				    $result = 30;
			}
		}
		else
		    $result = 10;

        $this->checkResult($result);
        showMessage($message.getMessage($result, $arFields));
        
		return ($result == 400 ? $localFile : false);
	}

	/**
	* Отправляет на удалённый сервер файл, если файл существует, то он будет заменён
	*
	* @param string || resource $localFile - путь к локальному файлу или его открытый дескриптор
	* @param string $remoteFile
	*
	* @return bool
	*/
	public function putRemoteFile($localFile, $remoteFile)
	{
        $arFields = ['LOCAL_FILE' => $localFile, 'REMOTE_FILE' => $remoteFile];
        $message = getMessage(510, $arFields);
		if($this->connection)
		{
			if(is_string($localFile)) // Получили путь к файлу
			{
				if(!$handle = fopen($localFile, 'r'))
				    $result = 20;
			}
			elseif(is_resource($localFile))
			    $handle = & $localFile;

			if(is_resource($handle) && !$result)
			{
				if(strcasecmp($this->connectionType, 'ftp') == 0) // FTP соединение
				{
					if(ftp_fput($this->connection, $remoteFile, $handle, FTP_BINARY))
					    $result = 500;
					else
					    $result = 520;
				}
				// SSH соединение
				elseif(strcasecmp($this->connectionType, 'ssh') == 0)
				    $result = 30;
				else
				    $result = 30;
			}
		}
		else
		    $result = 10;

		$this->checkResult($result);
        showMessage($message.getMessage($result, $arFields));
		return ($result == 500 ? true : false);
	}

	/**
	* Проверяет на удалённом ресурсе, является ли путь файлом или директорией
	*
	* @return bool
	*/
	protected function isDir()
	{

	}

    protected function checkResult(&$result)
    {
        $result = ($result ? $result : -1);
    }

	function __construct()
	{
		$this->localFilesDir = $this->localHomeDir."/".date("Y-m-d_His");
	}
}




/* ****************************************************************************** */




date_default_timezone_set('Europe/Moscow');
error_reporting(E_ERROR);

$config = [
	'indexFile'  => 'files10.csv',
	'typeconn'   => 'ftp',
	'host'       => 'demo.ваш-хост.ru',
	'user'       => 'demo',
	'password'   => 'ваш-пароль',
	'start_dir'   => "www/demo.ваш-хост.ru"];

$task = new Task();
$task->setLocalHomeDir('temp');
$task->remoteHomeDir = $config['start_dir'];
$task->connect($config['host'], $config['user'], $config['password'], '21', 'ftp');
$task->setRegexp('|[\s\n\r]+|', null);

if ($task->checkConnection())
{
    showMessage("\n\n * * * Читаем индексный файл ($config[indexFile]) и скачиваем файлы");
    $index = new LimitIterator(
        new SplFileObject($config["indexFile"]),
        0, // Читаем с нулевой строки
        1  // Итерация 1 строка
    );
    while (!$index->eof())
    {
        if ($remoteFile = $index->fgetcsv()[0])
            $localFile = $task->add($remoteFile);
    }
    
    showMessage("\n\n * * * Создаём бэкапы и производим замену в файлах");
    $task->replacement();
}

$task->disconnect();


/* ****************************************************************************** */


?>
