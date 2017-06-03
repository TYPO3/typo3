<?php
namespace TYPO3\CMS\Install\Service;

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

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service class to manage typo3temp/assets folder cleanup
 */
class Typo3tempFileService
{
    /**
     * Returns a list of directory names in typo3temp/assets and their number of files
     *
     * @return array
     */
    public function getDirectoryStatistics()
    {
        $basePath = PATH_site . 'typo3temp/assets';
        if (!is_dir($basePath)) {
            return [];
        }

        $dirFinder = new Finder();
        $dirsInAssets = $dirFinder->directories()->in($basePath)->depth(0)->sortByName();
        $stats = [];
        foreach ($dirsInAssets as $dirInAssets) {
            /** @var $dirInAssets SplFileInfo */
            $fileFinder = new Finder();
            $fileCount = $fileFinder->files()->in($dirInAssets->getPathname())->count();
            $stats[] = [
                'directory' => $dirInAssets->getFilename(),
                'numberOfFiles' => $fileCount,
            ];
        }

        return $stats;
    }

    /**
     * Clear processed files
     *
     * The sys_file_processedfile table is truncated and the physical files of local storages are deleted.
     *
     * @return int 0 if all went well, if >0 this number of files couldn't be deleted
     */
    public function clearProcessedFiles()
    {
        $repository = GeneralUtility::makeInstance(ProcessedFileRepository::class);
        return $repository->removeAll();
    }

    /**
     * Clear files in a typo3temp/assets/ folder (not _processed_!)
     *
     * @param string $folderName
     * @return bool TRUE if all went well
     * @throws \RuntimeException If folder path is not valid
     */
    public function clearAssetsFolder(string $folderName)
    {
        $basePath = PATH_site . 'typo3temp/assets/' . $folderName;
        if (empty($folderName) || !GeneralUtility::isAllowedAbsPath($basePath)) {
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

        $finder = new Finder();
        $files = $finder->files()->in($basePath)->depth(0)->sortByName();
        foreach ($files as $file) {
            /** @var $file SplFileInfo */
            $path = $file->getPathname();
            @unlink($path);
        }
        return true;
    }
}
