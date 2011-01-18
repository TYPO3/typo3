<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Core functions for admin
 *
 * $Id$
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */
/**
 * Core functions for administration
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_lowlevel
 */
class tx_lowlevel_admin_core extends t3lib_cli {

	var $adminModules = array(
		'setBElock' => 'Set the Backend Lock',
		'clearBElock' => 'Clears the Backend Lock',
		'msg' => 1
	);

	/**
	 * Constructor
	 *
	 * @return	void
	 */
	function tx_lowlevel_admin_core()	{

			// Running parent class constructor
		parent::t3lib_cli();

			// Adding options to help archive:
		$this->cli_options[] = array('--redirect=[URL]', 'For toolkey "setBElock": The URL to which the redirection will occur.');

			// Setting help texts:
		$this->cli_help['name'] = 'lowlevel_admin -- Various functions for administration and maintenance of TYPO3 from the command line';
		$this->cli_help['synopsis'] = 'toolkey ###OPTIONS###';
		$this->cli_help['description'] = "The 'toolkey' keywords are:\n\n  ".implode("\n  ",array_keys($this->adminModules));
		$this->cli_help['examples'] = "/.../cli_dispatch.phpsh lowlevel_admin setBElock --redirect=http://url_which_explains_why.com/";
		$this->cli_help['author'] = "Kasper Skaarhoej, (c) 2009";
	}









	/**************************
	 *
	 * CLI functionality
	 *
	 *************************/

	/**
	 * CLI engine
	 *
	 * @param	array		Command line arguments
	 * @return	string
	 */
	function cli_main($argv) {

			// Force user to admin state and set workspace to "Live":
		$GLOBALS['BE_USER']->user['admin'] = 1;
		$GLOBALS['BE_USER']->setWorkspace(0);

			// Print help
		$analysisType = (string)$this->cli_args['_DEFAULT'][1];
		if (!$analysisType)	{
			$this->cli_validateArgs();
			$this->cli_help();
			exit;
		}

			// Analysis type:
		switch((string)$analysisType)    {
			case 'setBElock':
				if (@is_file(PATH_typo3conf.'LOCK_BACKEND'))	{
					$this->cli_echo("A lockfile already exists. Overwriting it... \n");
				}
				$lockFileContent = $this->cli_argValue('--redirect');
				t3lib_div::writeFile(PATH_typo3conf.'LOCK_BACKEND', $lockFileContent);
				$this->cli_echo("Wrote lock-file to '".PATH_typo3conf."LOCK_BACKEND' with content '".$lockFileContent."'");
			break;
			case 'clearBElock':
				if (@is_file(PATH_typo3conf.'LOCK_BACKEND'))	{
					unlink(PATH_typo3conf.'LOCK_BACKEND');
					if (@is_file(PATH_typo3conf.'LOCK_BACKEND')	)	{
						$this->cli_echo("ERROR: Could not remove lock file '".PATH_typo3conf."LOCK_BACKEND'!!\n",1);
					} else {
						$this->cli_echo("Removed lock file '".PATH_typo3conf."LOCK_BACKEND'\n");
					}
				} else {
					$this->cli_echo("No lock file '".PATH_typo3conf."LOCK_BACKEND' was found; hence no lock can be removed.'\n");
				}
			break;
			default:
				$this->cli_echo("Unknown toolkey, '".$analysisType."'");
			break;
		}
		$this->cli_echo(LF);
	}


}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/lowlevel/class.tx_lowlevel_admin.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/lowlevel/class.tx_lowlevel_admin.php']);
}

?>