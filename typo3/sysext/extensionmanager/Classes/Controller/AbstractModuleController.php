<?php
namespace TYPO3\CMS\Extensionmanager\Controller;

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
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

/**
 * Abstract action controller.
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
     *
     * @return void
     */
    protected function generateMenu()
    {
        $menuItems = [
            'installedExtensions' => [
                'controller' => 'List',
                'action' => 'index',
                'label' => $this->translate('installedExtensions')
            ]
        ];

        if (!$this->settings['offlineMode'] && !Bootstrap::usesComposerClassLoading()) {
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

        $uriBuilder = $this->objectManager->get(UriBuilder::class);
        $uriBuilder->setRequest($this->request);

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
                ->setHref($this->getHref($menuItemConfig['controller'], $menuItemConfig['action']))
                ->setActive($isActive);
            $menu->addMenuItem($menuItem);
        }

        $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
        $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());
    }

    /**
     * Creates te URI for a backend action
     *
     * @param string $controller
     * @param string $action
     * @param array $parameters
     * @return string
     */
    protected function getHref($controller, $action, $parameters = [])
    {
        $uriBuilder = $this->objectManager->get(UriBuilder::class);
        $uriBuilder->setRequest($this->request);
        return $uriBuilder->reset()->uriFor($action, $parameters, $controller);
    }
}
