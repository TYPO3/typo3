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

namespace TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class GeneralUtilityFilesystemFixture
 */
class GeneralUtilityFilesystemFixture extends GeneralUtility
{
    /**
     * For testing we must allow vfs:// as first part file path
     *
     * @param string $path File path to evaluate
     * @return bool
     */
    public static function isAllowedAbsPath($path): bool
    {
        return str_starts_with($path, 'vfs://') || parent::isAllowedAbsPath($path);
    }

    /**
     * For testing we must allow vfs:// as first part of file path
     *
     * @param string $theFile File path to evaluate
     * @return bool TRUE, $theFile is allowed path string, FALSE otherwise
     */
    public static function validPathStr($theFile): bool
    {
        return str_starts_with($theFile, 'vfs://') || parent::validPathStr($theFile);
    }

    /**
     * For testing we must skip the path checks
     *
     * @param string $filepath Absolute file path to write to inside "typo3temp/". First part of this string must match Environment::getPublicPath() ."/typo3temp/"
     * @param string $content Content string to write
     */
    public static function writeFileToTypo3tempDir($filepath, $content): void
    {
        static::writeFile($filepath, $content);
    }
}
