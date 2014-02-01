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

/**
 * Interface for a Flow Package class
 *
 * @api
 */
interface PackageInterface extends \TYPO3\Flow\Package\PackageInterface {

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
	 * @return boolean
	 */
	public function isPartOfFactoryDefault();

	/**
	 * Tells if the package is required for a minimal usable (backend) system
	 * and therefor activated if PackageStates is created from scratch for
	 * whatever reason.
	 *
	 * @return boolean
	 */
	public function isPartOfMinimalUsableSystem();
}
