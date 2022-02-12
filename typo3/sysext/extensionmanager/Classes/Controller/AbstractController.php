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

namespace TYPO3\CMS\Extensionmanager\Controller;

use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Abstract action controller.
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class AbstractController extends ActionController
{
    const TRIGGER_RefreshModuleMenu = 'refreshModuleMenu';
    const TRIGGER_RefreshTopbar = 'refreshTopbar';

    protected ModuleTemplateFactory $moduleTemplateFactory;

    protected array $triggerArguments = [
        self::TRIGGER_RefreshModuleMenu,
        self::TRIGGER_RefreshTopbar,
    ];

    public function injectModuleTemplateFactory(ModuleTemplateFactory $moduleTemplateFactory)
    {
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    /**
     * Translation shortcut
     *
     * @param string $key
     * @param array|null $arguments
     * @return string
     */
    protected function translate($key, $arguments = null)
    {
        return LocalizationUtility::translate($key, 'extensionmanager', $arguments) ?? '';
    }

    /**
     * Handles trigger arguments, e.g. refreshing the module menu
     * widget if an extension with backend modules has been enabled
     * or disabled.
     */
    protected function handleTriggerArguments(ModuleTemplate $view)
    {
        $triggers = [];
        foreach ($this->triggerArguments as $triggerArgument) {
            if ($this->request->hasArgument($triggerArgument)) {
                $triggers[$triggerArgument] = $this->request->getArgument($triggerArgument);
            }
        }
        $view->assign('triggers', $triggers);
    }

    /**
     * Generates the action menu. Helper used in action that render backend moduleTemplate
     * views and not just redirect or response download things.
     */
    protected function initializeModuleTemplate(Request $request): ModuleTemplate
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

        $view = $this->moduleTemplateFactory->create($request, 'typo3/cms-extensionmanager');
        // Assign some view vars we always need.
        $view->assignMultiple([
            'extensionName' => $request->getControllerExtensionName(),
            'controllerName' => $request->getControllerName(),
            'actionName' => $request->getControllerActionName(),
        ]);
        $menu = $view->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
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
                $view->setTitle(
                    $this->translate('LLL:EXT:extensionmanager/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'),
                    $menuItemConfig['label']
                );
            }
        }

        $view->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
        $view->setFlashMessageQueue($this->getFlashMessageQueue());

        return $view;
    }
}
