<?php

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

namespace TYPO3\CMS\Core\Package;

/**
 * This is an intermediate package manager that loads
 * all extensions that are present in one of the package base paths,
 * so that the class loader can find the classes of all tests,
 * whether the according extension is active in the installation itself or not.
 */
class UnitTestPackageManager extends PackageManager
{
    /**
     * Initializes the package manager
     */
    public function initialize()
    {
        $this->scanAvailablePackages();
        foreach ($this->packages as $package) {
            $this->registerActivePackage($package);
        }
    }

    /**
     * Overwrite the original method to avoid resolving dependencies (which we do not need)
     * and saving the PackageStates.php file (which we do not want), when calling scanAvailablePackages()
     */
    protected function sortAndSavePackageStates()
    {
        // Deliberately empty!
    }
}
