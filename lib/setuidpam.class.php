<?php
/*
	@uses DotForward
*/
class setuidpam extends VacationDriver {

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


    // Download .forward and .vacation.message file
    public function _get() {
        $vacArr = array("subject"=>"", "body"=>"","forward"=>"","keepcopy"=>false,"enabled"=>false);

        if ($vacation_msg = $this->downloadfile($this->cfg['vacation_message'])) {
            $dot_vacation_msg = explode("\n",$vacation_msg);
            $vacArr['subject'] = str_replace('Subject: ','',$dot_vacation_msg[1]);
            $vacArr['body'] = join("\n",array_slice($dot_vacation_msg,2));
        }
        if ($dotForwardFile = $this->downloadfile(".forward"))
        {
            $d = new DotForward();
            $vacArr = array_merge($vacArr,$d->parse($dotForwardFile,$this->identity['email']));
        }
        return $vacArr;

    }

 protected function setVacation() {

        // Remove existing vacation files
        $this->disable();

        $d = new DotForward;
        // Enable auto-reply?
        if ($this->enable) {
            $d->setOption("binary",$this->cfg['vacation_executable']);
            $d->setOption("flags",$this->cfg['vacation_flags']);


            // Create the .vacation.message file
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

        }
        $d->setOption("username",$this->user->data['username']);
        $d->setOption("keepcopy",$this->keepcopy);
        $d->setOption("forward",$this->forward);

        // Do we even need to upload a .forward file?
        if ($this->keepcopy || $this->enable || $this->forward != "")
        {
       
             $this->uploadfile($d->create(),".forward");
        }
        return true;

    }
    private function proxy($param, $src, $dst = '') {
            if (!empty($dst)) $dst = ' ' . escapeshellarg($dst);
            
            $cmd = escapeshellarg($this->cfg['setuid_executable']) . ' ' . '127.0.0.1' . ' '
                 . escapeshellarg(Q($this->user->data['username'])) . ' ' . " '".$param."' "
                 . escapeshellarg($src) . $dst;

            if (!($SUID = popen($cmd, 'w')))
            {
                 raise_error(array(
                    'code' => 600,
                    'type' => 'php',
                    'file' => __FILE__,
                    'message' => "Vacation plugin: Unable to establish connection to {$this->cfg['setuid_executable']}"
                    ),true, true);
            }

            if (fwrite($SUID, $this->rcmail->decrypt($_SESSION['password']) . "\n") === FALSE)
            {
                plcose($SUID);
                 raise_error(array(
                    'code' => 600,
                    'type' => 'php',
                    'file' => __FILE__,
                    'message' => "Vacation plugin: Unable to send setuidpam password"
                    ),true, true);
               
            }
           plcose($SUID);
           return true;
    }

    
    private function disable() {
		/*
		 * Syntax:	squirrelmail_vacation_proxy  server user password action source destination
		 */
        $deleteFiles = array(".vacation.msg",".vacation.db",".forward");
        if (isset($this->cfg['always_keep_message']) && $this->cfg['always_keep_message'])
	{
		unset($deleteFiles[0]);
	}

        foreach($deleteFiles as $file) {
            $this->proxy('delete', $file);
        }

        return true;
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
        
        $result = $this->proxy('put', $localFile,$remoteFile);
        unlink($localFile);
        return $result;
    }

    private function downloadfile($remoteFile) {
        $result = 0;
        $localFile = tempnam(sys_get_temp_dir(), 'Vac');
        $result = $this->proxy('get', $remoteFile,$localFile);
        
        if ($result )
        {
            $content = file_get_contents($localFile);
        } else {
            $content = false;
        }
        unlink($localFile);
        return $content;
    }

}
?>