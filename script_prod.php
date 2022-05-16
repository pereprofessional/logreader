<?
class LogReader
{
	protected $tempsFolder = 'temps'; // папка хранения частей разбитого файла
	protected $linesMax = 29000; // по сколько строк разбивать на отдельные файлы
	protected $execMinutesLimit = 5; // ограничение работы скрипта в минутах, 0 - без ограничения
	protected $botDetection = [ // данные о ботах. ключ - подстрока поиска в юзер агенте. контент - отображаемое имя бота
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

	// разделяем лог файл на несколько частей поменьше
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
	    	//if ($j == 2) break;

			$fname = $this->tempsFolder.'/temp_'.$source.'_part-'.$j;
	    		$fhandle = fopen($fname, 'a');
	    		fwrite($fhandle, $line);

	    		if ($i == 0)
		    		array_push($this->splittedLogFileNames, $fname);	    	

	    		if ($i+1 == $this->linesMax)
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

	public function formatLineToArray($line)
	{
		preg_match("/^(\S+) (\S+) (\S+) \[([^:]+):(\d+:\d+:\d+) ([^\]]+)\] \"(\S+) (.*?) (\S+)\" (\S+) (\S+) (\".*?\") (\".*?\")$/", $line, $matches);
    	$logs = $matches;

    	if (isset($logs[0])) 
	    {
	      	$formated_log = array(); 
	      	$formated_log['ip'] = $logs[1];
	      	$formated_log['identity'] = $logs[2];
	      	$formated_log['user'] = $logs[2];
	      	$formated_log['date'] = $logs[4];
	      	$formated_log['time'] = $logs[5];
	      	$formated_log['timezone'] = $logs[6];
	      	$formated_log['method'] = $logs[7];
	      	$formated_log['path'] = $logs[8];
	      	$formated_log['protocal'] = $logs[9];
	      	$formated_log['status'] = $logs[10];
	      	$formated_log['bytes'] = $logs[11];
	      	$formated_log['referer'] = $logs[12];
	      	$formated_log['agent'] = $logs[13];
	      	return $formated_log; 
	    }
	    else
	    {
	      	return false;
	    }

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

	public function getLogInfo()
	{
		$files = $this->splittedLogFileNames;
		$i = 0; // считаем строки
		$j = 0; // считаем файлы
		// массивы для хранения path
		$old_paths = [];
		$cur_paths = [];
		// массивы для хранения status 
		$old_statuses = []; 
		$cur_statuses = []; 
		// массивы для хранения известных ботов (из $this->botDetection())
		$old_bots_known = [];
		$cur_bots_known = [];
		// массивы для хранения неизвестных ботов
		$old_bots_unknown = [];
		$cur_bots_unknown = [];

		$records_all = 0; // всего записей
		$records_valid = 0; // всего валидных записей 
		$bytes = 0; // общий трафик
		foreach ($files as $key => $file)
		{
			$fp = fopen($file, 'r');

			while (($line = fgets($fp)) !== false) 
			{
				$records_all++;
				$record = $this->formatLineToArray($line);
				if (!$record) continue;

				if (is_numeric($record['bytes'])) $bytes += $record['bytes'];
				$cur_paths[] = $record['path'];
				$cur_statuses[] = $record['status'];

				if ($this->isUserAgentBot($record['agent']))
				{
					$botInfo = $this->isUserAgentBotKnown($record['agent']);
					if ($botInfo)
						$cur_bots_known[] = $botInfo;
					else
						$cur_bots_unknown[] = $record['agent'];
				}

				$i++;
			}
			fclose($fp);

			if (count($files) == 1) // если всего один файл
			{
				$old_paths = array_unique($cur_paths);
				$old_statuses = array_count_values($cur_statuses);
				$old_bots_known = array_count_values($cur_bots_known);
				$old_bots_unknown = array_count_values($cur_bots_unknown);
			}
			else // если лог был разделён на несколько файлов
			{
				if ($j == 0) // первый файл
				{
					$old_paths = array_unique($cur_paths);
					$cur_paths = [];

					$old_statuses = array_count_values($cur_statuses);
					$cur_statuses = [];

					$old_bots_known = array_count_values($cur_bots_known);
					$cur_bots_known = [];

					$old_bots_unknown = array_count_values($cur_bots_unknown);
					$cur_bots_unknown = [];
				}
				else // все последующие файлы
				{
					$old_paths = array_unique(array_merge($old_paths, $cur_paths));
					$cur_paths = [];

					$old_statuses = $this->mergeTwoArraysAndCalcValuesByKeys($old_statuses, array_count_values($cur_statuses));
					$cur_statuses = [];

					$old_bots_known = $this->mergeTwoArraysAndCalcValuesByKeys($old_bots_known, array_count_values($cur_bots_known));
					$cur_bots_known = [];

					$old_bots_unknown = $this->mergeTwoArraysAndCalcValuesByKeys($old_bots_unknown, array_count_values($cur_bots_unknown));
					$cur_bots_unknown = [];
				}
			}
			$j++;
		}
		$records_valid = $i;

		$data = [
			'lines' => [
				'total' => $records_all,
				'good' => $records_valid,
				'bad' => $records_all - $records_valid,
			],
			'hits' => $records_valid,
			'bytes' => $bytes,
			'paths' => count($old_paths),
			'statuses' => $old_statuses,
			'bots' => [
				'known' => $old_bots_known,
				'unknown' => $old_bots_unknown,
			]
		];

		return $data;
	}

	protected function isUserAgentBot($user_agent)
	{
		if (preg_match('/bot|crawl|slurp|spider|mediapartners/i', $user_agent))
			return true;
		else
			return false;
	}

	protected function isUserAgentBotKnown($user_agent)
	{
		foreach ($this->botDetection as $key => $value)
			if (strstr(strtolower($user_agent), $key))
				return $value;
		return false;
	}

	// данные с прошло и текущего файлов после прохода через array_count_values()
	// подсчитываем скока было в прошлом файле статус=200 и в текущем. плюсуем. так для всех статусов и чего угодно в целом
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



	protected function deleteDirectory($dirname) 
	{
		if (is_dir($dirname))
			$dir_handle = opendir($dirname);
     	if (!$dir_handle)
          	return false;
     	while ($file = readdir($dir_handle)) 
     	{
           	if ($file != "." && $file != "..") 
           	{
                	if (!is_dir($dirname."/".$file))
                     	unlink($dirname."/".$file);
                	else
                     	delete_directory($dirname.'/'.$file);
           	}
     	}
     	closedir($dir_handle);
     	rmdir($dirname);
     	return true;
	}
}


$lr = new LogReader();

$lr->assignLogFileName('access2_log'); // название лог файла для считывания
if (!$lr->splitLogFile()) { print 'Could not find splitted log files.'; return; }
$data = $lr->getLogInfo();

print $lr->formatBytes(memory_get_peak_usage());
echo '<pre>'; print json_encode($data, JSON_PRETTY_PRINT); echo '</pre>';

?>