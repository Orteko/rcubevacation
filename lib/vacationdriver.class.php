<?php
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
}?>