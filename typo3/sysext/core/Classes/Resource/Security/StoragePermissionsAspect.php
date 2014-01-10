<?php
namespace TYPO3\CMS\Core\Resource\Security;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Helmut Hummel <helmut.hummel@typo3.org>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;

/**
 * Class StoragePermissionsAspect
 *
 * We do not have AOP in TYPO3 for now, thus the acspect which
 * deals with resource security is a slot which reacts on a signal
 * on storage object creation.
 *
 * The aspect injects user permissions and mount points into the storage
 * based on user or group configuration.
 */
class StoragePermissionsAspect {

	/**
	 * @var BackendUserAuthentication
	 */
	protected $backendUserAuthentication;

	/**
	 * @var array
	 */
	protected $defaultStorageZeroPermissions = array(
		'readFolder' => TRUE,
		'readFile' => TRUE
	);


	/**
	 * @param BackendUserAuthentication|null $backendUserAuthentication
	 */
	public function __construct($backendUserAuthentication = NULL) {
		$this->backendUserAuthentication = $backendUserAuthentication ?: $GLOBALS['BE_USER'];
	}

	/**
	 * The slot for the signal in ResourceFactory where storage objects are created
	 *
	 * @param ResourceFactory $resourceFactory
	 * @param ResourceStorage $storage
	 * @return void
	 */
	public function addUserPermissionsToStorage(ResourceFactory $resourceFactory, ResourceStorage $storage) {
		if (!$this->backendUserAuthentication->isAdmin()) {
			$storage->setEvaluatePermissions(TRUE);
			if ($storage->getUid() > 0) {
				$storage->setUserPermissions($this->backendUserAuthentication->getFilePermissionsForStorage($storage));
			} else {
				$storage->setEvaluatePermissions(FALSE);
			}
			$this->addFileMountsToStorage($storage);
		}
	}

	/**
	 * Adds file mounts from the user's file mount records
	 *
	 * @param ResourceStorage $storage
	 * @return void
	 */
	protected function addFileMountsToStorage(ResourceStorage $storage) {
		foreach ($this->backendUserAuthentication->getFileMountRecords() as $fileMountRow) {
			if ((int)$fileMountRow['base'] === (int)$storage->getUid()) {
				try {
					$storage->addFileMount($fileMountRow['path'], $fileMountRow);
				} catch (FolderDoesNotExistException $e) {
					// That file mount does not seem to be valid, fail silently
				}
			}
		}
	}
}
