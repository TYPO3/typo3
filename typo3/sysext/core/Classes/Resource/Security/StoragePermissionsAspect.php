<?php

declare(strict_types=1);

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
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\Event\AfterResourceStorageInitializationEvent;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The aspect injects user permissions and mount points into the storage
 * based on user or group configuration.
 *
 * We do not have AOP in TYPO3, thus the aspect which
 * deals with resource security is an EventListener which reacts on storage object creation.
 *
 * @internal this is an Event Listener, and not part of TYPO3 Core API.
 */
final class StoragePermissionsAspect
{
    /**
     * The event listener for the event where storage objects are created
     */
    #[AsEventListener('backend-user-permissions')]
    public function addUserPermissionsToStorage(AfterResourceStorageInitializationEvent $event): void
    {
        $storage = $event->getStorage();
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()
            && !$this->getBackendUser()->isAdmin()
            && !$storage->isFallbackStorage()
        ) {
            $storage->setEvaluatePermissions(true);
            $storage->setUserPermissions($this->getFilePermissionsForStorage($storage));
            $this->addFileMountsToStorage($storage);
        }
    }

    /**
     * Adds file mounts from the user's file mount records
     */
    private function addFileMountsToStorage(ResourceStorage $storage): void
    {
        foreach ($this->getBackendUser()->getFileMountRecords() as $fileMountRow) {
            if (!str_contains($fileMountRow['identifier'] ?? '', ':')) {
                // Skip record since the file mount identifier is invalid
                continue;
            }
            [$base, $path] = GeneralUtility::trimExplode(':', $fileMountRow['identifier'], false, 2);
            if ((int)$base === $storage->getUid()) {
                try {
                    $storage->addFileMount($path, $fileMountRow);
                } catch (FolderDoesNotExistException $e) {
                    // That file mount does not seem to be valid, fail silently
                }
            }
        }
    }

    /**
     * Gets the file permissions for a storage
     * by merging any storage-specific permissions for a
     * storage with the default settings.
     * Admin users will always get the default settings.
     */
    private function getFilePermissionsForStorage(ResourceStorage $storageObject): array
    {
        $backendUser = $this->getBackendUser();
        $finalUserPermissions = $backendUser->getFilePermissions();
        if ($backendUser->isAdmin()) {
            return $finalUserPermissions;
        }
        $storageFilePermissions = $backendUser->getTSConfig()['permissions.']['file.']['storage.'][$storageObject->getUid() . '.'] ?? [];
        if (!empty($storageFilePermissions)) {
            array_walk(
                $storageFilePermissions,
                static function (string $value, string $permission) use (&$finalUserPermissions): void {
                    $finalUserPermissions[$permission] = (bool)$value;
                }
            );
        }
        return $finalUserPermissions;
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
