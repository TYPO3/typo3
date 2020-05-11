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

namespace TYPO3\CMS\Core\Service\Archive;

use TYPO3\CMS\Core\Exception\Archive\ExtractException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service that handles zip creation and extraction
 *
 * @internal
 */
class ZipService
{
    /**
     * Extracts the zip archive to a given directory. This method makes sure a file cannot be placed outside the directory.
     *
     * @param string $fileName
     * @param string $directory
     * @return bool
     * @throws ExtractException
     */
    public function extract(string $fileName, string $directory): bool
    {
        $this->assertDirectoryIsWritable($directory);

        $zip = new \ZipArchive();
        $state = $zip->open($fileName);
        if ($state !== true) {
            throw new ExtractException(
                sprintf('Unable to open zip file %s, error code %d', $fileName, $state),
                1565709712
            );
        }

        $result = $zip->extractTo($directory);
        $zip->close();
        if ($result) {
            GeneralUtility::fixPermissions(rtrim($directory, '/'), true);
        }
        return $result;
    }

    /**
     * @param string $fileName
     * @return bool
     * @throws ExtractException
     */
    public function verify(string $fileName): bool
    {
        $zip = new \ZipArchive();
        $state = $zip->open($fileName);
        if ($state !== true) {
            throw new ExtractException(
                sprintf('Unable to open zip file %s, error code %d', $fileName, $state),
                1565709713
            );
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = (string)$zip->getNameIndex($i);
            if (preg_match('#/(?:\.{2,})+#', $entryName) // Contains any traversal sequence starting with a slash, e.g. /../, /.., /.../
                || preg_match('#^(?:\.{2,})+/#', $entryName) // Starts with a traversal sequence, e.g. ../, .../
            ) {
                throw new ExtractException(
                    sprintf('Suspicious sequence in zip file %s: %s', $fileName, $entryName),
                    1565709714
                );
            }
        }

        $zip->close();
        return true;
    }

    /**
     * @param string $directory
     */
    private function assertDirectoryIsWritable(string $directory): void
    {
        if (!is_dir($directory)) {
            throw new \RuntimeException(
                sprintf('Directory %s does not exist', $directory),
                1565773005
            );
        }
        if (!is_writable($directory)) {
            throw new \RuntimeException(
                sprintf('Directory %s is not writable', $directory),
                1565773006
            );
        }
    }
}
