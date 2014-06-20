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

		$cacheIdentifier = str_replace('.', '', uniqid('', TRUE));
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