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

namespace TYPO3\CMS\Core\Resource\TextExtraction;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provides a FAL file or any other system resource as a local file to text
 * extractors, for example to hand it to an external tool.
 *
 * A local storage hands out the original file, other drivers a temporary
 * copy created via GeneralUtility::tempnam(). Other system resources, such
 * as files of packages, are copied from their contents, so no local path of
 * a resource has to be exposed. Only such temporary copies below
 * var/transient/ are removed afterwards, the original file of a storage is
 * never touched, even if the storage itself is located below the var/ folder.
 *
 * @internal
 */
final class LocalFileAccess
{
    /**
     * @template T
     * @param \Closure(string): T $callback receives the absolute path of the local file
     * @return T
     */
    public static function withLocalFile(FileInterface|SystemResourceInterface $resource, \Closure $callback): mixed
    {
        if ($resource instanceof FileInterface) {
            $localFilePath = $resource->getForLocalProcessing(false);
        } else {
            $localFilePath = GeneralUtility::tempnam('text-extraction-', $resource->getExtension() !== '' ? '.' . $resource->getExtension() : '');
            GeneralUtility::writeFile($localFilePath, $resource->getContents(), true);
        }
        try {
            return $callback($localFilePath);
        } finally {
            if (str_starts_with(GeneralUtility::fixWindowsFilePath($localFilePath), Environment::getVarPath() . '/transient/')) {
                GeneralUtility::unlink_tempfile($localFilePath);
            }
        }
    }
}
