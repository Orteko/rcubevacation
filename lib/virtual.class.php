<?php

class Virtual extends VacationDriver {
    private $db,$domain,$domain_id,$goto = "";

    public function init() {
    // Use the DSN from db.inc.php or a dedicated DSN defined in config.inc.php

        if (empty($this->cfg['dsn'])) {
            $this->db = $this->rcmail->db;
            $dsn = MDB2::parseDSN($this->rcmail->config->get('db_dsnw'));
        } else {
            $this->db = new rcube_mdb2($this->cfg['dsn'], '', FALSE);
            $this->db->db_connect('w');
            $this->db->set_debug((bool)$this->rcmail->config->get('sql_debug'));
            $dsn = MDB2::parseDSN($this->cfg['dsn']);
        }

        $this->createVirtualConfig($dsn);
    }



	/*
	 * @return Array Values for the form
	 */
    public function _get() {
        $vacArr = array("subject"=>"", "body"=>"");

        $fwdArr = $this->virtual_alias();
        
        $sql = sprintf("SELECT subject,body FROM %s.vacation WHERE email='%s' AND active=1",
        $this->cfg['dbase'],Q($this->user->data['username']));

        $res = $this->db->query($sql);
        if ($row = $this->db->fetch_assoc($res)) {
            $vacArr['body'] = $row['body'];
            $vacArr['subject'] = $row['subject'];
            $enabled = true;
        }

        return array_merge($fwdArr, $vacArr );
    }

	/*
	 * @return boolean True on succes, false on failure
	 */
    public function setVacation() {
        // If there is an existing entry in the vacation table, delete it.
        // This also triggers the cascading delete on the vacation_notification, but's ok for now.

        // We store since version 1.6 all data into one row. 
        $aliasArr = array();

		// Sets class property
        $this->domain_id = $this->domainLookup();

        $sql = sprintf("DELETE FROM %s.vacation WHERE email='%s'",$this->cfg['dbase'],Q($this->user->data['username']));

        $this->db->query($sql);

        // Delete the alias to the vacation transport
        $sql = $this->translate($this->cfg['delete_query']);
        $this->db->query($sql);

        // (Re)enable the vacation message and the vacation transport alias
        if ($this->enable && $this->body != "" && $this->subject != "") {
            $sql = "INSERT INTO {$this->cfg['dbase']}.vacation (email,subject,body,domain,created,active) VALUES (?,?,?,?,NOW(),1)";
            $this->db->query($sql, Q($this->user->data['username']), $this->subject, $this->body, $this->domain);

            $aliasArr[] = '%g';
        }
      

        // Keep a copy of the mail if explicitly asked for or when using vacation
	$always = (isset($this->cfg['always_keep_copy']) && $this->cfg['always_keep_copy']);
        if ($this->keepcopy || in_array('%g',$aliasArr) || $always )
        {
            $aliasArr[] = '%e';
        }

        // Set a forward
        if ($this->forward != null)
        {
            $aliasArr[] = '%f';
        }

        // Aliases are re-created if $sqlArr is not empty.
        $sql = $this->translate($this->cfg['delete_query']);
        $this->db->query($sql);

        // One row to store all aliases
        if (! empty($aliasArr))
        {
            
            $alias = join(",",$aliasArr);
            $sql = str_replace('%g',$alias,$this->cfg['insert_query']);
            $sql = $this->translate($sql);
            
            $this->db->query($sql);
        }
        return true;
    }

	/*
	 * @return string SQL query with substituted parameters
	 */
    private function translate($query) {
        return str_replace(array('%e','%d','%i','%g','%f','%m'),
        array($this->user->data['username'], $this->domain,$this->domain_id,
        Q($this->user->data['username'])."@".$this->cfg['transport'],$this->forward,$this->cfg['dbase']),$query);
    }

    // Sets %i. Lookup the domain_id based on the domainname. Returns the domainname if the query is empty
    private function domainLookup()
    {
        // Sets the domain
        list($username,$this->domain) = explode("@",$this->user->get_username());
        if (! empty($this->cfg['domain_lookup_query']))
        {
            $sql = $this->translate($this->cfg['domain_lookup_query']);
            $res = $this->db->query($sql);
            $row = $this->db->fetch_array($res);
            return $row[0];
        } else {
            return $this->domain;
        }

    }


     /*Creates configuration file for vacation.pl
	 *
	 * @param array dsn
	 * @return void
	 */
    private function createVirtualConfig(array $dsn) {

        $virtual_config = "/etc/postfixadmin/";
        if (! is_writeable($virtual_config)) {
            raise_error(array(
                'code' => 600,
                'type' => 'php',
                'file' => __FILE__,
                'message' => "Vacation plugin: Cannot save {$virtual_config}/vacation.conf . Check permissions."
                ),true, true);
        }

        $virtual_config.="vacation.conf";
		// Only recreate vacation.conf if config.inc.php has been modified since
        if (! file_exists($virtual_config) || (filemtime("plugins/vacation/config.inc.php") > filemtime($virtual_config))) {
            $config = sprintf("
        our \$db_username = '%s';
        our \$db_password = '%s';
        our \$db_name     = '%s';
        our \$vacation_domain = '%s';",$dsn['username'],$dsn['password'],$this->cfg['dbase'],$this->cfg['transport']);
            file_put_contents($virtual_config,$config);
        }
    }

		/*
			Retrieves the localcopy and/or forward settings.
		* @return array with virtual aliases
	 	*/
    private function virtual_alias() {
        $forward = "";
        $enabled = false;
        $goto = Q($this->user->data['username'])."@".$this->cfg['transport'];

		// Backwards compatiblity. Since >=1.6 this is no longer needed
		$sql= str_replace("='%g'","<>''",$this->cfg['select_query']);
        $sql = $this->translate($sql);

        $res = $this->db->query($sql);

        $rows = array();

        while (list($row) = $this->db->fetch_array($res))
        {
       
            // Postfix accepts multiple aliases on 1 row as well as an alias per row
            if (strpos($row,",") !== false)
            {
               $rows = explode(",",$row);
            
            } else {
               $rows[] = $row;
            }
        }

  

        foreach($rows as $row)
        {
            // Source = destination means keep a local copy
            if ($row == $this->user->data['username'])
            {
                $keepcopy = true;
            } else {
                // Neither keepcopy or postfix transport means it's an a forward address
                if ($row == $goto)
                {
                    $enabled = true;
                } else {
                    // Support multi forwarding addresses
                    $forward .= $row.",";
                }
            }

      }
        // Substr removes any trailing comma
        return array("forward"=>substr($forward,0,-1),"keepcopy"=>$keepcopy,"enabled"=>$enabled);
    }



    // Destroy the database connection of our temporary database connection
    public function __destruct() {
        if (! empty($this->cfg['dsn']) && is_resource($this->db)) {
            $this->db = null;
        }
    }
}



?>