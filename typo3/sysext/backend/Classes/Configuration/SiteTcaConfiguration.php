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

namespace TYPO3\CMS\Backend\Configuration;

use Symfony\Component\Finder\Finder;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Helper class for the backend "Sites" module
 *
 * Load Site configuration TCA from ext:*Configuration/SiteConfiguration
 * and ext:*Configuration/SiteConfiguration/Overrides
 *
 * @internal This class is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
class SiteTcaConfiguration
{
    /**
     * Returns a "fake TCA" array that is syntactically identical to
     * "normal" TCA, but is not available as $GLOBALS['TCA']. During
     * configuration loading time, the target array is available as
     * $GLOBALS['SiteConfiguration'] within the Overrides files.
     *
     * It is not possible to use ExtensionManagementUtility methods.
     *
     * @return array
     */
    public function getTca(): array
    {
        $GLOBALS['SiteConfiguration'] = [];
        $activePackages = GeneralUtility::makeInstance(PackageManager::class)->getActivePackages();
        // First load "full table" files from Configuration/SiteConfiguration
        $finder = (new Finder())->files()->depth(0)->name('*.php');
        $hasDirectoryEntries = false;
        foreach ($activePackages as $package) {
            try {
                $finder->in($package->getPackagePath() . 'Configuration/SiteConfiguration');
            } catch (\InvalidArgumentException $e) {
                // No such directory in this package
                continue;
            }
            $hasDirectoryEntries = true;
        }
        if ($hasDirectoryEntries) {
            foreach ($finder as $fileInfo) {
                $GLOBALS['SiteConfiguration'][substr($fileInfo->getBasename(), 0, -4)] = require $fileInfo->getPathname();
            }
        }
        // Execute override files from Configuration/SiteConfiguration/Overrides
        $finder = (new Finder())->files()->depth(0)->name('*.php');
        $hasDirectoryEntries = false;
        foreach ($activePackages as $package) {
            try {
                $finder->in($package->getPackagePath() . 'Configuration/SiteConfiguration/Overrides');
            } catch (\InvalidArgumentException $e) {
                // No such directory in this package
                continue;
            }
            $hasDirectoryEntries = true;
        }
        if ($hasDirectoryEntries) {
            foreach ($finder as $fileInfo) {
                require $fileInfo->getPathname();
            }
        }
        $result = $GLOBALS['SiteConfiguration'];
        unset($GLOBALS['SiteConfiguration']);
        return $result;
    }
}
