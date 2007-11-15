<?php
/**
 * 
 */
class tx_install_module_php	extends tx_install_module_base	{

	/**
	 * Returns the result from phpinfo()
	 * 
	 * @return	String
	 */
	public function getPHPInfo()	{
		ob_start();
		phpinfo();
		$contents = explode('<body>',ob_get_contents());
		ob_end_clean();
		$contents = explode('</body>',$contents[1]);
		
		return $contents[0];
	}
	
	/**
	 * Checks wether the installed PHP version is ok (>= 5.2)
	 * 
	 * @return	Boolean
	 */
	public function checkVersion()	{
		$phpVersion = phpversion();	
		if (t3lib_div::int_from_ver($phpVersion) < PHP_minVersion)	{
			$this->addError(sprintf($this->get_LL('module_php_error_phpversion'), $phpVersion), FATAL);
			return false;
		}
		return true;
	}
}

?>
