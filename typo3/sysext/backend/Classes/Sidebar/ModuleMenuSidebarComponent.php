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

namespace TYPO3\CMS\Backend\Sidebar;

use TYPO3\CMS\Backend\Attribute\AsSidebarComponent;
use TYPO3\CMS\Backend\Module\MenuModule;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Module menu sidebar component.
 *
 * Renders the main backend module navigation menu in the sidebar.
 *
 * @internal
 */
#[AsSidebarComponent(identifier: 'module-menu')]
final readonly class ModuleMenuSidebarComponent implements SidebarComponentInterface
{
    public function __construct(
        private ModuleProvider $moduleProvider,
        private BackendViewFactory $viewFactory,
        private UriBuilder $uriBuilder,
    ) {}

    public function hasAccess(SidebarComponentContext $context): bool
    {
        return !empty($this->moduleProvider->getModulesForModuleMenu($context->user));
    }

    public function getResult(SidebarComponentContext $context): SidebarComponentResult
    {
        $modules = $this->moduleProvider->getModulesForModuleMenu($context->user);

        if ($modules === []) {
            return new SidebarComponentResult('', '');
        }

        $view = $this->viewFactory->create($context->request);
        $view->assignMultiple([
            'modules' => $modules,
            'modulesInformation' => GeneralUtility::jsonEncodeForHtmlAttribute(
                $this->getModulesInformation($context),
                false
            ),
        ]);

        return new SidebarComponentResult(
            identifier: 'module-menu',
            html: $view->render('Backend/ModuleMenu'),
            module: '@typo3/backend/module-menu.js',
        );
    }

    /**
     * Get module information for JavaScript.
     *
     * @return array<string, mixed>
     */
    private function getModulesInformation(SidebarComponentContext $context): array
    {
        $modules = [];
        foreach ($this->moduleProvider->getModules($context->user, true, false) as $identifier => $module) {
            $menuModule = new MenuModule(clone $module);
            $modules[$identifier] = [
                'name' => $identifier,
                'aliases' => $module->getAliases(),
                'component' => $menuModule->getComponent(),
                'navigationComponentId' => $menuModule->getNavigationComponent(),
                'parent' => $menuModule->hasParentModule() ? $menuModule->getParentIdentifier() : '',
                'link' => $menuModule->getShouldBeLinked() ? (string)$this->uriBuilder->buildUriFromRoute($module->getIdentifier()) : '',
            ];
        }
        return $modules;
    }
}
