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
            return sprintf("%s%s%s",$this->options['keepcopy'],$this->options['username'],$this->options['forward']);

        }


       
    }

    
    public function parse($dotForward) {
        $this->options['keepcopy'] = (substr($dotForward,0,1)=='\\');

        
        $dotForward =  str_replace(array("|","\"","\\"),"",$dotForward);
        $arr = explode(",",trim($dotForward));
        $this->options['username'] = array_shift($arr);

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