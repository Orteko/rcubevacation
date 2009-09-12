<?php
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

    // Download .forward and .vacation.message file
    public function _get() {
        $subject = $body = $forward = "";
        $keepcopy = false;

        if ($this->is_active()) {
            $dot_vacation_msg = explode("\n",$this->downloadfile($this->cfg['vacation_message']));
            $subject = str_replace('Subject: ','',$dot_vacation_msg[1]);
            $body = join("\n",array_slice($dot_vacation_msg,2));
            $dotForwardFile = $this->downloadfile(".forward");
            $d = new DotForward();
            $options = $d->parse($dotForwardFile);
            $forward = $options['forward'];
            $keepcopy = $options['keepcopy'];
            $enabled = $options['enabled'];
        }
        
        return array("enabled"=>$enabled, "subject"=>$subject, "body"=>$body,"forward"=>$forward,"keepcopy"=>$keepcopy);
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

    // Cleans up files

    private function disable() {
        $this->deletefiles(array(".forward",$this->cfg['vacation_message'],$this->cfg['vacation_database']));
        return true;
    }

	/*
	 * @return boolean True if both .vacation.msg and .forward exist, false otherwise
	*/
    private function is_active() {
        return (ftp_size($this->ftp,".forward") > 0);
    }

    // Delete files when disabling vacation
    private function deletefiles(array $remoteFiles) {
        foreach ($remoteFiles as $file)
        {
             
            if (ftp_size($this->ftp, $file) > 0)
            {
                ftp_delete($this->ftp, $file);
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
?>