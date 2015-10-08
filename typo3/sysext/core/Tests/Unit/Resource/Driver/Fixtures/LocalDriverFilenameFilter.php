<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource\Driver\Fixtures;

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

/**
 * Fixture class for the filename filters in the local driver.
 */
class LocalDriverFilenameFilter
{
    /**
     * Filter filename
     *
     * @param string $itemName
     * @param string $itemIdentifier
     * @param string $parentIdentifier
     * @param array $additionalInformation
     * @param \TYPO3\CMS\Core\Resource\Driver\AbstractDriver $driverInstance
     * @return bool|int
     */
    public static function filterFilename($itemName, $itemIdentifier, $parentIdentifier, array $additionalInformation, \TYPO3\CMS\Core\Resource\Driver\AbstractDriver $driverInstance)
    {
        if ($itemName == 'fileA' || $itemName == 'folderA/') {
            return -1;
        } else {
            return true;
        }
    }
}
