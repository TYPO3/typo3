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

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Styleguide\Service\KauderwelschService;
use TYPO3\CMS\Styleguide\TcaDataGenerator\Generator;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordFinder;

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
    protected function initializeView(ViewInterface $view): void
    {
        parent::initializeView($view);

        // Early return for actions without valid view like tcaCreateAction or tcaDeleteAction
        if (!($this->view instanceof BackendTemplateView)) {
            return;
        }

        // Hand over flash message queue to module template
        $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());
        $this->view->assign('actions', ['index', 'typography', 'tca', 'trees', 'tab', 'tables', 'avatar', 'buttons',
            'infobox', 'flashMessages', 'icons', 'debug', 'helpers', 'modal']);
        $this->view->assign('currentAction', $this->request->getControllerActionName());

        // Shortcut button
        $arguments = $this->request->getArguments();
        $shortcutArguments = [];
        if (!empty($arguments['controller']) && !empty($arguments['action'])) {
            $shortcutArguments['tx_styleguide_help_styleguidestyleguide'] = [
                'controller' => $arguments['controller'],
                'action' => $arguments['action']
            ];
        }
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setDisplayName(sprintf(
                '%s - %s',
                LocalizationUtility::translate($this->languageFilePrefix . 'styleguide', 'styleguide'),
                LocalizationUtility::translate($this->languageFilePrefix . ($arguments['action'] ?? 'index'), 'styleguide')
            ))
            ->setRouteIdentifier('help_StyleguideStyleguide')
            ->setArguments($shortcutArguments);
        $buttonBar->addButton($shortcutButton);
    }

    /**
     * Buttons
     */
    public function buttonsAction(): void
    {
    }

    /**
     * Index
     */
    public function indexAction(): void
    {
    }

    /**
     * Typography
     */
    public function typographyAction(): void
    {
    }

    /**
     * Trees
     */
    public function treesAction(): void
    {
    }

    /**
     * Tables
     */
    public function tablesAction(): void
    {
    }

    /**
     * TCA
     */
    public function tcaAction(): void
    {
        $finder = GeneralUtility::makeInstance(RecordFinder::class);
        $demoExists = count($finder->findUidsOfStyleguideEntryPages());
        $this->view->assign('demoExists', $demoExists);
    }

    /**
     * TCA create default data action
     */
    public function tcaCreateAction(): ResponseInterface
    {
        $finder = GeneralUtility::makeInstance(RecordFinder::class);
        if (count($finder->findUidsOfStyleguideEntryPages())) {
            // Tell something was done here
            $this->addFlashMessage(
                LocalizationUtility::translate($this->languageFilePrefix . 'tcaCreateActionFailedBody', 'styleguide'),
                LocalizationUtility::translate($this->languageFilePrefix . 'tcaCreateActionFailedTitle', 'styleguide'),
                AbstractMessage::ERROR
            );
        } else {
            $generator = GeneralUtility::makeInstance(Generator::class);
            $generator->create();
            // Tell something was done here
            $this->addFlashMessage(
                LocalizationUtility::translate($this->languageFilePrefix . 'tcaCreateActionOkBody', 'styleguide'),
                LocalizationUtility::translate($this->languageFilePrefix . 'tcaCreateActionOkTitle', 'styleguide')
            );
        }
        // And redirect to display action
        return new ForwardResponse('tca');
    }

    /**
     * TCA delete default data action
     */
    public function tcaDeleteAction(): ResponseInterface
    {
        $generator = GeneralUtility::makeInstance(Generator::class);
        $generator->delete();
        // Tell something was done here
        $this->addFlashMessage(
            LocalizationUtility::translate($this->languageFilePrefix . 'tcaDeleteActionOkBody', 'styleguide'),
            LocalizationUtility::translate($this->languageFilePrefix . 'tcaDeleteActionOkTitle', 'styleguide')
        );
        // And redirect to display action
        return new ForwardResponse('tca');
    }

    /**
     * Debug
     */
    public function debugAction(): void
    {
    }

    /**
     * Icons
     */
    public function iconsAction(): void
    {
        $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
        $allIcons = $iconRegistry->getAllRegisteredIconIdentifiers();
        $overlays = array_filter(
            $allIcons,
            function ($key) {
                return strpos($key, 'overlay') === 0;
            }
        );

        $this->view->assignMultiple([
            'allIcons' => $allIcons,
            'deprecatedIcons' => $iconRegistry->getDeprecatedIcons(),
            'overlays' => $overlays,
        ]);
    }

    /**
     * Infobox
     */
    public function infoboxAction(): void
    {
    }

    /**
     * FlashMessages
     */
    public function flashMessagesAction(): void
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
    public function helpersAction(): void
    {
    }

    /**
     * Avatar
     */
    public function avatarAction(): void
    {
        $this->view->assign(
            'backendUser',
            $GLOBALS['BE_USER']->user
        );
    }

    /**
     * Tabs
     */
    public function tabAction(): void
    {
        $module = GeneralUtility::makeInstance(ModuleTemplate::class);

        $menuItems = [
            0 => [
                'label' => 'First label',
                'content' => 'First content'
            ],
            1 => [
                'label' => 'Second label',
                'content' => 'Second content'
            ],
            2 => [
                'label' => 'Third label',
                'content' => 'Third content'
            ]
        ];
        $tabs = $module->getDynamicTabMenu($menuItems, 'ident');
        $this->view->assign('tabs', $tabs);
    }

    public function modalAction(): void
    {
    }
}
