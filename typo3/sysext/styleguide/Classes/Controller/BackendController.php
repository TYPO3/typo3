<?php

declare(strict_types=1);

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
namespace TYPO3\CMS\Styleguide\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Styleguide\Service\KauderwelschService;
use TYPO3\CMS\Styleguide\TcaDataGenerator\Generator;
use TYPO3\CMS\Styleguide\TcaDataGenerator\GeneratorFrontend;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordFinder;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * Backend module for Styleguide
 */
class BackendController extends ActionController
{
    protected ModuleTemplateFactory $moduleTemplateFactory;
    protected ModuleTemplate $moduleTemplate;

    /**
     * @var string
     */
    protected $languageFilePrefix = 'LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:';

    public function __construct(
        ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    /**
     * Method is called before each action and sets up the doc header.
     *
     * @param ViewInterface $view
     */
    protected function initializeView($view): void
    {
        // Hand over flash message queue to module template
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->moduleTemplate = $moduleTemplate;
        $moduleTemplate->setFlashMessageQueue($this->getFlashMessageQueue());
        $this->view->assign('actions', ['index', 'typography', 'tca', 'trees', 'tab', 'tables', 'avatar', 'buttons',
            'infobox', 'flashMessages', 'icons', 'debug', 'modal', 'accordion', 'pagination']);
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
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
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
    public function buttonsAction(): ResponseInterface
    {
        return $this->htmlResponse($this->moduleTemplate->setContent($this->view->render())->renderContent());
    }

    /**
     * Index
     */
    public function indexAction(): ResponseInterface
    {
        return $this->htmlResponse($this->moduleTemplate->setContent($this->view->render())->renderContent());
    }

    /**
     * Typography
     */
    public function typographyAction(): ResponseInterface
    {
        return $this->htmlResponse($this->moduleTemplate->setContent($this->view->render())->renderContent());
    }

    /**
     * Trees
     */
    public function treesAction(): ResponseInterface
    {
        return $this->htmlResponse($this->moduleTemplate->setContent($this->view->render())->renderContent());
    }

    /**
     * Tables
     */
    public function tablesAction(): ResponseInterface
    {
        return $this->htmlResponse($this->moduleTemplate->setContent($this->view->render())->renderContent());
    }

    /**
     * TCA
     */
    public function tcaAction(): ResponseInterface
    {
        $finder = GeneralUtility::makeInstance(RecordFinder::class);
        $demoExists = count($finder->findUidsOfStyleguideEntryPages());
        $demoFrontendExists = count($finder->findUidsOfFrontendPages());
        $this->view->assignMultiple([
            'demoExists' => $demoExists,
            'demoFrontendExists' => $demoFrontendExists,
        ]);
        return $this->htmlResponse($this->moduleTemplate->setContent($this->view->render())->renderContent());
    }

    /**
     * TCA create default data action
     */
    public function tcaCreateAction(): ResponseInterface
    {
        $finder = GeneralUtility::makeInstance(RecordFinder::class);
        if (count($finder->findUidsOfStyleguideEntryPages())) {
            // Tell something was done here
            $json = [
                'title' => LocalizationUtility::translate($this->languageFilePrefix . 'tcaCreateActionFailedTitle', 'styleguide'),
                'body' => LocalizationUtility::translate($this->languageFilePrefix . 'tcaCreateActionFailedBody', 'styleguide'),
                'status' => AbstractMessage::ERROR
            ];
        } else {
            $generator = GeneralUtility::makeInstance(Generator::class);
            $generator->create();

            // Tell something was done here
            $json = [
                'title' => LocalizationUtility::translate($this->languageFilePrefix . 'tcaCreateActionOkTitle', 'styleguide'),
                'body' => LocalizationUtility::translate($this->languageFilePrefix . 'tcaCreateActionOkBody', 'styleguide'),
                'status' => AbstractMessage::OK
            ];
        }
        // And redirect to display action
        return new JsonResponse($json);
    }

    /**
     * TCA delete default data action
     */
    public function tcaDeleteAction(): ResponseInterface
    {
        $generator = GeneralUtility::makeInstance(Generator::class);
        $generator->delete();
        // Tell something was done here
        $json = [
            'title' => LocalizationUtility::translate($this->languageFilePrefix . 'tcaDeleteActionOkTitle', 'styleguide'),
            'body' => LocalizationUtility::translate($this->languageFilePrefix . 'tcaDeleteActionOkBody', 'styleguide'),
            'status' => AbstractMessage::OK
        ];

        return new JsonResponse($json);
    }

    /**
     * Debug
     */
    public function debugAction(): ResponseInterface
    {
        return $this->htmlResponse($this->moduleTemplate->setContent($this->view->render())->renderContent());
    }

    /**
     * Icons
     */
    public function iconsAction(): ResponseInterface
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
        return $this->htmlResponse($this->moduleTemplate->setContent($this->view->render())->renderContent());
    }

    /**
     * Infobox
     */
    public function infoboxAction(): ResponseInterface
    {
        return $this->htmlResponse($this->moduleTemplate->setContent($this->view->render())->renderContent());
    }

    /**
     * FlashMessages
     */
    public function flashMessagesAction(): ResponseInterface
    {
        $loremIpsum = GeneralUtility::makeInstance(KauderwelschService::class)->getLoremIpsum();
        $this->addFlashMessage($loremIpsum, 'Info - Title for Info message', FlashMessage::INFO, true);
        $this->addFlashMessage($loremIpsum, 'Notice - Title for Notice message', FlashMessage::NOTICE, true);
        $this->addFlashMessage($loremIpsum, 'Error - Title for Error message', FlashMessage::ERROR, true);
        $this->addFlashMessage($loremIpsum, 'Ok - Title for OK message', FlashMessage::OK, true);
        $this->addFlashMessage($loremIpsum, 'Warning - Title for Warning message', FlashMessage::WARNING, true);
        return $this->htmlResponse($this->moduleTemplate->setContent($this->view->render())->renderContent());
    }

    /**
     * Avatar
     */
    public function avatarAction(): ResponseInterface
    {
        $this->view->assign(
            'backendUser',
            $GLOBALS['BE_USER']->user
        );
        return $this->htmlResponse($this->moduleTemplate->setContent($this->view->render())->renderContent());
    }

    /**
     * Tabs
     */
    public function tabAction(): ResponseInterface
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
        return $this->htmlResponse($this->moduleTemplate->setContent($this->view->render())->renderContent());
    }

    public function modalAction(): ResponseInterface
    {
        return $this->htmlResponse($this->moduleTemplate->setContent($this->view->render())->renderContent());
    }

    public function accordionAction(): ResponseInterface
    {
        return $this->htmlResponse($this->moduleTemplate->setContent($this->view->render())->renderContent());
    }

    /**
     * @throws NoSuchArgumentException
     */
    public function paginationAction(int $page = 1): ResponseInterface
    {
        // Prepare example data for pagination list
        $itemsToBePaginated = [
            'Warty Warthog',
            'Hoary Hedgehog',
            'Breezy Badger',
            'Dapper Drake',
            'Edgy Eft',
            'Feisty Fawn',
            'Gutsy Gibbon',
            'Hardy Heron',
            'Intrepid Ibex',
            'Jaunty Jackalope',
            'Karmic Koala',
            'Lucid Lynx',
            'Maverick Meerkat',
            'Natty Narwhal',
            'Oneiric Ocelot',
            'Precise Pangolin',
            'Quantal Quetzal',
            'Raring Ringtail',
            'Saucy Salamander',
            'Trusty Tahr',
            'Utopic Unicorn',
            'Vivid Vervet',
            'Wily Werewolf',
            'Xenial Xerus',
            'Yakkety Yak',
            'Zesty Zapus',
            'Artful Aardvark',
            'Bionic Beaver',
            'Cosmic Cuttlefish',
            'Disco Dingo',
            'Eoan Ermine',
            'Focal Fossa',
            'Groovy Gorilla',
        ];
        $itemsPerPage = 10;

        if ($this->request->hasArgument('page')) {
            $page = (int)$this->request->getArgument('page');
        }

        // Prepare example data for dropdown
        $userGroupArray = [
            0 => '[All users]',
            -1 => 'Self',
            'gr-7' => 'Group styleguide demo group 1',
            'gr-8' => 'Group styleguide demo group 2',
            'us-9' => 'User _cli_',
            'us-1' => 'User admin',
            'us-10' => 'User styleguide demo user 1',
            'us-11' => 'User styleguide demo user 2',
        ];

        $paginator = new ArrayPaginator($itemsToBePaginated, $page, $itemsPerPage);
        $this->view->assignMultiple([
            'paginator' => $paginator,
            'pagination' => new SimplePagination($paginator),
            'userGroups' => $userGroupArray,
        ]);

        return $this->htmlResponse($this->moduleTemplate->setContent($this->view->render())->renderContent());
    }

    public function frontendCreateAction(): ResponseInterface
    {
        $recordFinder = GeneralUtility::makeInstance(RecordFinder::class);
        if (count($recordFinder->findUidsOfFrontendPages())) {
            $json = [
              'title' => LocalizationUtility::translate($this->languageFilePrefix . 'frontendCreateActionFailedTitle', 'styleguide'),
              'body' => LocalizationUtility::translate($this->languageFilePrefix . 'frontendCreateActionFailedBody', 'styleguide'),
              'status' => AbstractMessage::ERROR
            ];
        } else {
            $frontend = GeneralUtility::makeInstance(GeneratorFrontend::class);
            $frontend->create();

            $json = [
                'title' => LocalizationUtility::translate($this->languageFilePrefix . 'frontendCreateActionOkTitle', 'styleguide'),
                'body' => LocalizationUtility::translate($this->languageFilePrefix . 'frontendCreateActionOkBody', 'styleguide'),
                'status' => AbstractMessage::OK
            ];
        }

        return new JsonResponse($json);
    }

    public function frontendDeleteAction(): ResponseInterface
    {
        $frontend = GeneralUtility::makeInstance(GeneratorFrontend::class);
        $frontend->delete();

        $json = [
            'title' => LocalizationUtility::translate($this->languageFilePrefix . 'frontendDeleteActionOkTitle', 'styleguide'),
            'body' => LocalizationUtility::translate($this->languageFilePrefix . 'frontendDeleteActionOkBody', 'styleguide'),
            'status' => AbstractMessage::OK
        ];

        return new JsonResponse($json);
    }
}
