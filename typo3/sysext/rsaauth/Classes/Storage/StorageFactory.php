<?php
namespace TYPO3\CMS\Rsaauth\Storage;

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
class StorageFactory {

	/**
	 * A list of all available storages. Currently this list cannot be extended.
	 * This is for security reasons to avoid inserting some dummy storage to
	 * the list.
	 *
	 * @var string
	 */
	static protected $preferredStorage = 'EXT:rsaauth/sv1/storage/class.tx_rsaauth_split_storage.php:TYPO3\\CMS\\Rsaauth\\Storage\\SplitStorage';

	/**
	 * An instance of the storage. This member is set in the getStorage() function.
	 * It will not be an abstract storage as shown below but a real class, which is
	 * derieved from the tx_rsaauth_abstract_storage.
	 *
	 * <!-- Please, keep the variable type! It helps IDEs to provide autocomple! -->
	 *
	 * @var \TYPO3\CMS\Rsaauth\Storage\AbstractStorage
	 */
	static protected $storageInstance = NULL;

	/**
	 * Obtains a storage. This function will return a non-abstract class, which
	 * is derieved from the tx_rsaauth_abstract_storage. Applications should
	 * not use anoy methods that are not declared in the tx_rsaauth_abstract_storage.
	 *
	 * @return \TYPO3\CMS\Rsaauth\Storage\AbstractStorage A storage
	 */
	static public function getStorage() {
		if (is_null(self::$storageInstance)) {
			self::$storageInstance = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj(self::$preferredStorage);
		}
		return self::$storageInstance;
	}

	/**
	 * Sets the preferred storage to the factory. This method can be called from
	 * another extension or ext_localconf.php
	 *
	 * @param string $preferredStorage Preferred storage
	 * @return void
	 */
	static public function setPreferredStorage($preferredStorage) {
		self::$preferredStorage = $preferredStorage;
	}

}


?>