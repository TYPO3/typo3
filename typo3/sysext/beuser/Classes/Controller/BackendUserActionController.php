<?php
namespace TYPO3\CMS\Beuser\Controller;

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

use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Backend module user/group action controller
 */
class BackendUserActionController extends ActionController
{
    /**
     * Backend Template Container
     *
     * @var string
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * BackendTemplateContainer
     *
     * @var BackendTemplateView
     */
    protected $view;

    /**
     * Set up the doc header properly here
     *
     * @param ViewInterface $view
     * @return void
     */
    protected function initializeView(ViewInterface $view)
    {
        /** @var BackendTemplateView $view */
        parent::initializeView($view);
        if ($this->actionMethodName == 'indexAction'
            || $this->actionMethodName == 'onlineAction'
            || $this->actionMethodName == 'compareAction') {
            $this->generateMenu();
            $this->registerDocheaderButtons();
            $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());
        }
        if ($view instanceof BackendTemplateView) {
            $view->getModuleTemplate()->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/Modal');
        }
    }

    /**
     * Generates the action menu
     *
     * @return void
     */
    protected function generateMenu()
    {
        $menuItems = [
            'index' => [
                'controller' => 'BackendUser',
                'action' => 'index',
                'label' => $this->getLanguageService()->sL('LLL:EXT:beuser/Resources/Private/Language/locallang.xml:backendUsers')
            ],
            'pages' => [
                'controller' => 'BackendUserGroup',
                'action' => 'index',
                'label' => $this->getLanguageService()->sL('LLL:EXT:beuser/Resources/Private/Language/locallang.xml:backendUserGroupsMenu')
            ],
            'online' => [
                'controller' => 'BackendUser',
                'action' => 'online',
                'label' => $this->getLanguageService()->sL('LLL:EXT:beuser/Resources/Private/Language/locallang.xml:onlineUsers')
            ]
        ];
        $uriBuilder = $this->objectManager->get(UriBuilder::class);
        $uriBuilder->setRequest($this->request);

        $menu = $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('BackendUserModuleMenu');

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
    }

    /**
     * Registers the Icons into the docheader
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function registerDocheaderButtons()
    {
        /** @var ButtonBar $buttonBar */
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        $currentRequest = $this->request;
        $moduleName = $currentRequest->getPluginName();
        $getVars = $this->request->getArguments();

        $extensionName = $currentRequest->getControllerExtensionName();
        if (count($getVars) === 0) {
            $modulePrefix = strtolower('tx_' . $extensionName . '_' . $moduleName);
            $getVars = ['id', 'M', $modulePrefix];
        }
        $shortcutName = $this->getLanguageService()->sL('LLL:EXT:beuser/Resources/Private/Language/locallang.xml:backendUsers');
        if ($this->request->getControllerName() === 'BackendUser') {
            if ($this->request->getControllerActionName() === 'index') {
                $returnUrl = rawurlencode(BackendUtility::getModuleUrl('system_BeuserTxBeuser'));
                $parameters = GeneralUtility::explodeUrl2Array('edit[be_users][0]=new&returnUrl=' . $returnUrl);
                $addUserLink = BackendUtility::getModuleUrl('record_edit', $parameters);
                $title = $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:newRecordGeneral');
                $icon = $this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-document-new', Icon::SIZE_SMALL);
                $addUserButton = $buttonBar->makeLinkButton()
                    ->setHref($addUserLink)
                    ->setTitle($title)
                    ->setIcon($icon);
                $buttonBar->addButton($addUserButton, ButtonBar::BUTTON_POSITION_LEFT);
            }
            if ($this->request->getControllerActionName() === 'compare') {
                $addUserLink = BackendUtility::getModuleUrl('system_BeuserTxBeuser');
                $title = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.goBack');
                $icon = $this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL);
                $addUserButton = $buttonBar->makeLinkButton()
                    ->setHref($addUserLink)
                    ->setTitle($title)
                    ->setIcon($icon);
                $buttonBar->addButton($addUserButton, ButtonBar::BUTTON_POSITION_LEFT);
            }
            if ($this->request->getControllerActionName() === 'online') {
                $shortcutName = $this->getLanguageService()->sL('LLL:EXT:beuser/Resources/Private/Language/locallang.xml:onlineUsers');
            }
        }
        if ($this->request->getControllerName() === 'BackendUserGroup') {
            $shortcutName = $this->getLanguageService()->sL('LLL:EXT:beuser/Resources/Private/Language/locallang.xml:backendUserGroupsMenu');
            $returnUrl = rawurlencode(BackendUtility::getModuleUrl('system_BeuserTxBeuser', [
                'tx_beuser_system_beusertxbeuser' => [
                    'action' => 'index',
                    'controller' => 'BackendUserGroup'
                ]
            ]));
            $parameters = GeneralUtility::explodeUrl2Array('edit[be_groups][0]=new&returnUrl=' . $returnUrl);
            $addUserLink = BackendUtility::getModuleUrl('record_edit', $parameters);
            $title = $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:newRecordGeneral');
            $icon = $this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-document-new', Icon::SIZE_SMALL);
            $addUserGroupButton = $buttonBar->makeLinkButton()
                ->setHref($addUserLink)
                ->setTitle($title)
                ->setIcon($icon);
            $buttonBar->addButton($addUserGroupButton, ButtonBar::BUTTON_POSITION_LEFT);
        }
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setModuleName($moduleName)
            ->setDisplayName($shortcutName)
            ->setGetVariables($getVars);
        $buttonBar->addButton($shortcutButton);
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

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
