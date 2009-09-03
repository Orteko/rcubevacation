<?php
/*
	Mockup classes that simulate rcmail
*/

error_reporting(E_ALL);

const RCUBE_INPUT_POST = 1;
require 'config.php';
require 'vacation.php';
chdir('../../program');
//require './lib/PEAR.php';
require './lib/MDB2.php';
require './include/rcube_mdb2.php';




// Your password goes here
$_SESSION['password'] = '';

function raise_error($array,$log,$terminate)
{
	print_r($array);
	if ($terminate) { exit(1);}
}

function Q($str)
{
	return $str;
}



class User
{
    // Your username goes here
	public $data = array("username"=>"jasper");

	public function get_identity()
	{
		return array("jasper");
	}
}

function get_input_value($param,$const)
{
	$paramArr = array('_vacation_enabled'=>true,'_vacation_body'=>'AA','_vacation_subject'=>'AA','_vacation_keepcopy'=>null,'_vacation_forward'=>'jaspersl@gmail.com');
	return $paramArr[$param];
}

class config
{
	public function get($iets)
	{
		return $iets;
	}
}


class rcmail
{
	public $user,$config,$db = '';

	public function __construct()
	{
		$this->user = new User;
		$this->config = new Config;
		$this->db = null;
	}

	public static function get_instance()
	{
		return new rcmail;
	}

	public function decrypt($pw)
	{
		return $pw;
	}
}

// Dummy class 
class rcube_plugin
{

}


$v = VacationBackendFactory::create('ftp');
$v->loadConfig($config);
print_r($v->_get());
//$v->save();
?>
