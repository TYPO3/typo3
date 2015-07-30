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
use Helhum\ClassAliasLoader\Composer\ClassAliasLoader;
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
	 * Base directory storing all autoload information in testing context
	 */
	const AUTOLOAD_INFO_DIR_TESTS = 'typo3temp/autoload-tests/';

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
		return file_exists(self::getClassLoadingInformationDirectory() . self::AUTOLOAD_CLASSMAP_FILENAME);
	}

	/**
	 * Puts all information compiled by the ClassLoadingInformationGenerator to files
	 */
	static public function dumpClassLoadingInformation() {
		self::ensureAutoloadInfoDirExists();
		/** @var ClassLoadingInformationGenerator  $generator */
		$generator = GeneralUtility::makeInstance(ClassLoadingInformationGenerator::class);
		$classInfoFiles = $generator->buildAutoloadInformationFiles();
		GeneralUtility::writeFile(self::getClassLoadingInformationDirectory() . self::AUTOLOAD_CLASSMAP_FILENAME, $classInfoFiles['classMapFile']);
		GeneralUtility::writeFile(self::getClassLoadingInformationDirectory() . self::AUTOLOAD_PSR4_FILENAME, $classInfoFiles['psr-4File']);

		$classAliasMapFile = $generator->buildClassAliasMapFile();
		GeneralUtility::writeFile(self::getClassLoadingInformationDirectory() . self::AUTOLOAD_CLASSALIASMAP_FILENAME, $classAliasMapFile);
	}

	/**
	 * Registers the class aliases, the class maps and the PSR4 prefixes previously identified by
	 * the ClassLoadingInformationGenerator during runtime.
	 */
	static public function registerClassLoadingInformation() {
		$composerClassLoader = static::getClassLoader();

		$dynamicClassAliasMapFile = self::getClassLoadingInformationDirectory() . self::AUTOLOAD_CLASSALIASMAP_FILENAME;
		if (file_exists($dynamicClassAliasMapFile)) {
			$classAliasMap = require $dynamicClassAliasMapFile;
			if (is_array($classAliasMap) && !empty($classAliasMap['aliasToClassNameMapping']) && !empty($classAliasMap['classNameToAliasMapping'])) {
				self::getClassAliasLoader($composerClassLoader)->addAliasMap($classAliasMap);
			}
		}

		$dynamicClassMapFile = self::getClassLoadingInformationDirectory() . self::AUTOLOAD_CLASSMAP_FILENAME;
		if (file_exists($dynamicClassMapFile)) {
			$classMap = require $dynamicClassMapFile;
			if (is_array($classMap)) {
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
	 * @param PackageInterface $package
	 * @throws \TYPO3\CMS\Core\Error\Exception
	 */
	static public function registerTransientClassLoadingInformationForPackage(PackageInterface $package) {
		$composerClassLoader = static::getClassLoader();

		/** @var ClassLoadingInformationGenerator  $generator */
		$generator = GeneralUtility::makeInstance(ClassLoadingInformationGenerator::class);

		$classInformation = $generator->buildClassLoadingInformationForPackage($package);
		$composerClassLoader->addClassMap($classInformation['classMap']);
		foreach ($classInformation['psr-4'] as $prefix => $paths) {
			$composerClassLoader->setPsr4($prefix, $paths);
		}
		$classAliasMap = $generator->buildClassAliasMapForPackage($package);
		if (is_array($classAliasMap) && !empty($classAliasMap['aliasToClassNameMapping']) && !empty($classAliasMap['classNameToAliasMapping'])) {
			self::getClassAliasLoader($composerClassLoader)->addAliasMap($classAliasMap);
		}
	}

	/**
	 * @return string
	 */
	static protected function getClassLoadingInformationDirectory() {
		if (Bootstrap::getInstance()->getApplicationContext()->isTesting()) {
			return PATH_site . self::AUTOLOAD_INFO_DIR_TESTS;
		} else {
			return PATH_site . self::AUTOLOAD_INFO_DIR;
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
	 * And clears it in case we're in testing context
	 */
	static protected function ensureAutoloadInfoDirExists() {
		$autoloadInfoDir = self::getClassLoadingInformationDirectory();
		if (!file_exists($autoloadInfoDir)) {
			GeneralUtility::mkdir_deep($autoloadInfoDir);
		} elseif (Bootstrap::getInstance()->getApplicationContext()->isTesting()) {
			GeneralUtility::flushDirectory($autoloadInfoDir, TRUE);
		}
	}

	/**
	 * Internal method calling the bootstrap to fetch the composer class loader
	 *
	 * @return ClassAliasLoader|ComposerClassLoader
	 * @throws \TYPO3\CMS\Core\Exception
	 */
	static protected function getClassLoader() {
		return Bootstrap::getInstance()->getEarlyInstance(ComposerClassLoader::class);
	}

	/**
	 * Internal method calling the bootstrap to fetch the composer class loader
	 *
	 * @param ClassAliasLoader|ComposerClassLoader $composerClassLoader
	 * @return ClassAliasLoader
	 * @throws \TYPO3\CMS\Core\Exception
	 */
	static protected function getClassAliasLoader($composerClassLoader) {
		if ($composerClassLoader instanceof ClassAliasLoader) {
			return $composerClassLoader;
		}
		$aliasLoader = new ClassAliasLoader($composerClassLoader);
		$aliasLoader->register(TRUE);
		Bootstrap::getInstance()->setEarlyInstance(ComposerClassLoader::class, $aliasLoader);

		return $aliasLoader;
	}

}
