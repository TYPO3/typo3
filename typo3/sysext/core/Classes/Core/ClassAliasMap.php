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
 *  A copy is found in the text file GPL.txt and important notices to the license
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

/**
 * This class is responsible for setting and containing class aliases
 */
class ClassAliasMap implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Old class name to new class name mapping
	 *
	 * @var array
	 */
	protected $aliasToClassNameMapping = array();

	/**
	 * New class name to old class name mapping
	 *
	 * @var array
	 */
	protected $classNameToAliasMapping = array();

	/**
	 * @var \TYPO3\CMS\Core\Cache\Frontend\StringFrontend
	 */
	protected $classesCache;

	/**
	 * @var \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend
	 */
	protected $coreCache;

	/**
	 * @var ClassLoader
	 */
	protected $classLoader;

	/**
	 * @var string Cache identifier
	 */
	protected $cacheIdentifier;

	/**
	 * @var \TYPO3\Flow\Package\Package[]
	 */
	protected $packages = array();

	/**
	 * @param \TYPO3\CMS\Core\Cache\Frontend\StringFrontend $classesCache
	 */
	public function injectClassesCache(\TYPO3\CMS\Core\Cache\Frontend\StringFrontend $classesCache) {
		$this->classesCache = $classesCache;
	}

	/**
	 * @param \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend $coreCache
	 */
	public function injectCoreCache(\TYPO3\CMS\Core\Cache\Frontend\PhpFrontend $coreCache) {
		$this->coreCache = $coreCache;
	}

	/**
	 * @param ClassLoader
	 */
	public function injectClassLoader(ClassLoader $classLoader) {
		$this->classLoader = $classLoader;
	}

	/**
	 * @return string
	 */
	protected function getCacheIdentifier() {
		return $this->cacheIdentifier;
	}

	/**
	 * Set cache identifier
	 *
	 * @param string $cacheIdentifier
	 * @return ClassAliasMap
	 */
	public function setCacheIdentifier($cacheIdentifier) {
		$this->cacheIdentifier = $cacheIdentifier;
		return $this;
	}

	/**
	 * Get cache identifier
	 *
	 * @return string
	 */
	protected function getCacheEntryIdentifier() {
		$cacheIdentifier = $this->getCacheIdentifier();
		return $cacheIdentifier !== NULL ? 'ClassAliasMap_' . TYPO3_MODE  . '_' . $cacheIdentifier : NULL;
	}

	/**
	 * Set packages
	 *
	 * @param array $packages
	 * @return ClassAliasMap
	 */
	public function setPackages(array $packages) {
		$this->packages = $packages;
		return $this;
	}

	/**
	 * Load early instance mapping
	 *
	 * @return boolean
	 */
	public function loadEarlyInstanceMappingFromCache() {
		$cacheEntryIdentifier = $this->getCacheEntryIdentifier();
		if (!$cacheEntryIdentifier !== NULL && $this->coreCache->has($cacheEntryIdentifier)) {
			return (boolean)$this->coreCache->requireOnce($cacheEntryIdentifier);
		}
		return FALSE;
	}

	/**
	 * Build mapping for early instances
	 *
	 * @return array
	 */
	public function buildMappingAndInitializeEarlyInstanceMapping() {
		$aliasToClassNameMapping = array();
		foreach ($this->packages as $package) {
			if ($package instanceof \TYPO3\CMS\Core\Package\Package) {
				$aliasToClassNameMapping = array_merge($aliasToClassNameMapping, $package->getClassAliases());
			}
		}
		$lowercasedAliasToClassNameMapping = array();
		foreach ($aliasToClassNameMapping as $aliasClassName => $className) {
			$lowercasedAliasToClassNameMapping[strtolower($aliasClassName)] = $className;
		}
		$aliasToClassNameMapping = $lowercasedAliasToClassNameMapping;
		$classNameToAliasMapping = array();
		foreach (array_flip($aliasToClassNameMapping) as $className => $aliasClassName) {
			$lookUpClassName = strtolower($className);
			if (!isset($classNameToAliasMapping[$lookUpClassName])) {
				$classNameToAliasMapping[$lookUpClassName] = array();
			}
			$classNameToAliasMapping[$lookUpClassName][] = $aliasClassName;
		}

		$this->buildEarlyInstanceMappingAndSaveToCache($aliasToClassNameMapping);

		$classNameToAliasMapping = array();
		foreach ($aliasToClassNameMapping as $aliasClassName => $originalClassName) {
			$classNameToAliasMapping[$originalClassName][$aliasClassName] = $aliasClassName;
		}

		return $classNameToAliasMapping;
	}

	/**
	 * Build mapping files
	 *
	 * @param array $classNameToAliasMapping
	 * @return void
	 */
	public function buildMappingFiles(array $classNameToAliasMapping) {
		foreach ($classNameToAliasMapping as $originalClassName => $aliasClassNames) {
			$originalClassNameCacheEntryIdentifier = str_replace('\\', '_', strtolower($originalClassName));
			// Trigger autoloading for all aliased class names, so a cache entry is created
			$classLoadingInformation = $this->classLoader->buildClassLoadingInformation($originalClassName);
			if (NULL !== $classLoadingInformation) {
				$classLoadingInformation = implode("\xff", array_merge($classLoadingInformation, $aliasClassNames));
				$this->classesCache->set($originalClassNameCacheEntryIdentifier, $classLoadingInformation);
				foreach ($aliasClassNames as $aliasClassName) {
					$aliasClassNameCacheEntryIdentifier = str_replace('\\', '_', strtolower($aliasClassName));
					$this->classesCache->set($aliasClassNameCacheEntryIdentifier, $classLoadingInformation);
				}
			}
		}
	}

	/**
	 * Build and save mapping files to cache
	 *
	 * @param array $aliasToClassNameMapping
	 * @return void
	 */
	protected function buildEarlyInstanceMappingAndSaveToCache(array $aliasToClassNameMapping) {
		$classedLoadedPriorToClassLoader = array_intersect($aliasToClassNameMapping, array_merge(get_declared_classes(), get_declared_interfaces()));
		if (!empty($classedLoadedPriorToClassLoader)) {
			$proxyContent = array($this->buildClassLoaderCommand());
			foreach ($classedLoadedPriorToClassLoader as $aliasClassName => $originalClassName) {
				$proxyContent[] = $this->buildAliasCommand($aliasClassName, $originalClassName);
			}
			$cacheEntryIdentifier = $this->getCacheEntryIdentifier();
			if ($cacheEntryIdentifier !== NULL) {
				$this->coreCache->set($this->getCacheEntryIdentifier(), implode(LF, $proxyContent));
				$this->coreCache->requireOnce($cacheEntryIdentifier);
			} else {
				eval(implode(PHP_EOL, $proxyContent));
			}
		}
	}

	/**
	 * String command to build class loader
	 *
	 * @return string
	 */
	protected function buildClassLoaderCommand() {
		return '$classLoader = \\TYPO3\\CMS\\Core\\Core\\Bootstrap::getInstance()->getEarlyInstance(\'TYPO3\\CMS\\Core\\Core\\ClassLoader\');';
	}

	/**
	 * String command to build class alias
	 *
	 * @param string $aliasClassName
	 * @param string $originalClassName
	 * @return string
	 */
	protected function buildAliasCommand($aliasClassName, $originalClassName) {
		return sprintf('%s->setAliasForClassName(\'%s\', \'%s\');', '$classLoader', $aliasClassName, $originalClassName);
	}

	/**
	 * Creates a require_once command for the given file.
	 *
	 * @param string $requiredFile
	 * @return string
	 */
	protected function buildRequireOnceCommand($requiredFile) {
		return sprintf('require_once \'%s\';', $requiredFile);
	}

	/**
	 * Set an alias for a class name
	 *
	 * @param string $aliasClassName
	 * @param string $originalClassName
	 * @return bool true on success or false on failure
	 */
	public function setAliasForClassName($aliasClassName, $originalClassName) {
		if (isset($this->aliasToClassNameMapping[$lowercasedAliasClassName = strtolower($aliasClassName)])) {
			return TRUE;
		}
		$this->aliasToClassNameMapping[$lowercasedAliasClassName] = $originalClassName;
		$this->classNameToAliasMapping[strtolower($originalClassName)][$lowercasedAliasClassName] = $aliasClassName;
		return (\class_exists($aliasClassName, FALSE) || \interface_exists($aliasClassName, FALSE)) ? TRUE : class_alias($originalClassName, $aliasClassName);
	}

	/**
	 * Get final class name of alias
	 *
	 * @param string $alias
	 * @return mixed
	 */
	public function getClassNameForAlias($alias) {
		$lookUpClassName = strtolower($alias);
		return isset($this->aliasToClassNameMapping[$lookUpClassName]) ? $this->aliasToClassNameMapping[$lookUpClassName] : $alias;
	}


	/**
	 * Get list of aliases for class name
	 *
	 * @param string $className
	 * @return mixed
	 */
	public function getAliasesForClassName($className) {
		$lookUpClassName = strtolower($className);
		return isset($this->classNameToAliasMapping[$lookUpClassName]) ? $this->classNameToAliasMapping[$lookUpClassName] : array($className);
	}
}