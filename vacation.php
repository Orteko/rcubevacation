<?php
/*
 * Vacation plugin that adds a new tab to the settings section
 * to enable forward / out of office replies.
 *
 * @package	plugins
 * @uses	rcube_plugin
 * @author	Jasper Slits <jaspersl@gmail.com>
 * @version	1.5
 * @license     GPL
 * @link	https://sourceforge.net/projects/rcubevacation/
 * @todo	See README.TXT
 *
 */


// Load available drivers.
require 'lib/vacationdriver.class.php';
require 'lib/ftp.class.php';
require 'lib/dotforward.class.php';
require 'lib/setuid.class.php';
require 'lib/virtual.class.php';

class vacation extends rcube_plugin {
    public $task = 'settings';
    private $v = "";

    public function init() {
        $this->add_texts('localization/', array('vacation'));
        $this->load_config();
        $driver = rcmail::get_instance()->config->get("driver");

        $this->register_action('plugin.vacation', array($this, 'vacation_init'));
        $this->register_action('plugin.vacation-save', array($this, 'vacation_save'));
        $this->register_handler('plugin.vacation_form', array($this, 'vacation_form'));
          $this->include_script('vacation.js');

        $this->v = VacationDriverFactory::create( $driver );
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
        
          // Initialize the driver
        $this->v->init();
       
        if ( $this->v->save() ) {
           $rcmail->output->show_message($this->gettext("success_changed"), 'confirmation');
        } else {
           $rcmail->output->show_message($this->gettext("failed"), 'error');
        }

        $this->vacation_init();
    }

    public function vacation_form() {
        $rcmail = rcmail::get_instance();

      
        // Initialize the driver
        $this->v->init();
        $settings = $this->v->_get();

        $rcmail->output->add_script("var settings_account=true;");  

        $rcmail->output->set_env('product_name', $rcmail->config->get('product_name'));
   

        // TODO: find out where $attrib should originate from. Found in the hmail_autoreply plugin code?
        $attrib_str = create_attrib_string($attrib, array('style', 'class', 'id', 'cellpadding', 'cellspacing', 'border', 'summary'));
        
        // return the complete edit form as table
        $out = '<fieldset><legend>' . $this->gettext('outofoffice') . ' ::: ' . $rcmail->user->data['username'] . '</legend>' . "\n";
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

        $out .= "\n</table>
                    </fieldset>";

        // Information on the forward in a seperate fieldset.
        $out.='<fieldset><legend>' . $this->gettext('forward') . '</legend>' . "\n";
        $out .= '<br />' . "\n";
        $out .= '<table' . $attrib_str . ">\n\n";

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

?>