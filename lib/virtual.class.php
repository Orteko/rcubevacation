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
        // TODO Determine domain

         

        $this->domain_id = $this->domainLookup();

        $this->createVirtualConfig($dsn);
    }



	/*
	 * @return Array Values for the form
	 */
    public function _get() {
        $subject = $body = "";
        $enabled = false;
        $fwd = $this->virtual_alias();
        $sql = sprintf("SELECT body,subject FROM %s.vacation WHERE email='%s' AND active=1",
        $this->cfg['dbase'],Q($this->user->data['username']));

        $res = $this->db->query($sql);
        if ($row = $this->db->fetch_assoc($res)) {
            $body = $row['body'];
            $subject = $row['subject'];
            $enabled = true;
        }

        return array("enabled"=>$enabled, "subject"=>$subject, "body"=>$body,"keepcopy"=>$fwd['keepcopy'],"forward"=>$fwd['forward']);
    }

	/*
	 * @return boolean True on succes, false on failure
	 */
    public function setVacation() {
        // If there is an existing entry in the vacation table, delete it.
        // This also triggers the cascading delete on the vacation_notification, but's ok for now.
        // @todo: allow update statements

        $sql = sprintf("DELETE FROM %s.vacation WHERE email='%s'",$this->cfg['dbase'],Q($this->user->data['username']));
        $this->db->query($sql);

        // Delete the alias to the vacation transport
        $sql = $this->translate($this->cfg['delete_query']);
        $this->db->query($sql);

        // (Re)enable the vacation message and the vacation transport alias
        if ($this->enable && $this->body != "" && $this->subject != "") {


        $sql = sprintf("INSERT INTO %s.vacation (email,subject,body,domain,created,active) VALUES ('%s','%s','%s','%s',NOW(),1)",
                $this->cfg['dbase'],
                Q($this->user->data['username']),
                $this->subject,
                $this->body,
                $this->domain
            );
        $this->db->query($sql);

         $sql = $this->translate($this->cfg['insert_query']);
         $this->db->query($sql);
        }
        $current = $this->_get();

        // Keep a copy of the mail

        if ($this->keepcopy != $current['keepcopy'])
        {
            if ($this->keepcopy)
            {
                $sql = str_replace('%g','%e',$this->cfg['insert_query']);
                $sql = $this->translate($sql);
                $this->db->query($sql);
            } else {
                $sql = str_replace('%g','%e',$this->cfg['delete_query']);
                $sql = $this->translate($sql);
                $this->db->query($sql);
            }
        }

        // Set a forward
        if ($this->forward != $current['forward'])
        {
            $sql = str_replace('%g',$current['forward'],$this->cfg['delete_query']);
            $sql = $this->translate($sql);
            $this->db->query($sql);
            if ($this->forward != null)
            {
                $sql = str_replace('%g','%f',$this->cfg['insert_query']);
                $sql = $this->translate($sql);
                $res = $this->db->query($sql);
            }
        }
        return true;
    }

 	/*
	 * @return boolean True if an alias to the virtual transport is found, false otherwise
	 */
    private function is_active() {
        $sql = $this->translate($this->cfg['select_query']);
        $res = $this->db->query($sql,0,0,1);
        return $this->db->num_rows($res)==1;
    }

	/*
	 * @return array SQL query with substituted parameters
	 */
    private function translate($query) {
        return str_replace(array('%e','%d','%i','%g','%f'),
        array($this->user->data['username'], $this->domain,$this->domain_id,
        Q($this->user->data['username'])."@".$this->cfg['transport'],$this->forward),$query);
    }

    private function domainLookup()
    {
        // Sets the domain
        list($username,$this->domain) = explode("@",$this->user->get_username());
        if (! empty($this->cfg['domain_lookup_query']))
        {
            $sql = $this->translate($this->cfg['domain_lookup_query']);
            $res = $this->db->query($sql);
            $row = $this->db->fetch_array($res);
            return $row['id'];
        } else {
            return $domain;
        }

    }


     /*Removes the aliases
	 *
	 * @param array dsn
	 * @return void
	 */
    private function createVirtualConfig(array $dsn) {
        $virtual_config = "/etc/postfixadmin/vacation.conf";
        if (! is_writeable($virtual_config)) {
            raise_error(array(
                'code' => 600,
                'type' => 'php',
                'file' => __FILE__,
                'message' => "Vacation plugin: Cannot save {$virtual_config} . Check permissions."
                ),true, true);
        }


        if (! file_exists($virtual_config) || (filemtime("plugins/vacation/config.inc.php") > filemtime($virtual_config))) {
            $config = sprintf("
        our \$db_username = '%s';\n
        our \$db_password = '%s';\n
        our \$db_name     = '%s';\n
        our \$vacation_domain = '%s';\n",$dsn['username'],$dsn['password'],$dsn['database'],$this->cfg['transport']);
            file_put_contents($virtual_config,$config);
        }
    }

		/*
			Retrieves the localcopy and/or forward settings.
		* @return array with virtual aliases
	 	*/
    private function virtual_alias() {
        $forward = "";
        $sql = sprintf("SELECT 1 FROM %1\$s.virtual_aliases WHERE source = '%2\$s' AND destination='%2\$s'",
            $this->cfg['dbase'], Q($this->user->data['username']));
        $res = $this->db->query($sql);
        $keepcopy = $this->db->num_rows($res)==1;

        $goto = Q($this->user->data['username'])."@".$this->cfg['transport'];
        $sql = sprintf("SELECT destination FROM %1\$s.virtual_aliases WHERE source = '%2\$s' AND destination NOT IN ('%2\$s','%2\$s@%3\$s')",
            $this->cfg['dbase'],Q($this->user->data['username']),$this->cfg['transport']);

        $res = $this->db->query($sql);

        if ($row = $this->db->fetch_assoc($res)) {
            $forward = $row['destination'];
        }

        return array("forward"=>$forward,"keepcopy"=>$keepcopy);
    }



    // Destroy the database connection of our temporary database connection
    public function __destruct() {
        if (! empty($this->cfg['dsn']) && is_resource($this->db)) {
            $this->db = null;
        }
    }
}



?>