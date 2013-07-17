<?php
namespace TYPO3\CMS\Rsaauth;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Reinhard Führicht <rf@typoheads.at>
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
 * Utility class to help other extensions and the Core to easily decrypt passwords.
 *
 */
class RsaCryptService {

	/**
	 * @var TYPO3\CMS\Rsaauth\Storage\AbstractStorage
	 */
	protected $storage;

	/**
	 * @var TYPO3\CMS\Rsaauth\Storage\AbstractBackend
	 */
	protected $backend;

	public function __construct() {
		$this->storage = \TYPO3\CMS\Rsaauth\Storage\StorageFactory::getStorage();
		$this->backend = \TYPO3\CMS\Rsaauth\Backend\BackendFactory::getBackend();
	}

	/**
	 * Decrypts a given password string
	 *
	 * @param string $password The password string to decrypt
	 * @return Decrypted password or original password if decryption was not possible
	 */
	public function decrypt($password) {
		$decryptedPassword = $password;
		$key = $this->storage->get();
		if ($key !== NULL && substr($password, 0, 4) === 'rsa:') {
			$decryptedPassword = $this->backend->decrypt($key, substr($password, 4));
		}
		return $decryptedPassword;
	}
}

?>