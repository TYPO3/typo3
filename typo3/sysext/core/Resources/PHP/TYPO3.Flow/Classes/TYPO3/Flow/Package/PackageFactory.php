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
 * Class for building Packages
 */
class PackageFactory {

	/**
	 * @var PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * Constructor
	 *
	 * @param \TYPO3\Flow\Package\PackageManagerInterface $packageManager
	 */
	public function __construct(PackageManagerInterface $packageManager) {
		$this->packageManager = $packageManager;
	}

	/**
	 * Returns a package instance.
	 *
	 * @param string $packagesBasePath the base install path of packages,
	 * @param string $packagePath path to package, relative to base path
	 * @param string $packageKey key / name of the package
	 * @param string $classesPath path to the classes directory, relative to the package path
	 * @param string $manifestPath path to the package's Composer manifest, relative to package path, defaults to same path
	 * @return \TYPO3\Flow\Package\PackageInterface
	 * @throws Exception\CorruptPackageException
	 */
	public function create($packagesBasePath, $packagePath, $packageKey, $classesPath, $manifestPath = '') {
		$packageClassPathAndFilename = Files::concatenatePaths(array($packagesBasePath, $packagePath, 'Classes/' . str_replace('.', '/', $packageKey) . '/Package.php'));
		if (file_exists($packageClassPathAndFilename)) {
			require_once($packageClassPathAndFilename);
			/**
			 * @todo there should be a general method for getting Namespace from $packageKey
			 * @todo it should be tested if the package class implements the interface
			 */
			$packageClassName = str_replace('.', '\\', $packageKey) . '\Package';
			if (!class_exists($packageClassName)) {
				throw new \TYPO3\Flow\Package\Exception\CorruptPackageException(sprintf('The package "%s" does not contain a valid package class. Check if the file "%s" really contains a class called "%s".', $packageKey, $packageClassPathAndFilename, $packageClassName), 1327587091);
			}
		} else {
			$packageClassName = 'TYPO3\Flow\Package\Package';
		}
		$packagePath = Files::concatenatePaths(array($packagesBasePath, $packagePath)) . '/';

		$package = new $packageClassName($this->packageManager, $packageKey, $packagePath, $classesPath, $manifestPath);

		return $package;
	}

	/**
	 * Resolves package key from Composer manifest
	 *
	 * If it is a Flow package the name of the containing directory will be used.
	 *
	 * Else if the composer name of the package matches the first part of the lowercased namespace of the package, the mixed
	 * case version of the composer name / namespace will be used, with backslashes replaced by dots.
	 *
	 * Else the composer name will be used with the slash replaced by a dot
	 *
	 * @param object $manifest
	 * @param string $packagesBasePath
	 * @return string
	 */
	public static function getPackageKeyFromManifest($manifest, $packagePath, $packagesBasePath) {
		if (!is_object($manifest)) {
			throw new  \TYPO3\Flow\Package\Exception\InvalidPackageManifestException('Invalid composer manifest.', 1348146450);
		}
		if (isset($manifest->type) && substr($manifest->type, 0, 11) === 'typo3-flow-') {
			$relativePackagePath = substr($packagePath, strlen($packagesBasePath));
			$packageKey = substr($relativePackagePath, strpos($relativePackagePath, '/') + 1, -1);
			/**
			 * @todo check that manifest name and directory follows convention
			 */
		} else {
			$packageKey = str_replace('/', '.', $manifest->name);
			if (isset($manifest->autoload) && isset($manifest->autoload->{"psr-0"})) {
				$namespaces = array_keys(get_object_vars($manifest->autoload->{"psr-0"}));
				foreach ($namespaces as $namespace) {
					$namespaceLead = substr($namespace, 0, strlen($manifest->name));
					$dottedNamespaceLead = str_replace('\\', '.', $namespaceLead);
					if (strtolower($dottedNamespaceLead) === $packageKey) {
						$packageKey = $dottedNamespaceLead;
					}
				}
			}
		}
		$packageKey = preg_replace('/[^A-Za-z0-9.]/', '', $packageKey);
		return $packageKey;
	}
}
?>