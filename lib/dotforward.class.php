<?php

/*
	This helper class is responsible for reading and writing the .forward file
*/

class DotForward {
    private $options = array("binary"=>"","username"=>"","flags"=>"","alias"=>"","enabled"=>false,"forward"=>null,"keepcopy"=>false);

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

        // Keep a local copy of the e-mail if we send an auto-reply
        if ($this->options['keepcopy'] == true || $this->options['binary'] != "") {
            $this->options['keepcopy'] = "\\";
        }

        // No alias support yet
        $a = null;

        // If there is no binary set, we do not send an out office reply. 
        if ($this->options['binary'] != "")
        {
             return sprintf('%s%s%s,|"%s %s %s"',$this->options['keepcopy'],$this->options['username'],
            $this->options['forward'],
         $this->options['binary'],$this->options['flags'], $a);

        } else {
            // Just set a forwarding address
            return sprintf("%s%s%s",$this->options['keepcopy'],$this->options['username'],$this->options['forward']);

        }
    }

    
    public function parse($dotForward) {

        // If the first character is a \, user wants to keep a copy
        $this->options['keepcopy'] = (substr($dotForward,0,1)=='\\');

        // Clean up the .forward file for easier parsing
        $dotForward =  str_replace(array("|","\"","\\"),"",$dotForward);
        $arr = explode(",",trim($dotForward));

        // Assumption: first element is always the username.
        $this->options['username'] = array_shift($arr);

        // Location of the vacation binary may very, so we only back for the slash
        while ($next = array_shift($arr))
        {
             if (substr($next,0,1) == '/')
            {
                // For future use like parsing the flags?
                list($this->options['binary']) = explode(" ",$next);
                $this->options['enabled'] = true;
            } else {
                
                $this->options['forward'] = $next;
            }
        }

       return $this->options;
    }
}
?>