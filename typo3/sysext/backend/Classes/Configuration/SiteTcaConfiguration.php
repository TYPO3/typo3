<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Configuration;

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

use Symfony\Component\Finder\Finder;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Helper class for the backend "Sites" module
 *
 * Load Site configuration TCA from ext:*Configuration/SiteConfigurationTCA
 * and ext:*Configuration/SiteConfigurationTCA/Overrides
 */
class SiteTcaConfiguration
{
    /**
     * Returns a "fake TCA" array that is syntactically identical to
     * "normal" TCA, and just isn't available as $GLOBALS['TCA'].
     *
     * @return array
     */
    public function getTca(): array
    {
        // To allow casual ExtensionManagementUtility methods that works on $GLOBALS['TCA']
        // to change our fake TCA, just kick original TCA, and reset to original at the end.
        $originalTca = $GLOBALS['TCA'];
        $GLOBALS['TCA'] = [];
        $activePackages = GeneralUtility::makeInstance(PackageManager::class)->getActivePackages();
        // First load "full table" files from Configuration/SiteConfigurationTCA
        $finder = new Finder();
        foreach ($activePackages as $package) {
            try {
                $finder->files()->depth(0)->name('*.php')->in($package->getPackagePath() . 'Configuration/SiteConfigurationTCA');
            } catch (\InvalidArgumentException $e) {
                // No such directory in this package
                continue;
            }
            foreach ($finder as $fileInfo) {
                $GLOBALS['TCA'][substr($fileInfo->getBasename(), 0, -4)] = require $fileInfo->getPathname();
            }
        }
        // Execute override files from Configuration/TCA/Overrides
        foreach ($activePackages as $package) {
            try {
                $finder->files()->depth(0)->name('*.php')->in($package->getPackagePath() . 'Configuration/SiteConfigurationTCA/Overrides');
            } catch (\InvalidArgumentException $e) {
                // No such directory in this package
                continue;
            }
            foreach ($finder as $fileInfo) {
                require $fileInfo->getPathname();
            }
        }
        $result = $GLOBALS['TCA'];
        $GLOBALS['TCA'] = $originalTca;
        return $result;
    }
}
