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

use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Core\Environment;

/**
 * Abstract action controller.
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class AbstractModuleController extends AbstractController
{
    /**
     * BackendTemplateContainer
     *
     * @var BackendTemplateView
     */
    protected $view;

    /**
     * Backend Template Container
     *
     * @var string
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * Resolve view and initialize the general view-variables extensionName,
     * controllerName and actionName based on the request object
     *
     * @return \TYPO3\CMS\Fluid\View\TemplateView
     */
    protected function resolveView()
    {
        $view = parent::resolveView();
        $view->assignMultiple([
            'extensionName' => $this->request->getControllerExtensionName(),
            'controllerName' => $this->request->getControllerName(),
            'actionName' => $this->request->getControllerActionName()
        ]);
        return $view;
    }

    /**
     * Generates the action menu
     */
    protected function generateMenu()
    {
        $menuItems = [
            'installedExtensions' => [
                'controller' => 'List',
                'action' => 'index',
                'label' => $this->translate('installedExtensions')
            ],
            'extensionComposerStatus' => [
                'controller' => 'ExtensionComposerStatus',
                'action' => 'list',
                'label' => $this->translate('extensionComposerStatus')
            ]
        ];

        if (!$this->settings['offlineMode'] && !Environment::isComposerMode()) {
            $menuItems['getExtensions'] = [
                'controller' => 'List',
                'action' => 'ter',
                'label' => $this->translate('getExtensions')
            ];
            $menuItems['distributions'] = [
                'controller' => 'List',
                'action' => 'distributions',
                'label' => $this->translate('distributions')
            ];

            if ($this->actionMethodName === 'showAllVersionsAction') {
                $menuItems['showAllVersions'] = [
                    'controller' => 'List',
                    'action' => 'showAllVersions',
                    'label' => $this->translate('showAllVersions') . ' ' . $this->request->getArgument('extensionKey')
                ];
            }
        }

        $menu = $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('ExtensionManagerModuleMenu');

        foreach ($menuItems as  $menuItemConfig) {
            if ($this->request->getControllerName() === $menuItemConfig['controller']) {
                $isActive = $this->request->getControllerActionName() === $menuItemConfig['action'] ? true : false;
            } else {
                $isActive = false;
            }
            $menuItem = $menu->makeMenuItem()
                ->setTitle($menuItemConfig['label'])
                ->setHref($this->uriBuilder->reset()->uriFor($menuItemConfig['action'], [], $menuItemConfig['controller']))
                ->setActive($isActive);
            $menu->addMenuItem($menuItem);
        }

        $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
        $this->view->getModuleTemplate()->setFlashMessageQueue($this->getFlashMessageQueue());
    }
}
