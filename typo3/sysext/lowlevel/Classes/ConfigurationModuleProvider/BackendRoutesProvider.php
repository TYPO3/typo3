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

namespace TYPO3\CMS\Lowlevel\ConfigurationModuleProvider;

use TYPO3\CMS\Backend\Module\Module;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Utility\ArrayUtility;

class BackendRoutesProvider extends AbstractProvider
{
    public function __construct(protected readonly Router $router) {}

    public function getConfiguration(): array
    {
        $configurationArray = [];
        $routeCollection = $this->router->getRouteCollection();
        foreach ($routeCollection->getIterator() as $identifier => $route) {
            $configurationArray[$identifier] = [
                'path' => $route->getPath(),
                'options' => $route->getOptions(),
                'methods' => implode(',', $route->getMethods()) ?: '*',
            ];
            // Modules can be a double-linked list through 'parent module' and 'submodules'.
            // To prevent an infinite recursion loop when rendering the object, we 'unset' both properties.
            if (isset($configurationArray[$identifier]['options']['module'])
                && $configurationArray[$identifier]['options']['module'] instanceof ModuleInterface
                && $configurationArray[$identifier]['options']['module']->getParentModule() instanceof ModuleInterface
            ) {
                $clonedModule = clone $configurationArray[$identifier]['options']['module'];
                $clonedModule->setParentModule(Module::createFromConfiguration('recursion dummy', ['path' => 'dummy']));
                $configurationArray[$identifier]['options']['module'] = $clonedModule;
            }
            if (isset($configurationArray[$identifier]['options']['module'])
                && $configurationArray[$identifier]['options']['module'] instanceof ModuleInterface
                && $configurationArray[$identifier]['options']['module']->hasSubModules()
            ) {
                $clonedModule = clone $configurationArray[$identifier]['options']['module'];
                foreach ($clonedModule->getSubModules() as $subModule) {
                    $clonedModule->removeSubModule($subModule->getIdentifier());
                }
                $configurationArray[$identifier]['options']['module'] = $clonedModule;
            }
        }
        foreach ($routeCollection->getAliases() as $aliasName => $alias) {
            $configurationArray[$alias->getId()]['aliases'] ??= [];
            $configurationArray[$alias->getId()]['aliases'][] = $aliasName;
        }
        ArrayUtility::naturalKeySortRecursive($configurationArray);
        return $configurationArray;
    }
}
