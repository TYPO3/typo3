<?php
namespace TYPO3\CMS\Core\Core;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Thomas Maroschik <tmaroschik@dfau.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class Loader implementation which loads .php files found in the classes
 * directory of an object.
 *
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 */
class ClassLoader {

	/**
	 * @var \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend
	 */
	protected $classesCache;

	/**
	 * An array of \TYPO3\Flow\Package\Package objects
	 * @var array
	 */
	protected $packages = array();

	/**
	 * @var ClassAliasMap
	 */
	protected $classAliasMap;

	/**
	 * @var ClassAliasMap
	 */
	static protected $staticAliasMap;

	/**
	 * @var array
	 */
	protected $classFileAutoloadRegistry = array();

	/**
	 * A list of namespaces this class loader is definitely responsible for
	 * @var array
	 */
	protected $packageNamespaces = array(
		'TYPO3\CMS\Core' => 14
	);

	/**

	 * @param ClassAliasMap
	 */
	public function injectClassAliasMap(ClassAliasMap $classAliasMap) {
		$this->classAliasMap = $classAliasMap;
		static::$staticAliasMap = $classAliasMap;
	}

	/**
	 * Loads php files containing classes or interfaces found in the classes directory of
	 * a package and specifically registered classes.
	 *
	 * @param string $className Name of the class/interface to load
	 * @return boolean
	 */
	public function loadClass($className) {
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}

		$realClassName = $this->classAliasMap->getClassNameForAlias($className);
		$aliasClassNames = $this->classAliasMap->getAliasesForClassName($className);
		$hasAliasClassNames = (!in_array($className, $aliasClassNames));
		$lookUpClassName = ($hasRealClassName = $className !== $realClassName) ? $realClassName : $className;
		$classLoaded = FALSE;

		// Loads any known proxied class:
		if ($this->classesCache !== NULL && $this->classesCache->requireOnce($cacheEntryIdentifier = str_replace('\\', '_', $lookUpClassName)) !== FALSE) {
			$classLoaded = TRUE;
		}

		// Load classes from the CMS Core package at a very early stage where
		// no packages have been registered yet:
		if (!$classLoaded && substr($lookUpClassName, 0, 14) === 'TYPO3\CMS\Core') {
			$classLoaded = (bool) @include_once(PATH_typo3 . 'sysext/core/Classes/' . str_replace('\\', '/', substr($lookUpClassName, 15)) . '.php');
		}

		if (isset($this->classFileAutoloadRegistry[$lowercasedUpClassName = strtolower($lookUpClassName)])) {
			$classLoaded = (bool) @include_once($this->classFileAutoloadRegistry[$lowercasedUpClassName]);
		}

		if (!$classLoaded) {
			// Loads any non-proxied class of registered packages:
			foreach ($this->packageNamespaces as $packageNamespace => $packageData) {
				if (substr($lookUpClassName, 0, $packageData['namespaceLength']) === $packageNamespace) {
					if ($packageData['substituteNamespaceInPath']) {
						// If it's a TYPO3 package, classes don't comply to PSR-0.
						// The namespace part is substituted.
						$classPathAndFilename = $packageData['classesPath'] . '/'.  str_replace('\\', '/', ltrim(substr($lookUpClassName, $packageData['namespaceLength']), '\\')) . '.php';
					} else {
						$classPathAndFilename = $packageData['classesPath'] . '/'.  str_replace('\\', '/', $lookUpClassName) . '.php';
					}
					if (class_exists($lookUpClassName, FALSE) || interface_exists($lookUpClassName, FALSE)) {
						//@todo Class has already been loaded, log error
					} elseif ($classLoaded = (bool) @include_once($classPathAndFilename)) {
						if ($this->classesCache !== NULL && $this->classesCache->has($cacheEntryIdentifier) === FALSE) {
							$this->classesCache->set($cacheEntryIdentifier, 'return require \'' . $classPathAndFilename . '\';');
						}
						break;
					}
				}
			}
		}

		if (!$classLoaded) {
			$classLoaded = (bool) @include_once($this->getClassPathByNamingConventionForGivenClassName($lookUpClassName));
		}

		if ($hasRealClassName && !class_exists($className, FALSE)) {
			class_alias($realClassName, $className);
		}
		if ($hasAliasClassNames) {
			foreach ($aliasClassNames as $aliasClassName) {
				if (!class_exists($aliasClassName, FALSE)) {
					class_alias($className, $aliasClassName);
				}
			}
		}
		return $classLoaded;
	}

	/**
	 * Sets the available packages
	 *
	 * @param array $packages An array of \TYPO3\Flow\Package\Package objects
	 * @return void
	 */
	public function setPackages(array $packages) {
		$this->classAliasMap->setPackages($packages);
		$this->packages = $packages;
		/** @var $package \TYPO3\Flow\Package\Package */
		foreach ($packages as $package) {
			$packageNamespace = $package->getNamespace();
			if ($packageNamespace[0] !== '*') {
				$this->packageNamespaces[$packageNamespace] = array(
					'namespaceLength' => strlen($packageNamespace),
					'classesPath' => $package->getClassesPath(),
					'substituteNamespaceInPath' => ($package instanceof \TYPO3\CMS\Core\Package\Package)
				);
			}
			/** @var $package \TYPO3\CMS\Core\Package\Package */
			if ($package instanceof \TYPO3\CMS\Core\Package\Package) {
				$classFilesFromAutoloadRegistry = $package->getClassFilesFromAutoloadRegistry();
				if (is_array($classFilesFromAutoloadRegistry)) {
					$this->classFileAutoloadRegistry = array_merge($this->classFileAutoloadRegistry, $classFilesFromAutoloadRegistry);
				}
			}
		}
		// sort longer package namespaces first, to find specific matches before generic ones
		$sortPackages = function($a, $b) {
			if (($lenA = strlen($a)) === ($lenB = strlen($b))) {
				return strcmp($a, $b);
			}
			return ($lenA > $lenB) ? -1 : 1;
		};
		uksort($this->packageNamespaces, $sortPackages);
	}

	/**
	 * This method is necessary for the early loading of the cores autoload registry
	 *
	 * @param array $classFileAutoloadRegistry
	 */
	public function setClassFileAutoloadRegistry($classFileAutoloadRegistry) {
		$this->classFileAutoloadRegistry = $classFileAutoloadRegistry;
	}

	/**
	 * Try to load a given class name based on 'extbase' naming convention into the registry.
	 * If the file is found it writes an entry to $classNameToFileMapping and re-caches the
	 * array to the file system to save this lookup for next call.
	 *
	 * @param string $className Class name to find source file of
	 * @return string
	 */
	static protected function getClassPathByNamingConventionForGivenClassName($className) {
		$delimiter = '_';
		// To handle namespaced class names, split the class name at the
		// namespace delimiters.
		if (strpos($className, '\\') !== FALSE) {
			$delimiter = '\\';
		}
		$classNameParts = explode($delimiter, $className, 4);
		if (isset($classNameParts[0]) && $classNameParts[0] === 'TYPO3' && (isset($classNameParts[1]) && $classNameParts[1] === 'CMS')) {
			$extensionKey = GeneralUtility::camelCaseToLowerCaseUnderscored($classNameParts[2]);
			$classNameWithoutVendorAndProduct = $classNameParts[3];
		} else {
			$extensionKey = GeneralUtility::camelCaseToLowerCaseUnderscored($classNameParts[1]);
			$classNameWithoutVendorAndProduct = $classNameParts[2];

			if (isset($classNameParts[3])) {
				$classNameWithoutVendorAndProduct .= $delimiter . $classNameParts[3];
			}
		}

		if ($extensionKey) {
			try {
				// This will throw a BadFunctionCallException if the extension is not loaded
				$extensionPath = ExtensionManagementUtility::extPath($extensionKey);
				$classPath = (substr(strtolower($classNameWithoutVendorAndProduct), 0, 5) === 'tests') ? '' : 'Classes/';
				$classFilePathAndName = $extensionPath . $classPath . strtr($classNameWithoutVendorAndProduct, $delimiter, '/') . '.php';
				return $classFilePathAndName;
			} catch (\BadFunctionCallException $exception) {

			}
		}
	}

	/**
	 * @param string $alias
	 * @return mixed
	 */
	static public function getClassNameForAlias($alias) {
		return static::$staticAliasMap->getClassNameForAlias($alias);
	}

	/**
	 * @param string $className
	 * @deprecated since 6.2, use getAliasesForClassName instead. will be removed 2 versions later
	 * @return mixed
	 */
	static public function getAliasForClassName($className) {
		$aliases = static::$staticAliasMap->getAliasesForClassName($className);
		return (is_array($aliases) && isset($aliases[0])) ? $aliases[0] : NULL;
	}

	/**
	 * @param string $className
	 * @return mixed
	 */
	static public function getAliasesForClassName($className) {
		return static::$staticAliasMap->getAliasesForClassName($className);
	}

}

?>