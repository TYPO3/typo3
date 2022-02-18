<?php

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

namespace TYPO3\CMS\Core\Resource\Security;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\Event\AfterResourceStorageInitializationEvent;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceStorage;

/**
 * The aspect injects user permissions and mount points into the storage
 * based on user or group configuration.
 *
 * We do not have AOP in TYPO3 for now, thus the aspect which
 * deals with resource security is a EventListener which reacts on storage object creation.
 *
 * @internal this is an Event Listener, and not part of TYPO3 Core API.
 */
final class StoragePermissionsAspect
{
    /**
     * The event listener for the event where storage objects are created
     * @param AfterResourceStorageInitializationEvent $event
     */
    public function addUserPermissionsToStorage(AfterResourceStorageInitializationEvent $event): void
    {
        $storage = $event->getStorage();
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()
            && !$GLOBALS['BE_USER']->isAdmin()
        ) {
            $storage->setEvaluatePermissions(true);
            if ($storage->getUid() > 0) {
                $storage->setUserPermissions($GLOBALS['BE_USER']->getFilePermissionsForStorage($storage));
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
     */
    private function addFileMountsToStorage(ResourceStorage $storage)
    {
        foreach ($GLOBALS['BE_USER']->getFileMountRecords() as $fileMountRow) {
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
