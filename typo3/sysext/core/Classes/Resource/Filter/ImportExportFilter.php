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

namespace TYPO3\CMS\Core\Resource\Filter;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;

/**
 * Utility methods for filtering filenames stored in `importexport` temporary folder.
 * Albeit this filter is in the scope of `ext:impexp`, it is located in `ext:core` to
 * apply filters on left-over fragments, even when `ext:impexp` is not installed.
 *
 * @internal
 */
class ImportExportFilter
{
    /**
     * Filter method that checks if a directory or a file in such directory belongs to the temp directory of EXT:impexp
     * and the user has "export" permissions.
     */
    public static function filterImportExportFilesAndFolders(string $itemName, string $itemIdentifier, string $parentIdentifier, array $additionalInformation, DriverInterface $driverInstance)
    {
        // + `_temp_` is hard-coded in `BackendUserAuthentication::getDefaultUploadTemporaryFolder()`
        // + `importexport` is hard-coded in `ImportExport::createDefaultImportExportFolder()`
        $importExportFolderSubPath = '/_temp_/importexport/';
        if (str_ends_with($parentIdentifier, $importExportFolderSubPath) || str_contains($itemIdentifier, $importExportFolderSubPath)) {
            $backendUser = self::getBackendUser();
            if ($backendUser === null || !$backendUser->isExportEnabled()) {
                return -1;
            }
        }

        return true;
    }

    protected static function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }
}
