<?php

/*
	This helper class is responsible for reading and writing the .forward file
*/

class DotForward {
    private $options = array("binary"=>"",
    							"username"=>"",
    							"flags"=>"",
    							"aliases"=>"",
    							"enabled"=>false,
    							"forward"=>null,
    							"envelop_sender"=>null,
    							"keepcopy"=>true);

    // set options to be used with create()
    public function setOption($key,$value) {
        $this->options[$key] = $value;
    }
    
    public function mergeOptions(array $cfgArr) 
    {
    	$this->options = array_merge($this->options,$cfgArr);
    }

    // Creates the content for the .forward file
    public function create() {
        if ($this->options['forward'] != null && $this->options['forward'] != "") {
            $this->options['forward'] = ",".$this->options['forward'];
        }

        // Keep a local copy of the e-mail if we send an auto-reply
        if ($this->options['keepcopy'] == true || $this->options['binary'] != "") {
            $this->options['keepcopy'] = "\\";
        }
        
        // Create aliases
        if ($this->options['aliases'] != null)
        {
        	// Strip leading and trailing slashes. Convert colons to spaces
        	$this->options['aliases'] = str_replace(","," ",$this->options['aliases']);
        	$aliases = explode(" ",trim($this->options['aliases']));
        	
        	// Creates -a alias1 -a alias2
        	foreach($aliases as $alias)
        	{
        		$this->options['flags'].= " -a ".$alias;
        	}
        }
        
        // If the vacation binary supports -R for envelop sender, use this. 
		// TODO: support for Debian (-z)
        if ($this->options['envelop_sender'] != null) {
        	$this->options['flags'].= " -R ".$this->options['envelop_sender'];
        }        

        // If there is no binary set, we do not send an out office reply. 
        if ($this->options['binary'] != "")
        {
             return sprintf('%s%s%s,|"%s %s %s"',$this->options['keepcopy'],$this->options['username'],
            $this->options['forward'],
         $this->options['binary'],$this->options['flags'], $this->options['username']);

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

        // Location of the vacation binary may very, so we only look for the slash
        
        // Check for aliases
        $aliasArr = array();
        while ($tmp = strstr( $dotForward ,"-a"))
        {
        	$tmpArr = explode(" ",$tmp);
        	array_shift($tmpArr);
        	$aliasArr[] = array_shift($tmpArr);
      		$dotForward = join(" ",$tmpArr);
        } 
        // Join the elements
     	$this->options["aliases"] = trim(join(",",$aliasArr));
        
        while ($next = array_shift($arr))
        {
             if (substr($next,0,1) == '/')
            {
                // For future use like parsing the flags?
                list($this->options['binary']) = explode(" ",$next);

				// Checkbox will be checked
                $this->options['enabled'] = true;
            } else {
                
                $this->options['forward'] = $next;
            }
        }

       return $this->options;
    }
}
?>