<?php
namespace TYPO3\Flow\Package;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Package\Package;
use TYPO3\Flow\Package\PackageFactory;
use TYPO3\Flow\Package\PackageInterface;
use TYPO3\Flow\Utility\Files;
use TYPO3\Flow\Annotations as Flow;

/**
 * The default TYPO3 Package Manager
 *
 * @api
 * @Flow\Scope("singleton")
 */
class PackageManager implements \TYPO3\Flow\Package\PackageManagerInterface {

	/**
	 * @var \TYPO3\Flow\Core\ClassLoader
	 */
	protected $classLoader;

	/**
	 * @var \TYPO3\Flow\Core\Bootstrap
	 */
	protected $bootstrap;

	/**
	 * @var PackageFactory
	 */
	protected $packageFactory;

	/**
	 * Array of available packages, indexed by package key
	 * @var array
	 */
	protected $packages = array();

	/**
	 * A translation table between lower cased and upper camel cased package keys
	 * @var array
	 */
	protected $packageKeys = array();

	/**
	 * A map between ComposerName and PackageKey, only available when scanAvailablePackages is run
	 * @var array
	 */
	protected $composerNameToPackageKeyMap = array();

	/**
	 * List of active packages as package key => package object
	 * @var array
	 */
	protected $activePackages = array();

	/**
	 * Absolute path leading to the various package directories
	 * @var string
	 */
	protected $packagesBasePath;

	/**
	 * @var string
	 */
	protected $packageStatesPathAndFilename;

	/**
	 * Package states configuration as stored in the PackageStates.php file
	 * @var array
	 */
	protected $packageStatesConfiguration = array();

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @param \TYPO3\Flow\Core\ClassLoader $classLoader
	 * @return void
	 */
	public function injectClassLoader(\TYPO3\Flow\Core\ClassLoader $classLoader) {
		$this->classLoader = $classLoader;
	}

	/**
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * @param \TYPO3\Flow\Log\SystemLoggerInterface $systemLogger
	 * @return void
	 */
	public function injectSystemLogger(\TYPO3\Flow\Log\SystemLoggerInterface $systemLogger) {
		if ($this->systemLogger instanceof \TYPO3\Flow\Log\EarlyLogger) {
			$this->systemLogger->replayLogsOn($systemLogger);
			unset($this->systemLogger);
		}
		$this->systemLogger = $systemLogger;
	}

	/**
	 * Initializes the package manager
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap The current bootstrap
	 * @param string $packagesBasePath Absolute path of the Packages directory
	 * @param string $packageStatesPathAndFilename
	 * @return void
	 */
	public function initialize(\TYPO3\Flow\Core\Bootstrap $bootstrap, $packagesBasePath = FLOW_PATH_PACKAGES, $packageStatesPathAndFilename = '') {
		$this->systemLogger = new \TYPO3\Flow\Log\EarlyLogger();

		$this->bootstrap = $bootstrap;
		$this->packagesBasePath = $packagesBasePath;
		$this->packageStatesPathAndFilename = ($packageStatesPathAndFilename === '') ? FLOW_PATH_CONFIGURATION . 'PackageStates.php' : $packageStatesPathAndFilename;
		$this->packageFactory = new PackageFactory($this);

		$this->loadPackageStates();

		foreach ($this->packages as $packageKey => $package) {
			if ($package->isProtected() || (isset($this->packageStatesConfiguration['packages'][$packageKey]['state']) && $this->packageStatesConfiguration['packages'][$packageKey]['state'] === 'active')) {
				$this->activePackages[$packageKey] = $package;
			}
		}

		$this->classLoader->setPackages($this->activePackages);

		foreach ($this->activePackages as $package) {
			$package->boot($bootstrap);
		}

	}

	/**
	 * Returns TRUE if a package is available (the package's files exist in the packages directory)
	 * or FALSE if it's not. If a package is available it doesn't mean necessarily that it's active!
	 *
	 * @param string $packageKey The key of the package to check
	 * @return boolean TRUE if the package is available, otherwise FALSE
	 * @api
	 */
	public function isPackageAvailable($packageKey) {
		return (isset($this->packages[$packageKey]));
	}

	/**
	 * Returns TRUE if a package is activated or FALSE if it's not.
	 *
	 * @param string $packageKey The key of the package to check
	 * @return boolean TRUE if package is active, otherwise FALSE
	 * @api
	 */
	public function isPackageActive($packageKey) {
		return (isset($this->activePackages[$packageKey]));
	}

	/**
	 * Returns the base path for packages
	 *
	 * @return string
	 */
	public function getPackagesBasePath() {
		return $this->packagesBasePath;
	}

	/**
	 * Returns a PackageInterface object for the specified package.
	 * A package is available, if the package directory contains valid MetaData information.
	 *
	 * @param string $packageKey
	 * @return \TYPO3\Flow\Package\PackageInterface The requested package object
	 * @throws \TYPO3\Flow\Package\Exception\UnknownPackageException if the specified package is not known
	 * @api
	 */
	public function getPackage($packageKey) {
		if (!$this->isPackageAvailable($packageKey)) {
			throw new \TYPO3\Flow\Package\Exception\UnknownPackageException('Package "' . $packageKey . '" is not available. Please check if the package exists and that the package key is correct (package keys are case sensitive).', 1166546734);
		}
		return $this->packages[$packageKey];
	}

	/**
	 * Finds a package by a given object of that package; if no such package
	 * could be found, NULL is returned. This basically works with comparing the package class' location
	 * against the given class' location. In order to not being satisfied with a shorter package's root path,
	 * the packages to check are sorted by the length of their root path descending.
	 *
	 * Please note that the class itself must be existing anyways, else PHP's generic "class not found"
	 * exception will be thrown.
	 *
	 * @param object $object The object to find the possessing package of
	 * @return \TYPO3\Flow\Package\PackageInterface The package the given object belongs to or NULL if it could not be found
	 */
	public function getPackageOfObject($object) {
		$sortedAvailablePackages = $this->getAvailablePackages();
		usort($sortedAvailablePackages, function (PackageInterface $packageOne, PackageInterface $packageTwo) {
			return strlen($packageTwo->getPackagePath()) - strlen($packageOne->getPackagePath());
		});

		$className = $this->bootstrap->getObjectManager()->get('TYPO3\Flow\Reflection\ReflectionService')->getClassNameByObject($object);
		$reflectedClass = new \ReflectionClass($className);
		$fileName = Files::getUnixStylePath($reflectedClass->getFileName());

		foreach ($sortedAvailablePackages as $package) {
			$packagePath = Files::getUnixStylePath($package->getPackagePath());
			if (strpos($fileName, $packagePath) === 0) {
				return $package;
			}
		}
		return NULL;
	}

	/**
	 * Returns an array of \TYPO3\Flow\Package objects of all available packages.
	 * A package is available, if the package directory contains valid meta information.
	 *
	 * @return array Array of \TYPO3\Flow\Package\PackageInterface
	 * @api
	 */
	public function getAvailablePackages() {
		return $this->packages;
	}

	/**
	 * Returns an array of \TYPO3\Flow\Package objects of all active packages.
	 * A package is active, if it is available and has been activated in the package
	 * manager settings.
	 *
	 * @return array Array of \TYPO3\Flow\Package\PackageInterface
	 * @api
	 */
	public function getActivePackages() {
		return $this->activePackages;
	}

	/**
	 * Returns an array of \TYPO3\Flow\Package objects of all frozen packages.
	 * A frozen package is not considered by file monitoring and provides some
	 * precompiled reflection data in order to improve performance.
	 *
	 * @return array Array of \TYPO3\Flow\Package\PackageInterface
	 */
	public function getFrozenPackages() {
		$frozenPackages = array();
		if ($this->bootstrap->getContext()->isDevelopment()) {
			foreach ($this->packages as $packageKey => $package) {
				if (isset($this->packageStatesConfiguration['packages'][$packageKey]['frozen']) &&
						$this->packageStatesConfiguration['packages'][$packageKey]['frozen'] === TRUE) {
					$frozenPackages[$packageKey] = $package;
				}
			}
		}
		return $frozenPackages;
	}

	/**
	 * Returns an array of \TYPO3\Flow\PackageInterface objects of all packages that match
	 * the given package state, path, and type filters. All three filters must match, if given.
	 *
	 * @param string $packageState defaults to available
	 * @param string $packagePath
	 * @param string $packageType
	 *
	 * @return array Array of \TYPO3\Flow\Package\PackageInterface
	 * @throws Exception\InvalidPackageStateException
	 * @api
	 */
	public function getFilteredPackages($packageState = 'available', $packagePath = NULL, $packageType = NULL) {
		$packages = array();
		switch (strtolower($packageState)) {
			case 'available':
				$packages = $this->getAvailablePackages();
			break;
			case 'active':
				$packages = $this->getActivePackages();
			break;
			case 'frozen':
				$packages = $this->getFrozenPackages();
			break;
			default:
				throw new \TYPO3\Flow\Package\Exception\InvalidPackageStateException('The package state "' . $packageState . '" is invalid', 1372458274);
		}

		if($packagePath !== NULL) {
			$packages = $this->filterPackagesByPath($packages, $packagePath);
		}
		if($packageType !== NULL) {
			$packages = $this->filterPackagesByType($packages, $packageType);
		}

		return $packages;
	}

	/**
	 * Returns an array of \TYPO3\Flow\Package objects in the given array of packages
	 * that are in the specified Package Path
	 *
	 * @param array $packages Array of \TYPO3\Flow\Package\PackageInterface to be filtered
	 * @param string $filterPath Filter out anything that's not in this path
	 * @return array Array of \TYPO3\Flow\Package\PackageInterface
	 */
	protected function filterPackagesByPath(&$packages, $filterPath) {
		$filteredPackages = array();
		/** @var $package Package */
		foreach ($packages as $package) {
			$packagePath = substr($package->getPackagePath(), strlen(FLOW_PATH_PACKAGES));
			$packageGroup = substr($packagePath, 0, strpos($packagePath, '/'));
			if ($packageGroup === $filterPath) {
				$filteredPackages[$package->getPackageKey()] = $package;
			}
		}
		return $filteredPackages;
	}

	/**
	 * Returns an array of \TYPO3\Flow\Package objects in the given array of packages
	 * that are of the specified package type.
	 *
	 * @param array $packages Array of \TYPO3\Flow\Package\PackageInterface to be filtered
	 * @param string $packageType Filter out anything that's not of this packageType
	 * @return array Array of \TYPO3\Flow\Package\PackageInterface
	 */
	protected function filterPackagesByType(&$packages, $packageType) {
		$filteredPackages = array();
		/** @var $package Package */
		foreach ($packages as $package) {
			if ($package->getComposerManifest('type') === $packageType) {
				$filteredPackages[$package->getPackageKey()] = $package;
			}
		}
		return $filteredPackages;
	}

	/**
	 * Returns the upper camel cased version of the given package key or FALSE
	 * if no such package is available.
	 *
	 * @param string $unknownCasedPackageKey The package key to convert
	 * @return mixed The upper camel cased package key or FALSE if no such package exists
	 * @api
	 */
	public function getCaseSensitivePackageKey($unknownCasedPackageKey) {
		$lowerCasedPackageKey = strtolower($unknownCasedPackageKey);
		return (isset($this->packageKeys[$lowerCasedPackageKey])) ? $this->packageKeys[$lowerCasedPackageKey] : FALSE;
	}

	/**
	 * Resolves a Flow package key from a composer package name.
	 *
	 * @param string $composerName
	 * @return string
	 * @throws Exception\InvalidPackageStateException
	 */
	public function getPackageKeyFromComposerName($composerName) {
		if (count($this->composerNameToPackageKeyMap) === 0) {
			foreach ($this->packageStatesConfiguration['packages'] as $packageKey => $packageStateConfiguration) {
				$this->composerNameToPackageKeyMap[strtolower($packageStateConfiguration['composerName'])] = $packageKey;
			}
		}
		$lowercasedComposerName = strtolower($composerName);
		if (!isset($this->composerNameToPackageKeyMap[$lowercasedComposerName])) {
			throw new \TYPO3\Flow\Package\Exception\InvalidPackageStateException('Could not find package with composer name "' . $composerName . '" in PackageStates configuration.', 1352320649);
		}
		return $this->composerNameToPackageKeyMap[$lowercasedComposerName];
	}

	/**
	 * Check the conformance of the given package key
	 *
	 * @param string $packageKey The package key to validate
	 * @return boolean If the package key is valid, returns TRUE otherwise FALSE
	 * @api
	 */
	public function isPackageKeyValid($packageKey) {
		return preg_match(PackageInterface::PATTERN_MATCH_PACKAGEKEY, $packageKey) === 1;
	}

	/**
	 * Create a package, given the package key
	 *
	 * @param string $packageKey The package key of the new package
	 * @param \TYPO3\Flow\Package\MetaData $packageMetaData If specified, this package meta object is used for writing the Package.xml file, otherwise a rudimentary Package.xml file is created
	 * @param string $packagesPath If specified, the package will be created in this path, otherwise the default "Application" directory is used
	 * @param string $packageType If specified, the package type will be set, otherwise it will default to "typo3-flow-package"
	 * @return \TYPO3\Flow\Package\PackageInterface The newly created package
	 * @throws \TYPO3\Flow\Package\Exception
	 * @throws \TYPO3\Flow\Package\Exception\PackageKeyAlreadyExistsException
	 * @throws \TYPO3\Flow\Package\Exception\InvalidPackageKeyException
	 * @api
	 */
	public function createPackage($packageKey, \TYPO3\Flow\Package\MetaData $packageMetaData = NULL, $packagesPath = NULL, $packageType = 'typo3-flow-package') {
		if (!$this->isPackageKeyValid($packageKey)) {
			throw new \TYPO3\Flow\Package\Exception\InvalidPackageKeyException('The package key "' . $packageKey . '" is invalid', 1220722210);
		}
		if ($this->isPackageAvailable($packageKey)) {
			throw new \TYPO3\Flow\Package\Exception\PackageKeyAlreadyExistsException('The package key "' . $packageKey . '" already exists', 1220722873);
		}

		if ($packagesPath === NULL) {
			if (is_array($this->settings['package']['packagesPathByType']) && isset($this->settings['package']['packagesPathByType'][$packageType])) {
				$packagesPath = $this->settings['package']['packagesPathByType'][$packageType];
			} else {
				$packagesPath = 'Application';
			}
			$packagesPath = Files::getUnixStylePath(Files::concatenatePaths(array($this->packagesBasePath, $packagesPath)));
		}

		if ($packageMetaData === NULL) {
			$packageMetaData = new MetaData($packageKey);
		}
		if ($packageMetaData->getPackageType() === NULL) {
			$packageMetaData->setPackageType($packageType);
		}

		$packagePath = Files::concatenatePaths(array($packagesPath, $packageKey)) . '/';
		Files::createDirectoryRecursively($packagePath);

		foreach (
			array(
				PackageInterface::DIRECTORY_METADATA,
				PackageInterface::DIRECTORY_CLASSES,
				PackageInterface::DIRECTORY_CONFIGURATION,
				PackageInterface::DIRECTORY_DOCUMENTATION,
				PackageInterface::DIRECTORY_RESOURCES,
				PackageInterface::DIRECTORY_TESTS_UNIT,
				PackageInterface::DIRECTORY_TESTS_FUNCTIONAL,
			) as $path) {
			Files::createDirectoryRecursively(Files::concatenatePaths(array($packagePath, $path)));
		}

		$this->writeComposerManifest($packagePath, $packageKey, $packageMetaData);

		$packagePath = str_replace($this->packagesBasePath, '', $packagePath);
		$package = $this->packageFactory->create($this->packagesBasePath, $packagePath, $packageKey, PackageInterface::DIRECTORY_CLASSES);

		$this->packages[$packageKey] = $package;
		foreach (array_keys($this->packages) as $upperCamelCasedPackageKey) {
			$this->packageKeys[strtolower($upperCamelCasedPackageKey)] = $upperCamelCasedPackageKey;
		}

		$this->activatePackage($packageKey);

		return $package;
	}

	/**
	 * Write a composer manifest for the package.
	 *
	 * @param string $manifestPath
	 * @param string $packageKey
	 * @param MetaData $packageMetaData
	 * @return void
	 */
	protected function writeComposerManifest($manifestPath, $packageKey, \TYPO3\Flow\Package\MetaData $packageMetaData = NULL) {
		$manifest = array();

		$nameParts = explode('.', $packageKey);
		$vendor = array_shift($nameParts);
		$manifest['name'] = strtolower($vendor . '/' . implode('-', $nameParts));
		if ($packageMetaData !== NULL) {
			$manifest['type'] = $packageMetaData->getPackageType();
			$manifest['description'] = $packageMetaData->getDescription();
			$manifest['version'] = $packageMetaData->getVersion();
		} else {
			$manifest['type'] = 'typo3-flow-package';
			$manifest['description'] = '';
		}
		$manifest['require'] = array('typo3/flow' => '*');
		$manifest['autoload'] = array('psr-0' => array(str_replace('.', '\\', $packageKey) => 'Classes'));

		if (defined('JSON_PRETTY_PRINT')) {
			file_put_contents(Files::concatenatePaths(array($manifestPath, 'composer.json')), json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
		} else {
			file_put_contents(Files::concatenatePaths(array($manifestPath, 'composer.json')), json_encode($manifest));
		}
	}

	/**
	 * Deactivates a package
	 *
	 * @param string $packageKey The package to deactivate
	 * @return void
	 * @throws \TYPO3\Flow\Package\Exception\ProtectedPackageKeyException if a package is protected and cannot be deactivated
	 * @api
	 */
	public function deactivatePackage($packageKey) {
		if (!$this->isPackageActive($packageKey)) {
			return;
		}

		$package = $this->getPackage($packageKey);
		if ($package->isProtected()) {
			throw new \TYPO3\Flow\Package\Exception\ProtectedPackageKeyException('The package "' . $packageKey . '" is protected and cannot be deactivated.', 1308662891);
		}

		unset($this->activePackages[$packageKey]);
		$this->packageStatesConfiguration['packages'][$packageKey]['state'] = 'inactive';
		$this->sortAndSavePackageStates();
	}

	/**
	 * Activates a package
	 *
	 * @param string $packageKey The package to activate
	 * @return void
	 * @api
	 */
	public function activatePackage($packageKey) {
		if ($this->isPackageActive($packageKey)) {
			return;
		}

		$package = $this->getPackage($packageKey);
		$this->activePackages[$packageKey] = $package;
		$this->packageStatesConfiguration['packages'][$packageKey]['state'] = 'active';
		if (!isset($this->packageStatesConfiguration['packages'][$packageKey]['packagePath'])) {
			$this->packageStatesConfiguration['packages'][$packageKey]['packagePath'] = str_replace($this->packagesBasePath, '', $package->getPackagePath());
		}
		if (!isset($this->packageStatesConfiguration['packages'][$packageKey]['classesPath'])) {
			$this->packageStatesConfiguration['packages'][$packageKey]['classesPath'] = Package::DIRECTORY_CLASSES;
		}
		$this->sortAndSavePackageStates();
	}

	/**
	 * Freezes a package
	 *
	 * @param string $packageKey The package to freeze
	 * @return void
	 * @throws \LogicException
	 * @throws \TYPO3\Flow\Package\Exception\UnknownPackageException
	 */
	public function freezePackage($packageKey) {
		if (!$this->bootstrap->getContext()->isDevelopment()) {
			throw new \LogicException('Package freezing is only supported in Development context.', 1338810870);
		}

		if (!$this->isPackageActive($packageKey)) {
			throw new \TYPO3\Flow\Package\Exception\UnknownPackageException('Package "' . $packageKey . '" is not available or active.', 1331715956);
		}
		if ($this->isPackageFrozen($packageKey)) {
			return;
		}

		$this->bootstrap->getObjectManager()->get('TYPO3\Flow\Reflection\ReflectionService')->freezePackageReflection($packageKey);

		$this->packageStatesConfiguration['packages'][$packageKey]['frozen'] = TRUE;
		$this->sortAndSavePackageStates();
	}

	/**
	 * Tells if a package is frozen
	 *
	 * @param string $packageKey The package to check
	 * @return boolean
	 */
	public function isPackageFrozen($packageKey) {
		return (
			$this->bootstrap->getContext()->isDevelopment()
			&& isset($this->packageStatesConfiguration['packages'][$packageKey]['frozen'])
			&& $this->packageStatesConfiguration['packages'][$packageKey]['frozen'] === TRUE
		);
	}

	/**
	 * Unfreezes a package
	 *
	 * @param string $packageKey The package to unfreeze
	 * @return void
	 */
	public function unfreezePackage($packageKey) {
		if (!$this->isPackageFrozen($packageKey)) {
			return;
		}

		$this->bootstrap->getObjectManager()->get('TYPO3\Flow\Reflection\ReflectionService')->unfreezePackageReflection($packageKey);

		unset($this->packageStatesConfiguration['packages'][$packageKey]['frozen']);
		$this->sortAndSavePackageStates();
	}

	/**
	 * Refreezes a package
	 *
	 * @param string $packageKey The package to refreeze
	 * @return void
	 */
	public function refreezePackage($packageKey) {
		if (!$this->isPackageFrozen($packageKey)) {
			return;
		}

		$this->bootstrap->getObjectManager()->get('TYPO3\Flow\Reflection\ReflectionService')->unfreezePackageReflection($packageKey);
	}

	/**
	 * Register a native Flow package
	 *
	 * @param string $packageKey The Package to be registered
	 * @param boolean $sortAndSave allows for not saving packagestates when used in loops etc.
	 * @return PackageInterface
	 * @throws Exception\CorruptPackageException
	 */
	public function registerPackage(PackageInterface $package, $sortAndSave = TRUE) {
		$packageKey = $package->getPackageKey();
		if ($this->isPackageAvailable($packageKey)) {
			throw new Exception\InvalidPackageStateException('Package "' . $packageKey . '" is already registered.', 1338996122);
		}

		$this->packages[$packageKey] = $package;
		$this->packageStatesConfiguration['packages'][$packageKey]['packagePath'] = str_replace($this->packagesBasePath, '', $package->getPackagePath());
		$this->packageStatesConfiguration['packages'][$packageKey]['classesPath'] = str_replace($package->getPackagePath(), '', $package->getClassesPath());

		if ($sortAndSave === TRUE) {
			$this->sortAndSavePackageStates();
		}

		return $package;
	}

	/**
	 * Unregisters a package from the list of available packages
	 *
	 * @param PackageInterface $package The package to be unregistered
	 * @return void
	 * @throws Exception\InvalidPackageStateException
	 */
	public function unregisterPackage(PackageInterface $package) {
		$packageKey = $package->getPackageKey();
		if (!$this->isPackageAvailable($packageKey)) {
			throw new Exception\InvalidPackageStateException('Package "' . $packageKey . '" is not registered.', 1338996142);
		}
		$this->unregisterPackageByPackageKey($packageKey);
	}

	/**
	 * Unregisters a package from the list of available packages
	 *
	 * @param string $packageKey Package Key of the package to be unregistered
	 * @return void
	 */
	protected function unregisterPackageByPackageKey($packageKey) {
		unset($this->packages[$packageKey]);
		unset($this->packageKeys[strtolower($packageKey)]);
		unset($this->packageStatesConfiguration['packages'][$packageKey]);
		$this->sortAndSavePackageStates();
	}

	/**
	 * Removes a package from registry and deletes it from filesystem
	 *
	 * @param string $packageKey package to remove
	 * @return void
	 * @throws \TYPO3\Flow\Package\Exception\UnknownPackageException if the specified package is not known
	 * @throws \TYPO3\Flow\Package\Exception\ProtectedPackageKeyException if a package is protected and cannot be deleted
	 * @throws \TYPO3\Flow\Package\Exception
	 * @api
	 */
	public function deletePackage($packageKey) {
		if (!$this->isPackageAvailable($packageKey)) {
			throw new \TYPO3\Flow\Package\Exception\UnknownPackageException('Package "' . $packageKey . '" is not available and cannot be removed.', 1166543253);
		}

		$package = $this->getPackage($packageKey);
		if ($package->isProtected()) {
			throw new \TYPO3\Flow\Package\Exception\ProtectedPackageKeyException('The package "' . $packageKey . '" is protected and cannot be removed.', 1220722120);
		}

		if ($this->isPackageActive($packageKey)) {
			$this->deactivatePackage($packageKey);
		}

		$packagePath = $package->getPackagePath();
		try {
			Files::removeDirectoryRecursively($packagePath);
		} catch (\TYPO3\Flow\Utility\Exception $exception) {
			throw new \TYPO3\Flow\Package\Exception('Please check file permissions. The directory "' . $packagePath . '" for package "' . $packageKey . '" could not be removed.', 1301491089, $exception);
		}

		$this->unregisterPackage($package);
	}

	/**
	 * Loads the states of available packages from the PackageStates.php file.
	 * The result is stored in $this->packageStatesConfiguration.
	 *
	 * @return void
	 */
	protected function loadPackageStates() {
		$this->packageStatesConfiguration = file_exists($this->packageStatesPathAndFilename) ? include($this->packageStatesPathAndFilename) : array();
		if (!isset($this->packageStatesConfiguration['version']) || $this->packageStatesConfiguration['version'] < 4) {
			$this->packageStatesConfiguration = array();
		}
		if ($this->packageStatesConfiguration === array() || !$this->bootstrap->getContext()->isProduction()) {
			$this->scanAvailablePackages();
		} else {
			$this->registerPackagesFromConfiguration();
		}
	}

	/**
	 * Scans all directories in the packages directories for available packages.
	 * For each package a Package object is created and stored in $this->packages.
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Package\Exception\DuplicatePackageException
	 */
	protected function scanAvailablePackages() {
		$previousPackageStatesConfiguration = $this->packageStatesConfiguration;

		if (isset($this->packageStatesConfiguration['packages'])) {
			foreach ($this->packageStatesConfiguration['packages'] as $packageKey => $configuration) {
				if (!file_exists($this->packagesBasePath . $configuration['packagePath'])) {
					unset($this->packageStatesConfiguration['packages'][$packageKey]);
				}
			}
		} else {
			$this->packageStatesConfiguration['packages'] = array();
		}

		$packagePaths = array();
		foreach (new \DirectoryIterator($this->packagesBasePath) as $parentFileInfo) {
			$parentFilename = $parentFileInfo->getFilename();
			if ($parentFilename[0] !== '.' && $parentFileInfo->isDir()) {
				$packagePaths = array_merge($packagePaths, $this->scanPackagesInPath($parentFileInfo->getPathName()));
			}
		}

		/**
		 * @todo similar functionality in registerPackage - should be refactored
		 */
		foreach ($packagePaths as $packagePath => $composerManifestPath) {
			try {
				$composerManifest = self::getComposerManifest($composerManifestPath);
				$packageKey = PackageFactory::getPackageKeyFromManifest($composerManifest, $packagePath, $this->packagesBasePath);
				$this->composerNameToPackageKeyMap[strtolower($composerManifest->name)] = $packageKey;
				$this->packageStatesConfiguration['packages'][$packageKey]['manifestPath'] = substr($composerManifestPath, strlen($packagePath)) ?: '';
				$this->packageStatesConfiguration['packages'][$packageKey]['composerName'] = $composerManifest->name;
			} catch (\TYPO3\Flow\Package\Exception\MissingPackageManifestException $exception) {
				$relativePackagePath = substr($packagePath, strlen($this->packagesBasePath));
				$packageKey = substr($relativePackagePath, strpos($relativePackagePath, '/') + 1, -1);
			}
			if (!isset($this->packageStatesConfiguration['packages'][$packageKey]['state'])) {
				/**
				 * @todo doesn't work, settings not available at this time
				 */
				if (is_array($this->settings['package']['inactiveByDefault']) && in_array($packageKey, $this->settings['package']['inactiveByDefault'], TRUE)) {
					$this->packageStatesConfiguration['packages'][$packageKey]['state'] = 'inactive';
				} else {
					$this->packageStatesConfiguration['packages'][$packageKey]['state'] = 'active';
				}
			}

			$this->packageStatesConfiguration['packages'][$packageKey]['packagePath'] = str_replace($this->packagesBasePath, '', $packagePath);

				// Change this to read the target from Composer or any other source
			$this->packageStatesConfiguration['packages'][$packageKey]['classesPath'] = Package::DIRECTORY_CLASSES;
		}

		$this->registerPackagesFromConfiguration();
		if ($this->packageStatesConfiguration != $previousPackageStatesConfiguration) {
			$this->sortAndsavePackageStates();
		}
	}

	/**
	 * Looks for composer.json in the given path and returns a path or NULL.
	 *
	 * @param string $packagePath
	 * @return array
	 */
	protected function findComposerManifestPaths($packagePath) {
		$manifestPaths = array();
		if (file_exists($packagePath . '/composer.json')) {
			$manifestPaths[] = $packagePath . '/';
		} else {
			$jsonPathsAndFilenames = Files::readDirectoryRecursively($packagePath, '.json');
			asort($jsonPathsAndFilenames);
			while (list($unusedKey, $jsonPathAndFilename) = each($jsonPathsAndFilenames)) {
				if (basename($jsonPathAndFilename) === 'composer.json') {
					$manifestPath = dirname($jsonPathAndFilename) . '/';
					$manifestPaths[] = $manifestPath;
					$isNotSubPathOfManifestPath = function ($otherPath) use ($manifestPath) {
						return strpos($otherPath, $manifestPath) !== 0;
					};
					$jsonPathsAndFilenames = array_filter($jsonPathsAndFilenames, $isNotSubPathOfManifestPath);
				}
			}
		}

		return $manifestPaths;
	}

	/**
	 * Scans all sub directories of the specified directory and collects the package keys of packages it finds.
	 *
	 * The return of the array is to make this method usable in array_merge.
	 *
	 * @param string $startPath
	 * @param array $collectedPackagePaths
	 * @return array
	 */
	protected function scanPackagesInPath($startPath, array &$collectedPackagePaths = array()) {
		foreach (new \DirectoryIterator($startPath) as $fileInfo) {
			if (!$fileInfo->isDir()) {
				continue;
			}
			$filename = $fileInfo->getFilename();
			if ($filename[0] !== '.') {
				$currentPath = Files::getUnixStylePath($fileInfo->getPathName());
				$composerManifestPaths = $this->findComposerManifestPaths($currentPath);
				foreach ($composerManifestPaths as $composerManifestPath) {
					$targetDirectory = rtrim(self::getComposerManifest($composerManifestPath, 'target-dir'), '/');
					$packagePath = $targetDirectory ? substr(rtrim($composerManifestPath, '/'), 0, -strlen((string)$targetDirectory)) : $composerManifestPath;
					$collectedPackagePaths[$packagePath] = $composerManifestPath;
				}
			}
		}
		return $collectedPackagePaths;
	}

	/**
	 * Returns contents of Composer manifest - or part there of.
	 *
	 * @param string $manifestPath
	 * @param string $key Optional. Only return the part of the manifest indexed by 'key'
	 * @param object $composerManifest Optional. Manifest to use instead of reading it from file
	 * @return mixed
	 * @throws \TYPO3\Flow\Package\Exception\MissingPackageManifestException
	 * @see json_decode for return values
	 */
	static public function getComposerManifest($manifestPath, $key = NULL, $composerManifest = NULL) {
		if ($composerManifest === NULL) {
			if (!file_exists($manifestPath . 'composer.json')) {
				throw new \TYPO3\Flow\Package\Exception\MissingPackageManifestException('No composer manifest file found at "' . $manifestPath . '/composer.json".', 1349868540);
			}
			$json = file_get_contents($manifestPath . 'composer.json');
			$composerManifest = json_decode($json);
		}

		if ($key !== NULL) {
			if (isset($composerManifest->{$key})) {
				$value = $composerManifest->{$key};
			} else {
				$value = NULL;
			}
		} else {
			$value = $composerManifest;
		}
		return $value;
	}

	/**
	 * Requires and registers all packages which were defined in packageStatesConfiguration
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Package\Exception\CorruptPackageException
	 */
	protected function registerPackagesFromConfiguration() {
		foreach ($this->packageStatesConfiguration['packages'] as $packageKey => $stateConfiguration) {

			$packagePath = isset($stateConfiguration['packagePath']) ? $stateConfiguration['packagePath'] : NULL;
			$classesPath = isset($stateConfiguration['classesPath']) ? $stateConfiguration['classesPath'] : NULL;
			$manifestPath = isset($stateConfiguration['manifestPath']) ? $stateConfiguration['manifestPath'] : NULL;

			try {
				$package = $this->packageFactory->create($this->packagesBasePath, $packagePath, $packageKey, $classesPath, $manifestPath);
			} catch (\TYPO3\Flow\Package\Exception\InvalidPackagePathException $exception) {
				$this->unregisterPackageByPackageKey($packageKey);
				$this->systemLogger->log('Package ' . $packageKey . ' could not be loaded, it has been unregistered. Error description: "' . $exception->getMessage() . '" (' . $exception->getCode() . ')', LOG_WARNING);
				continue;
			}

			$this->registerPackage($package, FALSE);

			if (!$this->packages[$packageKey] instanceof PackageInterface) {
				throw new \TYPO3\Flow\Package\Exception\CorruptPackageException(sprintf('The package class in package "%s" does not implement PackageInterface.', $packageKey), 1300782487);
			}

			$this->packageKeys[strtolower($packageKey)] = $packageKey;
			if ($stateConfiguration['state'] === 'active') {
				$this->activePackages[$packageKey] = $this->packages[$packageKey];
			}
		}
	}

	/**
	 * Saves the current content of $this->packageStatesConfiguration to the
	 * PackageStates.php file.
	 *
	 * @return void
	 */
	protected function sortAndSavePackageStates() {
		$this->sortAvailablePackagesByDependencies();

		$this->packageStatesConfiguration['version'] = 4;

		$fileDescription = "# PackageStates.php\n\n";
		$fileDescription .= "# This file is maintained by Flow's package management. Although you can edit it\n";
		$fileDescription .= "# manually, you should rather use the command line commands for maintaining packages.\n";
		$fileDescription .= "# You'll find detailed information about the typo3.flow:package:* commands in their\n";
		$fileDescription .= "# respective help screens.\n\n";
		$fileDescription .= "# This file will be regenerated automatically if it doesn't exist. Deleting this file\n";
		$fileDescription .= "# should, however, never become necessary if you use the package commands.\n";

			// we do not need the dependencies on disk...
		foreach ($this->packageStatesConfiguration['packages'] as &$packageConfiguration) {
			if (isset($packageConfiguration['dependencies'])) {
				unset($packageConfiguration['dependencies']);
			}
		}
		$packageStatesCode = "<?php\n$fileDescription\nreturn " . var_export($this->packageStatesConfiguration, TRUE) . "\n ?>";
		@file_put_contents($this->packageStatesPathAndFilename, $packageStatesCode);
	}

	/**
	 * Resolves the dependent packages from the meta data of all packages recursively. The
	 * resolved direct or indirect dependencies of each package will put into the package
	 * states configuration array.
	 *
	 * @return void
	 */
	protected function resolvePackageDependencies() {
		foreach ($this->packages as $packageKey => $package) {
			$this->packageStatesConfiguration['packages'][$packageKey]['dependencies'] = $this->getDependencyArrayForPackage($packageKey);
		}
	}

	/**
	 * Returns an array of dependent package keys for the given package. It will
	 * do this recursively, so dependencies of dependant packages will also be
	 * in the result.
	 *
	 * @param string $packageKey The package key to fetch the dependencies for
	 * @param array $dependentPackageKeys
	 * @param array $trace An array of already visited package keys, to detect circular dependencies
	 * @return array|NULL An array of direct or indirect dependant packages
	 * @throws \TYPO3\Flow\Mvc\Exception\InvalidPackageKeyException
	 */
	protected function getDependencyArrayForPackage($packageKey, array &$dependentPackageKeys = array(), array $trace = array()) {
		if (!isset($this->packages[$packageKey])) {
			return NULL;
		}
		if (in_array($packageKey, $trace) !== FALSE) {
			return $dependentPackageKeys;
		}
		$trace[] = $packageKey;
		$dependentPackageConstraints = $this->packages[$packageKey]->getPackageMetaData()->getConstraintsByType(MetaDataInterface::CONSTRAINT_TYPE_DEPENDS);
		foreach ($dependentPackageConstraints as $constraint) {
			if ($constraint instanceof \TYPO3\Flow\Package\MetaData\PackageConstraint) {
				$dependentPackageKey = $constraint->getValue();
				if (in_array($dependentPackageKey, $dependentPackageKeys) === FALSE && in_array($dependentPackageKey, $trace) === FALSE) {
					$dependentPackageKeys[] = $dependentPackageKey;
				}
				$this->getDependencyArrayForPackage($dependentPackageKey, $dependentPackageKeys, $trace);
			}
		}
		return array_reverse($dependentPackageKeys);
	}

	/**
	 * Orders all packages by comparing their dependencies. By this, the packages
	 * and package configurations arrays holds all packages in the correct
	 * initialization order.
	 *
	 * @return void
	 */
	protected function sortAvailablePackagesByDependencies() {
		$this->resolvePackageDependencies();

		$packageStatesConfiguration = $this->packageStatesConfiguration['packages'];

		$comparator = function ($firstPackageKey, $secondPackageKey) use ($packageStatesConfiguration) {
			if (isset($packageStatesConfiguration[$firstPackageKey]['dependencies'])
					&& (in_array($secondPackageKey, $packageStatesConfiguration[$firstPackageKey]['dependencies'])
						&& !in_array($firstPackageKey, $packageStatesConfiguration[$secondPackageKey]['dependencies']))) {
				return 1;
			} elseif (isset($packageStatesConfiguration[$secondPackageKey]['dependencies'])
				&& (in_array($firstPackageKey, $packageStatesConfiguration[$secondPackageKey]['dependencies'])
					&& !in_array($secondPackageKey, $packageStatesConfiguration[$firstPackageKey]['dependencies']))) {
				return -1;
			}
			return strcmp($firstPackageKey, $secondPackageKey);
		};

		uasort($this->packages,
			function (\TYPO3\Flow\Package\PackageInterface $firstPackage, \TYPO3\Flow\Package\PackageInterface $secondPackage) use ($comparator) {
				return $comparator($firstPackage->getPackageKey(), $secondPackage->getPackageKey());
			}
		);

		uksort($this->packageStatesConfiguration['packages'],
			function ($firstPackageKey, $secondPackageKey) use ($comparator) {
				return $comparator($firstPackageKey, $secondPackageKey);
			}
		);
	}
}

?>