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
/**
 * This class is responsible for setting and containing class aliases
 *
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
	 * @var \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend
	 */
	protected $classesCache;

	/**
	 * @var \TYPO3\CMS\Core\Core\ClassLoader
	 */
	protected $classLoader;

	/**
	 * @var string
	 */
	protected $cacheIdentifier;

	/**
	 * An array of \TYPO3\Flow\Package\Package objects
	 * @var array
	 */
	protected $packages = array();

	/**
	 * @param \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend $classesCache
	 */
	public function injectClassesCache(\TYPO3\CMS\Core\Cache\Frontend\PhpFrontend $classesCache) {
		$this->classesCache = $classesCache;
	}

	/**
	 * @param \TYPO3\CMS\Core\Core\ClassLoader
	 */
	public function injectClassLoader(\TYPO3\CMS\Core\Core\ClassLoader $classLoader) {
		$this->classLoader = $classLoader;
	}

	/**
	 * @return string
	 */
	protected function getCacheIdentifier() {
		return $this->cacheIdentifier;
	}

	/**
	 * @param string $cacheIdentifier
	 * @return ClassAliasMap
	 */
	public function setCacheIdentifier($cacheIdentifier) {
		$this->cacheIdentifier = $cacheIdentifier;
		return $this;
	}

	/**
	 * @return string
	 */
	protected function getCacheEntryIdentifier() {
		$cacheIdentifier = $this->getCacheIdentifier();
		return $cacheIdentifier !== NULL ? 'ClassAliasMap_' . TYPO3_MODE  . '_' . $cacheIdentifier : NULL;
	}

	/**
	 * @param array $packages
	 */
	public function setPackages(array $packages) {
		$this->packages = $packages;
		if (!$this->loadEarlyInstanceMappingFromCache()) {
			$classNameToAliasMapping = $this->buildMappingAndInitializeEarlyInstanceMapping();
			$this->buildMappingFiles($classNameToAliasMapping);
		}
	}

	/**
	 * @param array $packages
	 * @return array
	 */
	public function setPackagesButDontBuildMappingFilesReturnClassNameToAliasMappingInstead(array $packages) {
		$this->packages = $packages;
		return $this->buildMappingAndInitializeEarlyInstanceMapping();
	}

	/**
	 * @return bool
	 */
	protected function loadEarlyInstanceMappingFromCache() {
		$cacheEntryIdentifier = $this->getCacheEntryIdentifier();
		if (!$cacheEntryIdentifier !== NULL && $this->classesCache->has($cacheEntryIdentifier)) {
			return (bool) $this->classesCache->requireOnce($cacheEntryIdentifier);
		}
		return FALSE;
	}

	/**
	 * @return array
	 */
	protected function buildMappingAndInitializeEarlyInstanceMapping() {
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
	 * @param array $classNameToAliasMapping
	 */
	public function buildMappingFiles(array $classNameToAliasMapping) {
		/** @var $cacheBackend \TYPO3\CMS\Core\Cache\Backend\ClassLoaderBackend */
		$cacheBackend = $this->classesCache->getBackend();
		foreach ($classNameToAliasMapping as $originalClassName => $aliasClassNames) {
			$originalClassNameCacheEntryIdentifier = str_replace('\\', '_', strtolower($originalClassName));
			// Trigger autoloading for all aliased class names, so a cache entry is created
			if ($this->classLoader->loadClass($originalClassName, FALSE) && $originalClassTarget = $cacheBackend->getTargetOfLinkedCacheEntry($originalClassNameCacheEntryIdentifier)) {
				$proxyContent = array(
					$this->buildRequireOnceCommand($originalClassTarget),
					$this->buildClassLoaderCommand(),
				);
				foreach ($aliasClassNames as $aliasClassName) {
					$proxyContent[] = $this->buildAliasCommand($aliasClassName, $originalClassName);
				}
				$this->classesCache->set($originalClassNameCacheEntryIdentifier, implode(LF, $proxyContent));
			}
		}
		foreach ($classNameToAliasMapping as $originalClassName => $aliasClassNames) {
			foreach ($aliasClassNames as $aliasClassName) {
				$aliasClassNameCacheEntryIdentifier = str_replace('\\', '_', strtolower($aliasClassName));
				$originalClassNameCacheEntryIdentifier = str_replace('\\', '_', strtolower($originalClassName));
				if ($this->classesCache->has($aliasClassNameCacheEntryIdentifier)) {
					$this->classesCache->remove($aliasClassNameCacheEntryIdentifier);
				}
				// Link all aliases to original cache entry
				if ($this->classesCache->has($originalClassNameCacheEntryIdentifier)) {
					$cacheBackend->setLinkToOtherCacheEntry($aliasClassNameCacheEntryIdentifier, $originalClassNameCacheEntryIdentifier);
				}
			}
		}
	}

	/**
	 * @param array $aliasToClassNameMapping
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
				$this->classesCache->set($this->getCacheEntryIdentifier(), implode(LF, $proxyContent));
				$this->classesCache->requireOnce($cacheEntryIdentifier);
			} else {
				eval(implode(PHP_EOL, $proxyContent));
			}
		}
	}

	/**
	 * @return string
	 */
	protected function buildClassLoaderCommand() {
		return '$classLoader = \\TYPO3\\CMS\\Core\\Core\\Bootstrap::getInstance()->getEarlyInstance(\'TYPO3\\CMS\\Core\\Core\\ClassLoader\');';
	}

	/**
	 * @param string $aliasClassName
	 * @param string $originalClassName
	 * @return string
	 */
	protected function buildAliasCommand($aliasClassName, $originalClassName) {
		return sprintf('%s->setAliasForClassName(\'%s\', \'%s\');', '$classLoader', $aliasClassName, $originalClassName);
	}

	/**
	 * @param string $classFilePath
	 * @return string
	 */
	protected function buildRequireOnceCommand($classFilePath) {
		return sprintf('require_once __DIR__ . \'/%s\';', $classFilePath);
	}

	/**
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
	 * @param string $alias
	 * @return mixed
	 */
	public function getClassNameForAlias($alias) {
		$lookUpClassName = strtolower($alias);
		return isset($this->aliasToClassNameMapping[$lookUpClassName]) ? $this->aliasToClassNameMapping[$lookUpClassName] : $alias;
	}


	/**
	 * @param string $className
	 * @return mixed
	 */
	public function getAliasesForClassName($className) {
		$lookUpClassName = strtolower($className);
		return isset($this->classNameToAliasMapping[$lookUpClassName]) ? $this->classNameToAliasMapping[$lookUpClassName] : array($className);
	}

}

?>