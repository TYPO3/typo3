<?php
namespace TYPO3\CMS\Core\Core;
/**
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

use Helhum\ClassAliasLoader\Composer\ClassAliasLoader;

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
	 * @var ClassAliasLoader
	 */
	protected $composerClassLoader;

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
	 * @param ClassAliasLoader $composerClassLoader
	 */
	public function injectComposerClassLoader(ClassAliasLoader $composerClassLoader) {
		$this->composerClassLoader = $composerClassLoader;
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
	 * Build mapping for early instances
	 *
	 * @return array
	 */
	public function buildMappingAndInitializeEarlyInstanceMapping() {
		// Needed for early instance alias mapping
		$aliasToClassNameMapping = array();
		// Final mapping array
		$classNameToAliasMapping = array();
		foreach ($this->packages as $package) {
			if (!$package instanceof \TYPO3\CMS\Core\Package\Package || $package->isProtected()) {
				// Skip non core packages and all protected packages.
				// The latter will be covered by composer class loader.
				continue;
			}
			foreach ($package->getClassAliases() as $aliasClassName => $className) {
				$lowercasedAliasClassName = strtolower($aliasClassName);
				$aliasToClassNameMapping[$lowercasedAliasClassName] = $className;
				$classNameToAliasMapping[$className][$lowercasedAliasClassName] = $lowercasedAliasClassName;
			}
		}
		$this->initializeAndSetAliasesForEarlyInstances($aliasToClassNameMapping);

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
			if (FALSE !== $classLoadingInformation) {
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
	protected function initializeAndSetAliasesForEarlyInstances(array $aliasToClassNameMapping) {
		$classesLoadedPriorToClassLoader = array_intersect($aliasToClassNameMapping, array_merge(get_declared_classes(), get_declared_interfaces()));
		if (empty($classesLoadedPriorToClassLoader)) {
			return;
		}

		foreach ($classesLoadedPriorToClassLoader as $aliasClassName => $originalClassName) {
			$this->setAliasForClassName($aliasClassName, $originalClassName);
		}
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
		return (class_exists($aliasClassName, FALSE) || interface_exists($aliasClassName, FALSE)) ? TRUE : class_alias($originalClassName, $aliasClassName);
	}

	/**
	 * Get final class name of alias
	 *
	 * @param string $alias
	 * @return string
	 */
	public function getClassNameForAlias($alias) {
		$lookUpClassName = strtolower($alias);
		$className = $this->composerClassLoader->getClassNameForAlias($alias);
		return isset($this->aliasToClassNameMapping[$lookUpClassName]) ? $this->aliasToClassNameMapping[$lookUpClassName] : $className;
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