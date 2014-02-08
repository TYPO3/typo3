<?php
namespace TYPO3\CMS\Core\Package;

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
 * Adapted from FLOW for TYPO3 CMS
 */
class PackageFactory extends \TYPO3\Flow\Package\PackageFactory {

	/**
	 * @var PackageManager
	 */
	protected $packageManager;

	/**
	 * Constructor
	 *
	 * @param \TYPO3\Flow\Package\PackageManager $packageManager
	 */
	public function __construct(PackageManager $packageManager) {
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
	 * @throws \TYPO3\Flow\Package\Exception\CorruptPackageException
	 */
	public function create($packagesBasePath, $packagePath, $packageKey, $classesPath, $manifestPath = '') {
		$packagePath = Files::getNormalizedPath(Files::concatenatePaths(array($packagesBasePath, $packagePath)));
		$packageClassPathAndFilename = Files::concatenatePaths(array($packagePath, 'Classes/' . str_replace('.', '/', $packageKey) . '/Package.php'));
		$alternativeClassPathAndFilename = Files::concatenatePaths(array($packagePath, 'Classes/Package.php'));
		$packageClassPathAndFilename = @file_exists($alternativeClassPathAndFilename) ? $alternativeClassPathAndFilename : $packageClassPathAndFilename;
		if (@file_exists($packageClassPathAndFilename)) {
			require_once($packageClassPathAndFilename);
			if (substr($packagePath, 0, strlen(PATH_typo3)) === PATH_typo3 && strpos($packageKey, '.') === FALSE) {
				//TODO Remove this exception once the systextension are renamed to proper Flow naming scheme packages
				$packageClassName = 'TYPO3\\CMS\\' . \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($packageKey) . '\Package';
			} else {
				$packageClassName = str_replace('.', '\\', $packageKey) . '\Package';
			}
			if (!class_exists($packageClassName, FALSE)) {
				throw new \TYPO3\Flow\Package\Exception\CorruptPackageException(sprintf('The package "%s" does not contain a valid package class. Check if the file "%s" really contains a class called "%s".', $packageKey, $packageClassPathAndFilename, $packageClassName), 1327587092);
			}
		} else {
			$emConfPath = Files::concatenatePaths(array($packagePath, 'ext_emconf.php'));
			$packageClassName = @file_exists($emConfPath) ? 'TYPO3\CMS\Core\Package\Package' : 'TYPO3\Flow\Package\Package';
		}

		/** @var $package \TYPO3\Flow\Package\PackageInterface */
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
			throw new  \TYPO3\Flow\Package\Exception\InvalidPackageManifestException('Invalid composer manifest.', 1348146451);
		}
		if (isset($manifest->type) && substr($manifest->type, 0, 10) === 'typo3-cms-') {
			$relativePackagePath = substr($packagePath, strlen($packagesBasePath));
			$packageKey = substr($relativePackagePath, strpos($relativePackagePath, '/') + 1, -1);
			/**
			 * @todo check that manifest name and directory follows convention
			 */
			$packageKey = preg_replace('/[^A-Za-z0-9._-]/', '', $packageKey);
			return $packageKey;
		} else {
			return parent::getPackageKeyFromManifest($manifest, $packagePath, $packagesBasePath);
		}
	}

}
