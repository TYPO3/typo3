<?php
/***************************************************************
*  Copyright notice
*
*  (c) Marcus Krause (marcus#exp2009@t3sec.info)
*  (c) Steffen Ritter (info@rs-websystems.de)
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
 * Contains class "tx_saltedpasswords_div"
 * that provides various helper functions.
 */

/**
 * General library class.
 *
 * @author      Marcus Krause <marcus#exp2009@t3sec.info>
 * @author		Steffen Ritter <info@rs-websystems.de>
 *
 * @since       2009-06-14
 * @package     TYPO3
 * @subpackage  tx_saltedpasswords
 */
class tx_saltedpasswords_div {
		/**
		 * Keeps this extension's key.
		 */
		const EXTKEY = 'saltedpasswords';


		/**
		 * Returns extension configuration data from $TYPO3_CONF_VARS (configurable in Extension Manager)
		 *
		 * @author  Rainer Kuhn <kuhn@punkt.de>
		 * @author  Marcus Krause <marcus#exp2009@t3sec.info>
		 *
		 * @param	string		TYPO3_MODE, wether Configuration for Frontend or Backend should be delivered
		 * @return	array		extension configuration data
		 */
		public static function returnExtConf($mode = TYPO3_MODE) {
			$currentConfiguration = self::returnExtConfDefaults();

			if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['saltedpasswords'])) {
				$extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['saltedpasswords']);

					// Merge default configuration with modified configuration:
				if (isset($extensionConfiguration[$mode . '.'])) {
					$currentConfiguration = array_merge(
						$currentConfiguration,
						$extensionConfiguration[$mode . '.']
					);
				}
			}

			return $currentConfiguration;
		}

		/**
		 * Hook function for felogin "forgotPassword" functionality
		 * encrypts the new password before storing in database
		 *
		 * @param	array			$params: Parameter the hook delivers
		 * @param	tx_felogin_pi1	$pObj: Parent Object from which the hook is called
		 * @return	void
		 *
		 */
		public function feloginForgotPasswordHook(array &$params, tx_felogin_pi1 $pObj) {
			if (self::isUsageEnabled('FE')) {
				$this->objInstanceSaltedPW = tx_saltedpasswords_salts_factory::getSaltingInstance();
				$params['newPassword'] = $this->objInstanceSaltedPW->getHashedPassword($params['newPassword']);
			}
		}

		/**
		 * Returns default configuration of this extension.
		 *
		 * @return	array		default extension configuration data for localconf.php
		 */
		public static function returnExtConfDefaults() {
			return array(
				'onlyAuthService' => '0',
				'forceSalted' => '0',
				'updatePasswd' => '1',
				'saltedPWHashingMethod' => 'tx_saltedpasswords_salts_phpass',
				'enabled' => '1',
			);
		}

		/**
		 * Function determines the default(=configured) type of
		 * salted hashing method to be used.
		 *
		 * @param	string		$mode: (optional) The TYPO3 mode (FE or BE) saltedpasswords shall be used for
		 * @return	string		classname of object to be used
		 */
		public static function getDefaultSaltingHashingMethod($mode = TYPO3_MODE) {

			$extConf = self::returnExtConf($mode);
			$classNameToUse = 'tx_saltedpasswords_salts_md5';
			if (in_array($extConf['saltedPWHashingMethod'], array_keys($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/saltedpasswords']['saltMethods']))) {
				$classNameToUse = $extConf['saltedPWHashingMethod'];
			}

			return $classNameToUse;
		}

		/**
		 * Returns information if salted password hashes are
		 * indeed used in the TYPO3_MODE.
		 *
		 * @param	string		$mode: (optional) The TYPO3 mode (FE or BE) saltedpasswords shall be used for
		 * @return	boolean		TRUE, if salted password hashes are used in the TYPO3_MODE, otherwise FALSE
		 */
		public static function isUsageEnabled($mode = TYPO3_MODE) {
				// Login Security Level Recognition
			$extConf = self::returnExtConf($mode);
			$securityLevel = $GLOBALS['TYPO3_CONF_VARS'][$mode]['loginSecurityLevel'];
			if ($mode == 'BE' && $extConf['enabled']) {
				return (($securityLevel =='normal' && $GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL'] > 0) || $securityLevel == 'rsa');
			} elseif ($mode =='FE' && $extConf['enabled']) {
				return t3lib_div::inList('normal,rsa', $securityLevel);
			}

			return FALSE;
		}
}

?>