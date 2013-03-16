<?php
namespace TYPO3\CMS\Rsaauth\Backend;

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
 * This class contains a factory for the RSA backends.
 *
 * @author Dmitry Dulepov <dmitry@typo3.org>
 */
class BackendFactory {

	/**
	 * A list of all available backends. Currently this list cannot be extended.
	 * This is for security reasons to avoid inserting some dummy backend to
	 * the list.
	 *
	 * @var array
	 */
	static protected $availableBackends = array(
		'EXT:rsaauth/sv1/backends/class.tx_rsaauth_php_backend.php:TYPO3\\CMS\\Rsaauth\\Backend\\PhpBackend',
		'EXT:rsaauth/sv1/backends/class.tx_rsaauth_cmdline_backend.php:TYPO3\\CMS\\Rsaauth\\Backend\\CommandLineBackend'
	);

	/**
	 * A flag that tells if the factory is initialized. This is to prevent
	 * continious creation of backends in case if none of them is available.
	 *
	 * @var boolean
	 */
	static protected $initialized = FALSE;

	/**
	 * A selected backend. This member is set in the getBackend() function. It
	 * will not be an abstract backend as shown below but a real class, which is
	 * derieved from the tx_rsaauth_abstract_backend.
	 *
	 * <!-- Please, keep the variable type! It helps IDEs to provide autocomple! -->
	 *
	 * @var \TYPO3\CMS\Rsaauth\Backend\AbstractBackend
	 */
	static protected $selectedBackend = NULL;

	/**
	 * Obtains a backend. This function will return a non-abstract class, which
	 * is derieved from the tx_rsaauth_abstract_backend. Applications should
	 * not use any methods that are not declared in the tx_rsaauth_abstract_backend.
	 *
	 * @return \TYPO3\CMS\Rsaauth\Backend\AbstractBackend A backend
	 */
	static public function getBackend() {
		if (!self::$initialized) {
			// Backend does not exist yet. Create it.
			foreach (self::$availableBackends as $backend) {
				$backendObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($backend);
				// Check that it is derieved from the proper base class
				if ($backendObject instanceof \TYPO3\CMS\Rsaauth\Backend\AbstractBackend) {
					/** @var $backendObject \TYPO3\CMS\Rsaauth\Backend\AbstractBackend */
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


?>