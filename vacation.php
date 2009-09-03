<?php
/**
 * Vacation plugin that adds a new tab to the settings section
 * to enable vacation message
 *
 *  Copyright (c) 2009 Peter Ruiter <peter@peterruiter.com>
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * @package plugins
 *
 */


class VacationBackendFactory {
	
	public static function Create( $backend )
	{
		if (! class_exists($backend))
		{
			 raise_error(array(
			'code' => 600,
			'type' => 'php',
			'file' => __FILE__,
			'message' => "Vacation plugin: Backend {$backend} does not exist"
			),true, true);

		}
  		return new $backend;
	}
}

// Creating/parsing a .forward file is the same for FTP and setuid backends share code
class DotForward
{
        private $options = array("binary"=>"/usr/bin/vacation","username"=>"","flags"=>"","alias"=>"","forward"=>null,"localcopy"=>false);

        public function setOption($key,$value)
        {
                $this->options[$key] = $value;
        }

        public function getContent()
        {

                if ($this->options['forward'] != null)
                {
                    $this->options['forward'] = ",".$this->options['forward'];
                }
                $a = null;
                return sprintf('\%s%s |"%s %s %s"',$this->options['username'],
                                $this->options['forward'],
                                $this->options['binary'],$this->options['flags'], $a);
        }

        public function parse($dotForward)
        {
                $dotForward = str_replace("\"","",$dotForward);
                $excludeArr = array("a","t","1","|","|".$this->options['binary']);

                $this->options['localcopy'] = (substr($dotForward,0,1)=="\\");

                                $tokenArr = array();
                                $tok = strtok($dotForward," -\\|,");
                                while ($tok !== false) {
                                    $tokenArr[] = trim($tok);
                                    $tok = strtok(" -\\,");
                                }

                                while ($element = array_shift($tokenArr))
				{

                                    if ($this->options['username']=='') {
                                        $this->options['username'] = $element;
                                    } else {
                                        if ($this->options['forward']=='' && $element != "|".$this->options['binary'])
                                        {
                                          
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


abstract class VacationBackend
{
	protected $config = array();
	protected $rcmail,$user,$enable,$forward,$body,$subject,$keepcopy = "";
	
	abstract protected function enable();
	abstract protected function disable(); 
	abstract public function _get(); 
	abstract public function init(); 
	
	public function loadConfig(array $config)
	{
		// Load configuration based on the chosen backend
		$this->config = $config[$config['backend']];
		$this->init();
	}

	
	final public function save()
	{
		
		$this->enable = (NULL != get_input_value('_vacation_enabled', RCUBE_INPUT_POST));
		$this->subject = get_input_value('_vacation_subject', RCUBE_INPUT_POST);
	    $this->body = get_input_value('_vacation_body', RCUBE_INPUT_POST);
		$this->keepcopy = get_input_value('_vacation_keepcopy', RCUBE_INPUT_POST);
		$this->forward = get_input_value('_vacation_forward', RCUBE_INPUT_POST);

		// Enable or disable the vacation auto-reply
		if ($this->enable)
		{
			return $this->enable();
		} else {
			return $this->disable();
			
		}


	}
	
	public function __construct()
	{
		$this->rcmail = rcmail::get_instance();
		$this->user = $this->rcmail->user;
		$this->identity = $this->user->get_identity();
	}
}

/*
	setuid class. 
	TODO: implement downloadfile and uploadfile using the squirrel proxy

*/

class Virtual extends VacationBackend
{
	private $db,$domain,$goto;

	private function is_active()
	{
		$rv = $this->_get();
		return $rv['enabled'];
	}

	// Substitute parameters
	private function translate($query)
	{
		return str_replace(array('%e','%d','%g'),
			array($this->user->data['username'], $this->domain,
			Q($this->user->data['username']).$this->config['transport'],$this->domain),$query);
	}

	public function enable()
	{
			if (! $this->is_active()) {

			$sql = sprintf("INSERT INTO vacation (email,subject,body,domain) VALUES ('%s','%s','%s','%s')",
				Q($this->user->data['username']),
				$this->subject,
				$this->body,
				$this->domain
			);
			// Make alias
			$res = $this->db->query($sql);
			$sql = $this->translate($this->config['insert_query']);
			$res = $this->rcmail->db->query($sql);
		}
		return true;
	}

	public function disable()
	{
		$sql = sprintf("DELETE FROM vacation WHERE email='%s'",Q($this->user->data['username']));
		$res = $this->db->query($sql);
		$sql = $this->translate($this->config['delete_query']);
		$res = $this->rcmail->db->query($sql);
		return true;
	}

	public function _get()
	{
		$subject = $body = "";
		$enabled = false;
		$sql = sprintf("SELECT * FROM vacation WHERE email='%s' AND active=1",Q($this->user->data['username']));
		$res = $this->db->query($sql);

		if ($row = $this->db->fetch_assoc($res))
		{
			$body = $row['body'];
			$subject = $row['subject'];
			$enabled = true; 
		}

		return array("enabled"=>$enabled, "subject"=>$subject, "body"=>$body);
	}

	public function init()
	{
	  // $res = $this->rcmail->->db points to the configured dsn in config/db.inc.php
      $this->db = new rcube_mdb2($this->config['dsn'], '', FALSE);
//      $this->db->set_debug((bool)$this->rcmail->config->get('sql_debug'));
      $this->db->db_connect('r');
	  // TODO
	  $this->domain = 'dummy';

	}

	public function __destruct()
	{
		if (is_resource($this->db))
			$this->db = null;
	}
}

class setuid extends VacationBackend
{

	private function is_active()
	{
		$command = sprintf("%s localhost %s %s %s %s list . %s",
				$this->config['setuid_executable'],
				Q($this->user->data['username']),
				$this->rcmail->decrypt($_SESSION['password']),$remoteFile);
				$result = 0;
				exec($command,
					$resArr,
					$result);

		return ($result == 0);
	}

	private function uploadfile($data,$remoteFile)
	{
		$result = 0;
		$localFile = tempnam(sys_get_temp_dir(), 'Vac');
		file_put_contents($localFile,trim($data));
				$command = sprintf("%s localhost %s %s %s %s put . %s",
				$this->config['setuid_executable'],
				Q($this->user->data['username']),
				$this->rcmail->decrypt($_SESSION['password']),$remoteFile);
				exec($command,$resArr,$result);
		unlink($localFile);
		return $result;
	}

	private function downloadfile($remoteFile)
	{
		$result = 0;
		$localFile = tempnam(sys_get_temp_dir(), 'Vac');
		$command = sprintf("%s localhost %s %s %s %s get . %s",
				$this->config['setuid_executable'],
				Q($this->user->data['username']),
				$this->rcmail->decrypt($_SESSION['password']),$remoteFile);
		exec($command,$resArr,$result);
		
		$content = file_get_contents($localFile);
		unlink($localFile);
		return $content;
	}

	
	

	public function _get()
	{
		if ($this->is_active())
		{
			$dot_vacation_msg = explode("\n",$this->downloadfile($this->config['vacation_message']));
			$subject = str_replace('Subject: ','',$dot_vacation_msg[1]);
			$body = join("\n",array_slice($dot_vacation_msg,2));
			return array("enabled"=>false, "subject"=>$subject, "body"=>$body);
		} else {
			return array("enabled"=>false, "subject"=>"", "body"=>"");
		}
	}		
		
	public function init()
	{
		return true;

		if (! is_executable($this->config['setuid_executable']))
		{
			 raise_error(array(
        'code' => 600,
        'type' => 'php',
        'file' => __FILE__,
        'message' => "Vacation plugin: {$this->config['setuid_executable']} does not exist or is not an executable"
        ),true, true);


		} else {
			// Setuid ?
			$fstat = stat($this->config['setuid_executable']);
			if (! $fstat['mode'] & 0004000)
			{
				 raise_error(array(
        'code' => 600,
        'type' => 'php',
        'file' => __FILE__,
        'message' => "Vacation plugin: {$this->config['setuid_executable']} has no setuid bit"
        ),true, true);
			
			}
		}
		return true;
	}

	public function enable()
	{
	
		 /*
		 * Syntax:	squirrelmail_vacation_proxy  server user password action source destination
		 */
		


		$command = sprintf("%s localhost %s %s %s %s delete . %s",
				$this->config['setuid_executable'],
				Q($this->user->data['username']),
				$this->rcmail->decrypt($_SESSION['password']),$file);
			exec($command);


			return true;
	}

	public function disable()
	{
		/*
		 * Syntax:	squirrelmail_vacation_proxy  server user password action source destination
		 */
		 $deleteFiles = array(".vacation.msg",".forward");
		 foreach($deleteFiles as $file) {
			$command = sprintf("%s localhost %s %s %s %s delete . %s",
				$this->config['setuid_executable'],
				Q($this->user->data['username']),
				$this->rcmail->decrypt($_SESSION['password']),$file);
			exec($command);
		 }
		
		return true;
	}
}


/*
	FTP class

*/

class FTP extends VacationBackend
{
	private $ftp = false;

	
	private function is_active()
	{
		return ftp_size($this->ftp, $this->config['vacation_message']) && ftp_size($this->ftp,".forward") > 0;
	}
	
	public function _get()
	{
		if ($this->is_active())
		{
			/*  .vacation.msg contains this if it exists: 
				From: user@domain.org\n
				Subject: I am away\n
				\n
				Body tekst
			*/
			$dot_vacation_msg = explode("\n",$this->downloadfile($this->config['vacation_message']));
			$subject = str_replace('Subject: ','',$dot_vacation_msg[1]);
			$body = join("\n",array_slice($dot_vacation_msg,2));
                        $dotForwardFile = $this->downloadfile(".forward");
                        $d = new DotForward();
                        $options = $d->parse($dotForwardFile);
                    
			return array("enabled"=>true, "subject"=>$subject, "body"=>$body,"forward"=>$options['forward'],"keepcopy"=>$options['localcopy']);
			
		} else {
			return array("enabled"=>false, "subject"=>"", "body"=>"","keepcopy"=>false,"forward"=>"");
		}
		
	}
	
	// Delete files when disabling vacation
	private function deletefiles(array $remoteFiles)
	{
         foreach ($remoteFiles as $file)
         {
               if (ftp_size($this->ftp, $file) == 0 || !ftp_delete($this->ftp, $file))
               {
				   return false;
		       }
         }
         
		return true;
	}

	private function uploadfile($data,$remoteFile)
	{
		$localFile = tempnam(sys_get_temp_dir(), 'Vac');
		file_put_contents($localFile,trim($data));
		$result = ftp_put($this->ftp, $remoteFile, $localFile, FTP_ASCII);
		unlink($localFile);
		return $result;
	}

	private function downloadfile($remoteFile)
	{

		$localFile = tempnam(sys_get_temp_dir(), 'Vac');
		if (! ftp_get($this->ftp,$localFile,$remoteFile,FTP_ASCII))
		{
			unlink($localFile);
			return false;
		}
		$content = file_get_contents($localFile);
		unlink($localFile);
		return $content;
	}
	
	public function init()
	{
		$username = Q($this->user->data['username']);
		$userpass = $this->rcmail->decrypt($_SESSION['password']);
	
		if (! $this->ftp = ftp_connect($this->config['server'],21,15))
		{
			 raise_error(array(
			'code' => 600,
			'type' => 'php',
			'file' => __FILE__,
			'message' => "Vacation plugin: Cannot connect to the FTP-server {$this->config['server']}"
			),true, true);
			
		}
	
		// 
		if (! @ftp_login($this->ftp, $username,$userpass))
		{
				 raise_error(array(
				'code' => 600,
				'type' => 'php',
				'file' => __FILE__,
				'message' => "Vacation plugin: Cannot login to FTP-server {$this->config['server']} using {$username}"
				),true, true);
			
		}
		if ($this->config['passive'] && !ftp_pasv($this->ftp, TRUE))
		{
			raise_error(array(
			'code' => 600,
			'type' => 'php',
			'file' => __FILE__,
			'message' => "Vacation plugin: Cannot set PASV setting on {$this->config['server']}"
			),true, true);
		}
	}



	protected function enable()
	{
		// Sample .forward file: 
		//  \eric, "|/usr/bin/vacation -a allman eric"

                $d = new DotForward;
                $d->setOption("binary",$this->config['vacation_executable']);
                $d->setOption("flags",$this->config['vacation_flags']);
                $d->setOption("username",$this->user->data['username']);
                $d->setOption("localcopy",$this->keepcopy);
                $d->setOption("forward",$this->forward);

		$dot_forward = $d->getContent();
		$email = $this->identity['email'];
		$full_name = $this->identity['name'];

		if (!empty($full_name))
		   $vacation_header = sprintf("From: %s <%s>\n",$full_name,$email);
		else
		   $vacation_header = sprintf("From: %s\n",$email);
		$vacation_header .= sprintf("Subject: %s\n\n",$this->subject);
		$message = $vacation_header.$this->body;
		$this->uploadfile($message,$this->config['vacation_message']);
		$this->uploadfile($dot_forward,".forward");
		return true;
	}

	protected function disable()
	{
		$this->deletefiles(array(".forward",$this->config['vacation_message'],$this->config['vacation_database']));
		return true;
	}
	
	public function __destruct()
	{
		if (is_resource($this->ftp))
		{
			ftp_close($this->ftp);
		}
	}
	
}

	
class vacation extends rcube_plugin
{
  public $task = 'settings';
  private $v,$config = "";
  
  public function init()
  {
		$this->add_texts('localization/', array('vacation'));
		require_once("config.php");
		$this->config = $config;
		$this->v = VacationBackendFactory::create($this->config['backend']);
		$this->v->loadConfig($this->config);

		$this->register_action('plugin.vacation', array($this, 'vacation_init'));
		$this->register_action('plugin.vacation-save', array($this, 'vacation_save'));
		$this->register_handler('plugin.vacation_form', array($this, 'vacation_form'));
		$this->include_script('vacation.js');
	
  }

        public function vacation_init()
	{
		$this->add_texts('localization/');
		$rcmail = rcmail::get_instance();
		$rcmail->output->set_pagetitle($this->gettext('autoresponder')); 
		// Load template
		$rcmail->output->send('vacation.vacation');
	}

	public function vacation_save()
	{
		$rcmail = rcmail::get_instance();
               
		if ($rv = $this->v->save() )
		{
			$rcmail->output->command('display_message', "Vacation succesfully changed", 'confirmation');
		} else {
			$rcmail->output->command('display_message', "Error occured", 'error');
		}
		// Call vacation_init because it initialize the plugin.
                // TODO : If we do omit the init() we get the display_message but not the active tab
		// $this->vacation_init();
	}

	public function vacation_form()
	{
		$rcmail = rcmail::get_instance();
	//	$rcmail->output->add_script("var settings_account=true;");  
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

		$field_id = 'vacation_enabled';
		$input_autoresponderexpires = new html_checkbox(array('name' => '_vacation_enabled', 'id' => $field_id, 'value' => 1));

		$out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n",
                $field_id,
                rep_specialchars_output($this->gettext('autoreply')),
                $input_autoresponderexpires->show($settings['enabled']));

	    $field_id = 'vacation_subject';
		$input_autorespondersubject = new html_textarea(array('name' => '_vacation_subject', 'id' => $field_id, 'cols' => 48, 'rows' => 2));

		$out .= sprintf("<tr><td valign=\"top\" class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n",
                $field_id,
                rep_specialchars_output($this->gettext('autoreplysubject')),
                $input_autorespondersubject->show($settings['subject']));

		$field_id = 'vacation_body';
		$input_autoresponderbody = new html_textarea(array('name' => '_vacation_body', 'id' => $field_id, 'cols' => 48, 'rows' => 15));

		$out .= sprintf("<tr><td valign=\"top\" class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n",
                $field_id,
                rep_specialchars_output($this->gettext('autoreplymessage')),
                $input_autoresponderbody->show($settings['body']));
			$field_id = 'vacation_keepcopy';
			$input_autoresponderexpires = new html_checkbox(array('name' => '_vacation_keepcopy', 'id' => $field_id, 'value' => 1));

			$out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n",
	                $field_id,
	                rep_specialchars_output($this->gettext('keepcopy')),
	                $input_autoresponderexpires->show($settings['keepcopy']));
			$field_id = 'vacation_forward';
			$input_autoresponderforward = new html_textarea(array('name' => '_vacation_forward', 'id' => $field_id, 'cols' => 48, 'rows' => 1));

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