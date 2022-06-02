<?
class LogReader
{
	protected $tempsFolder = 'temps'; // папка хранения частей разбитого файла
	protected $linesMax = 29000; // по сколько строк разбивать на отдельные файлы
	protected $execMinutesLimit = 5; // ограничение работы скрипта в минутах, 0 - без ограничения
	protected $botDetection = [ // данные о ботах. ключ - подстрока поиска в юзер агенте
		'googlebot' => 'Google',
		'yandexbot' => 'Yandex',
		'bingbot' => 'Bing',
		'baiduspider' => 'Baidu',
	];

	protected $logFileName;
	protected $splittedLogFileNames = [];

	function __construct()
	{
		set_time_limit(60 * $this->execMinutesLimit);
	}

	public function splitLogFile() 
	{
		$source = $this->logFileName;
	    	$i = 0;
	    	$j = 1;

    		if (file_exists($this->tempsFolder)) $this->deleteDirectory($this->tempsFolder);
    		mkdir($this->tempsFolder, 0777, true);

	    	$handle = fopen($source, "r");
	    	while (($line = fgets($handle)) !== false)
	    	{
	    		$fname = $this->tempsFolder.'/temp_'.$source.'_part-'.$j;
    			$fhandle = fopen($fname, 'a');
    			fwrite($fhandle, $line);

    			if ($i == 0)
	    			array_push($this->splittedLogFileNames, $fname);	    	

    			if ($i + 1 == $this->linesMax)
    				fclose($fhandle);

	    		if ($i+1 < $this->linesMax)
	    			$i++;
	    		else
	    		{
	    			$i = 0;
	    			$j++;
	    		}
	    	}
	    	fclose($handle);
	    	if (count($this->splittedLogFileNames) > 0) return true; else return false;
	}

	protected function deleteDirectory($dirName) 
	{
        	if (is_dir($dirName))
           	$dirHandle = opendir($dirName);
     	if (!$dirHandle)
          	return false;
     	while ($file = readdir($dirHandle)) 
     	{
           	if ($file != "." && $file != "..") 
           	{
                	if (!is_dir($dirName."/".$file))
                    	unlink($dirName."/".$file);
                	else
                     	delete_directory($dirName.'/'.$file);
           	}
     	}
     	closedir($dirHandle);
     	rmdir($dirName);
     	return true;
	}

	public function formatLineToArray($line)
	{
		preg_match("/^(\S+) (\S+) (\S+) \[([^:]+):(\d+:\d+:\d+) ([^\]]+)\] \"(\S+) (.*?) (\S+)\" (\S+) (\S+) (\".*?\") (\".*?\")$/", $line, $matches);
    		$logs = $matches;

    		if (isset($logs[0])) 
	    	{
	      	$formatedLog = array(); 
	      	$formatedLog['ip'] = $logs[1];
	      	$formatedLog['identity'] = $logs[2];
	      	$formatedLog['user'] = $logs[2];
	      	$formatedLog['date'] = $logs[4];
	      	$formatedLog['time'] = $logs[5];
	      	$formatedLog['timezone'] = $logs[6];
	      	$formatedLog['method'] = $logs[7];
	      	$formatedLog['path'] = $logs[8];
	      	$formatedLog['protocal'] = $logs[9];
	      	$formatedLog['status'] = $logs[10];
	      	$formatedLog['bytes'] = $logs[11];
	      	$formatedLog['referer'] = $logs[12];
	      	$formatedLog['agent'] = $logs[13];
	     	return $formatedLog; 
	    	}
	    	else
	      	return false;

	}

	public function formatBytes($bytes, $precision = 2) 
	{
	    $units = array("b", "kb", "mb", "gb", "tb");

	    $bytes = max($bytes, 0);
	    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
	    $pow = min($pow, count($units) - 1);

	    $bytes /= (1 << (10 * $pow));

	    return round($bytes, $precision) . " " . $units[$pow];
	}

	public function assignLogFileName($fn)
	{
		$this->logFileName = $fn;
	}

	public function getLogLight()
	{
		$fp = fopen($this->logFileName, 'r');

		$processedRecords = 0;
		$totalRecords = 0;
		$totalBytes = 0;
		$totalPaths = [];
		$totalStatuses = [];
		$totalKnownBots = [];
		$totalunknownBots = [];
		while (($line = fgets($fp)) !== false) 
		{
			$totalRecords++;
			$record = $this->formatLineToArray($line);
			if (!$record) continue;

			if (is_numeric($record['bytes'])) $totalBytes += $record['bytes'];
			array_push($totalPaths, $record['path']);
			array_push($totalStatuses, $record['status']);
			if ($this->isUserAgentBot($record['agent']))
			{
				$botInfo = $this->isUserAgentBotKnown($record['agent']);
				if ($botInfo)
					$totalKnownBots[] = $botInfo;
				else
					$totalunknownBots[] = $record['agent'];
			}

			$processedRecords++;
		}
		fclose($fp);

		$output = [
			'lines' => [
				'total' => $totalRecords,
				'good' => $processedRecords,
				'bad' => $totalRecords - $processedRecords,
			],
			'hits' => $processedRecords,
			'bytes' => $totalBytes,
			'paths' => count(array_unique($totalPaths)),
			'statuses' => array_count_values($totalStatuses),
			'bots' => [
				'known' => array_count_values($totalKnownBots),
				'unknown' => array_count_values($totalunknownBots),
			]
		];

		return $output;
	}

	public function getLogHeavy()
	{
		$files = $this->splittedLogFileNames;
		$i = 0;
		$j = 0;
		$oldPaths = [];
		$curPaths = [];
		$recordsAll = 0; 
		$recordsValid = 0;  
		$bytes = 0; 
		$paths = []; 
		$oldStatuses = []; 
		$curStatuses = []; 
		$oldBotsKnown = [];
		$curBotsKnown = [];
		$oldBotsUnknown = [];
		$curBotsUnknown = [];
		foreach ($files as $key => $file)
		{
			$fp = fopen($file, 'r');

			while (($line = fgets($fp)) !== false) 
			{
				$recordsAll++;
				$record = $this->formatLineToArray($line);
				if (!$record) continue;

				if (is_numeric($record['bytes'])) $bytes += $record['bytes'];
				$curPaths[] = $record['path'];
				$curStatuses[] = $record['status'];

				if ($this->isUserAgentBot($record['agent']))
				{
					$botInfo = $this->isUserAgentBotKnown($record['agent']);
					if ($botInfo)
						$curBotsKnown[] = $botInfo;
					else
						$curBotsUnknown[] = $record['agent'];
				}

				$i++;
			}
			fclose($fp);

			if (count($files) == 1)
			{
				$oldPaths = array_unique($curPaths);
				$oldStatuses = array_count_values($curStatuses);
				$oldBotsKnown = array_count_values($curBotsKnown);
				$oldBotsUnknown = array_count_values($curBotsUnknown);
			}
			else
			{
				if ($j == 0)
				{
					$oldPaths = array_unique($curPaths);
					$curPaths = [];

					$oldStatuses = array_count_values($curStatuses);
					$curStatuses = [];

					$oldBotsKnown = array_count_values($curBotsKnown);
					$curBotsKnown = [];

					$oldBotsUnknown = array_count_values($curBotsUnknown);
					$curBotsUnknown = [];
				}
				else 
				{
					$oldPaths = array_unique(array_merge($oldPaths, $curPaths));
					$curPaths = [];

					$oldStatuses = $this->mergeTwoArraysAndCalcValuesByKeys($oldStatuses, array_count_values($curStatuses));
					$curStatuses = [];

					$oldBotsKnown = $this->mergeTwoArraysAndCalcValuesByKeys($oldBotsKnown, array_count_values($curBotsKnown));
					$curBotsKnown = [];

					$oldBotsUnknown = $this->mergeTwoArraysAndCalcValuesByKeys($oldBotsUnknown, array_count_values($curBotsUnknown));
					$curBotsUnknown = [];
				}
			}
			$j++;
		}
		$recordsValid = $i;

		$data = [
			'lines' => [
				'total' => $recordsAll,
				'good' => $recordsValid,
				'bad' => $recordsAll - $recordsValid,
			],
			'hits' => $recordsValid,
			'bytes' => $bytes,
			'paths' => count($oldPaths),
			'statuses' => $oldStatuses,
			'bots' => [
				'known' => $oldBotsKnown,
				'unknown' => $oldBotsUnknown,
			]
		];

		return $data;
	}

	protected function isUserAgentBot($userAgent)
	{
		if (preg_match('/bot|crawl|slurp|spider|mediapartners/i', $userAgent))
			return true;
		else
			return false;
	}

	protected function isUserAgentBotKnown($userAgent)
	{
		foreach ($this->botDetection as $key => $value)
			if (strstr(strtolower($userAgent), $key))
				return $value;
		return false;
	}

	protected function mergeTwoArraysAndCalcValuesByKeys($old, $cur)
	{
		$uni = array_unique(array_merge(array_flip($old), array_flip($cur)));
		foreach ($uni as $key => $value)
		if (isset($cur[$value]))
			if (isset($old[$value]))
				$old[$value] += $cur[$value];
			else
				$old[$value] = $cur[$value];
		return $old;
	}
}


$lr = new LogReader();

$lr->assignLogFileName('access2_log'); // название лог файла для считывания
if (!$lr->splitLogFile()) { print 'Could not find splitted log files.'; return; }
$data = $lr->getLogHeavy(); // считывание большого лог файла, экономия памяти засчет деления лога на части

print $lr->formatBytes(memory_get_peak_usage());
echo '<pre>'; print json_encode($data, JSON_PRETTY_PRINT); echo '</pre>';


echo '<hr/>';


$lr->assignLogFileName('access2_log'); // название лог файла для считывания
$data = $lr->getLogLight(); // считывание лог файла без мысли об экономии памяти

print $lr->formatBytes(memory_get_peak_usage());
echo '<pre>'; print json_encode($data, JSON_PRETTY_PRINT); echo '</pre>';
?>