<?php
namespace TYPO3\CMS\Core\Package;

/*
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

/**
 * Interface for a TYPO3 Package class
 * Adapted from FLOW for TYPO3 CMS
 */
interface PackageInterface {

	const PATTERN_MATCH_PACKAGEKEY = '/^[a-z0-9]+\.(?:[a-z0-9][\.a-z0-9]*)+$/i';

	const PATTERN_MATCH_EXTENSIONKEY = '/^[0-9a-z_-]+$/i';

	const DIRECTORY_CLASSES = 'Classes/';
	const DIRECTORY_CONFIGURATION = 'Configuration/';
	const DIRECTORY_RESOURCES = 'Resources/';

	/**
	 * @return array
	 */
	public function getPackageReplacementKeys();

	/**
	 * @return array
	 */
	public function getClassFilesFromAutoloadRegistry();

	/**
	 * Tells if the package is part of the default factory configuration
	 * and therefor activated at first installation.
	 *
	 * @return bool
	 */
	public function isPartOfFactoryDefault();

	/**
	 * Tells if the package is required for a minimal usable (backend) system
	 * and therefor activated if PackageStates is created from scratch for
	 * whatever reason.
	 *
	 * @return bool
	 */
	public function isPartOfMinimalUsableSystem();

	/**
	 * Invokes custom PHP code directly after the package manager has been initialized.
	 *
	 * @param \TYPO3\CMS\Core\Core\Bootstrap $bootstrap The current bootstrap
	 * @return void
	 */
	public function boot(\TYPO3\CMS\Core\Core\Bootstrap $bootstrap);

	/**
	 * Returns the package meta object of this package.
	 *
	 * @return MetaData
	 */
	public function getPackageMetaData();

	/**
	 * Returns the package key of this package.
	 *
	 * @return string
	 * @api
	 */
	public function getPackageKey();

	/**
	 * Returns the PHP namespace of classes in this package.
	 *
	 * @return string
	 * @api
	 */
	public function getNamespace();

	/**
	 * Tells if this package is protected and therefore cannot be deactivated or deleted
	 *
	 * @return bool
	 * @api
	 */
	public function isProtected();

	/**
	 * Sets the protection flag of the package
	 *
	 * @param bool $protected TRUE if the package should be protected, otherwise FALSE
	 * @return void
	 * @api
	 */
	public function setProtected($protected);

	/**
	 * Returns the full path to this package's main directory
	 *
	 * @return string Path to this package's main directory
	 * @api
	 */
	public function getPackagePath();

	/**
	 * Returns the full path to this package's Classes directory
	 *
	 * @return string Path to this package's Classes directory
	 * @api
	 */
	public function getClassesPath();

	/**
	 * Returns the full path to this package's Resources directory
	 *
	 * @return string Path to this package's Resources directory
	 * @api
	 */
	public function getResourcesPath();

	/**
	 * Returns the full path to this package's Configuration directory
	 *
	 * @return string Path to this package's Configuration directory
	 * @api
	 */
	public function getConfigurationPath();
}
