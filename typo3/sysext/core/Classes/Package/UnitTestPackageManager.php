<?php
namespace TYPO3\CMS\Core\Package;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Helmut Hummel <helmut.hummel@typo3.org>
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
use TYPO3\Flow\Utility\Files;

/**
 * This is an intermediate package manager that loads
 * all extensions that are present in one of the package base paths,
 * so that the class loader can find the classes of all tests,
 * whether the according extension is active in the installation itself or not.
 */
class UnitTestPackageManager extends \TYPO3\CMS\Core\Package\PackageManager {

	/**
	 * Initializes the package manager
	 *
	 * @param \TYPO3\CMS\Core\Core\Bootstrap|\TYPO3\Flow\Core\Bootstrap $bootstrap The current bootstrap; Flow Bootstrap is here by intention to keep the PackageManager valid to the interface
	 * @return void
	 */
	public function initialize(\TYPO3\Flow\Core\Bootstrap $bootstrap) {
		$this->bootstrap = $bootstrap;

		$this->scanAvailablePackages();
		$this->activePackages = $this->packages;

		$cacheIdentifier = uniqid();
		$this->classLoader->setCacheIdentifier($cacheIdentifier)->setPackages($this->activePackages);
	}

	/**
	 * Overwrite the original method to avoid resolving dependencies (which we do not need)
	 * and saving the PackageStates.php file (which we do not want), when calling scanAvailablePackages()
	 *
	 * @return void
	 */
	protected function sortAndSavePackageStates() {
		// Deliberately empty!
	}

}