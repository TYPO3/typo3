<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Adminpanel\Service;

use TYPO3\CMS\Adminpanel\ModuleApi\ConfigurableInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\SubmoduleProviderInterface;
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
     * @param array<string, mixed> $modules
     * @return array<string, ModuleInterface>
     * @throws \RuntimeException
     */
    public function validateSortAndInitializeModules(array $modules): array
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
            if (empty($configuration['module']) ||
                !is_string($configuration['module']) ||
                !class_exists($configuration['module']) ||
                !is_subclass_of(
                    $configuration['module'],
                    ModuleInterface::class,
                    true
                )
            ) {
                throw new \RuntimeException(
                    'The module "' .
                    $identifier .
                    '" defines an invalid module class. Ensure the class exists and implements the "' .
                    ModuleInterface::class .
                    '".',
                    1519490112
                );
            }
        }

        $orderedModules = GeneralUtility::makeInstance(DependencyOrderingService::class)->orderByDependencies(
            $modules
        );

        $moduleInstances = [];
        foreach ($orderedModules as $moduleConfiguration) {
            $module = GeneralUtility::makeInstance($moduleConfiguration['module']);
            if (
                $module instanceof ModuleInterface
                && (
                    ($module instanceof ConfigurableInterface && $module->isEnabled())
                    || !($module instanceof ConfigurableInterface)
                )
            ) {
                $moduleInstances[$module->getIdentifier()] = $module;
            }
            if ($module instanceof SubmoduleProviderInterface) {
                $subModuleInstances = $this->validateSortAndInitializeModules($moduleConfiguration['submodules'] ?? []);
                $module->setSubModules($subModuleInstances);
            }
        }
        return $moduleInstances;
    }
}
