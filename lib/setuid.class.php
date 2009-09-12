<?php
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
?>