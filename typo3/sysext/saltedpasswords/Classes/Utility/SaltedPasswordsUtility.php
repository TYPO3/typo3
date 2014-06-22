<?php
namespace TYPO3\CMS\Saltedpasswords\Utility;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * General library class.
 *
 * @author Marcus Krause <marcus#exp2009@t3sec.info>
 * @author Steffen Ritter <info@rs-websystems.de>
 */
class SaltedPasswordsUtility {

	/**
	 * Keeps this extension's key.
	 */
	const EXTKEY = 'saltedpasswords';

	/**
	 * Calculates number of backend users, who have no saltedpasswords
	 * protection.
	 *
	 * @return integer
	 */
	static public function getNumberOfBackendUsersWithInsecurePassword() {
		$userCount = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
			'*',
			'be_users',
			'password != ""'
				. ' AND password NOT LIKE ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('$%', 'be_users')
				. ' AND password NOT LIKE ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('M$%', 'be_users')
		);
		return $userCount;
	}

	/**
	 * Returns extension configuration data from $TYPO3_CONF_VARS (configurable in Extension Manager)
	 *
	 * @author Rainer Kuhn <kuhn@punkt.de>
	 * @author Marcus Krause <marcus#exp2009@t3sec.info>
	 * @param string $mode TYPO3_MODE, whether Configuration for Frontend or Backend should be delivered
	 * @return array Extension configuration data
	 */
	static public function returnExtConf($mode = TYPO3_MODE) {
		$currentConfiguration = self::returnExtConfDefaults();
		if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['saltedpasswords'])) {
			$extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['saltedpasswords']);
			// Merge default configuration with modified configuration:
			if (isset($extensionConfiguration[$mode . '.'])) {
				$currentConfiguration = array_merge($currentConfiguration, $extensionConfiguration[$mode . '.']);
			}
		}
		return $currentConfiguration;
	}

	/**
	 * Hook function for felogin "forgotPassword" functionality
	 * encrypts the new password before storing in database
	 *
	 * @param array $params Parameter the hook delivers
	 * @param \TYPO3\CMS\Felogin\Controller\FrontendLoginController $pObj Parent Object from which the hook is called
	 * @return void
	 */
	public function feloginForgotPasswordHook(array &$params, \TYPO3\CMS\Felogin\Controller\FrontendLoginController $pObj) {
		if (self::isUsageEnabled('FE')) {
			$objInstanceSaltedPW = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance();
			$params['newPassword'] = $objInstanceSaltedPW->getHashedPassword($params['newPassword']);
		}
	}

	/**
	 * Returns default configuration of this extension.
	 *
	 * @return array Default extension configuration data for localconf.php
	 */
	static public function returnExtConfDefaults() {
		return array(
			'onlyAuthService' => '0',
			'forceSalted' => '0',
			'updatePasswd' => '1',
			'saltedPWHashingMethod' => 'TYPO3\\CMS\\Saltedpasswords\\Salt\\PhpassSalt',
			'enabled' => '1'
		);
	}

	/**
	 * Function determines the default(=configured) type of
	 * salted hashing method to be used.
	 *
	 * @param string $mode (optional) The TYPO3 mode (FE or BE) saltedpasswords shall be used for
	 * @return string Classname of object to be used
	 */
	static public function getDefaultSaltingHashingMethod($mode = TYPO3_MODE) {
		$extConf = self::returnExtConf($mode);
		$classNameToUse = 'TYPO3\\CMS\\Saltedpasswords\\Salt\\Md5Salt';
		if (in_array($extConf['saltedPWHashingMethod'], array_keys(\TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getRegisteredSaltedHashingMethods()))) {
			$classNameToUse = $extConf['saltedPWHashingMethod'];
		}
		return $classNameToUse;
	}

	/**
	 * Returns information if salted password hashes are
	 * indeed used in the TYPO3_MODE.
	 *
	 * @param string $mode (optional) The TYPO3 mode (FE or BE) saltedpasswords shall be used for
	 * @return boolean TRUE, if salted password hashes are used in the TYPO3_MODE, otherwise FALSE
	 */
	static public function isUsageEnabled($mode = TYPO3_MODE) {
		// Login Security Level Recognition
		$extConf = self::returnExtConf($mode);
		$securityLevel = $GLOBALS['TYPO3_CONF_VARS'][$mode]['loginSecurityLevel'];
		if ($mode == 'BE') {
			return TRUE;
		} elseif ($mode == 'FE' && $extConf['enabled']) {
			return \TYPO3\CMS\Core\Utility\GeneralUtility::inList('normal,rsa', $securityLevel);
		}
		return FALSE;
	}

}
