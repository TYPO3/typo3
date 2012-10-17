<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Xavier Perseguers <xavier@typo3.org>
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
 *
 * @package TYPO3
 * @subpackage dbal
 */
class tx_dbal_autoloader {

	/**
	 * Activates DBAL if it is supported.
	 *
	 * @param tx_install $instObj
	 * @return void
	 */
	public function execute(tx_install $instObj) {
		if ($instObj->mode == '123') {
			switch ($instObj->step) {
				case 1:
					if (!t3lib_extMgm::isLoaded('dbal') && $this->isDbalSupported()) {
						$this->activateDbal();

						// Reload page to have Install Tool actually load DBAL
						$redirectUrl = t3lib_div::getIndpEnv('TYPO3_REQUEST_URL');
						t3lib_utility_Http::redirect($redirectUrl);
					}
					break;
				case 2:
					if (!t3lib_extMgm::isLoaded('dbal') && $this->isDbalSupported()) {
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
		return extension_loaded('odbc')
				|| extension_loaded('pdo')
				|| extension_loaded('oci8');
	}

	/**
	 * Activates DBAL.
	 *
	 * @return void
	 */
	protected function activateDbal() {
		if (!t3lib_extMgm::isLoaded('adodb')) {
			t3lib_extMgm::loadExtension('adodb');
		}
		if (!t3lib_extMgm::isLoaded('dbal')) {
			t3lib_extMgm::loadExtension('dbal');
		}
	}

	/**
	 * Dectivates DBAL.
	 *
	 * @return void
	 */
	protected function deactivateDbal() {
		if (t3lib_extMgm::isLoaded('dbal')) {
			t3lib_extMgm::unloadExtension('dbal');
		}
		if (t3lib_extMgm::isLoaded('adodb')) {
			t3lib_extMgm::unloadExtension('adodb');
		}
	}
}

// Make instance:
$SOBE = t3lib_div::makeInstance('tx_dbal_autoloader');
$SOBE->execute($this);
?>