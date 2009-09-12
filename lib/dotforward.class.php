<?php

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
?>