<?php
/*
 * Vacation plugin that adds a new tab to the settings section
 * to enable forward / out of office replies.
 *
 * @package		plugins
 * @uses		rcube_plugin
 * @author		Jasper Slits <jaspersl@gmail.com>
 * @version		1.1
 * @link		https://sourceforge.net/projects/rcubevacation/
 * @todo		See README.TXT
 *
 */

class vacation extends rcube_plugin {
    public $task = 'settings';
    private $v,$cfg = "";

    public function init() {
        $this->add_texts('localization/', array('vacation'));
        $this->load_config();
        $driver = rcmail::get_instance()->config->get("driver");

        $this->v = VacationDriverFactory::create($driver);
        // Initialize the driver
        $this->v->init();

        $this->register_action('plugin.vacation', array($this, 'vacation_init'));
        $this->register_action('plugin.vacation-save', array($this, 'vacation_save'));
        $this->register_handler('plugin.vacation_form', array($this, 'vacation_form'));
        $this->include_script('vacation.js');
    }

    public function vacation_init() {
        $this->add_texts('localization/',array('vacation'));
        $rcmail = rcmail::get_instance();
        $rcmail->output->set_pagetitle($this->gettext('autoresponder'));
        // Load template
        $rcmail->output->send('vacation.vacation');
    }

    public function vacation_save() {
        $rcmail = rcmail::get_instance();

        if ($rv = $this->v->save() ) {
            $text = "success_enabled";
            $rcmail->output->show_message($this->gettext($text), 'confirmation');
        } else {
            $rcmail->output->show_message($this->gettext("failed"), 'error');
        }
        //$this->vacation_init();
    }

    public function vacation_form() {
        $rcmail = rcmail::get_instance();
        $settings = $this->v->_get();

        $rcmail->output->set_env('product_name', $rcmail->config->get('product_name'));
        $rcmail->output->set_env('framed', true);

        // TODO: find out where $attrib should originate from. Found in the hmail_autoreply plugin code?
        $attrib_str = create_attrib_string($attrib, array('style', 'class', 'id', 'cellpadding', 'cellspacing', 'border', 'summary'));

        // return the complete edit form as table
        $out .= '<fieldset><legend>' . $this->gettext('autoresponder') . ' ::: ' . $rcmail->user->data['username'] . '</legend>' . "\n";
        $out .= '<br />' . "\n";
        $out .= '<table' . $attrib_str . ">\n\n";

        // show autoresponder properties

        // Auto-reply enabled
        $field_id = 'vacation_enabled';
        $input_autoresponderexpires = new html_checkbox(array('name' => '_vacation_enabled', 'id' => $field_id, 'value' => 1));
        $out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n",
            $field_id,
            rep_specialchars_output($this->gettext('autoreply')),
            $input_autoresponderexpires->show($settings['enabled']));

        // Subject
        $field_id = 'vacation_subject';
        $input_autorespondersubject = new html_inputfield(array('name' => '_vacation_subject', 'id' => $field_id, 'size' => 50));
        $out .= sprintf("<tr><td valign=\"top\" class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n",
            $field_id,
            rep_specialchars_output($this->gettext('autoreplysubject')),
            $input_autorespondersubject->show($settings['subject']));

        // Out of office body
        $field_id = 'vacation_body';
        $input_autoresponderbody = new html_textarea(array('name' => '_vacation_body', 'id' => $field_id, 'cols' => 48, 'rows' => 15));
        $out .= sprintf("<tr><td valign=\"top\" class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n",
            $field_id,
            rep_specialchars_output($this->gettext('autoreplymessage')),
            $input_autoresponderbody->show($settings['body']));

        // Keep a local copy of the mail
        $field_id = 'vacation_keepcopy';
        $input_localcopy = new html_checkbox(array('name' => '_vacation_keepcopy', 'id' => $field_id, 'value' => 1));
        $out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n",
            $field_id,
            rep_specialchars_output($this->gettext('keepcopy')),
            $input_localcopy->show($settings['keepcopy']));

        // Forward mail to another account
        $field_id = 'vacation_forward';
        $input_autoresponderforward = new html_inputfield(array('name' => '_vacation_forward', 'id' => $field_id, 'size' => 50));
        $out .= sprintf("<tr><td valign=\"top\" class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n",
            $field_id,
            rep_specialchars_output($this->gettext('forwardingaddresses')),
            $input_autoresponderforward->show($settings['forward']));

        $out .= "\n</table>";
        $out .= '<br />' . "\n";
        $out .= "</fieldset>\n";

        $rcmail->output->add_gui_object('vacationform', 'vacation-form');
        return $out;
    }
}

/*
 * Using factory method to create an instance of the driver
 * 
 */

class VacationDriverFactory {

	/*
	 * @param string driver class to be loaded
	 * @return object specific driver 
	 */
    public static function Create( $driver ) {
        if (! class_exists($driver)) {
            raise_error(array(
                'code' => 600,
                'type' => 'php',
                'file' => __FILE__,
                'message' => "Vacation plugin: Driver {$driver} does not exist"
                ),true, true);
        }
        return new $driver;
    }
}

/*
 * Abstract base class that handles the form input
 *
 */

abstract class VacationDriver {
    protected $cfg = array();
    protected $rcmail,$user,$forward,$body,$subject = "";
    protected $enable,$keepcopy = false;

    abstract protected function enable();
    abstract protected function disable();
    abstract public function _get();
    abstract public function init();

    public function __construct() {
        $this->rcmail = rcmail::get_instance();
        $this->user = $this->rcmail->user;
        $this->identity = $this->user->get_identity();
        $this->cfg = $this->rcmail->config->get( strtolower( get_class($this) ) );
    }

	/*
	 * @return boolean True on succes, false on failure 
	 */
    final public function save() {
        $this->enable = (NULL != get_input_value('_vacation_enabled', RCUBE_INPUT_POST));
        $this->subject = get_input_value('_vacation_subject', RCUBE_INPUT_POST);
        $this->body = get_input_value('_vacation_body', RCUBE_INPUT_POST);
        $this->keepcopy = (NULL != get_input_value('_vacation_keepcopy', RCUBE_INPUT_POST));
        $this->forward = get_input_value('_vacation_forward', RCUBE_INPUT_POST);

        // Enable or disable the vacation auto-reply
        if ($this->enable) {
            return $this->enable();
        } else {
            return $this->disable();
        }
    }
}


class Virtual extends VacationDriver {
    private $db,$domain,$goto;

    public function init() {
    // Use the DSN from db.inc.php or a dedicated DSN defined in config.inc.php

        if (empty($this->cfg['dsn'])) {
            $this->db = $this->rcmail->db;
            $dsn = MDB2::parseDSN($this->rcmail->config->get('db_dsnw'));
        } else {
            $this->db = new rcube_mdb2($this->cfg['dsn'], '', FALSE);
            $this->db->db_connect('w');
            $dsn = MDB2::parseDSN($this->cfg['dsn']);
        }
        // TODO Determine domain
        $this->domain = 1;

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
    public function enable() {
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

    // In the enable method, we determine what needs to be done.
    // @todo Make it look less ugly$this->cfg['delete_query']
    public function disable() {
        $this->enable();
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
        return str_replace(array('%e','%d','%g','%f'),
        array($this->user->data['username'], $this->domain,
        Q($this->user->data['username'])."@".$this->cfg['transport'],$this->forward),$query);
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

/*
	@uses DotForward
*/
class setuid extends VacationDriver {

    public function init() {
        if (! is_executable($this->cfg['setuid_executable'])) {
            raise_error(array(
                'code' => 600,
                'type' => 'php',
                'file' => __FILE__,
                'message' => "Vacation plugin: {$this->cfg['setuid_executable']} does not exist or is not an executable"
                ),true, true);


        } else {
        // Setuid ?
            $fstat = stat($this->cfg['setuid_executable']);
            if (! $fstat['mode'] & 0004000) {
                raise_error(array(
                    'code' => 600,
                    'type' => 'php',
                    'file' => __FILE__,
                    'message' => "Vacation plugin: {$this->cfg['setuid_executable']} has no setuid bit"
                    ),true, true);

            }
        }
    }

    public function _get() {
        $subject = $body = $forward = "";
        $keepcopy = false;
        if ($enabled = $this->is_active()) {
            $dot_vacation_msg = explode("\n",$this->downloadfile($this->cfg['vacation_message']));
            $subject = str_replace('Subject: ','',$dot_vacation_msg[1]);
            $body = join("\n",array_slice($dot_vacation_msg,2));
            $d = new DotForward();
            $options = $d->parse($dotForwardFile);
            $forward = $options['forward'];
            $keepcopy = $options['keepcopy'];
        }
        return array("enabled"=>$enabled, "subject"=>$subject, "body"=>$body,"keepcopy"=>$keepcopy,"forward"=>$forward);
    }

    public function enable() {

		 /*
		 * Syntax:	squirrelmail_vacation_proxy  server user password action source destination
		 */
        $d = new DotForward;
        $d->setOption("binary",$this->cfg['vacation_executable']);
        $d->setOption("flags",$this->cfg['vacation_flags']);
        $d->setOption("username",$this->user->data['username']);
        $d->setOption("localcopy",$this->keepcopy);
        $d->setOption("forward",$this->forward);


        $this->uploadfile($message,$this->cfg['vacation_message']);
        $this->uploadfile($d->create(),".forward");
        $command = sprintf("%s localhost %s %s %s %s delete . %s",
            $this->cfg['setuid_executable'],
            Q($this->user->data['username']),
            $this->rcmail->decrypt($_SESSION['password']),$file);
        exec($command);


        return true;
    }

    public function disable() {
		/*
		 * Syntax:	squirrelmail_vacation_proxy  server user password action source destination
		 */
        $deleteFiles = array(".vacation.msg",".forward");
        foreach($deleteFiles as $file) {
            $command = sprintf("%s localhost %s %s %s %s delete . %s",
                $this->cfg['setuid_executable'],
                Q($this->user->data['username']),
                $this->rcmail->decrypt($_SESSION['password']),$file);
            exec($command);
        }

        return true;
    }

    private function is_active() {
        $command = sprintf("%s localhost %s %s %s %s list . %s",
            $this->cfg['setuid_executable'],
            Q($this->user->data['username']),
            $this->rcmail->decrypt($_SESSION['password']),$remoteFile);
        $result = 0;
        exec($command,
            $resArr,
            $result);

        return ($result == 0);
    }
     /*Removes the aliases
	 * 
	 * @param string data
	 * @param string remoteFile 
	 * @return boolean 
	 */
    private function uploadfile($data,$remoteFile) {
        $result = 0;
        $localFile = tempnam(sys_get_temp_dir(), 'Vac');
        file_put_contents($localFile,trim($data));
        $command = sprintf("%s localhost %s %s %s %s put . %s",
            $this->cfg['setuid_executable'],
            Q($this->user->data['username']),
            $this->rcmail->decrypt($_SESSION['password']),$remoteFile);
        exec($command,$resArr,$result);
        unlink($localFile);
        return $result;
    }

    private function downloadfile($remoteFile) {
        $result = 0;
        $localFile = tempnam(sys_get_temp_dir(), 'Vac');
        $command = sprintf("%s localhost %s %s %s %s get . %s",
            $this->cfg['setuid_executable'],
            Q($this->user->data['username']),
            $this->rcmail->decrypt($_SESSION['password']),$remoteFile);
        exec($command,$resArr,$result);

        $content = file_get_contents($localFile);
        unlink($localFile);
        return $content;
    }

}


/*
	FTP class
	@uses DotForward
*/

class FTP extends VacationDriver {
    private $ftp = false;

    public function init() {
        $username = Q($this->user->data['username']);
        $userpass = $this->rcmail->decrypt($_SESSION['password']);

        // 15 second time-out
        if (! $this->ftp = ftp_connect($this->cfg['server'],21,15)) {
            raise_error(array(
                'code' => 600,
                'type' => 'php',
                'file' => __FILE__,
                'message' => "Vacation plugin: Cannot connect to the FTP-server {$this->cfg['server']}"
                ),true, true);

        }

        // Supress error here
        if (! @ftp_login($this->ftp, $username,$userpass)) {
            raise_error(array(
                'code' => 600,
                'type' => 'php',
                'file' => __FILE__,
                'message' => "Vacation plugin: Cannot login to FTP-server {$this->cfg['server']} using {$username}"
                ),true, true);
        }
        $username = $userpass = null;

        // Enable passive mode
        if ($this->cfg['passive'] && !ftp_pasv($this->ftp, TRUE)) {
            raise_error(array(
                'code' => 600,
                'type' => 'php',
                'file' => __FILE__,
                'message' => "Vacation plugin: Cannot enable PASV mode on {$this->cfg['server']}"
                ),true, true);
        }
    }


    public function _get() {
        $subject = $body = $forward = "";
        $keepcopy = false;

        if ($enabled = $this->is_active()) {
            $dot_vacation_msg = explode("\n",$this->downloadfile($this->cfg['vacation_message']));
            $subject = str_replace('Subject: ','',$dot_vacation_msg[1]);
            $body = join("\n",array_slice($dot_vacation_msg,2));
            $dotForwardFile = $this->downloadfile(".forward");
            $d = new DotForward();
            $options = $d->parse($dotForwardFile);
            $forward = $options['forward'];
            $keepcopy = $options['keepcopy'];
        }
        return array("enabled"=>$enabled, "subject"=>$subject, "body"=>$body,"forward"=>$forward,"keepcopy"=>$keepcopy);
    }

    protected function enable() {
    // Sample .forward file:
    //  \eric, "|/usr/bin/vacation -a allman eric"

        $d = new DotForward;
        $d->setOption("binary",$this->cfg['vacation_executable']);
        $d->setOption("flags",$this->cfg['vacation_flags']);
        $d->setOption("username",$this->user->data['username']);
        $d->setOption("localcopy",$this->keepcopy);
        $d->setOption("forward",$this->forward);

        $email = $this->identity['email'];
        $full_name = $this->identity['name'];

        if (!empty($full_name)) {
            $vacation_header = sprintf("From: %s <%s>\n",$full_name,$email);
        } else {
            $vacation_header = sprintf("From: %s\n",$email);
        }
        $vacation_header .= sprintf("Subject: %s\n\n",$this->subject);
        $message = $vacation_header.$this->body;
        $this->uploadfile($message,$this->cfg['vacation_message']);
        $this->uploadfile($d->create(),".forward");
        return true;
    }

    protected function disable() {
        $this->deletefiles(array(".forward",$this->cfg['vacation_message'],$this->cfg['vacation_database']));
        return true;
    }

	/*
	 * @return boolean True if both .vacation.msg and .forward exist, false otherwise 
	*/
    private function is_active() {
        return (ftp_size($this->ftp, $this->cfg['vacation_message']) > 0 && ftp_size($this->ftp,".forward") > 0);
    }

    // Delete files when disabling vacation
    private function deletefiles(array $remoteFiles) {
        foreach ($remoteFiles as $file) {
            if (ftp_size($this->ftp, $file) == 0 || !ftp_delete($this->ftp, $file)) {
                return false;
            }
        }

        return true;
    }

    private function uploadfile($data,$remoteFile) {
        $localFile = tempnam(sys_get_temp_dir(), 'Vac');
        file_put_contents($localFile,trim($data));
        $result = ftp_put($this->ftp, $remoteFile, $localFile, FTP_ASCII);
        unlink($localFile);
        return $result;
    }

    private function downloadfile($remoteFile) {

        $localFile = tempnam(sys_get_temp_dir(), 'Vac');
        if (! ftp_get($this->ftp,$localFile,$remoteFile,FTP_ASCII)) {
            unlink($localFile);
            return false;
        }
        $content = file_get_contents($localFile);
        unlink($localFile);
        return trim($content);
    }



    public function __destruct() {
        if (is_resource($this->ftp)) {
            ftp_close($this->ftp);
        }
    }

}

/*
	This helper class is responsible for reading and writing the .forward file
*/

class DotForward {
    private $options = array("binary"=>"/usr/bin/vacation","username"=>"","flags"=>"","alias"=>"","forward"=>null,"localcopy"=>false);

    // set options to be used with create()
    public function setOption($key,$value) {
        $this->options[$key] = $value;
    }

    // Creates the content for the .forward file
    public function create() {
    //
        if ($this->options['forward'] != null && $this->options['forward'] != "") {
            $this->options['forward'] = ",".$this->options['forward'];
        }

        // Keep a local copy of the e-mail
        if ($this->options['localcopy'] == true) {
            $this->options['localcopy'] = "\\";
        }

        // No alias support yet
        $a = null;
        return sprintf('%s%s%s |"%s %s %s"',$this->options['localcopy'],$this->options['username'],
        $this->options['forward'],
        $this->options['binary'],$this->options['flags'], $a);
    }

		/* TODO: rewrite me*/
    public function parse($dotForward) {
        $dotForward = str_replace("\"","",$dotForward);
        $excludeArr = array("a","t","1","|","|".$this->options['binary']);

        $this->options['localcopy'] = (substr($dotForward,0,1)=="\\");

        $tokenArr = array();
        $tok = strtok($dotForward," -\\|,");
        while ($tok !== false) {
            $tokenArr[] = trim($tok);
            $tok = strtok(" -\\,");
        }

        while ($element = array_shift($tokenArr)) {

            if ($this->options['username']=='') {
                $this->options['username'] = $element;
            } else {
                if ($this->options['forward']=='' && $element != "|".$this->options['binary']) {

                    $this->options['forward'] = $element;
                    break;
                } else {
                    break;
                }
            }
        }
        return $this->options;
    }
}
