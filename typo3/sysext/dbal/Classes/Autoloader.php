<?php
namespace TYPO3\CMS\Dbal;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Xavier Perseguers <xavier@typo3.org>
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
 * Autoloader included from Install Tool that lets DBAL load itself
 * if it makes sense.
 *
 * @author Xavier Perseguers <xavier@typo3.org>
 */
class Autoloader {

	/**
	 * Activates DBAL if it is supported.
	 *
	 * @param \TYPO3\CMS\Install\Installer $instObj
	 * @return void
	 */
	public function execute(\TYPO3\CMS\Install\Installer $instObj) {
		if ($instObj->mode == '123') {
			switch ($instObj->step) {
			case 1:
				if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('dbal') && $this->isDbalSupported()) {
					$this->activateDbal();
					// Reload page to have Install Tool actually load DBAL
					$redirectUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
					\TYPO3\CMS\Core\Utility\HttpUtility::redirect($redirectUrl);
				}
				break;
			case 2:
				if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('dbal') && $this->isDbalSupported()) {
					$this->activateDbal();
				}
				break;
			case 3:
				$driver = $instObj->INSTALL['Database']['typo_db_driver'];
				if ($driver === 'mysql') {
					$this->deactivateDbal();
				}
				break;
			}
		}
	}

	/**
	 * Returns TRUE if PHP modules to run DBAL are loaded.
	 *
	 * @return boolean
	 */
	protected function isDbalSupported() {
		return extension_loaded('odbc') || extension_loaded('pdo') || extension_loaded('oci8');
	}

	/**
	 * Activates DBAL.
	 *
	 * @return void
	 */
	protected function activateDbal() {
		if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('adodb')) {
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::loadExtension('adodb');
		}
		if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('dbal')) {
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::loadExtension('dbal');
		}
	}

	/**
	 * Dectivates DBAL.
	 *
	 * @return void
	 */
	protected function deactivateDbal() {
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('dbal')) {
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::unloadExtension('dbal');
		}
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('adodb')) {
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::unloadExtension('adodb');
		}
	}

}


?>