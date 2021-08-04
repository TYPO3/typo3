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

namespace TYPO3\CMS\Install\Updates;

use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\Security\SvgSanitizer;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SvgFilesSanitization implements UpgradeWizardInterface, ConfirmableInterface
{
    /**
     * @var StorageRepository
     */
    protected $storageRepository;

    /**
     * @var Confirmation
     */
    protected $confirmation;

    public function __construct()
    {
        $this->storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $this->confirmation = new Confirmation(
            'Continue sanitizing SVG files?',
            $this->getDescription(),
            false,
            'sanitize, backup available',
            'cancel',
            false
        );
    }

    /**
     * Return the identifier for this wizard
     * This should be the same string as used in the ext_localconf class registration
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return 'svgFilesSanitization';
    }

    /**
     * Return the speaking name of this wizard
     *
     * @return string
     */
    public function getTitle(): string
    {
        return 'Sanitize existing SVG files in fileadmin folder';
    }

    /**
     * Return the description for this wizard
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'This upgrade wizard will sanitize all SVG files located in local file storages. '
            . 'It is very likely that file contents will be changed.' . "\n"
            . 'Before continuing, please ensure a proper backup of *.svg and *.svgz files is in place before continuing.';
    }

    /**
     * To avoid timeout issues, no check is performed in advance
     *
     * @return bool
     */
    public function updateNecessary(): bool
    {
        return true;
    }

    /**
     * Execute the update
     *
     * Called when a wizard reports that an update is necessary
     *
     * @return bool
     */
    public function executeUpdate(): bool
    {
        return $this->processSvgFiles();
    }

    /**
     * Returns an array of class names of Prerequisite classes
     *
     * This way a wizard can define dependencies like "database up-to-date" or
     * "reference index updated"
     *
     * @return string[]
     */
    public function getPrerequisites(): array
    {
        return [];
    }

    /**
     * Return a confirmation message instance
     *
     * @return Confirmation
     */
    public function getConfirmation(): Confirmation
    {
        return $this->confirmation;
    }

    /**
     * @return ResourceStorage[]
     */
    protected function resolveLocalStorages(): array
    {
        return array_filter(
            $this->storageRepository->findByStorageType('Local'),
            static function (ResourceStorage $storage) {
                return $storage->isWritable();
            }
        );
    }

    /**
     * @param ResourceStorage $storage
     * @return File[]
     * @throws InsufficientFolderAccessPermissionsException
     */
    protected function resolveSvgFiles(ResourceStorage $storage): array
    {
        $filter = GeneralUtility::makeInstance(FileExtensionFilter::class);
        $filter->setAllowedFileExtensions(['svg', 'svgz']);
        $files = $storage
            ->setFileAndFolderNameFilters([
                [$filter, 'filterFileList'],
            ])
            ->getFilesInFolder(
                $storage->getRootLevelFolder(),
                0,
                0,
                true,
                true
            );
        $storage->resetFileAndFolderNameFiltersToDefault();
        return $files;
    }

    protected function processSvgFiles(): bool
    {
        $successful = true;
        $sanitizer = GeneralUtility::makeInstance(SvgSanitizer::class);
        foreach ($this->resolveLocalStorages() as $storage) {
            try {
                $svgFiles = $this->resolveSvgFiles($storage);
            } catch (InsufficientFolderAccessPermissionsException $exception) {
                // @todo Add notice/warning for this upgrade process
                $successful = false;
                continue;
            }
            foreach ($svgFiles as $svgFile) {
                $oldFileContent = $svgFile->getContents();
                $newFileContent = $sanitizer->sanitizeContent($oldFileContent);
                if ($oldFileContent !== $newFileContent) {
                    $svgFile->setContents($newFileContent);
                }
            }
        }
        return $successful;
    }
}
