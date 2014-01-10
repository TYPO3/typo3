<?php
namespace TYPO3\CMS\Core\Package;

/***************************************************************
*  Copyright notice
*
*  (c) 2013 Thomas Maroschik <tmaroschik@dfau.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the text file GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

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