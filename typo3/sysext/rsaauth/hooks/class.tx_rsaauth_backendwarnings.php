<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2011 Dmitry Dulepov <dmitry@typo3.org>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * $Id$
 */

require_once(t3lib_extMgm::extPath('rsaauth', 'sv1/backends/class.tx_rsaauth_backendfactory.php'));

/**
 * This class contains a hook to the backend warnings collection. It checks
 * RSA configuration and create a warning if the configuration is wrong.
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package	TYPO3
 * @subpackage	tx_rsaauth
 */

class tx_rsaauth_backendwarnings {

	/**
	 * Checks RSA configuration and creates warnings if necessary.
	 *
	 * @param	array	$warnings	Warnings
	 * @return	void
	 * @see	t3lib_BEfunc::displayWarningMessages()
	 */
	public function displayWarningMessages_postProcess(array &$warnings) {
		$backend = tx_rsaauth_backendfactory::getBackend();
		if ($backend instanceof tx_rsaauth_cmdline_backend) {

			// Not using the PHP extension!
			$warnings['rsaauth_cmdline'] = $GLOBALS['LANG']->sL('LLL:EXT:rsaauth/hooks/locallang.xml:hook_using_cmdline');

			// Check the path
			$extconf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['rsaauth']);
			$path = trim($extconf['temporaryDirectory']);
			if ($path == '') {
				// Path is empty
				$warnings['rsaauth'] = $GLOBALS['LANG']->sL('LLL:EXT:rsaauth/hooks/locallang.xml:hook_empty_directory');
			}
			elseif (!t3lib_div::isAbsPath($path)) {
				// Path is not absolute
				$warnings['rsaauth'] = $GLOBALS['LANG']->sL('LLL:EXT:rsaauth/hooks/locallang.xml:hook_directory_not_absolute');
			}
			elseif (!@is_dir($path)) {
				// Path does not represent a directory
				$warnings['rsaauth'] = $GLOBALS['LANG']->sL('LLL:EXT:rsaauth/hooks/locallang.xml:hook_directory_not_exist');
			}
			elseif (!@is_writable($path)) {
				// Directory is not writable
				$warnings['rsaauth'] = $GLOBALS['LANG']->sL('LLL:EXT:rsaauth/hooks/locallang.xml:hook_directory_not_writable');
			}
			elseif (substr($path, 0, strlen(PATH_site)) == PATH_site) {
				// Directory is inside the site root
				$warnings['rsaauth'] = $GLOBALS['LANG']->sL('LLL:EXT:rsaauth/hooks/locallang.xml:hook_directory_inside_siteroot');
			}
		}
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rsaauth/hooks/class.tx_rsaauth_backendwarnings.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rsaauth/hooks/class.tx_rsaauth_backendwarnings.php']);
}

?>