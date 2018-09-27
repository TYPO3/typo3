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
 * This is an intermediate package manager that loads just
 * the required extensions for the install in case the package
 * states are unavailable.
 */
class FailsafePackageManager extends PackageManager
{
    /**
     * @var bool TRUE if package manager is in failsafe mode
     */
    protected $inFailsafeMode = false;

    /**
     * Loads the states of available packages from the PackageStates.php file.
     * The result is stored in $this->packageStatesConfiguration.
     */
    protected function loadPackageStates()
    {
        try {
            parent::loadPackageStates();
        } catch (Exception\PackageStatesUnavailableException $exception) {
            $this->inFailsafeMode = true;
            $this->packageStatesConfiguration = [];
            $this->scanAvailablePackages();
        }
    }

    /**
     * Save states
     */
    protected function savePackageStates()
    {
        // Do not save if in rescue mode
        if (!$this->inFailsafeMode) {
            parent::savePackageStates();
        }
    }

    /**
     * To enable writing of the package states file the package states
     * migration needs to override eventual failsafe blocks.
     */
    public function forceSortAndSavePackageStates()
    {
        parent::sortActivePackagesByDependencies();
        parent::savePackageStates();
    }
}
