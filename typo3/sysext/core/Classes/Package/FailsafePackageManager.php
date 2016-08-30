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
     * @var \TYPO3\CMS\Core\Configuration\ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var bool TRUE if package manager is in failsafe mode
     */
    protected $inFailsafeMode = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->configurationManager = new \TYPO3\CMS\Core\Configuration\ConfigurationManager;
        parent::__construct();
    }

    /**
     * Loads the states of available packages from the PackageStates.php file.
     * The result is stored in $this->packageStatesConfiguration.
     *
     * @return void
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
     * Requires and registers all packages which were defined in packageStatesConfiguration
     *
     * @return void
     */
    protected function registerPackagesFromConfiguration($registerOnlyNewPackages = false)
    {
        $this->packageStatesConfiguration['packages']['install']['state'] = 'active';
        parent::registerPackagesFromConfiguration($registerOnlyNewPackages);
    }

    /**
     * Sort and save states
     *
     * @return void
     */
    protected function sortAndSavePackageStates()
    {
        // Do not save if in rescue mode
        if (!$this->inFailsafeMode) {
            parent::sortAndSavePackageStates();
        }
    }

    /**
     * To enable writing of the package states file the package states
     * migration needs to override eventual failsafe blocks.
     */
    public function forceSortAndSavePackageStates()
    {
        parent::sortAndSavePackageStates();
    }
}
