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

namespace TYPO3\CMS\Core\Resource\Filter;

use TYPO3\CMS\Core\Resource\Driver\DriverInterface;

/**
 * Utility methods for filtering filenames
 */
class FileNameFilter
{
    /**
     * whether to also show the hidden files (don't show them by default)
     *
     * @var bool
     */
    protected static $showHiddenFilesAndFolders = false;

    /**
     * Filter method that checks if a file/folder name starts with a dot (e.g. .htaccess)
     *
     * We use -1 as the "don't include“ return value, for historic reasons,
     * as call_user_func() used to return FALSE if calling the method failed.
     *
     * @param string $itemName
     * @param string $itemIdentifier
     * @param string $parentIdentifier
     * @param array $additionalInformation Additional information (driver dependent) about the inspected item
     * @param DriverInterface $driverInstance
     * @return bool|int -1 if the file should not be included in a listing
     */
    public static function filterHiddenFilesAndFolders($itemName, $itemIdentifier, $parentIdentifier, array $additionalInformation, DriverInterface $driverInstance)
    {
        // Only apply the filter if you want to hide the hidden files
        if (self::$showHiddenFilesAndFolders === false && str_contains($itemIdentifier, '/.')) {
            return -1;
        }
        return true;
    }

    /**
     * Gets the info whether the hidden files are also displayed currently
     *
     * @static
     * @return bool
     */
    public static function getShowHiddenFilesAndFolders()
    {
        return self::$showHiddenFilesAndFolders;
    }

    /**
     * set the flag to show (or hide) the hidden files
     *
     * @static
     * @param bool $showHiddenFilesAndFolders
     * @return bool
     */
    public static function setShowHiddenFilesAndFolders($showHiddenFilesAndFolders)
    {
        return self::$showHiddenFilesAndFolders = (bool)$showHiddenFilesAndFolders;
    }
}
