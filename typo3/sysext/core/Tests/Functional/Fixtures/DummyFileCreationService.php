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

namespace TYPO3\CMS\Core\Tests\Functional\Fixtures;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

class DummyFileCreationService
{
    private array $testFilesToDelete = [];

    public function __construct(private readonly ?StorageRepository $storageRepository = null) {}

    public function cleanupCreatedFiles(): void
    {
        foreach ($this->testFilesToDelete as $file) {
            unlink($file);
        }
    }

    public function ensureFilesExistInPublicFolder(string $targetFileName, ?string $contents = null): string
    {
        $targetFile = Environment::getPublicPath() . $targetFileName;
        if (!file_exists($targetFile)) {
            GeneralUtility::mkdir_deep(dirname($targetFile));
            touch($targetFile);
            if ($contents !== null) {
                file_put_contents($targetFile, $contents);
            }
            $this->testFilesToDelete[$targetFile] = $targetFile;
        }
        return $targetFile;
    }

    public function ensureFilesExistInStorage($fileName, ?string $contents = null): void
    {
        $storage = $this->storageRepository?->findByUid(1);
        if ($storage === null) {
            throw new \UnexpectedValueException('Storage could not be obtained', 1760868863);
        }
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.system.enforceAllowedFileExtensions'] = false;
        $fileadminDir = '/' . trim($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] ?? 'fileadmin', '/');
        $pathInFileadmin = $this->ensureFilesExistInPublicFolder($fileadminDir . $fileName, $contents);
        $tempFile = Environment::getVarPath() . '/transient/' . PathUtility::basename($pathInFileadmin);
        rename($pathInFileadmin, $tempFile);
        $storage->addFile(
            localFilePath: $tempFile,
            targetFolder: $storage->getFolder(dirname($fileName)),
            targetFileName: basename($fileName),
        );
    }
}
