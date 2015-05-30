<?php
namespace TYPO3\CMS\Core\Core;

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

use Composer\Autoload\ClassLoader as ComposerClassLoader;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Get and manipulate class loading information, only necessary/in use
 * when TYPO3 is not purely set up by composer but when e.g. extensions are installed via the extension manager
 * by utilizing the composer class loader and adding more information built by the ClassLoadingInformationGenerator
 * class.
 *
 * @internal
 */
class ClassLoadingInformation {

	/**
	 * Base directory storing all autoload information
	 */
	const AUTOLOAD_INFO_DIR = 'typo3temp/autoload/';

	/**
	 * Name of file that contains all classes-filename mappings
	 */
	const AUTOLOAD_CLASSMAP_FILENAME = 'autoload_classmap.php';

	/**
	 * Name of file that contains all PSR4 mappings, fetched from the composer.json files of extensions
	 */
	const AUTOLOAD_PSR4_FILENAME = 'autoload_psr4.php';

	/**
	 * Name of file that contains all class alias mappings
	 */
	const AUTOLOAD_CLASSALIASMAP_FILENAME = 'autoload_classaliasmap.php';

	/**
	 * Checks if the autoload_classmap.php exists. Used to see if the ClassLoadingInformationGenerator
	 * should be called.
	 *
	 * @return bool
	 */
	static public function classLoadingInformationExists() {
		return file_exists(PATH_site . self::AUTOLOAD_INFO_DIR . self::AUTOLOAD_CLASSMAP_FILENAME);
	}

	/**
	 * Puts all information compiled by the ClassLoadingInformationGenerator to files
	 */
	static public function writeClassLoadingInformation() {
		self::ensureAutoloadInfoDirExists();
		$classInfoFiles = ClassLoadingInformationGenerator::buildAutoloadInformationFiles();
		GeneralUtility::writeFile(PATH_site . self::AUTOLOAD_INFO_DIR . self::AUTOLOAD_CLASSMAP_FILENAME, $classInfoFiles['classMapFile']);
		GeneralUtility::writeFile(PATH_site . self::AUTOLOAD_INFO_DIR . self::AUTOLOAD_PSR4_FILENAME, $classInfoFiles['psr-4File']);

		$classAliasMapFile = ClassLoadingInformationGenerator::buildClassAliasMapFile();
		GeneralUtility::writeFile(PATH_site . self::AUTOLOAD_INFO_DIR . self::AUTOLOAD_CLASSALIASMAP_FILENAME, $classAliasMapFile);
	}

	/**
	 * Registers the class aliases, the class maps and the PSR4 prefixes previously identified by
	 * the ClassLoadingInformationGenerator during runtime.
	 */
	static public function registerClassLoadingInformation() {
		$composerClassLoader = static::getClassLoader();

		$dynamicClassAliasMapFile = PATH_site . self::AUTOLOAD_INFO_DIR . self::AUTOLOAD_CLASSALIASMAP_FILENAME;
		if (file_exists($dynamicClassAliasMapFile)) {
			$classAliasMap = require $dynamicClassAliasMapFile;
			if (is_array($classAliasMap) && !empty($classAliasMap['aliasToClassNameMapping']) && !empty($classAliasMap['classNameToAliasMapping'])) {
				$composerClassLoader->addAliasMap($classAliasMap);
			}
		}

		$dynamicClassMapFile = PATH_site . self::AUTOLOAD_INFO_DIR . self::AUTOLOAD_CLASSMAP_FILENAME;
		if (file_exists($dynamicClassMapFile)) {
			$classMap = require $dynamicClassMapFile;
			if (is_array($classMap)) {
				$composerClassLoader->addClassMap($classMap);
			}
		}

		$dynamicPsr4File = PATH_site . self::AUTOLOAD_INFO_DIR . self::AUTOLOAD_PSR4_FILENAME;
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
	 * @param PackageInterface $package
	 * @throws \TYPO3\CMS\Core\Error\Exception
	 */
	static public function registerTransientClassLoadingInformationForPackage(PackageInterface $package) {
		$composerClassLoader = static::getClassLoader();
		$classInformation = ClassLoadingInformationGenerator::buildClassLoadingInformationForPackage($package);
		$composerClassLoader->addClassMap($classInformation['classMap']);
		foreach ($classInformation['psr-4'] as $prefix => $paths) {
			$composerClassLoader->setPsr4($prefix, $paths);
		}
		if (is_callable(array($composerClassLoader, 'addAliasMap'))) {
			$aliasMap = ClassLoadingInformationGenerator::buildClassAliasMapForPackage($package);
			$composerClassLoader->addAliasMap($aliasMap);
		}
	}

	/**
	 * Get class name for alias
	 *
	 * @param string $alias
	 * @return mixed
	 */
	static public function getClassNameForAlias($alias) {
		$composerClassLoader = static::getClassLoader();
		if (!is_callable(array($composerClassLoader, 'getClassNameForAlias'))) {
			return $alias;
		}
		return $composerClassLoader->getClassNameForAlias($alias);
	}

	/**
	 * Ensures the defined path for class information files exists
	 */
	static protected function ensureAutoloadInfoDirExists() {
		$autoloadInfoDir = PATH_site . self::AUTOLOAD_INFO_DIR;
		if (!file_exists($autoloadInfoDir)) {
			GeneralUtility::mkdir_deep($autoloadInfoDir);
		}
	}

	/**
	 * Internal method calling the bootstrap to fetch the composer class loader
	 *
	 * @return ComposerClassLoader
	 * @throws \TYPO3\CMS\Core\Exception
	 */
	static protected function getClassLoader() {
		return Bootstrap::getInstance()->getEarlyInstance(ComposerClassLoader::class);
	}

}
