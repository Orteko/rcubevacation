<?php

class VacationConfig
{
	private $currentHost = null;
	private $iniArr,$currentArr = array();
	private $hasError = false;
	private $allowedOptions = array('none'=>array(),'ftp'=>array(),'sshftp'=>array(),'virtual'=>array(),'setuid'=>array());

	public function __construct()
	{
		$this->allowedOptions['ftp'] = array('server','passive');
		$this->allowedOptions['sshftp'] = array('server');
		$this->allowedOptions['virtual'] = array('dsn','transport','dbase','always_keep_copy','domain_lookup_query', 'select_query','delete_query','insert_query','createvacationconf','always_keep_message');
		$this->allowedOptions['setuid'] = array('executable');
		
		$this->parseIni();
	}
	
	private function parseIni()
	{
		$configini = "plugins/vacation/config.ini";		
		if (! is_readable($configini))
		{
			$this->hasError($configini." is not readable");
		} else {

			$this->iniArr = parse_ini_file($configini, true);
			
			if (! $this->iniArr)
			{
				$this->hasError = "Failed to parse config.ini";
			}
		}
		
	}
	
	public function hasError()
	{
            return $this->hasError;
	}

        // Get normalized hostname
	public function setCurrentHost($host)
	{
		if (! $this->currentHost = parse_url($host,PHP_URL_HOST))
		{
                    $this->currentHost = parse_url($host,PHP_URL_PATH);
		}
	}

	public function hasVacationEnabled()
	{
		
		return ( $this->currentArr['driver'] != 'none');
	}

	private function defaultServer()
	{
		if (in_array($this->currentArr['driver'],array('ftp','sshftp')) && empty($this->currentArr['server']))
		{
			$this->currentArr['server'] = $this->currentHost;
		}
	}

	public function getCurrentConfig()
	{
		// No host specific config for current host
		if (array_key_exists($this->currentHost,$this->iniArr))
		{
			$this->currentArr = $this->iniArr[$this->currentHost];
		} else {
			// No default either
			if (array_key_exists('default',$this->iniArr))
			{
				$this->currentArr = $this->iniArr['default'];
			} else {
				// No usable config
				$this->hasError = sprintf("No [default] or [%s] found in config.ini",$this->currentHost);
			}
		}

		$this->defaultServer();

		if (! array_key_exists($this->currentArr['driver'],$this->allowedOptions))
		{
			$this->hasError = sprintf($this->currentArr['driver']." is not a valid choice. See INSTALL.TXT");
			return;
		}
   
                $keys = $this->allowedOptions[$this->currentArr['driver']];

		$diff = array_diff_key($this->currentArr,array_flip($keys));

        
                unset($diff['driver']);

  
		if (! empty($diff))
		{
			// Invalid options found
			$this->hasError = sprintf("Invalid options found in config.ini for %s driver and section [%s]: %s is not supported",
			$this->currentArr['driver'],$this->currentHost,key($diff));
		}

		return $this->currentArr;
	}


	public function __destruct()
	{
		unset($this->iniArr);
	}
}
?>