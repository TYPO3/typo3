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

require_once(t3lib_extMgm::extPath('sv') . 'class.tx_sv_auth.php');
require_once(t3lib_extMgm::extPath('rsaauth') . 'sv1/backends/class.tx_rsaauth_backendfactory.php');
require_once(t3lib_extMgm::extPath('rsaauth') . 'sv1/storage/class.tx_rsaauth_storagefactory.php');

// Include backends

/**
 * Service "RSA authentication" for the "rsaauth" extension. This service will
 * authenticate a user using hos password encoded with one time public key. It
 * uses the standard TYPO3 service to do all dirty work. Firsts, it will decode
 * the password and then pass it to the parent service ('sv'). This ensures that it
 * always works, even if other TYPO3 internals change.
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package	TYPO3
 * @subpackage	tx_rsaauth
 */
class tx_rsaauth_sv1 extends tx_sv_auth  {

	/**
	 * An RSA backend.
	 *
	 * @var	tx_rsaauth_abstract_backend
	 */
	protected	$backend = NULL;

	/**
	 * Standard extension key for the service
	 *
	 * @var	string
	 */
	public	$extKey = 'rsaauth';	// The extension key.

	/**
	 * Standard prefix id for the service
	 *
	 * @var	string
	 */
	public	$prefixId = 'tx_rsaauth_sv1';		// Same as class name

	/**
	 * Standard relative path for the service
	 *
	 * @var	string
	 */
	public	$scriptRelPath = 'sv1/class.tx_rsaauth_sv1.php';	// Path to this script relative to the extension dir.

	/**
	 * Authenticates a user. The function decrypts the password, runs evaluations
	 * on it and passes to the parent authentication service.
	 *
	 * @param	array	$userRecord	User record
	 * @return	int		Code that shows if user is really authenticated.
	 * @see	t3lib_userAuth::checkAuthentication()
	 */
	public function authUser(array $userRecord) {
		$result = 100;

		if ($this->pObj->security_level == 'rsa') {

			$storage = tx_rsaauth_storagefactory::getStorage();
			/* @var $storage tx_rsaauth_abstract_storage */

			// Set failure status by default
			$result = -1;

			// Preprocess the password
			$password = $this->login['uident'];
			$key = $storage->get();
			if ($key != NULL && substr($password, 0, 4) == 'rsa:') {
				// Decode password and pass to parent
				$decryptedPassword = $this->backend->decrypt($key, substr($password, 4));
				if ($decryptedPassword != NULL) {
					// Run the password through the eval function
					$decryptedPassword = $this->runPasswordEvaluations($decryptedPassword);
					if ($decryptedPassword != NULL) {
						$this->login['uident'] = $decryptedPassword;
						if (parent::authUser($userRecord)) {
							$result = 200;
						}
					}
				}
				// Reset the password to its original value
				$this->login['uident'] = $password;
				// Remove the key
				$storage->put(NULL);
			}
		}
		return $result;
	}

	/**
	 * Initializes the service.
	 *
	 * @return	boolean
	 */
	public function init()	{
		$available = parent::init();
		if ($available) {
			// Get the backend
			$this->backend = tx_rsaauth_backendfactory::getBackend();
			if (is_null($this->backend)) {
				$available = FALSE;
			}
		}

		return $available;
	}

	/**
	 * Runs password evaluations. This is necessary because other extensions can
	 * modify the way the password is stored in the database. We check for all
	 * evaluations for the password column and run those.
	 *
	 * Notes:
	 * - we call t3lib_TCEmain::checkValue_input_Eval() but it is risky: if a hook
	 *   relies on BE_USER, it will fail. No hook should do this, so we risk it.
	 * - we cannot use t3lib_TCEmain::checkValue_input_Eval() for running all
	 *   evaluations because it does not create md5 hashes.
	 *
	 * @param	string	$password	Evaluated password
	 * @return	void
	 * @see	t3lib_TCEmain::checkValue_input_Eval()
	 */
	protected function runPasswordEvaluations($password) {
		$table = $this->pObj->user_table;
		t3lib_div::loadTCA($table);
		$conf = &$GLOBALS['TCA'][$table]['columns'][$this->pObj->userident_column]['config'];
		$evaluations = $conf['eval'];
		if ($evaluations) {
			$tce = NULL;
			foreach (t3lib_div::trimExplode(',', $evaluations, TRUE) as $evaluation) {
				switch ($evaluation) {
					case 'md5':
						$password = md5($password);
						break;
					case 'upper':
						// We do not pass this to TCEmain because TCEmain will use objects unavailable in FE
						$csConvObj = (TYPO3_MODE == 'BE' ? $GLOBALS['LANG']->csConvObj : $GLOBALS['TSFE']->csConvObj);
						$charset = (TYPO3_MODE == 'BE' ? $GLOBALS['LANG']->charSet : $GLOBALS['TSFE']->metaCharset);
						$password = $csConvObj->conv_case($charset, $password, 'toUpper');
						break;
					case 'lower':
						// We do not pass this to TCEmain because TCEmain will use objects unavailable in FE
						$csConvObj = (TYPO3_MODE == 'BE' ? $GLOBALS['LANG']->csConvObj : $GLOBALS['TSFE']->csConvObj);
						$charset = (TYPO3_MODE == 'BE' ? $GLOBALS['LANG']->charSet : $GLOBALS['TSFE']->metaCharset);
						$password = $csConvObj->conv_case($charset, $password, 'toLower');
						break;
					case 'password':
					case 'required':
						// Do nothing!
						break;
					default:
						// We must run these evaluations through TCEmain to avoid
						// code duplication and ensure that any custom evaluations
						// are called in a proper context
						if ($tce == NULL) {
							/* @var $tce t3lib_TCEmain */
							$tce = t3lib_div::makeInstance('t3lib_TCEmain');
						}
						$result = $tce->checkValue_input_Eval($password, array($evaluation), $conf['is_in']);
						if (!isset($result['value'])) {
							// Failure!!!
							return NULL;
						}
						$password = $result['value'];
				}
			}
		}
		return $password;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rsaauth/sv1/class.tx_rsaauth_sv1.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rsaauth/sv1/class.tx_rsaauth_sv1.php']);
}

?>