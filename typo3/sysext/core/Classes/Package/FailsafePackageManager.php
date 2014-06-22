<?php
namespace TYPO3\CMS\Core\Package;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This is an intermediate package manager that loads just
 * the required extensions for the install in case the package
 * states are unavailable.
 */
class FailsafePackageManager extends \TYPO3\CMS\Core\Package\PackageManager {

	/**
	 * @var \TYPO3\CMS\Core\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @var boolean TRUE if package manager is in failsafe mode
	 */
	protected $inFailsafeMode = FALSE;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->configurationManager = new \TYPO3\CMS\Core\Configuration\ConfigurationManager;
		parent::__construct();
	}

	/**
	 * Loads the states of available packages from the PackageStates.php file.
	 * The result is stored in $this->packageStatesConfiguration.
	 *
	 * @return void
	 */
	protected function loadPackageStates() {
		try {
			parent::loadPackageStates();
		} catch (\TYPO3\CMS\Core\Package\Exception\PackageStatesUnavailableException $exception) {
			$this->inFailsafeMode = TRUE;
			$this->packageStatesConfiguration = array();
			$this->scanAvailablePackages();
		}
	}

	/**
	 * Requires and registers all packages which were defined in packageStatesConfiguration
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Package\Exception\CorruptPackageException
	 */
	protected function registerPackagesFromConfiguration() {
		$this->packageStatesConfiguration['packages']['install']['state'] = 'active';
		parent::registerPackagesFromConfiguration();
	}

	/**
	 * Sort and save states
	 *
	 * @return void
	 */
	protected function sortAndSavePackageStates() {
		// Do not save if in rescue mode
		if (!$this->inFailsafeMode) {
			parent::sortAndSavePackageStates();
		}
	}

	/**
	 * To enable writing of the package states file the package states
	 * migration needs to override eventual failsafe blocks.
	 */
	public function forceSortAndSavePackageStates() {
		parent::sortAndSavePackageStates();
	}
}