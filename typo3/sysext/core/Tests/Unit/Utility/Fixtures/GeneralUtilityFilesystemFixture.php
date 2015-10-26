<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class GeneralUtilityFilesystemFixture
 */
class GeneralUtilityFilesystemFixture extends GeneralUtility
{
    /**
     * For testing we must allow vfs:// as first part of file path
     *
     * @param string $path File path to evaluate
     * @return bool
     */
    public static function isAbsPath($path)
    {
        return self::isFirstPartOfStr($path, 'vfs://') || parent::isAbsPath($path);
    }

    /**
     * For testing we must allow vfs:// as first part file path
     *
     * @param string $path File path to evaluate
     * @return bool
     */
    public static function isAllowedAbsPath($path)
    {
        return self::isFirstPartOfStr($path, 'vfs://') || parent::isAllowedAbsPath($path);
    }

    /**
     * For testing we must allow vfs:// as first part of file path
     *
     * @param string $theFile File path to evaluate
     * @return bool TRUE, $theFile is allowed path string, FALSE otherwise
     */
    public static function validPathStr($theFile)
    {
        return self::isFirstPartOfStr($theFile, 'vfs://') || parent::validPathStr($theFile);
    }

    /**
     * For testing we must skip the path checks
     *
     * @param string $filepath Absolute file path to write to inside "typo3temp/". First part of this string must match PATH_site."typo3temp/"
     * @param string $content Content string to write
     * @return string Returns NULL on success, otherwise an error string telling about the problem.
     */
    public static function writeFileToTypo3tempDir($filepath, $content)
    {
        static::writeFile($filepath, $content);
    }
}
