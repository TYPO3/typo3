<?php
namespace TYPO3\Flow\Core;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Class Loader implementation which loads .php files found in the classes
 * directory of an object.
 *
 * @Flow\Proxy(false)
 * @Flow\Scope("singleton")
 */
class ClassLoader {

	/**
	 * @var \TYPO3\Flow\Cache\Frontend\PhpFrontend
	 */
	protected $classesCache;

	/**
	 * An array of \TYPO3\Flow\Package\Package objects
	 * @var array
	 */
	protected $packages = array();

	/**
	 * @var string
	 */
	protected $packagesPath = FLOW_PATH_PACKAGES;

	/**
	 * A list of namespaces this class loader is definitely responsible for
	 * @var array
	 */
	protected $packageNamespaces = array(
		'TYPO3\Flow' => 10
	);

	/**
	 * @var boolean
	 */
	protected $considerTestsNamespace = FALSE;

	/**
	 * @var array
	 */
	protected $ignoredClassNames = array(
		'integer' => TRUE,
		'string' => TRUE,
		'param' => TRUE,
		'return' => TRUE,
		'var' => TRUE,
		'throws' => TRUE,
		'api' => TRUE,
		'todo' => TRUE,
		'fixme' => TRUE,
		'see' => TRUE,
		'license' => TRUE,
		'author' => TRUE,
		'test' => TRUE,
	);

	/**
	 * Injects the cache for storing the renamed original classes
	 *
	 * @param \TYPO3\Flow\Cache\Frontend\PhpFrontend $classesCache
	 * @return void
	 */
	public function injectClassesCache(\TYPO3\Flow\Cache\Frontend\PhpFrontend $classesCache) {
		$this->classesCache = $classesCache;
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

		// Loads any known proxied class:
		if ($this->classesCache !== NULL && $this->classesCache->requireOnce(str_replace('\\', '_', $className)) !== FALSE) {
			return TRUE;
		}

		// Workaround for Doctrine's annotation parser which does a class_exists() for annotations like "@param" and so on:
		if (isset($this->ignoredClassNames[$className]) || isset($this->ignoredClassNames[substr($className, strrpos($className, '\\') + 1)])) {
			return FALSE;
		}

		// Load classes from the Flow package at a very early stage where
		// no packages have been registered yet:
		if ($this->packages === array() && substr($className, 0, 10) === 'TYPO3\Flow') {
			require(FLOW_PATH_FLOW . 'Classes/TYPO3/Flow/' . str_replace('\\', '/', substr($className, 11)) . '.php');
			return TRUE;
		}

		// Loads any non-proxied class of registered packages:
		foreach ($this->packageNamespaces as $packageNamespace => $packageData) {
			// replace underscores in classname with \ to match for packagenamespace
			if (substr(str_replace('_', '\\', $className), 0, $packageData['namespaceLength']) === $packageNamespace) {
				if ($this->considerTestsNamespace === TRUE && substr($className, $packageData['namespaceLength'] + 1, 16) === 'Tests\Functional') {
					$classPathAndFilename = $this->packages[str_replace('\\', '.', $packageNamespace)]->getPackagePath() . str_replace('\\', '/', substr($className, $packageData['namespaceLength'] + 1)) . '.php';
				} else {
					// make the classname PSR-0 compliant by replacing underscores only in the classname not in the namespace
					$fileName  = '';
					$lastNamespacePosition = strrpos($className, '\\');
					if ($lastNamespacePosition !== FALSE) {
						$namespace = substr($className, 0, $lastNamespacePosition);
						$className = substr($className, $lastNamespacePosition + 1);
						$fileName  = str_replace('\\', '/', $namespace) . '/';
					}
					$fileName .= str_replace('_', '/', $className) . '.php';

					$classPathAndFilename = $packageData['classesPath'] . $fileName;
				}
				try {
					$result = include($classPathAndFilename);
					if ($result !== FALSE) {
						return TRUE;
					}
				} catch (\Exception $e) {
				}
			}
		}
		return FALSE;
	}

	/**
	 * Sets the available packages
	 *
	 * @param array $packages An array of \TYPO3\Flow\Package\Package objects
	 * @return void
	 */
	public function setPackages(array $packages) {
		$this->packages = $packages;
		foreach ($packages as $package) {
			$this->packageNamespaces[$package->getNamespace()] = array('namespaceLength' => strlen($package->getNamespace()), 'classesPath' => $package->getClassesPath());
		}

		// sort longer package namespaces first, to find specific matches before generic ones
		uksort($this->packageNamespaces, function($a, $b) {
			if (strlen($a) === strlen($b)) {
				return strcmp($a, $b);
			}
			return (strlen($a) > strlen($b)) ? -1 : 1;
		});
	}

	/**
	 * Sets the flag which enables or disables autoloading support for functional
	 * test files.
	 *
	 * @param boolean $flag
	 * @return void
	 */
	public function setConsiderTestsNamespace($flag) {
		$this->considerTestsNamespace = $flag;
	}
}
