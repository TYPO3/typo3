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

use TYPO3\Flow\Utility\Files;

/**
 * A Package
 *
 * @api
 */
class Package implements PackageInterface {

	/**
	 * Unique key of this package. Example for the Flow package: "TYPO3.Flow"
	 * @var string
	 */
	protected $packageKey;

	/**
	 * @var string
	 */
	protected $manifestPath;

	/**
	 * Full path to this package's main directory
	 * @var string
	 */
	protected $packagePath;

	/**
	 * Full path to this package's PSR-0 class loader entry point
	 * @var string
	 */
	protected $classesPath;

	/**
	 * If this package is protected and therefore cannot be deactivated or deleted
	 * @var boolean
	 * @api
	 */
	protected $protected = FALSE;

	/**
	 * @var \stdClass
	 */
	protected $composerManifest;

	/**
	 * Meta information about this package
	 * @var \TYPO3\Flow\Package\MetaData
	 */
	protected $packageMetaData;

	/**
	 * Names and relative paths (to this package directory) of files containing classes
	 * @var array
	 */
	protected $classFiles;

	/**
	 * The namespace of the classes contained in this package
	 * @var string
	 */
	protected $namespace;

	/**
	 * If enabled, the files in the Classes directory are registered and Reflection, Dependency Injection, AOP etc. are supported.
	 * Disable this flag if you don't need object management for your package and want to save some memory.
	 * @var boolean
	 * @api
	 */
	protected $objectManagementEnabled = TRUE;

	/**
	 * @var \TYPO3\Flow\Package\PackageManager
	 */
	protected $packageManager;

	/**
	 * Constructor
	 *
	 * @param \TYPO3\Flow\Package\PackageManager $packageManager the package manager which knows this package
	 * @param string $packageKey Key of this package
	 * @param string $packagePath Absolute path to the location of the package's composer manifest
	 * @param string $classesPath Path the classes of the package are in, relative to $packagePath. Optional, read from Composer manifest if not set.
	 * @param string $manifestPath Path the composer manifest of the package, relative to $packagePath. Optional, defaults to ''.
	 * @throws \TYPO3\Flow\Package\Exception\InvalidPackageKeyException if an invalid package key was passed
	 * @throws \TYPO3\Flow\Package\Exception\InvalidPackagePathException if an invalid package path was passed
	 * @throws \TYPO3\Flow\Package\Exception\InvalidPackageManifestException if no composer manifest file could be found
	 */
	public function __construct(\TYPO3\Flow\Package\PackageManager $packageManager, $packageKey, $packagePath, $classesPath = NULL, $manifestPath = '') {
		if (preg_match(self::PATTERN_MATCH_PACKAGEKEY, $packageKey) !== 1) {
			throw new \TYPO3\Flow\Package\Exception\InvalidPackageKeyException('"' . $packageKey . '" is not a valid package key.', 1217959510);
		}
		if (!(is_dir($packagePath) || (Files::is_link($packagePath) && is_dir(Files::getNormalizedPath($packagePath))))) {
			throw new \TYPO3\Flow\Package\Exception\InvalidPackagePathException(sprintf('Tried to instantiate a package object for package "%s" with a non-existing package path "%s". Either the package does not exist anymore, or the code creating this object contains an error.', $packageKey, $packagePath), 1166631889);
		}
		if (substr($packagePath, -1, 1) !== '/') {
			throw new \TYPO3\Flow\Package\Exception\InvalidPackagePathException(sprintf('The package path "%s" provided for package "%s" has no trailing forward slash.', $packagePath, $packageKey), 1166633720);
		}
		if ($classesPath[1] === '/') {
			throw new \TYPO3\Flow\Package\Exception\InvalidPackagePathException(sprintf('The package classes path provided for package "%s" has a leading forward slash.', $packageKey), 1334841320);
		}
		if (!file_exists($packagePath . $manifestPath . 'composer.json')) {
			throw new \TYPO3\Flow\Package\Exception\InvalidPackageManifestException(sprintf('No composer manifest file found for package "%s". Please create one at "%scomposer.json".', $packageKey, $manifestPath), 1349776393);
		}

		$this->packageManager = $packageManager;
		$this->manifestPath = $manifestPath;
		$this->packageKey = $packageKey;
		$this->packagePath = Files::getNormalizedPath($packagePath);
		if (isset($this->getComposerManifest()->autoload->{'psr-0'})) {
			$this->classesPath = Files::getNormalizedPath($this->packagePath . $this->getComposerManifest()->autoload->{'psr-0'}->{$this->getNamespace()});
		} else {
			$this->classesPath = Files::getNormalizedPath($this->packagePath . $classesPath);
		}
	}

	/**
	 * Invokes custom PHP code directly after the package manager has been initialized.
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap The current bootstrap
	 * @return void
	 */
	public function boot(\TYPO3\Flow\Core\Bootstrap $bootstrap) {
	}

	/**
	 * Returns the package meta data object of this package.
	 *
	 * @return \TYPO3\Flow\Package\MetaData
	 */
	public function getPackageMetaData() {
		if ($this->packageMetaData === NULL) {
			$this->packageMetaData = new MetaData($this->getPackageKey());
			$this->packageMetaData->setDescription($this->getComposerManifest('description'));
			$this->packageMetaData->setVersion($this->getComposerManifest('version'));
			$requirements = $this->getComposerManifest('require');
			if ($requirements !== NULL) {
				foreach ($requirements as $requirement => $version) {
					if ($this->packageRequirementIsComposerPackage($requirement) === FALSE) {
							// Skip non-package requirements
						continue;
					}
					$packageKey = $this->packageManager->getPackageKeyFromComposerName($requirement);
					$constraint = new MetaData\PackageConstraint(MetaDataInterface::CONSTRAINT_TYPE_DEPENDS, $packageKey);
					$this->packageMetaData->addConstraint($constraint);
				}
			}

		}
		return $this->packageMetaData;
	}

	/**
	 * Check whether the given package requirement (like "typo3/flow" or "php") is a composer package or not
	 *
	 * @param string $requirement the composer requirement string
	 * @return boolean TRUE if $requirement is a composer package (contains a slash), FALSE otherwise
	 */
	protected function packageRequirementIsComposerPackage($requirement) {
		return (strpos($requirement, '/') !== FALSE);
	}

	/**
	 * Returns the array of filenames of the class files
	 *
	 * @return array An array of class names (key) and their filename, including the relative path to the package's directory
	 */
	public function getClassFiles() {
		if (!is_array($this->classFiles)) {
			$this->classFiles = $this->buildArrayOfClassFiles($this->classesPath);
		}
		return $this->classFiles;
	}

	/**
	 * Returns the array of filenames of class files provided by functional tests contained in this package
	 *
	 * @return array An array of class names (key) and their filename, including the relative path to the package's directory
	 */
	public function getFunctionalTestsClassFiles() {
		return $this->buildArrayOfClassFiles($this->packagePath . self::DIRECTORY_TESTS_FUNCTIONAL, $this->getNamespace() . '\\Tests\\Functional\\');
	}

	/**
	 * Returns the package key of this package.
	 *
	 * @return string
	 * @api
	 */
	public function getPackageKey() {
		return $this->packageKey;
	}

	/**
	 * Returns the PHP namespace of classes in this package.
	 *
	 * @return string
	 * @throws \TYPO3\Flow\Package\Exception\InvalidPackageStateException
	 * @api
	 */
	public function getNamespace() {
		if (!$this->namespace) {
			$manifest = $this->getComposerManifest();
			if (isset($manifest->autoload->{'psr-0'})) {
				$namespaces = (array)$manifest->autoload->{'psr-0'};
				if (count($namespaces) === 1) {
					$namespace = key($namespaces);
				} else {
					throw new \TYPO3\Flow\Package\Exception\InvalidPackageStateException(sprintf('The Composer manifest of package "%s" contains multiple namespace definitions in its autoload section but Flow does only support one namespace per package.', $this->packageKey), 1348053245);
				}
			} else {
				$namespace = str_replace('.', '\\', $this->getPackageKey());
			}
			$this->namespace = $namespace;
		}
		return $this->namespace;
	}

	/**
	 * Tells if this package is protected and therefore cannot be deactivated or deleted
	 *
	 * @return boolean
	 * @api
	 */
	public function isProtected() {
		return $this->protected;
	}

	/**
	 * Tells if files in the Classes directory should be registered and object management enabled for this package.
	 *
	 * @return boolean
	 */
	public function isObjectManagementEnabled() {
		return $this->objectManagementEnabled;
	}

	/**
	 * Sets the protection flag of the package
	 *
	 * @param boolean $protected TRUE if the package should be protected, otherwise FALSE
	 * @return void
	 * @api
	 */
	public function setProtected($protected) {
		$this->protected = (boolean)$protected;
	}

	/**
	 * Returns the full path to this package's main directory
	 *
	 * @return string Path to this package's main directory
	 * @api
	 */
	public function getPackagePath() {
		return $this->packagePath;
	}

	/**
	 * Returns the full path to the packages Composer manifest
	 *
	 * @return string
	 */
	public function getManifestPath() {
		return $this->packagePath . $this->manifestPath;
	}

	/**
	 * Returns the full path to this package's Classes directory
	 *
	 * @return string Path to this package's Classes directory
	 * @api
	 */
	public function getClassesPath() {
		return $this->classesPath;
	}

	/**
	 * Returns the full path to the package's classes namespace entry path,
	 * e.g. "My.Package/ClassesPath/My/Package/"
	 *
	 * @return string Path to this package's Classes directory
	 * @api
	 */
	public function getClassesNamespaceEntryPath() {
		$pathifiedNamespace = str_replace('\\', '/', $this->getNamespace());
		return Files::getNormalizedPath($this->classesPath . trim($pathifiedNamespace, '/'));
	}

	/**
	 * Returns the full path to this package's functional tests directory
	 *
	 * @return string Path to this package's functional tests directory
	 * @api
	 */
	public function getFunctionalTestsPath() {
		return $this->packagePath . self::DIRECTORY_TESTS_FUNCTIONAL;
	}

	/**
	 * Returns the full path to this package's Resources directory
	 *
	 * @return string Path to this package's Resources directory
	 * @api
	 */
	public function getResourcesPath() {
		return $this->packagePath . self::DIRECTORY_RESOURCES;
	}

	/**
	 * Returns the full path to this package's Configuration directory
	 *
	 * @return string Path to this package's Configuration directory
	 * @api
	 */
	public function getConfigurationPath() {
		return $this->packagePath . self::DIRECTORY_CONFIGURATION;
	}

	/**
	 * Returns the full path to the package's meta data directory
	 *
	 * @return string Full path to the package's meta data directory
	 * @api
	 */
	public function getMetaPath() {
		return $this->packagePath . self::DIRECTORY_METADATA;
	}

	/**
	 * Returns the full path to the package's documentation directory
	 *
	 * @return string Full path to the package's documentation directory
	 * @api
	 */
	public function getDocumentationPath() {
		return $this->packagePath . self::DIRECTORY_DOCUMENTATION;
	}

	/**
	 * Returns the available documentations for this package
	 *
	 * @return array Array of \TYPO3\Flow\Package\Documentation
	 * @api
	 */
	public function getPackageDocumentations() {
		$documentations = array();
		$documentationPath = $this->getDocumentationPath();
		if (is_dir($documentationPath)) {
			$documentationsDirectoryIterator = new \DirectoryIterator($documentationPath);
			$documentationsDirectoryIterator->rewind();
			while ($documentationsDirectoryIterator->valid()) {
				$filename = $documentationsDirectoryIterator->getFilename();
				if ($filename[0] != '.' && $documentationsDirectoryIterator->isDir()) {
					$filename = $documentationsDirectoryIterator->getFilename();
					$documentation = new \TYPO3\Flow\Package\Documentation($this, $filename, $documentationPath . $filename . '/');
					$documentations[$filename] = $documentation;
				}
				$documentationsDirectoryIterator->next();
			}
		}
		return $documentations;
	}

	/**
	 * Returns contents of Composer manifest - or part there of.
	 *
	 * @param string $key Optional. Only return the part of the manifest indexed by 'key'
	 * @return mixed|NULL
	 * @see json_decode for return values
	 */
	public function getComposerManifest($key = NULL) {
		if (!isset($this->composerManifest)) {
			$this->composerManifest = PackageManager::getComposerManifest($this->getManifestPath());
		}

		return PackageManager::getComposerManifest($this->getManifestPath(), $key, $this->composerManifest);
	}

	/**
	 * Builds and returns an array of class names => file names of all
	 * *.php files in the package's Classes directory and its sub-
	 * directories.
	 *
	 * @param string $classesPath Base path acting as the parent directory for potential class files
	 * @param string $extraNamespaceSegment A PHP class namespace segment which should be inserted like so: \TYPO3\PackageKey\{namespacePrefix\}PathSegment\PathSegment\Filename
	 * @param string $subDirectory Used internally
	 * @param integer $recursionLevel Used internally
	 * @return array
	 * @throws \TYPO3\Flow\Package\Exception if recursion into directories was too deep or another error occurred
	 */
	protected function buildArrayOfClassFiles($classesPath, $extraNamespaceSegment = '', $subDirectory = '', $recursionLevel = 0) {
		$classFiles = array();
		$currentPath = $classesPath . $subDirectory;
		$currentRelativePath = substr($currentPath, strlen($this->packagePath));

		if (!is_dir($currentPath)) {
			return array();
		}
		if ($recursionLevel > 100) {
			throw new \TYPO3\Flow\Package\Exception('Recursion too deep.', 1166635495);
		}

		try {
			$classesDirectoryIterator = new \DirectoryIterator($currentPath);
			while ($classesDirectoryIterator->valid()) {
				$filename = $classesDirectoryIterator->getFilename();
				if ($filename[0] != '.') {
					if (is_dir($currentPath . $filename)) {
						$classFiles = array_merge($classFiles, $this->buildArrayOfClassFiles($classesPath, $extraNamespaceSegment, $subDirectory . $filename . '/', ($recursionLevel + 1)));
					} else {
						if (substr($filename, -4, 4) === '.php') {
							$className = (str_replace('/', '\\', ($extraNamespaceSegment . substr($currentPath, strlen($classesPath)) . substr($filename, 0, -4))));
							$classFiles[$className] = $currentRelativePath . $filename;
						}
					}
				}
				$classesDirectoryIterator->next();
			}

		} catch (\Exception $exception) {
			throw new \TYPO3\Flow\Package\Exception($exception->getMessage(), 1166633721);
		}
		return $classFiles;
	}

	/**
	 * Added by TYPO3 CMS
	 *
	 * The package caching serializes package objects.
	 * The package manager instance may not be serialized
	 * as a fresh instance is created upon every request.
	 *
	 * This method will be removed once the package is
	 * released of the package manager dependency.
	 *
	 * @return array
	 */
	public function __sleep() {
		$properties = get_class_vars(__CLASS__);
		unset($properties['packageManager']);
		return array_keys($properties);
	}

	/**
	 * Added by TYPO3 CMS
	 *
	 * The package caching deserializes package objects.
	 * A fresh package manager instance has to be set
	 * during bootstrapping.
	 *
	 * This method will be removed once the package is
	 * released of the package manager dependency.
	 */
	public function __wakeup() {
		if (isset($GLOBALS['TYPO3_currentPackageManager'])) {
			$this->packageManager = $GLOBALS['TYPO3_currentPackageManager'];
		}
	}
}

?>