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

namespace TYPO3\CMS\Install\Service;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service class to manage typo3temp/assets and FAL storage
 * processed file statistics / cleanup.
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class Typo3tempFileService
{
    private $processedFileRepository;
    private $storageRepository;

    public function __construct(ProcessedFileRepository $processedFileRepository, StorageRepository $storageRepository)
    {
        $this->processedFileRepository = $processedFileRepository;
        $this->storageRepository = $storageRepository;
    }

    /**
     * Returns a list of directory names in typo3temp/assets and their number of files
     *
     * @return array
     */
    public function getDirectoryStatistics(): array
    {
        return array_merge(
            $this->statsFromTypo3temp(),
            $this->statsFromStorages()
        );
    }

    public function getStatsFromStorageByUid(int $storageUid): array
    {
        if ($storageUid === 0) {
            return $this->statsFromTypo3tempProcessed();
        }

        $storage = $this->storageRepository->findByUid($storageUid);
        return $this->getStatsFromStorage($storage);
    }

    /**
     * Directory statistics for typo3temp/assets folders with some
     * special handling for legacy processed file storage _processed_
     *
     * @return array
     */
    protected function statsFromTypo3temp(): array
    {
        $stats = [];
        $typo3TempAssetsPath = '/typo3temp/assets/';
        $basePath = Environment::getPublicPath() . $typo3TempAssetsPath;
        if (is_dir($basePath)) {
            $dirFinder = new Finder();
            $dirsInAssets = $dirFinder->directories()->in($basePath)->depth(0)->sortByName();
            foreach ($dirsInAssets as $dirInAssets) {
                /** @var SplFileInfo $dirInAssets */
                $fileFinder = new Finder();
                $fileCount = $fileFinder->files()->in($dirInAssets->getPathname())->count();
                $folderName = $dirInAssets->getFilename();
                $stat = [
                    'directory' => $typo3TempAssetsPath . $folderName,
                    'numberOfFiles' => $fileCount,
                ];
                if ($folderName === '_processed_') {
                    // The processed file storage for legacy files (eg. TCA type=group internal_type=file)
                    // gets the storageUid set, so this one can be removed via FAL functionality
                    $stat['storageUid'] = 0;
                }
                $stats[] = $stat;
            }
        }
        return $stats;
    }

    /**
     * Directory statistics for typo3temp/assets/_processed_ folder
     *
     * @return array
     */
    protected function statsFromTypo3tempProcessed(): array
    {
        $typo3TempProcessedAssetsPath = '/typo3temp/assets/_processed_/';

        $stats = [
            'storageUid' => 0,
            'directory' => $typo3TempProcessedAssetsPath,
        ];

        $basePath = Environment::getPublicPath() . $typo3TempProcessedAssetsPath;
        if (is_dir($basePath)) {
            $fileFinder = new Finder();
            $stats['numberOfFiles'] = $fileFinder->files()->in($basePath)->count();
        } else {
            $stats['numberOfFiles'] = 0;
        }

        return $stats;
    }

    /**
     * Directory statistics for configured FAL storages.
     *
     * @return array
     */
    protected function statsFromStorages(): array
    {
        $stats = [];
        $storages = $this->storageRepository->findAll();
        foreach ($storages as $storage) {
            if ($storage->isOnline()) {
                $stats[] = $this->getStatsFromStorage($storage);
            }
        }
        return $stats;
    }

    protected function getStatsFromStorage(ResourceStorage $storage): array
    {
        $storageConfiguration = $storage->getConfiguration();
        $storageBasePath = rtrim($storageConfiguration['basePath'], '/');
        $processedPath = '/' . $storageBasePath . $storage->getProcessingFolder()->getIdentifier();
        $numberOfFiles = $this->processedFileRepository->countByStorage($storage);

        return [
            'directory' => $processedPath,
            'numberOfFiles' => $numberOfFiles,
            'storageUid' => $storage->getUid(),
        ];
    }

    /**
     * Clear processed files. The sys_file_processedfile table is cleared for
     * given storage uid and the physical files of local processed storages are deleted.
     *
     * @return int 0 if all went well, if >0 this number of files that could not be deleted
     */
    public function clearProcessedFiles(int $storageUid): int
    {
        $repository = GeneralUtility::makeInstance(ProcessedFileRepository::class);
        return $repository->removeAll($storageUid);
    }

    /**
     * Clears files and folders in a typo3temp/assets/ folder (not _processed_!)
     *
     * @param string $folderName
     * @return bool TRUE if all went well
     * @throws \RuntimeException If folder path is not valid
     */
    public function clearAssetsFolder(string $folderName)
    {
        $basePath = Environment::getPublicPath() . $folderName;
        if (empty($folderName)
            || !GeneralUtility::isAllowedAbsPath($basePath)
            || strpos($folderName, '/typo3temp/assets/') !== 0
        ) {
            throw new \RuntimeException(
                'Path to folder ' . $folderName . ' not allowed.',
                1501781453
            );
        }
        if (!is_dir($basePath)) {
            throw new \RuntimeException(
                'Folder path ' . $basePath . ' does not exist or is no directory.',
                1501781454
            );
        }

        // first remove directories
        foreach ((new Finder())->directories()->in($basePath)->depth(0) as $directory) {
            /** @var SplFileInfo $directory */
            GeneralUtility::rmdir($directory->getPathname(), true);
        }

        // then remove files directly in the main dir
        foreach ((new Finder())->files()->in($basePath)->depth(0) as $file) {
            /** @var SplFileInfo $file */
            $path = $file->getPathname();
            @unlink($path);
        }
        return true;
    }
}
