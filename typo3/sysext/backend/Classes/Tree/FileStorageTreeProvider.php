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

namespace TYPO3\CMS\Backend\Tree;

use TYPO3\CMS\Backend\Configuration\BackendUserConfiguration;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderReadPermissionsException;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\InaccessibleFolder;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\Utility\ListUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Responsible for fetching a tree-structure of folders.
 *
 * @internal not part of TYPO3 Core API due to the specific use case for the FileStorageTree component.
 */
class FileStorageTreeProvider
{
    protected ?array $expandedState = null;
    protected string $userSettingsIdentifier = 'BackendComponents.States.FileStorageTree';

    public function prepareFolderInformation(Folder $folder, ?string $alternativeName = null, ?Folder $parentFolder = null, ?array $children = null): array
    {
        $name = $alternativeName ?? $folder->getName();
        $storage = $folder->getStorage();
        try {
            $parentFolder = $parentFolder ?? $folder->getParentFolder();
        } catch (FolderDoesNotExistException | InsufficientFolderAccessPermissionsException $e) {
            $parentFolder = null;
        }
        if (str_contains($folder->getRole(), FolderInterface::ROLE_MOUNT)) {
            $tableName = 'sys_filemount';
            $isStorage = true;
        } elseif ($parentFolder === null || $folder->getIdentifier() === $storage->getRootLevelFolder(true)->getIdentifier()) {
            $tableName = 'sys_file_storage';
            $isStorage = true;
        } else {
            $tableName = 'sys_file';
            $isStorage = false;
        }

        try {
            $hasSubfolders = is_array($children) ? $children !== [] : !empty($folder->getSubfolders());
        } catch (\InvalidArgumentException | InsufficientFolderReadPermissionsException $e) {
            $hasSubfolders = false;
        }

        return [
            'resource' => $folder,
            'stateIdentifier' => $this->getStateIdentifier($folder),
            'identifier' => rawurlencode($folder->getCombinedIdentifier()),
            'name' => $name,
            'storage' => $storage->getUid(),
            'pathIdentifier' => rawurlencode($folder->getIdentifier()),
            'hasChildren' => $hasSubfolders,
            'parentIdentifier' => $parentFolder instanceof Folder && !$isStorage ? rawurlencode($parentFolder->getCombinedIdentifier()) : null,
            'itemType' => $tableName,
        ];
    }

    /**
     * Fetch all file storages / file mounts visible for a user.
     */
    public function getRootNodes(BackendUserAuthentication $user): array
    {
        $items = [];
        $storages = $user->getFileStorages();
        foreach ($storages as $storageObject) {
            $items = array_merge($items, $this->getFoldersInStorage($storageObject, $user));
        }
        return $items;
    }

    /**
     * Fetch all folders recursively in a single store.
     */
    protected function getFoldersInStorage(ResourceStorage $resourceStorage, BackendUserAuthentication $user): array
    {
        $rootLevelFolders = $this->getMountsInStorage($resourceStorage, $user);
        $items = [];
        foreach ($rootLevelFolders as $i => $rootLevelFolderInfo) {
            /** @var Folder $rootLevelFolder */
            $rootLevelFolder = $rootLevelFolderInfo['folder'];
            // Root level is always expanded if not defined otherwise
            $expanded = $this->isExpanded($rootLevelFolder, true);

            $itm = $this->prepareFolderInformation($rootLevelFolder, $rootLevelFolderInfo['name']);
            $itm['depth'] = 0;
            $itm['expanded'] = $expanded;
            $itm['loaded'] = $expanded;
            $itm['siblingsCount'] = count($rootLevelFolders) - 1;
            $itm['siblingsPosition'] = $i;
            $items[] = $itm;

            // If the mount is expanded, go down:
            if ($expanded && $resourceStorage->isBrowsable()) {
                $childItems = $this->getSubfoldersRecursively($rootLevelFolder, 1);
                array_push($items, ...$childItems);
            }
        }
        return $items;
    }

    /**
     * Filter a tree by a search word
     *
     * @return FolderInterface[]
     * @throws \Exception
     */
    public function getFilteredTree(BackendUserAuthentication $user, string $search): array
    {
        $foundFolders = [];
        $storages = $user->getFileStorages();
        foreach ($storages as $resourceStorage) {
            $processingFolders = $resourceStorage->getProcessingFolders();
            $processingFolderIdentifiers = array_map(static function ($folder) {
                return $folder->getIdentifier();
            }, $processingFolders);
            $resourceStorage->addFileAndFolderNameFilter(static function ($itemName, $itemIdentifier, $parentIdentifier, array $additionalInformation, DriverInterface $driver) use ($resourceStorage, $search, $processingFolderIdentifiers) {
                // Skip items in processing folders
                $isInProcessingFolder = array_filter($processingFolderIdentifiers, static function ($processingFolderIdentifier) use ($parentIdentifier) {
                    return stripos($parentIdentifier, $processingFolderIdentifier) !== false;
                });
                if (!empty($isInProcessingFolder)) {
                    return -1;
                }
                if ($itemName instanceof Folder) {
                    if ($resourceStorage->isProcessingFolder($itemName)) {
                        return -1;
                    }
                    $name = $itemName->getName();
                } elseif (is_string($itemName)) {
                    $name = $itemName;
                } else {
                    return -1;
                }
                if (stripos($name, $search) !== false) {
                    return true;
                }
                return -1;
            });
            try {
                $files = $folders = [];
                // Because $resourceStorage->getRootLevelFolder() does not return an actual root folder but
                // the first file mount, we first need to check if we have file mounts and then fetch them one by one.
                if (($fileMounts = $resourceStorage->getFileMounts()) !== []) {
                    foreach ($fileMounts as $identifier => $configuration) {
                        foreach ($resourceStorage->getFilesInFolder($resourceStorage->getFolder($identifier), 0, 0, true, true) as $file) {
                            $files[] = $file;
                        }
                        foreach ($resourceStorage->getFolderIdentifiersInFolder($identifier, true, true) as $folder) {
                            $folders[] = $folder;
                        }
                    }
                } else {
                    $files = $resourceStorage->getFilesInFolder($resourceStorage->getRootLevelFolder(), 0, 0, true, true);
                    $folders = $resourceStorage->getFolderIdentifiersInFolder($resourceStorage->getRootLevelFolder()->getIdentifier(), true, true);
                }
                foreach ($files as $file) {
                    $folder = $file->getParentFolder();
                    $foundFolders[$folder->getCombinedIdentifier()] = $folder;
                }
                foreach ($folders as $folder) {
                    $folderObj = $resourceStorage->getFolder($folder);
                    if ($folderObj !== null) {
                        $foundFolders[$folderObj->getCombinedIdentifier()] = $folderObj;
                    }
                }
            } catch (InsufficientFolderAccessPermissionsException $e) {
                // do nothing
            }
            $resourceStorage->resetFileAndFolderNameFiltersToDefault();
        }
        return $foundFolders;
    }

    public function getSubfoldersRecursively(Folder $folderObject, int $currentDepth, ?array $subFolders = null): array
    {
        $items = [];
        if ($folderObject instanceof InaccessibleFolder) {
            $subFolders = [];
        } else {
            $subFolders = is_array($subFolders) ? $subFolders : $folderObject->getSubfolders();
            $subFolders = ListUtility::resolveSpecialFolderNames($subFolders);
            uksort($subFolders, 'strnatcasecmp');
        }

        $subFolderCounter = 0;
        foreach ($subFolders as $subFolderName => $subFolder) {
            $subFolderName = (string)$subFolderName; // Enforce string cast in case $subFolderName contains numeric chars only
            $expanded = $this->isExpanded($subFolder);
            if (!($subFolder instanceof InaccessibleFolder)) {
                $children = $subFolder->getSubfolders();
            } else {
                $children = [];
            }

            $items[] = array_merge(
                $this->prepareFolderInformation($subFolder, $subFolderName, $folderObject, $children),
                [
                    'depth' => $currentDepth,
                    'expanded' => $expanded,
                    'loaded' => $expanded,
                    'siblingsCount' => count($subFolders) - 1,
                    'siblingsPosition' => ++$subFolderCounter,
                ]
            );

            if ($expanded && !empty($children)) {
                $childItems = $this->getSubfoldersRecursively($subFolder, $currentDepth + 1, $children);
                array_push($items, ...$childItems);
            }
        }
        return $items;
    }

    /**
     * Fetches all "root level folders" of a storage. If a user has filemounts in this storage, they are properly resolved.
     *
     * @return array|array[]
     */
    protected function getMountsInStorage(ResourceStorage $resourceStorage, BackendUserAuthentication $user): array
    {
        $fileMounts = $resourceStorage->getFileMounts();
        if (!empty($fileMounts)) {
            return array_map(static function ($fileMountInfo) {
                return [
                    'folder' => $fileMountInfo['folder'],
                    'name' => $fileMountInfo['title'],
                ];
            }, $fileMounts);
        }

        if ($user->isAdmin()) {
            return [
                [
                    'folder' => $resourceStorage->getRootLevelFolder(),
                    'name' => $resourceStorage->getName(),
                ],
            ];
        }
        return [];
    }

    /**
     * The state identifier is the folder stored in the user settings, and also used to uniquely identify
     * a folder throughout the folder tree structure.
     */
    protected function getStateIdentifier(Folder $folder): string
    {
        return $folder->getStorage()->getUid() . '_' . GeneralUtility::md5int($folder->getIdentifier());
    }

    /**
     * Checks if a folder was previously opened by the user.
     */
    protected function isExpanded(Folder $folder, bool $fallback = false): bool
    {
        $stateIdentifier = $this->getStateIdentifier($folder);
        if (!is_array($this->expandedState)) {
            $this->expandedState = GeneralUtility::makeInstance(BackendUserConfiguration::class)->get($this->userSettingsIdentifier);
            $this->expandedState = ($this->expandedState['stateHash'] ?? []) ?: [];
        }
        return (bool)($this->expandedState[$stateIdentifier] ?? $fallback);
    }
}
