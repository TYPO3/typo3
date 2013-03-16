<?php
namespace TYPO3\CMS\Rsaauth;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Dmitry Dulepov <dmitry@typo3.org>
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
 * This class contains a hook to the backend warnings collection. It checks
 * RSA configuration and create a warning if the configuration is wrong.
 *
 * @author Dmitry Dulepov <dmitry@typo3.org>
 */
class BackendWarnings {

	/**
	 * Checks RSA configuration and creates warnings if necessary.
	 *
	 * @param array $warnings Warnings
	 * @return void
	 * @see 	\TYPO3\CMS\Backend\Utility\BackendUtility::displayWarningMessages()
	 */
	public function displayWarningMessages_postProcess(array &$warnings) {
		$backend = \TYPO3\CMS\Rsaauth\Backend\BackendFactory::getBackend();
		if ($backend instanceof \TYPO3\CMS\Rsaauth\Backend\CommandLineBackend) {
			// Not using the PHP extension!
			$warnings['rsaauth_cmdline'] = $GLOBALS['LANG']->sL('LLL:EXT:rsaauth/hooks/locallang.xml:hook_using_cmdline');
			// Check the path
			$extconf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['rsaauth']);
			$path = trim($extconf['temporaryDirectory']);
			if ($path == '') {
				// Path is empty
				$warnings['rsaauth'] = $GLOBALS['LANG']->sL('LLL:EXT:rsaauth/hooks/locallang.xml:hook_empty_directory');
			} elseif (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($path)) {
				// Path is not absolute
				$warnings['rsaauth'] = $GLOBALS['LANG']->sL('LLL:EXT:rsaauth/hooks/locallang.xml:hook_directory_not_absolute');
			} elseif (!@is_dir($path)) {
				// Path does not represent a directory
				$warnings['rsaauth'] = $GLOBALS['LANG']->sL('LLL:EXT:rsaauth/hooks/locallang.xml:hook_directory_not_exist');
			} elseif (!@is_writable($path)) {
				// Directory is not writable
				$warnings['rsaauth'] = $GLOBALS['LANG']->sL('LLL:EXT:rsaauth/hooks/locallang.xml:hook_directory_not_writable');
			} elseif (substr($path, 0, strlen(PATH_site)) == PATH_site) {
				// Directory is inside the site root
				$warnings['rsaauth'] = $GLOBALS['LANG']->sL('LLL:EXT:rsaauth/hooks/locallang.xml:hook_directory_inside_siteroot');
			}
		}
	}

}


?>