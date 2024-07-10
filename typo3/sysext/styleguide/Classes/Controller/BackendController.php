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

namespace TYPO3\CMS\Styleguide\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Styleguide\Service\KauderwelschService;
use TYPO3\CMS\Styleguide\TcaDataGenerator\Generator;
use TYPO3\CMS\Styleguide\TcaDataGenerator\GeneratorFrontend;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordFinder;

/**
 * Styleguide main backend module in the help toolbar
 *
 * @internal
 */
#[AsController]
final class BackendController
{
    /**
     * @var non-empty-array<int, string>
     */
    private array $allowedActions = [
        'index',
        'typography',
        'colorTokens',
        'surfaces',
        'shadows',
        'trees',
        'tab',
        'tables',
        'cards',
        'avatar',
        'buttons',
        'badges',
        'infobox',
        'flashMessages',
        'notifications',
        'icons',
        'modal',
        'accordion',
        'pagination',
        'filter',
        'progressIndicators',
    ];

    /**
     * @var non-empty-array<int, string>
     */
    private array $allowedAjaxActions = [
        'tcaCreate',
        'tcaDelete',
        'frontendCreateWithSets',
        'frontendCreateWithSysTemplate',
        'frontendDelete',
    ];

    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly PageRenderer $pageRenderer,
        private readonly FlashMessageService $flashMessageService,
    ) {}

    /**
     * Main entry point dispatcher
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $currentAction = $request->getQueryParams()['action'] ?? 'index';
        if (!in_array($currentAction, $this->allowedActions, true)
            && !in_array($currentAction, $this->allowedAjaxActions, true)
        ) {
            throw new \RuntimeException('Action not allowed', 1672751508);
        }
        $actionMethodName = $currentAction . 'Action';
        return $this->$actionMethodName($request);
    }

    private function indexAction(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->createModuleTemplate($request, 'index');
        $this->pageRenderer->loadJavaScriptModule('@typo3/styleguide/processing-indicator.js');
        $finder = GeneralUtility::makeInstance(RecordFinder::class);
        $demoExists = count($finder->findUidsOfStyleguideEntryPages());
        $demoFrontendExists = count($finder->findUidsOfFrontendPages());
        $moduleTemplate->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'index',
            'demoExists' => $demoExists,
            'demoFrontendExists' => $demoFrontendExists,
        ]);
        return $moduleTemplate->renderResponse('Backend/Index');
    }

    private function tcaCreateAction(): ResponseInterface
    {
        $finder = GeneralUtility::makeInstance(RecordFinder::class);
        if (count($finder->findUidsOfStyleguideEntryPages())) {
            // Tell something was done here
            $json = [
                'title' => $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:tcaCreateActionFailedTitle'),
                'body' => $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:tcaCreateActionFailedBody'),
                'status' => ContextualFeedbackSeverity::ERROR,
            ];
        } else {
            $generator = GeneralUtility::makeInstance(Generator::class);
            $generator->create();
            // Tell something was done here
            $json = [
                'title' => $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:tcaCreateActionOkTitle'),
                'body' => $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:tcaCreateActionOkBody'),
                'status' => ContextualFeedbackSeverity::OK,
            ];
        }
        // And redirect to display action
        return new JsonResponse($json);
    }

    private function tcaDeleteAction(): ResponseInterface
    {
        $generator = GeneralUtility::makeInstance(Generator::class);
        $generator->delete();
        $json = [
            'title' => $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:tcaDeleteActionOkTitle'),
            'body' => $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:tcaDeleteActionOkBody'),
            'status' => ContextualFeedbackSeverity::OK,
        ];
        return new JsonResponse($json);
    }

    private function buttonsAction(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->createModuleTemplate($request, 'buttons');
        $moduleTemplate->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'buttons',
        ]);
        return $moduleTemplate->renderResponse('Backend/Buttons');
    }

    private function badgesAction(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->createModuleTemplate($request, 'badges');
        $moduleTemplate->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'badges',
            'variants' => ['primary', 'secondary', 'info', 'success', 'warning', 'danger', 'notice', 'default'],
        ]);
        return $moduleTemplate->renderResponse('Backend/Badges');
    }

    private function typographyAction(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->createModuleTemplate($request, 'typography');
        $moduleTemplate->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'typography',
        ]);
        return $moduleTemplate->renderResponse('Backend/Typography');
    }

    private function colorTokensAction(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->createModuleTemplate($request, 'colorTokens');
        $moduleTemplate->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'colorTokens',
            'neutralColors' => ['neutral'],
            'neutralSteps' => [0, 3, 4, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80, 85, 90, 95, 96, 97, 100],
            'accentColors' => ['blue', 'purple', 'teal', 'green', 'magenta', 'yellow', 'orange', 'red'],
            'accentSteps' => [10, 20, 30, 40, 50, 60, 70, 80, 90],
        ]);
        return $moduleTemplate->renderResponse('Backend/ColorTokens');
    }

    private function surfacesAction(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->createModuleTemplate($request, 'surfaces');
        $moduleTemplate->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'surfaces',
            'themeColors' => ['primary', 'secondary', 'info', 'success', 'warning', 'danger', 'notice', 'default'],
        ]);
        return $moduleTemplate->renderResponse('Backend/Surfaces');
    }

    private function shadowsAction(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->createModuleTemplate($request, 'shadows');
        $moduleTemplate->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'shadows',
        ]);
        return $moduleTemplate->renderResponse('Backend/Shadows');
    }

    private function treesAction(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->createModuleTemplate($request, 'trees');
        $moduleTemplate->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'trees',
        ]);
        return $moduleTemplate->renderResponse('Backend/Trees');
    }

    private function tablesAction(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->createModuleTemplate($request, 'tables');
        $moduleTemplate->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'tables',
        ]);
        return $moduleTemplate->renderResponse('Backend/Tables');
    }

    private function cardsAction(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->createModuleTemplate($request, 'cards');
        $moduleTemplate->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'cards',
        ]);
        return $moduleTemplate->renderResponse('Backend/Cards');
    }

    private function frontendCreateWithSetsAction(): ResponseInterface
    {
        $recordFinder = GeneralUtility::makeInstance(RecordFinder::class);
        if (count($recordFinder->findUidsOfFrontendPages())) {
            $json = [
                'title' => $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:frontendCreateActionFailedTitle'),
                'body' => $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:frontendCreateActionFailedBody'),
                'status' => ContextualFeedbackSeverity::ERROR,
            ];
        } else {
            $frontend = GeneralUtility::makeInstance(GeneratorFrontend::class);
            $frontend->create('', 1, true);
            $json = [
                'title' => $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:frontendCreateActionOkTitle'),
                'body' => $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:frontendCreateActionOkBody'),
                'status' => ContextualFeedbackSeverity::OK,
            ];
        }
        return new JsonResponse($json);
    }

    private function frontendCreateWithSysTemplateAction(): ResponseInterface
    {
        $recordFinder = GeneralUtility::makeInstance(RecordFinder::class);
        if (count($recordFinder->findUidsOfFrontendPages())) {
            $json = [
                'title' => $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:frontendCreateActionFailedTitle'),
                'body' => $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:frontendCreateActionFailedBody'),
                'status' => ContextualFeedbackSeverity::ERROR,
            ];
        } else {
            $frontend = GeneralUtility::makeInstance(GeneratorFrontend::class);
            $frontend->create();
            $json = [
                'title' => $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:frontendCreateActionOkTitle'),
                'body' => $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:frontendCreateActionOkBody'),
                'status' => ContextualFeedbackSeverity::OK,
            ];
        }
        return new JsonResponse($json);
    }

    private function frontendDeleteAction(): ResponseInterface
    {
        $frontend = GeneralUtility::makeInstance(GeneratorFrontend::class);
        $frontend->delete();
        $json = [
            'title' => $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:frontendDeleteActionOkTitle'),
            'body' => $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:frontendDeleteActionOkBody'),
            'status' => ContextualFeedbackSeverity::OK,
        ];
        return new JsonResponse($json);
    }

    private function iconsAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->pageRenderer->loadJavaScriptModule('@typo3/styleguide/find-icons.js');
        $moduleTemplate = $this->createModuleTemplate($request, 'icons');
        $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
        $allIcons = $iconRegistry->getAllRegisteredIconIdentifiers();
        $overlays = array_filter(
            $allIcons,
            function ($key) {
                return str_starts_with($key, 'overlay');
            }
        );
        $moduleTemplate->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'icons',
            'allIcons' => $allIcons,
            'deprecatedIcons' => $iconRegistry->getDeprecatedIcons(),
            'overlays' => $overlays,
        ]);
        return $moduleTemplate->renderResponse('Backend/Icons');
    }

    private function infoboxAction(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->createModuleTemplate($request, 'infobox');
        $moduleTemplate->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'infobox',
        ]);
        return $moduleTemplate->renderResponse('Backend/Infobox');
    }

    private function flashMessagesAction(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->createModuleTemplate($request, 'flashMessages');
        $moduleTemplate->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'flashMessages',
        ]);
        $loremIpsum = GeneralUtility::makeInstance(KauderwelschService::class)->getLoremIpsum();
        // We're writing to an own queue here to position the messages within the body.
        // Normal modules wouldn't usually do this and would let ModuleTemplate layout take care of rendering
        // at some appropriate position.
        $flashMessageQueue = $this->flashMessageService->getMessageQueueByIdentifier('styleguide.demo');
        $flashMessageQueue->enqueue(GeneralUtility::makeInstance(FlashMessage::class, $loremIpsum, 'Info - Title for Info message', ContextualFeedbackSeverity::INFO, true));
        $flashMessageQueue->enqueue(GeneralUtility::makeInstance(FlashMessage::class, $loremIpsum, 'Notice - Title for Notice message', ContextualFeedbackSeverity::NOTICE, true));
        $flashMessageQueue->enqueue(GeneralUtility::makeInstance(FlashMessage::class, $loremIpsum, 'Error - Title for Error message', ContextualFeedbackSeverity::ERROR, true));
        $flashMessageQueue->enqueue(GeneralUtility::makeInstance(FlashMessage::class, $loremIpsum, 'Ok - Title for OK message', ContextualFeedbackSeverity::OK, true));
        $flashMessageQueue->enqueue(GeneralUtility::makeInstance(FlashMessage::class, $loremIpsum, 'Warning - Title for Warning message', ContextualFeedbackSeverity::WARNING, true));
        return $moduleTemplate->renderResponse('Backend/FlashMessages');
    }

    private function notificationsAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->pageRenderer->loadJavaScriptModule('@typo3/styleguide/render-notifications.js');
        $moduleTemplate = $this->createModuleTemplate($request, 'notifications');
        $moduleTemplate->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'notifications',
        ]);
        return $moduleTemplate->renderResponse('Backend/Notifications');
    }

    private function avatarAction(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->createModuleTemplate($request, 'avatar');
        $moduleTemplate->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'avatar',
            'backendUser' => $GLOBALS['BE_USER']->user,
        ]);
        return $moduleTemplate->renderResponse('Backend/Avatar');
    }

    private function tabAction(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->createModuleTemplate($request, 'tab');
        $moduleTemplate->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'tab',
        ]);
        return $moduleTemplate->renderResponse('Backend/Tab');
    }

    private function modalAction(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->createModuleTemplate($request, 'modal');
        $moduleTemplate->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'modal',
        ]);
        return $moduleTemplate->renderResponse('Backend/Modal');
    }

    private function accordionAction(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->createModuleTemplate($request, 'accordion');
        $moduleTemplate->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'accordion',
        ]);
        return $moduleTemplate->renderResponse('Backend/Accordion');
    }

    private function paginationAction(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->createModuleTemplate($request, 'pagination');
        $page = (int)($request->getQueryParams()['page'] ?? 1);
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
        $paginator = new ArrayPaginator($itemsToBePaginated, $page, $itemsPerPage);
        $moduleTemplate->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'pagination',
            'paginator' => $paginator,
            'pagination' => new SimplePagination($paginator),
        ]);
        return $moduleTemplate->renderResponse('Backend/Pagination');
    }

    private function filterAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->pageRenderer->loadJavaScriptModule('@typo3/styleguide/filter.js');
        $moduleTemplate = $this->createModuleTemplate($request, 'filter');
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
        $moduleTemplate->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'filter',
            'userGroups' => $userGroupArray,
            'dateTimeFormat' => 'h:m d-m-Y',
        ]);
        return $moduleTemplate->renderResponse('Backend/Filter');
    }

    private function progressIndicatorsAction(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->createModuleTemplate($request, 'progressIndicators');
        $moduleTemplate->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'progressIndicators',
        ]);
        return $moduleTemplate->renderResponse('Backend/ProgressIndicators');
    }

    private function addShortcutButton(ModuleTemplate $moduleTemplate, string $action): void
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setDisplayName(sprintf(
                '%s - %s',
                $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:styleguide'),
                $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:' . $action)
            ))
            ->setRouteIdentifier('help_styleguide')
            ->setArguments(['action' => $action]);
        $buttonBar->addButton($shortcutButton);
    }

    private function createModuleTemplate(ServerRequestInterface $request, string $action): ModuleTemplate
    {
        $moduleTemplate = $this->moduleTemplateFactory
            ->create($request)
            ->setModuleClass('module-styleguide');
        $this->addShortcutButton($moduleTemplate, $action);
        return $moduleTemplate;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
