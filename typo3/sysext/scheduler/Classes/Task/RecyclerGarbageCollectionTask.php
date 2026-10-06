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

namespace TYPO3\CMS\Scheduler\Task;

use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Recycler folder garbage collection task
 *
 * This task finds all "_recycler_" folders below all storages and
 * deletes all files in them that have not changed for more than
 * a given number of days.
 *
 * Compatible drivers should be implemented correctly for this. The shipped "local driver"
 * does a "touch()" after the file is moved into the recycler folder.
 * @internal This class is a specific scheduler task implementation is not considered part of the Public TYPO3 API.
 */
class RecyclerGarbageCollectionTask extends AbstractTask
{
    /**
     * Elapsed period since last modification before a file will
     * be deleted in a recycler directory.
     *
     * @var int Number of days before cleaning up files
     */
    public $numberOfDays = 0;

    /**
     * Cleanup recycled files, called by scheduler.
     *
     * @return bool TRUE if task run was successful
     * @throws \BadMethodCallException
     */
    public function execute()
    {
        $recyclerFolders = [];
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        foreach ($storageRepository->findAll() as $storage) {
            if ($storage->isBrowsable()) {
                array_push($recyclerFolders, ...$this->findRecyclerFolders($storage));
            }
        }

        // Execute cleanup
        $seconds = 60 * 60 * 24 * (int)$this->numberOfDays;
        $timestamp = $GLOBALS['EXEC_TIME'] - $seconds;
        foreach ($recyclerFolders as $recyclerFolder) {
            $this->cleanupRecycledFiles($recyclerFolder, $timestamp);
        }
        return true;
    }

    /**
     * Finds the recycler folders at any depth of the storage. Only folder identifiers are
     * listed, one level at a time, as a recursive listing of the whole storage is too slow
     * for large storages. Neither recycler nor processing folders are descended into, as a
     * recycler nested in a recycler is cleaned along with the outer one.
     *
     * @return Folder[]
     */
    protected function findRecyclerFolders(ResourceStorage $storage): array
    {
        $recyclerFolders = [];
        $folderIdentifiers = [$storage->getRootLevelFolder(false)->getIdentifier()];
        while (($folderIdentifier = array_pop($folderIdentifiers)) !== null) {
            foreach ($storage->getFolderIdentifiersInFolder($folderIdentifier) as $subFolderIdentifier) {
                $subFolder = new Folder($storage, $subFolderIdentifier, PathUtility::basename($subFolderIdentifier));
                $role = $subFolder->getRole();
                if ($role === FolderInterface::ROLE_RECYCLER) {
                    $recyclerFolders[] = $subFolder;
                } elseif ($role !== FolderInterface::ROLE_PROCESSING) {
                    $folderIdentifiers[] = $subFolderIdentifier;
                }
            }
        }
        return $recyclerFolders;
    }

    /**
     * Gets a list of all files in a directory recursively and removes
     * old ones.
     *
     * @param Folder $folder the folder
     * @param int $timestamp Timestamp of the last file modification
     */
    protected function cleanupRecycledFiles(Folder $folder, $timestamp)
    {
        foreach ($folder->getFiles() as $file) {
            if ($timestamp > $file->getModificationTime()) {
                $file->delete();
            }
        }
        foreach ($folder->getSubfolders() as $subFolder) {
            $this->cleanupRecycledFiles($subFolder, $timestamp);
            // if no more files and subdirectories are in the folder, remove the folder as well
            if ($subFolder->getFileCount() === 0 && count($subFolder->getSubfolders()) === 0) {
                $subFolder->delete(true);
            }
        }
    }

    public function getTaskParameters(): array
    {
        return [
            'number_of_days' => $this->numberOfDays,
        ];
    }

    public function setTaskParameters(array $parameters): void
    {
        $this->numberOfDays = (int)($parameters['numberOfDays'] ?? $parameters['number_of_days'] ?? 0);
    }
}
