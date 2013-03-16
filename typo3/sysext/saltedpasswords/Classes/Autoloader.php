<?php
namespace TYPO3\CMS\Saltedpasswords;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Helmut Hummel <helmut.hummel@typo3.org>
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
 * Autoloader included from Install Tool that lets saltedpasswords load itself
 *
 * @author Helmut Hummel <helmut.hummel@typo3.org>
 */
class Autoloader {

	/**
	 * Activates saltedpasswords if it is supported.
	 *
	 * @param \TYPO3\CMS\Install\Installer $instObj
	 * @return void
	 */
	public function execute(\TYPO3\CMS\Install\Installer $instObj) {
		switch ($instObj->step) {
		case 4:
			if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('saltedpasswords') && $this->isSaltedPasswordsSupported()) {
				$this->activateSaltedPasswords();
			}
			break;
		}
	}

	/**
	 * Checks whether the OpenSSL PHP extension is working properly.
	 *
	 * Before automatically enabling saltedpasswords, we check for a working OpenSSL PHP extension. As we enable rsaauth
	 * in the process of automatically enabling saltedpasswords, working OpenSSL is a requirement for this.
	 * Availability of the command line openssl binary is not checked here, thus saltedpasswords is NOT enabled
	 * automatically in this case.
	 *
	 * @return boolean TRUE, in case of OpenSSL works and requirements for saltedpasswords are met.
	 * @see tx_rsaauth_php_backend
	 */
	protected function isSaltedPasswordsSupported() {
		$isSupported = FALSE;
		if (is_callable('openssl_pkey_new')) {
			$testKey = @openssl_pkey_new();
			if (is_resource($testKey)) {
				openssl_free_key($testKey);
				$isSupported = TRUE;
			}
		}
		return $isSupported;
	}

	/**
	 * Activates saltedpasswords.
	 *
	 * @return void
	 */
	protected function activateSaltedPasswords() {
		if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('rsaauth')) {
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::loadExtension('rsaauth');
		}
		if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('saltedpasswords')) {
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::loadExtension('saltedpasswords');
		}
		\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager')->setLocalConfigurationValueByPath('EXT/extConf/saltedpasswords', 'a:2:{s:3:"FE.";a:2:{s:7:"enabled";s:1:"1";s:21:"saltedPWHashingMethod";s:28:"tx_saltedpasswords_salts_md5";}s:3:"BE.";a:2:{s:7:"enabled";s:1:"1";s:21:"saltedPWHashingMethod";s:28:"tx_saltedpasswords_salts_md5";}}');
		\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager')->setLocalConfigurationValueByPath('BE/loginSecurityLevel', 'rsa');
		\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager')->setLocalConfigurationValueByPath('FE/loginSecurityLevel', 'rsa');
	}

}


?>