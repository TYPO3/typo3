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

use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Core\Utility\ArrayUtility;

class BackendModuleProvider extends AbstractProvider
{
    public function __construct(protected readonly ModuleProvider $provider) {}

    public function getConfiguration(): array
    {
        $configurationArray = [];
        foreach ($this->provider->getModules(respectWorkspaceRestrictions: false, grouped: false) as $identifier => $module) {
            $configurationArray[$identifier] = [
                'identifier' => $module->getIdentifier(),
                'parentIdentifier' => $module->getParentIdentifier(),
                'iconIdentifier' => $module->getIconIdentifier(),
                'title' => $module->getTitle(),
                'description' => $module->getDescription(),
                'shortDescription' => $module->getShortDescription(),
                'access' => $module->getAccess(),
                'aliases' => $module->getAliases(),
                'position' => $module->getPosition(),
                'workspaces' => $module->getWorkspaceAccess() ?: $module->getParentModule()?->getWorkspaceAccess(),
                'isStandalone' => $module->isStandalone() ? 'true' : 'false',
                'submodules' => $module->hasSubModules() ? implode(',', array_keys($module->getSubModules())) : '',
                'path' => $module->getPath(),
            ];
        }
        ArrayUtility::naturalKeySortRecursive($configurationArray);
        return $configurationArray;
    }
}
