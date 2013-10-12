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

/**
 * Interface for the TYPO3 Package Manager
 *
 * @api
 */
interface PackageManagerInterface {

	/**
	 * Initializes the package manager.
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return void
	 */
	public function initialize(\TYPO3\Flow\Core\Bootstrap $bootstrap);

	/**
	 * Returns TRUE if a package is available (the package's files exist in the packages directory)
	 * or FALSE if it's not. If a package is available it doesn't mean necessarily that it's active!
	 *
	 * @param string $packageKey The key of the package to check
	 * @return boolean TRUE if the package is available, otherwise FALSE
	 * @api
	 */
	public function isPackageAvailable($packageKey);

	/**
	 * Returns TRUE if a package is activated or FALSE if it's not.
	 *
	 * @param string $packageKey The key of the package to check
	 * @return boolean TRUE if package is active, otherwise FALSE
	 * @api
	 */
	public function isPackageActive($packageKey);

	/**
	 * Returns a \TYPO3\Flow\Package\PackageInterface object for the specified package.
	 * A package is available, if the package directory contains valid meta information.
	 *
	 * @param string $packageKey
	 * @return \TYPO3\Flow\Package\PackageInterface
	 * @api
	 */
	public function getPackage($packageKey);

	/**
	 * Finds a package by a given object of that package; if no such package
	 * could be found, NULL is returned.
	 *
	 * @param object $object The object to find the possessing package of
	 * @return \TYPO3\Flow\Package\PackageInterface The package the given object belongs to or NULL if it could not be found
	 */
	public function getPackageOfObject($object);

	/**
	 * Returns an array of \TYPO3\Flow\Package\PackageInterface objects of all available packages.
	 * A package is available, if the package directory contains valid meta information.
	 *
	 * @return array Array of \TYPO3\Flow\Package\PackageInterface
	 * @api
	 */
	public function getAvailablePackages();

	/**
	 * Returns an array of \TYPO3\Flow\PackageInterface objects of all active packages.
	 * A package is active, if it is available and has been activated in the package
	 * manager settings.
	 *
	 * @return array Array of \TYPO3\Flow\Package\PackageInterface
	 * @api
	 */
	public function getActivePackages();

	/**
	 * Returns an array of \TYPO3\Flow\PackageInterface objects of all packages that match
	 * the given package state, path, and type filters. All three filters must match, if given.
	 *
	 * @param string $packageState defaults to available
	 * @param string $packagePath
	 * @param string $packageType
	 *
	 * @return array Array of \TYPO3\Flow\Package\PackageInterface
	 * @api
	 */
	public function getFilteredPackages($packageState = 'available', $packagePath = NULL, $packageType = NULL);

	/**
	 * Returns the upper camel cased version of the given package key or FALSE
	 * if no such package is available.
	 *
	 * @param string $unknownCasedPackageKey The package key to convert
	 * @return mixed The upper camel cased package key or FALSE if no such package exists
	 * @api
	 */
	public function getCaseSensitivePackageKey($unknownCasedPackageKey);

	/**
	 * Check the conformance of the given package key
	 *
	 * @param string $packageKey The package key to validate
	 * @api
	 */
	public function isPackageKeyValid($packageKey);

	/**
	 * Create a new package, given the package key
	 *
	 * @param string $packageKey The package key to use for the new package
	 * @param \TYPO3\Flow\Package\MetaData $packageMetaData Package metadata
	 * @param string $packagesPath If specified, the package will be created in this path
	 * @param string $packageType If specified, the package type will be set
	 * @return \TYPO3\Flow\Package\Package The newly created package
	 * @api
	 */
	public function createPackage($packageKey, \TYPO3\Flow\Package\MetaData $packageMetaData = NULL, $packagesPath = NULL, $packageType = NULL);

	/**
	 * Deactivates a package if it is in the list of active packages
	 *
	 * @param string $packageKey The package to deactivate
	 * @return void
	 * @api
	 */
	public function deactivatePackage($packageKey);

	/**
	 * Activates a package
	 *
	 * @param string $packageKey The package to activate
	 * @return void
	 * @api
	 */
	public function activatePackage($packageKey);

	/**
	 * Freezes a package
	 *
	 * @param string $packageKey The package to freeze
	 * @return void
	 */
	public function freezePackage($packageKey);

	/**
	 * Tells if a package is frozen
	 *
	 * @param string $packageKey The package to check
	 * @return boolean
	 */
	public function isPackageFrozen($packageKey);

	/**
	 * Unfreezes a package
	 *
	 * @param string $packageKey The package to unfreeze
	 * @return void
	 */
	public function unfreezePackage($packageKey);

	/**
	 * Refreezes a package
	 *
	 * @param string $packageKey The package to refreeze
	 * @return void
	 */
	public function refreezePackage($packageKey);

	/**
	 * Register a native Flow package
	 *
	 * @param string $packageKey The Package to be registered
	 * @param boolean $sortAndSave allows for not saving packagestates when used in loops etc.
	 * @return PackageInterface
	 * @throws Exception\CorruptPackageException
	 */
	public function registerPackage(PackageInterface $package, $sortAndSave = TRUE);

	/**
	 * Unregisters a package from the list of available packages
	 *
	 * @param PackageInterface $package The package to be unregistered
	 * @throws Exception\InvalidPackageStateException
	 */
	public function unregisterPackage(PackageInterface $package);

	/**
	 * Removes a package from registry and deletes it from filesystem
	 *
	 * @param string $packageKey package to delete
	 * @return void
	 * @api
	 */
	public function deletePackage($packageKey);

}
?>