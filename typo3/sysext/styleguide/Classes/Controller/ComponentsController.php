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
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Styleguide\Service\KauderwelschService;

/**
 * Styleguide: Components submodule
 *
 * @internal
 */
#[AsController]
final class ComponentsController
{
    /**
     * @var non-empty-array<int, string>
     */
    private array $allowedActions = [
        'componentsOverview',
        'accordion',
        'avatar',
        'badges',
        'buttons',
        'cards',
        'checkboxes',
        'flashMessages',
        'form',
        'infobox',
        'modal',
        'notifications',
        'pagination',
        'progressIndicators',
        'progressTrackers',
        'tab',
        'tables',
        'trees',
    ];

    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly FlashMessageService $flashMessageService,
    ) {}

    /**
     * Main entry point dispatcher
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $queryAction = $queryParams['action'] ?? '';

        // Actions from components navigation
        return match ($queryAction) {
            'accordion' => $this->renderAccordionView($request),
            'avatar' => $this->renderAvatarView($request),
            'badges' => $this->renderBadgesView($request),
            'buttons' => $this->renderButtonsView($request),
            'cards' => $this->renderCardsView($request),
            'checkboxes' => $this->renderCheckboxesView($request),
            'flashMessages' => $this->renderFlashMessagesView($request),
            'form' => $this->renderFormView($request),
            'infobox' => $this->renderInfoboxView($request),
            'modal' => $this->renderModalView($request),
            'notifications' => $this->renderNotificationsView($request),
            'pagination' => $this->renderPaginationView($request),
            'progressIndicators' => $this->renderProgressIndicatorsView($request),
            'progressTrackers' => $this->renderProgressTrackersView($request),
            'tab' => $this->renderTabView($request),
            'tables' => $this->renderTablesView($request),
            'trees' => $this->renderTreesView($request),
            // Fallback: Render overview
            default => $this->componentsOverviewAction($request),
        };
    }

    private function componentsOverviewAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->createModuleTemplate($request, 'componentsOverview');
        $view->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'componentsOverview',
            'routeIdentifier' => 'styleguide_components',
        ]);
        return $view->renderResponse('Backend/ComponentsOverview');
    }

    private function renderAccordionView(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->createModuleTemplate($request, 'accordion');
        $view->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'accordion',
            'routeIdentifier' => 'styleguide_components',
        ]);
        return $view->renderResponse('Backend/Components/Accordion');
    }

    private function renderAvatarView(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->createModuleTemplate($request, 'avatar');
        $view->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'avatar',
            'routeIdentifier' => 'styleguide_components',
            'backendUser' => $GLOBALS['BE_USER']->user,
        ]);
        return $view->renderResponse('Backend/Components/Avatar');
    }

    private function renderBadgesView(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->createModuleTemplate($request, 'badges');
        $view->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'badges',
            'routeIdentifier' => 'styleguide_components',
            'variants' => ['primary', 'secondary', 'info', 'success', 'warning', 'danger', 'notice', 'default'],
        ]);
        return $view->renderResponse('Backend/Components/Badges');
    }

    private function renderButtonsView(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->createModuleTemplate($request, 'buttons');
        $view->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'buttons',
            'routeIdentifier' => 'styleguide_components',
            'variants' => ['primary', 'secondary', 'info', 'success', 'warning', 'danger', 'notice', 'default', 'link'],
        ]);
        return $view->renderResponse('Backend/Components/Buttons');
    }

    private function renderCardsView(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->createModuleTemplate($request, 'cards');
        $view->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'cards',
            'routeIdentifier' => 'styleguide_components',
            'variants' => ['primary', 'secondary', 'info', 'success', 'warning', 'danger', 'notice', 'default'],
        ]);
        return $view->renderResponse('Backend/Components/Cards');
    }

    private function renderCheckboxesView(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->createModuleTemplate($request, 'checkboxes');
        $view->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'checkboxes',
            'routeIdentifier' => 'styleguide_components',
        ]);
        return $view->renderResponse('Backend/Components/Checkboxes');
    }

    private function renderFlashMessagesView(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->createModuleTemplate($request, 'flashMessages');
        $view->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'flashMessages',
            'routeIdentifier' => 'styleguide_components',
        ]);

        $loremIpsum = GeneralUtility::makeInstance(KauderwelschService::class)->getLoremIpsum();
        // We're writing to an own queue here to position the messages within the body.
        // Normal modules wouldn't usually do this and would let ModuleTemplate layout take care of rendering
        // at some appropriate position.
        $flashMessageQueue = $this->flashMessageService->getMessageQueueByIdentifier('styleguide.default');
        $flashMessageQueue->enqueue(GeneralUtility::makeInstance(FlashMessage::class, $loremIpsum, 'Title', ContextualFeedbackSeverity::NOTICE, true));

        $flashMessageQueue = $this->flashMessageService->getMessageQueueByIdentifier('styleguide.color');
        $flashMessageQueue->enqueue(GeneralUtility::makeInstance(FlashMessage::class, $loremIpsum, 'Notice', ContextualFeedbackSeverity::NOTICE, true));
        $flashMessageQueue->enqueue(GeneralUtility::makeInstance(FlashMessage::class, $loremIpsum, 'Info', ContextualFeedbackSeverity::INFO, true));
        $flashMessageQueue->enqueue(GeneralUtility::makeInstance(FlashMessage::class, $loremIpsum, 'Ok', ContextualFeedbackSeverity::OK, true));
        $flashMessageQueue->enqueue(GeneralUtility::makeInstance(FlashMessage::class, $loremIpsum, 'Warning', ContextualFeedbackSeverity::WARNING, true));
        $flashMessageQueue->enqueue(GeneralUtility::makeInstance(FlashMessage::class, $loremIpsum, 'Error', ContextualFeedbackSeverity::ERROR, true));

        $flashMessageQueue = $this->flashMessageService->getMessageQueueByIdentifier('styleguide.colorscheme');
        $flashMessageQueue->enqueue(GeneralUtility::makeInstance(FlashMessage::class, $loremIpsum, 'Notice', ContextualFeedbackSeverity::NOTICE, true));
        $flashMessageQueue->enqueue(GeneralUtility::makeInstance(FlashMessage::class, $loremIpsum, 'Info', ContextualFeedbackSeverity::INFO, true));
        $flashMessageQueue->enqueue(GeneralUtility::makeInstance(FlashMessage::class, $loremIpsum, 'Ok', ContextualFeedbackSeverity::OK, true));
        $flashMessageQueue->enqueue(GeneralUtility::makeInstance(FlashMessage::class, $loremIpsum, 'Warning', ContextualFeedbackSeverity::WARNING, true));
        $flashMessageQueue->enqueue(GeneralUtility::makeInstance(FlashMessage::class, $loremIpsum, 'Error', ContextualFeedbackSeverity::ERROR, true));

        return $view->renderResponse('Backend/Components/FlashMessages');
    }

    private function renderFormView(ServerRequestInterface $request): ResponseInterface
    {
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
        $view = $this->createModuleTemplate($request, 'form');
        $view->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'form',
            'routeIdentifier' => 'styleguide_components',
            'userGroups' => $userGroupArray,
            'dateTimeFormat' => 'c',
        ]);
        return $view->renderResponse('Backend/Components/Form');
    }

    private function renderInfoboxView(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->createModuleTemplate($request, 'infobox');
        $view->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'infobox',
            'routeIdentifier' => 'styleguide_components',
        ]);
        return $view->renderResponse('Backend/Components/Infobox');
    }

    private function renderModalView(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->createModuleTemplate($request, 'modal');
        $view->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'modal',
            'routeIdentifier' => 'styleguide_components',
            'variants' => ['notice', 'info', 'ok', 'warning', 'error'],
        ]);
        return $view->renderResponse('Backend/Components/Modal');
    }

    private function renderNotificationsView(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->createModuleTemplate($request, 'notifications');
        $view->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'notifications',
            'routeIdentifier' => 'styleguide_components',
            'variants' => ['notice', 'info', 'success', 'warning', 'error'],
        ]);
        return $view->renderResponse('Backend/Components/Notifications');
    }

    private function renderPaginationView(ServerRequestInterface $request): ResponseInterface
    {
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
        $view = $this->createModuleTemplate($request, 'pagination');
        $view->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'pagination',
            'routeIdentifier' => 'styleguide_components',
            'paginator' => $paginator,
            'pagination' => new SimplePagination($paginator),
        ]);
        return $view->renderResponse('Backend/Components/Pagination');
    }

    private function renderProgressIndicatorsView(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->createModuleTemplate($request, 'progressIndicators');
        $view->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'progressIndicators',
            'routeIdentifier' => 'styleguide_components',
        ]);
        return $view->renderResponse('Backend/Components/ProgressIndicators');
    }

    private function renderProgressTrackersView(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->createModuleTemplate($request, 'progressTrackers');
        $view->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'renderProgressTrackersView',
            'routeIdentifier' => 'styleguide_components',
        ]);
        return $view->renderResponse('Backend/Components/ProgressTrackers');
    }

    private function renderTabView(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->createModuleTemplate($request, 'tab');
        $view->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'tab',
            'routeIdentifier' => 'styleguide_components',
        ]);
        return $view->renderResponse('Backend/Components/Tab');
    }

    private function renderTablesView(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->createModuleTemplate($request, 'tables');
        $view->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'tables',
            'routeIdentifier' => 'styleguide_components',
        ]);
        return $view->renderResponse('Backend/Components/Tables');
    }

    private function renderTreesView(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->createModuleTemplate($request, 'trees');
        $view->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'trees',
            'routeIdentifier' => 'styleguide_components',
        ]);
        return $view->renderResponse('Backend/Components/Trees');
    }

    private function createModuleTemplate(ServerRequestInterface $request, string $action): ModuleTemplate
    {
        $languageService = $this->getLanguageService();
        $view = $this->moduleTemplateFactory->create($request);
        $view->setTitle(
            $languageService->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:styleguide'),
            $languageService->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:action.' . $action),
        );
        $view->setModuleClass('module-styleguide');
        $view->makeDocHeaderModuleMenu();
        $this->addDocHeaderShortcutButton($view, $action);
        return $view;
    }

    private function addDocHeaderShortcutButton(ModuleTemplate $moduleTemplate, string $action = ''): void
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setDisplayName(sprintf(
                '%s - %s',
                $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:styleguide'),
                $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:action.' . $action)
            ))
            ->setRouteIdentifier('styleguide_components')
            ->setArguments(['action' => $action]);
        $buttonBar->addButton($shortcutButton);
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
