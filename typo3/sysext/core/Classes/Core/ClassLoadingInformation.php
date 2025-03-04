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

namespace TYPO3\CMS\Core\Core;

use Composer\Autoload\ClassLoader;
use TYPO3\ClassAliasLoader\ClassAliasMap;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Get and manipulate class loading information, only necessary/in use
 * when TYPO3 is not purely set up by composer but when e.g. extensions are installed via the extension manager
 * by utilizing the composer class loader and adding more information built by the ClassLoadingInformationGenerator
 * class.
 *
 * @internal
 */
class ClassLoadingInformation
{
    /**
     * Base directory storing all autoload information
     */
    public const AUTOLOAD_INFO_DIR = 'autoload/';

    /**
     * Base directory storing all autoload information in testing context
     */
    public const AUTOLOAD_INFO_DIR_TESTS = 'autoload-tests/';

    /**
     * Name of file that contains all classes-filename mappings
     */
    public const AUTOLOAD_CLASSMAP_FILENAME = 'autoload_classmap.php';

    /**
     * Name of file that contains all PSR4 mappings, fetched from the composer.json files of extensions
     */
    public const AUTOLOAD_PSR4_FILENAME = 'autoload_psr4.php';

    /**
     * Name of file that contains all class alias mappings
     */
    public const AUTOLOAD_CLASSALIASMAP_FILENAME = 'autoload_classaliasmap.php';

    /**
     * @var ClassLoader
     */
    protected static $classLoader;

    /**
     * Sets the package manager instance
     *
     * @internal
     */
    public static function setClassLoader(ClassLoader $classLoader)
    {
        static::$classLoader = $classLoader;
    }

    /**
     * Checks if the autoload_classmap.php exists and we are not in testing context.
     * Used to see if the ClassLoadingInformationGenerator should be called.
     *
     * @return bool
     */
    public static function isClassLoadingInformationAvailable()
    {
        return file_exists(self::getClassLoadingInformationDirectory() . self::AUTOLOAD_CLASSMAP_FILENAME);
    }

    /**
     * Puts all information compiled by the ClassLoadingInformationGenerator to files
     */
    public static function dumpClassLoadingInformation()
    {
        self::ensureAutoloadInfoDirExists();
        $activeExtensionPackages = static::getActiveExtensionPackages();

        $generator = new ClassLoadingInformationGenerator();
        $classInfoFiles = $generator->buildAutoloadInformationFiles(self::isTestingContext(), Environment::getPublicPath() . '/', $activeExtensionPackages);
        GeneralUtility::writeFile(self::getClassLoadingInformationDirectory() . self::AUTOLOAD_CLASSMAP_FILENAME, $classInfoFiles['classMapFile'], true);
        GeneralUtility::writeFile(self::getClassLoadingInformationDirectory() . self::AUTOLOAD_PSR4_FILENAME, $classInfoFiles['psr-4File'], true);

        $classAliasMapFile = $generator->buildClassAliasMapFile($activeExtensionPackages);
        GeneralUtility::writeFile(self::getClassLoadingInformationDirectory() . self::AUTOLOAD_CLASSALIASMAP_FILENAME, $classAliasMapFile, true);
    }

    /**
     * Registers the class aliases, the class maps and the PSR4 prefixes previously identified by
     * the ClassLoadingInformationGenerator during runtime.
     */
    public static function registerClassLoadingInformation()
    {
        $composerClassLoader = static::getClassLoader();

        $dynamicClassAliasMapFile = self::getClassLoadingInformationDirectory() . self::AUTOLOAD_CLASSALIASMAP_FILENAME;
        if (file_exists($dynamicClassAliasMapFile)) {
            $classAliasMap = require $dynamicClassAliasMapFile;
            if (is_array($classAliasMap) && !empty($classAliasMap['aliasToClassNameMapping']) && !empty($classAliasMap['classNameToAliasMapping'])) {
                ClassAliasMap::addAliasMap($classAliasMap);
            }
        }

        $dynamicClassMapFile = self::getClassLoadingInformationDirectory() . self::AUTOLOAD_CLASSMAP_FILENAME;
        if (file_exists($dynamicClassMapFile)) {
            $classMap = require $dynamicClassMapFile;
            if (!empty($classMap) && is_array($classMap)) {
                $composerClassLoader->addClassMap($classMap);
            }
        }

        $dynamicPsr4File = self::getClassLoadingInformationDirectory() . self::AUTOLOAD_PSR4_FILENAME;
        if (file_exists($dynamicPsr4File)) {
            $psr4 = require $dynamicPsr4File;
            if (is_array($psr4)) {
                foreach ($psr4 as $prefix => $paths) {
                    $composerClassLoader->setPsr4($prefix, $paths);
                }
            }
        }
    }

    /**
     * Sets class loading information for a package for the current web request
     *
     * @throws \TYPO3\CMS\Core\Error\Exception
     */
    public static function registerTransientClassLoadingInformationForPackage(PackageInterface $package)
    {
        $composerClassLoader = static::getClassLoader();
        $generator = new ClassLoadingInformationGenerator();
        $classInformation = $generator->buildClassLoadingInformationForPackage($package, false, self::isTestingContext(), Environment::getPublicPath() . '/');
        $composerClassLoader->addClassMap($classInformation['classMap']);
        foreach ($classInformation['psr-4'] as $prefix => $paths) {
            $composerClassLoader->setPsr4($prefix, $paths);
        }
        $classAliasMap = $generator->buildClassAliasMapForPackage($package);
        if (!empty($classAliasMap['aliasToClassNameMapping']) && !empty($classAliasMap['classNameToAliasMapping'])) {
            ClassAliasMap::addAliasMap($classAliasMap);
        }
    }

    /**
     * @return string
     */
    protected static function getClassLoadingInformationDirectory()
    {
        if (self::isTestingContext()) {
            return Environment::getLegacyConfigPath() . '/' . self::AUTOLOAD_INFO_DIR_TESTS;
        }
        return Environment::getLegacyConfigPath() . '/' . self::AUTOLOAD_INFO_DIR;
    }

    /**
     * Get class name for alias
     *
     * @param string $alias
     * @return class-string
     */
    public static function getClassNameForAlias($alias)
    {
        return ClassAliasMap::getClassNameForAlias($alias);
    }

    /**
     * Ensures the defined path for class information files exists
     * And clears it in case we're in testing context
     */
    protected static function ensureAutoloadInfoDirExists()
    {
        $autoloadInfoDir = self::getClassLoadingInformationDirectory();
        if (!file_exists($autoloadInfoDir)) {
            GeneralUtility::mkdir_deep($autoloadInfoDir);
        }
    }

    /**
     * Internal method calling the bootstrap to fetch the composer class loader
     *
     * @return ClassLoader
     * @internal Currently used in TYPO3 testing. Public visibility is experimental and may vanish without further notice.
     */
    public static function getClassLoader()
    {
        return static::$classLoader;
    }

    /**
     * Internal method calling the bootstrap to get application context information
     *
     * @return bool
     * @throws \TYPO3\CMS\Core\Exception
     */
    protected static function isTestingContext()
    {
        return Environment::getContext()->isTesting();
    }

    /**
     * Get all packages except the protected ones, as they are covered already
     *
     * @return PackageInterface[]
     */
    protected static function getActiveExtensionPackages()
    {
        $activeExtensionPackages = [];
        $packageManager = GeneralUtility::makeInstance(PackageManager::class);
        foreach ($packageManager->getActivePackages() as $package) {
            if ($package->getPackageMetaData()->isFrameworkType()) {
                // Skip all core packages as the class loading info is prepared for them already
                continue;
            }
            $activeExtensionPackages[] = $package;
        }
        return $activeExtensionPackages;
    }
}
