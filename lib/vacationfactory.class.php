<?
/*
 * Using factory method to create an instance of the driver
 * 
 */

class VacationDriverFactory {
	
	public function __construct()
	{
		die("Cannot instantiate this class");		
	}

	/*
	 * @param string driver class to be loaded
	 * @return object specific driver */
    public static function Create( $driver ) {
        $driver = strtolower($driver);
		$driverclass = sprintf("plugins/vacation/lib/%s.class.php",$driver);
		
        if (! is_readable($driverclass)) {
            raise_error(array('code' => 601,'type' => 'php','file' => __FILE__,
                'message' => sprintf("Vacation plugin: Driver %s cannot be loaded using %s",$driver,$driverclass)
                ),true, true);
        }
        
		require $driverclass;
        return new $driver;
    }
}?>