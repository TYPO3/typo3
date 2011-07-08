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

require_once(t3lib_extMgm::extPath('rsaauth', 'sv1/backends/class.tx_rsaauth_abstract_backend.php'));

/**
 * This class contains a factory for the RSA backends.
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package	TYPO3
 * @subpackage	tx_rsaauth
 */
class tx_rsaauth_backendfactory {

	/**
	 * A list of all available backends. Currently this list cannot be extended.
	 * This is for security reasons to avoid inserting some dummy backend to
	 * the list.
	 *
	 * @var	array
	 */
	static protected $availableBackends = array(
		'EXT:rsaauth/sv1/backends/class.tx_rsaauth_php_backend.php:tx_rsaauth_php_backend',
		'EXT:rsaauth/sv1/backends/class.tx_rsaauth_cmdline_backend.php:tx_rsaauth_cmdline_backend'
	);

	/**
	 * A flag that tells if the factory is initialized. This is to prevent
	 * continious creation of backends in case if none of them is available.
	 *
	 * @var	boolean
	 */
	static protected $initialized = FALSE;

	/**
	 * A selected backend. This member is set in the getBackend() function. It
	 * will not be an abstract backend as shown below but a real class, which is
	 * derieved from the tx_rsaauth_abstract_backend.
	 *
	 * <!-- Please, keep the variable type! It helps IDEs to provide autocomple! -->
	 *
	 * @var	tx_rsaauth_abstract_backend
	 */
	static protected $selectedBackend = NULL;

	/**
	 * Obtains a backend. This function will return a non-abstract class, which
	 * is derieved from the tx_rsaauth_abstract_backend. Applications should
	 * not use anoy methods that are not declared in the tx_rsaauth_abstract_backend.
	 *
	 * @return	tx_rsaauth_abstract_backend	A backend
	 */
	static public function getBackend() {
		if (!self::$initialized) {
			// Backend does not exist yet. Create it.
			foreach (self::$availableBackends as $backend) {
				$backendObject = t3lib_div::getUserObj($backend);
				// Check that it is derieved from the proper base class
				if ($backendObject instanceof tx_rsaauth_abstract_backend) {
					/* @var $backendObject tx_rsaauth_abstract_backend */
					if ($backendObject->isAvailable()) {
						// The backend is available, save it and stop the loop
						self::$selectedBackend = $backendObject;
						self::$initialized = TRUE;
						break;
					}
					// Attempt to force destruction of the object
					unset($backend);
				}
			}
		}
		return self::$selectedBackend;
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rsaauth/sv1/backends/class.tx_rsaauth_backendfactory.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rsaauth/sv1/backends/class.tx_rsaauth_backendfactory.php']);
}

?>