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

namespace TYPO3\CMS\Backend\Module;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Core\Bootstrap;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

/**
 * An extbase built backend module
 *
 * @internal only for use within TYPO3 Core.
 */
class ExtbaseModule extends BaseModule implements ModuleInterface
{
    protected string $extensionName;
    protected array $controllerActions;

    /**
     * Extbase modules always need a parent, use "web" as default
     */
    protected string $parent = 'web';

    /**
     * Access is restricted to "admin" by default for extbase modules
     */
    protected string $access = 'admin';

    public function getExtensionName(): string
    {
        return $this->extensionName;
    }

    public function getControllerActions(): array
    {
        return $this->controllerActions;
    }

    public function getDefaultRouteOptions(): array
    {
        $allRoutes = [];
        foreach ($this->controllerActions as $controllerConfiguration) {
            foreach ($controllerConfiguration['actions'] as $actionName) {
                if ($allRoutes === []) {
                    $allRoutes['_default'] = array_replace_recursive(
                        $this->routeOptions,
                        [
                            'module' => $this,
                            'packageName' => $this->packageName,
                            'absolutePackagePath' => $this->absolutePackagePath,
                            'access' => $this->access,
                            'target' => Bootstrap::class . '::handleBackendRequest',
                            'controller' => $controllerConfiguration['alias'],
                            'action' => $actionName,
                        ]
                    );
                }
                $allRoutes[$controllerConfiguration['alias'] . '_' . $actionName] = array_replace_recursive(
                    $this->routeOptions,
                    [
                        'module' => $this,
                        'path' => $controllerConfiguration['alias'] . '/' . $actionName,
                        'packageName' => $this->packageName,
                        'absolutePackagePath' => $this->absolutePackagePath,
                        'access' => $this->access,
                        'target' => Bootstrap::class . '::handleBackendRequest',
                        'controller' => $controllerConfiguration['alias'],
                        'action' => $actionName,
                    ]
                );
            }
        }
        return $allRoutes;
    }

    protected static function sanitizeExtensionName(string $extensionName): string
    {
        return (string)str_replace(' ', '', ucwords(str_replace('_', ' ', $extensionName)));
    }

    protected static function sanitizeControllerActions(array $controllerActions): array
    {
        $sanitizedControllerActions = [];
        foreach ($controllerActions as $controllerName => $actions) {
            $sanitizedControllerActions[$controllerName] = [
                'actions' => (is_array($actions) ? $actions : GeneralUtility::trimExplode(',', $actions)),
                'alias' => ExtensionUtility::resolveControllerAliasFromControllerClassName($controllerName),
                'className' => $controllerName,
            ];
        }
        return $sanitizedControllerActions;
    }

    public static function createFromConfiguration(string $identifier, array $configuration): static
    {
        $obj = parent::createFromConfiguration($identifier, $configuration);
        $obj->extensionName = self::sanitizeExtensionName((string)($configuration['extensionName'] ?? ''));
        $obj->controllerActions = self::sanitizeControllerActions((array)($configuration['controllerActions'] ?? []));
        return $obj;
    }
}
