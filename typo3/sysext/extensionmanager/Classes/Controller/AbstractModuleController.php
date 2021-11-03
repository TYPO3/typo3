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

namespace TYPO3\CMS\Extensionmanager\Controller;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Core\Environment;

/**
 * Abstract action controller.
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class AbstractModuleController extends AbstractController
{
    protected ModuleTemplateFactory $moduleTemplateFactory;

    public function injectModuleTemplateFactory(ModuleTemplateFactory $moduleTemplateFactory)
    {
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    /**
     * Resolve view and initialize the general view-variables extensionName,
     * controllerName and actionName based on the request object
     *
     * @return \TYPO3\CMS\Extbase\Mvc\View\ViewInterface
     */
    protected function resolveView()
    {
        $view = parent::resolveView();
        $view->assignMultiple([
            'extensionName' => $this->request->getControllerExtensionName(),
            'controllerName' => $this->request->getControllerName(),
            'actionName' => $this->request->getControllerActionName(),
        ]);
        return $view;
    }

    /**
     * Generates the action menu
     */
    protected function initializeModuleTemplate(ServerRequestInterface $request): ModuleTemplate
    {
        $menuItems = [
            'installedExtensions' => [
                'controller' => 'List',
                'action' => 'index',
                'label' => $this->translate('installedExtensions'),
            ],
            'extensionComposerStatus' => [
                'controller' => 'ExtensionComposerStatus',
                'action' => 'list',
                'label' => $this->translate('extensionComposerStatus'),
            ],
        ];

        if (!(bool)($this->settings['offlineMode'] ?? false) && !Environment::isComposerMode()) {
            $menuItems['getExtensions'] = [
                'controller' => 'List',
                'action' => 'ter',
                'label' => $this->translate('getExtensions'),
            ];
            $menuItems['distributions'] = [
                'controller' => 'List',
                'action' => 'distributions',
                'label' => $this->translate('distributions'),
            ];

            if ($this->actionMethodName === 'showAllVersionsAction') {
                $menuItems['showAllVersions'] = [
                    'controller' => 'List',
                    'action' => 'showAllVersions',
                    'label' => $this->translate('showAllVersions') . ' ' . $request->getArgument('extensionKey'),
                ];
            }
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $menu = $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('ExtensionManagerModuleMenu');

        foreach ($menuItems as  $menuItemConfig) {
            if ($request->getControllerName() === $menuItemConfig['controller']) {
                $isActive = $request->getControllerActionName() === $menuItemConfig['action'] ? true : false;
            } else {
                $isActive = false;
            }
            $menuItem = $menu->makeMenuItem()
                ->setTitle($menuItemConfig['label'])
                ->setHref($this->uriBuilder->reset()->uriFor($menuItemConfig['action'], [], $menuItemConfig['controller']))
                ->setActive($isActive);
            $menu->addMenuItem($menuItem);
            if ($isActive) {
                $moduleTemplate->setTitle(
                    $this->translate('LLL:EXT:extensionmanager/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'),
                    $menuItemConfig['label']
                );
            }
        }

        $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
        $moduleTemplate->setFlashMessageQueue($this->getFlashMessageQueue());

        return $moduleTemplate;
    }
}
