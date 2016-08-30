<?php
namespace TYPO3\CMS\Core\Resource\Security;

/*
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
class StoragePermissionsAspect
{
    /**
     * @var BackendUserAuthentication
     */
    protected $backendUserAuthentication;

    /**
     * @var array
     */
    protected $defaultStorageZeroPermissions = [
        'readFolder' => true,
        'readFile' => true
    ];

    /**
     * @param BackendUserAuthentication|null $backendUserAuthentication
     */
    public function __construct($backendUserAuthentication = null)
    {
        $this->backendUserAuthentication = $backendUserAuthentication ?: $GLOBALS['BE_USER'];
    }

    /**
     * The slot for the signal in ResourceFactory where storage objects are created
     *
     * @param ResourceFactory $resourceFactory
     * @param ResourceStorage $storage
     * @return void
     */
    public function addUserPermissionsToStorage(ResourceFactory $resourceFactory, ResourceStorage $storage)
    {
        if (!$this->backendUserAuthentication->isAdmin()) {
            $storage->setEvaluatePermissions(true);
            if ($storage->getUid() > 0) {
                $storage->setUserPermissions($this->backendUserAuthentication->getFilePermissionsForStorage($storage));
            } else {
                $storage->setEvaluatePermissions(false);
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
    protected function addFileMountsToStorage(ResourceStorage $storage)
    {
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
