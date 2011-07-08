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

require_once(t3lib_extMgm::extPath('rsaauth', 'sv1/storage/class.tx_rsaauth_abstract_storage.php'));

/**
 * This class contains a factory for the RSA backends.
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package	TYPO3
 * @subpackage	tx_rsaauth
 */
class tx_rsaauth_storagefactory {

	/**
	 * A list of all available storages. Currently this list cannot be extended.
	 * This is for security reasons to avoid inserting some dummy storage to
	 * the list.
	 *
	 * @var	string
	 */
	static protected $preferredStorage = 'EXT:rsaauth/sv1/storage/class.tx_rsaauth_split_storage.php:tx_rsaauth_split_storage';

	/**
	 * An instance of the storage. This member is set in the getStorage() function.
	 * It will not be an abstract storage as shown below but a real class, which is
	 * derieved from the tx_rsaauth_abstract_storage.
	 *
	 * <!-- Please, keep the variable type! It helps IDEs to provide autocomple! -->
	 *
	 * @var	tx_rsaauth_abstract_storage
	 */
	static protected $storageInstance = NULL;

	/**
	 * Obtains a storage. This function will return a non-abstract class, which
	 * is derieved from the tx_rsaauth_abstract_storage. Applications should
	 * not use anoy methods that are not declared in the tx_rsaauth_abstract_storage.
	 *
	 * @return	tx_rsaauth_abstract_storage	A storage
	 */
	static public function getStorage() {
		if (is_null(self::$storageInstance)) {
			self::$storageInstance = t3lib_div::getUserObj(self::$preferredStorage);
		}
		return self::$storageInstance;
	}

	/**
	 * Sets the preffered storage to the factory. This method can be called from
	 * another extension or ext_localconf.php
	 *
	 * @param	string	$preferredStorage	Preffered storage
	 * @return	void
	 */
	static public function setPreferredStorage($preferredStorage) {
		self::$preferredStorage = $preferredStorage;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rsaauth/sv1/storage/class.tx_rsaauth_storagefactory.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rsaauth/sv1/storage/class.tx_rsaauth_storagefactory.php']);
}

?>