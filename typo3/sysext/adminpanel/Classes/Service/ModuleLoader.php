<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Adminpanel\Service;

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

use TYPO3\CMS\Adminpanel\Modules\AdminPanelModuleInterface;
use TYPO3\CMS\Adminpanel\Modules\AdminPanelSubModuleInterface;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Admin Panel Module Loader
 *
 * @internal
 */
class ModuleLoader
{

    /**
     * Validates, sorts and initiates the registered modules
     *
     * @param array $modules
     * @param string $type
     * @return AdminPanelModuleInterface[]|AdminPanelSubModuleInterface[]
     * @throws \RuntimeException
     */
    public function validateSortAndInitializeModules(array $modules, string $type = 'main'): array
    {
        if (empty($modules)) {
            return [];
        }
        foreach ($modules as $identifier => $configuration) {
            if (empty($configuration) || !is_array($configuration)) {
                throw new \RuntimeException(
                    'Missing configuration for module "' . $identifier . '".',
                    1519490105
                );
            }
            if (!is_string($configuration['module']) ||
                empty($configuration['module']) ||
                !class_exists($configuration['module']) ||
                !is_subclass_of(
                    $configuration['module'],
                    ($type === 'main' ? AdminPanelModuleInterface::class : AdminPanelSubModuleInterface::class)
                )
            ) {
                throw new \RuntimeException(
                    'The module "' .
                    $identifier .
                    '" defines an invalid module class. Ensure the class exists and implements the "' .
                    AdminPanelModuleInterface::class .
                    '".',
                    1519490112
                );
            }
        }

        $orderedModules = GeneralUtility::makeInstance(DependencyOrderingService::class)->orderByDependencies(
            $modules
        );

        $moduleInstances = [];
        foreach ($orderedModules as $module) {
            $module = GeneralUtility::makeInstance($module['module']);
            if ($module instanceof AdminPanelSubModuleInterface || ($module instanceof AdminPanelModuleInterface && $module->isEnabled())) {
                $moduleInstances[] = $module;
            }
        }
        return $moduleInstances;
    }

    /**
     * Validates, sorts and initializes sub-modules
     *
     * @param array $modules
     * @return AdminPanelSubModuleInterface[]
     */
    public function validateSortAndInitializeSubModules(array $modules): array
    {
        return $this->validateSortAndInitializeModules($modules, 'sub');
    }
}
