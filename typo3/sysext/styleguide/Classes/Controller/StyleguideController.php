<?php
declare(strict_types=1);
namespace TYPO3\CMS\Styleguide\Controller;

/**
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

use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Styleguide\Service\KauderwelschService;
use TYPO3\CMS\Styleguide\TcaDataGenerator\Generator;

/**
 * Backend module for Styleguide
 */
class StyleguideController extends ActionController
{

    /**
     * Backend Template Container.
     * Takes care of outer "docheader" and other stuff this module is embedded in.
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
     * @var string
     */
    protected $languageFilePrefix = 'LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:';

    /**
     * Method is called before each action and sets up the doc header.
     *
     * @param ViewInterface $view
     */
    protected function initializeView(ViewInterface $view)
    {
        parent::initializeView($view);

        // Early return for actions without valid view like tcaCreateAction or tcaDeleteAction
        if (!($this->view instanceof BackendTemplateView)) {
            return;
        }

        // Hand over flash message queue to module template
        $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());
        $this->view->assign('actions', ['index', 'typography', 'tca', 'trees', 'tab', 'tables', 'avatar', 'buttons',
            'infobox', 'flashMessages', 'icons', 'debug', 'helpers']);
        $this->view->assign('currentAction', $this->request->getControllerActionName());

        // Shortcut button
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        $getVars = $this->request->getArguments();
        $extensionName = $this->request->getControllerExtensionName();
        $moduleName = $this->request->getPluginName();
        if (count($getVars) === 0) {
            $modulePrefix = strtolower('tx_' . $extensionName . '_' . $moduleName);
            $getVars = array('id', 'M', $modulePrefix);
        }
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setModuleName($moduleName)
            ->setGetVariables($getVars);
        $buttonBar->addButton($shortcutButton);
    }

    /**
     * Buttons
     */
    public function buttonsAction()
    {
    }

    /**
     * Index
     */
    public function indexAction()
    {
    }

    /**
     * Typography
     */
    public function typographyAction()
    {
    }

    /**
     * Trees
     */
    public function treesAction()
    {
    }

    /**
     * Tables
     */
    public function tablesAction()
    {
    }

    /**
     * TCA
     */
    public function tcaAction()
    {
    }

    /**
     * TCA create default data action
     */
    public function tcaCreateAction()
    {
        /** @var Generator $generator */
        $generator = GeneralUtility::makeInstance(Generator::class);
        $generator->create();
        // Tell something was done here
        $this->addFlashMessage(
            LocalizationUtility::translate($this->languageFilePrefix . 'tcaCreateActionOkBody', 'styleguide'),
            LocalizationUtility::translate($this->languageFilePrefix . 'tcaCreateActionOkTitle', 'styleguide')
        );
        // And redirect to display action
        $this->forward('tca');
    }

    /**
     * TCA delete default data action
     */
    public function tcaDeleteAction()
    {
        /** @var Generator $generator */
        $generator = GeneralUtility::makeInstance(Generator::class);
        $generator->delete();
        // Tell something was done here
        $this->addFlashMessage(
            LocalizationUtility::translate($this->languageFilePrefix . 'tcaDeleteActionOkBody', 'styleguide'),
            LocalizationUtility::translate($this->languageFilePrefix . 'tcaDeleteActionOkTitle', 'styleguide')
        );
        // And redirect to display action
        $this->forward('tca');
    }

    /**
     * Debug
     */
    public function debugAction()
    {
    }

    /**
     * Icons
     */
    public function iconsAction()
    {
        $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
        $allIcons = $iconRegistry->getAllRegisteredIconIdentifiers();
        $this->view->assign('allIcons', $allIcons);

        $overlays = [];
        foreach ($allIcons as $key) {
            if (strpos($key, 'overlay') === 0) {
                $overlays[] = $key;
            }
        }
        $this->view->assign('overlays', $overlays);
    }

    /**
     * Infobox
     */
    public function infoboxAction()
    {
    }

    /**
     * FlashMessages
     */
    public function flashMessagesAction()
    {
        $loremIpsum = $this->objectManager->get(KauderwelschService::class)->getLoremIpsum();
        $this->addFlashMessage($loremIpsum, 'Info - Title for Info message', FlashMessage::INFO, true);
        $this->addFlashMessage($loremIpsum, 'Notice - Title for Notice message', FlashMessage::NOTICE, true);
        $this->addFlashMessage($loremIpsum, 'Error - Title for Error message', FlashMessage::ERROR, true);
        $this->addFlashMessage($loremIpsum, 'Ok - Title for OK message', FlashMessage::OK, true);
        $this->addFlashMessage($loremIpsum, 'Warning - Title for Warning message', FlashMessage::WARNING, true);
    }

    /**
     * Helpers
     */
    public function helpersAction()
    {
    }

    /**
     * Avatar
     */
    public function avatarAction()
    {
        $this->view->assign(
            'backendUser',
            $GLOBALS['BE_USER']->user
        );
    }

    /**
     * Tabs
     */
    public function tabAction()
    {
        $module = GeneralUtility::makeInstance(ModuleTemplate::class);

        $menuItems = array(
            0 => array(
                'label' => 'First label',
                'content' => 'First content'
            ),
            1 => array(
                'label' => 'Second label',
                'content' => 'Second content'
            ),
            2 => array(
                'label' => 'Third label',
                'content' => 'Third content'
            )
        );
        $tabs = $module->getDynamicTabMenu($menuItems, 'ident');
        $this->view->assign('tabs', $tabs);
    }
}
