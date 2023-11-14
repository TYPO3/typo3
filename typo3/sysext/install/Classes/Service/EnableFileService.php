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

namespace TYPO3\CMS\Install\Service;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Basic Service to check and create install tool files
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class EnableFileService
{
    /**
     * @var string file name of the ENABLE_INSTALL_TOOL file
     */
    public const INSTALL_TOOL_ENABLE_FILE_PATH = 'ENABLE_INSTALL_TOOL';

    /**
     * @var string Relative path to  FIRST_INSTALL file
     */
    public const FIRST_INSTALL_FILE_PATH = 'FIRST_INSTALL';

    /**
     * @var int Maximum age of ENABLE_INSTALL_TOOL file before it gets removed (in seconds)
     */
    public const INSTALL_TOOL_ENABLE_FILE_LIFETIME = 3600;

    public static function isFirstInstallAllowed(): bool
    {
        $files = self::getFirstInstallFilePaths();
        if (!empty($files)) {
            return true;
        }
        return false;
    }

    /**
     * Creates the INSTALL_TOOL_ENABLE file
     */
    public static function createInstallToolEnableFile(): bool
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
     * Removes the INSTALL_TOOL_ENABLE file from all locations
     */
    public static function removeInstallToolEnableFile(): bool
    {
        $result = false;
        while (is_file(self::getInstallToolEnableFilePath())) {
            $result = unlink(self::getInstallToolEnableFilePath());
        }
        return $result;
    }

    /**
     * Removes the FIRST_INSTALL file
     */
    public static function removeFirstInstallFile(): bool
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
     */
    public static function installToolEnableFileExists(): bool
    {
        return @is_file(self::getInstallToolEnableFilePath());
    }

    /**
     * Checks if the install tool file exists
     */
    public static function checkInstallToolEnableFile(): bool
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
     */
    public static function isInstallToolEnableFilePermanent(): bool
    {
        if (self::installToolEnableFileExists()) {
            $content = (string)@file_get_contents(self::getInstallToolEnableFilePath());
            if (str_contains($content, 'KEEP_FILE')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if the lifetime of the install tool file is expired
     */
    public static function installToolEnableFileLifetimeExpired(): bool
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
     * Returns a static directory path that is suitable to be presented to
     * unauthenticated visitors, in order to circumvent "Full Path Disclosure" issues.
     */
    public static function getStaticLocationForInstallToolEnableFileDirectory(): string
    {
        return Environment::isComposerMode() ? 'var/transient/' : 'typo3conf/';
    }

    public static function getBestLocationForInstallToolEnableFile(): string
    {
        $possibleLocations = [
            'default' => Environment::getVarPath() . '/transient/' . self::INSTALL_TOOL_ENABLE_FILE_PATH,
            'permanent' => Environment::getConfigPath() . '/' . self::INSTALL_TOOL_ENABLE_FILE_PATH,
        ];
        return Environment::isComposerMode() ? $possibleLocations['default'] : $possibleLocations['permanent'];
    }

    /**
     * Returns the absolute path to the INSTALL_TOOL_ENABLE file
     */
    protected static function getInstallToolEnableFilePath(): string
    {
        $possibleLocations = [
            'default' => Environment::getVarPath() . '/transient/' . self::INSTALL_TOOL_ENABLE_FILE_PATH,
            'permanent' => Environment::getConfigPath() . '/' . self::INSTALL_TOOL_ENABLE_FILE_PATH,
            'legacy' => Environment::getLegacyConfigPath() . self::INSTALL_TOOL_ENABLE_FILE_PATH,
        ];
        foreach ($possibleLocations as $location) {
            if (@is_file($location)) {
                return $location;
            }
        }
        return self::getBestLocationForInstallToolEnableFile();
    }

    /**
     * Returns the paths to the FIRST_INSTALL files
     */
    protected static function getFirstInstallFilePaths(): array
    {
        $files = scandir(Environment::getPublicPath() . '/');
        $files = is_array($files) ? $files : [];
        $files = array_filter($files, static function ($file) {
            return @is_file(Environment::getPublicPath() . '/' . $file) && preg_match('~^' . self::FIRST_INSTALL_FILE_PATH . '.*~i', $file);
        });
        return $files;
    }
}
