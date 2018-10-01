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
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Basic Service to check and create install tool files
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class EnableFileService
{
    /**
     * @var string Relative path to ENABLE_INSTALL_TOOL file
     */
    const INSTALL_TOOL_ENABLE_FILE_PATH = 'typo3conf/ENABLE_INSTALL_TOOL';

    /**
     * @var string Relative path to  FIRST_INSTALL file
     */
    const FIRST_INSTALL_FILE_PATH = 'FIRST_INSTALL';

    /**
     * @var string Maximum age of ENABLE_INSTALL_TOOL file before it gets removed (in seconds)
     */
    const INSTALL_TOOL_ENABLE_FILE_LIFETIME = 3600;

    /**
     * @return bool
     */
    public static function isFirstInstallAllowed()
    {
        $files = self::getFirstInstallFilePaths();
        if (!empty($files)) {
            return true;
        }
        return false;
    }

    /**
     * Creates the INSTALL_TOOL_ENABLE file
     *
     * @return bool
     */
    public static function createInstallToolEnableFile()
    {
        $installEnableFilePath = self::getInstallToolEnableFilePath();
        if (!is_file($installEnableFilePath)) {
            $result = touch($installEnableFilePath);
        } else {
            $result = true;
            self::extendInstallToolEnableFileLifetime();
        }
        GeneralUtility::fixPermissions($installEnableFilePath);
        return $result;
    }

    /**
     * Removes the INSTALL_TOOL_ENABLE file
     *
     * @return bool
     */
    public static function removeInstallToolEnableFile()
    {
        return unlink(self::getInstallToolEnableFilePath());
    }

    /**
     * Removes the FIRST_INSTALL file
     *
     * @return bool
     */
    public static function removeFirstInstallFile()
    {
        $result = true;
        $files = self::getFirstInstallFilePaths();
        foreach ($files as $file) {
            $result = unlink(Environment::getPublicPath() . '/' . $file) && $result;
        }
        return $result;
    }

    /**
     * Checks if the install tool file exists
     *
     * @return bool
     */
    public static function installToolEnableFileExists()
    {
        return @is_file(self::getInstallToolEnableFilePath());
    }

    /**
     * Checks if the install tool file exists
     *
     * @return bool
     */
    public static function checkInstallToolEnableFile()
    {
        if (!self::installToolEnableFileExists()) {
            return false;
        }
        if (!self::isInstallToolEnableFilePermanent()) {
            if (self::installToolEnableFileLifetimeExpired()) {
                self::removeInstallToolEnableFile();
                return false;
            }
            self::extendInstallToolEnableFileLifetime();
        }
        return true;
    }

    /**
     * Checks if the install tool file should be kept
     *
     * @return bool
     */
    public static function isInstallToolEnableFilePermanent()
    {
        if (self::installToolEnableFileExists()) {
            $content = @file_get_contents(self::getInstallToolEnableFilePath());
            if (strpos($content, 'KEEP_FILE') !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if the lifetime of the install tool file is expired
     *
     * @return bool
     */
    public static function installToolEnableFileLifetimeExpired()
    {
        if (time() - @filemtime(self::getInstallToolEnableFilePath()) > self::INSTALL_TOOL_ENABLE_FILE_LIFETIME) {
            return true;
        }
        return false;
    }

    /**
     * Updates the last modification of the ENABLE_INSTALL_TOOL file
     */
    protected static function extendInstallToolEnableFileLifetime()
    {
        $enableFile = self::getInstallToolEnableFilePath();
        // Extend the age of the ENABLE_INSTALL_TOOL file by one hour
        if (is_file($enableFile)) {
            $couldTouch = @touch($enableFile);
            if (!$couldTouch) {
                // If we can't remove the creation method will call us again.
                if (self::removeInstallToolEnableFile()) {
                    self::createInstallToolEnableFile();
                }
            }
        }
    }

    /**
     * Returns the path to the INSTALL_TOOL_ENABLE file
     *
     * @return string
     */
    protected static function getInstallToolEnableFilePath()
    {
        return Environment::getPublicPath() . '/' . self::INSTALL_TOOL_ENABLE_FILE_PATH;
    }

    /**
     * Returns the paths to the FIRST_INSTALL files
     *
     * @return array
     */
    protected static function getFirstInstallFilePaths()
    {
        $files = array_filter(scandir(Environment::getPublicPath() . '/'), function ($file) {
            return @is_file(Environment::getPublicPath() . '/' . $file) && preg_match('~^' . self::FIRST_INSTALL_FILE_PATH . '.*~i', $file);
        });
        return $files;
    }
}
