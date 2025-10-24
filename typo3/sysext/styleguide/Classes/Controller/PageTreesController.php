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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Styleguide\TcaDataGenerator\Generator;
use TYPO3\CMS\Styleguide\TcaDataGenerator\GeneratorFrontend;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordFinder;

/**
 * Styleguide: Page trees submodule
 *
 * @internal
 */
#[AsController]
final class PageTreesController
{
    /**
     * @var non-empty-array<int, string>
     */
    private array $allowedActions = [
        'managePageTrees',
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
        private readonly GeneratorFrontend $frontend,
        private readonly Generator $generator,
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly RecordFinder $recordFinder,
        private readonly IconFactory $iconFactory,
        private readonly UriBuilder $uriBuilder,
    ) {}

    /**
     * Main entry point dispatcher
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $currentAction = $request->getQueryParams()['action'] ?? 'managePageTrees';
        if (!in_array($currentAction, $this->allowedActions, true)
            && !in_array($currentAction, $this->allowedAjaxActions, true)
        ) {
            throw new \RuntimeException('Action not allowed', 1720610774);
        }
        $actionMethodName = $currentAction . 'Action';
        try {
            return $this->$actionMethodName($request);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => false,
                'title' => $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:pageTreeProcessingError'),
                'body' => $e->getMessage(),
            ]);
        }
    }

    private function managePageTreesAction(ServerRequestInterface $request): ResponseInterface
    {
        $languageService = $this->getLanguageService();
        $demoExists = count($this->recordFinder->findUidsOfStyleguideEntryPages());
        $demoFrontendExists = count($this->recordFinder->findUidsOfFrontendPages());

        $view = $this->moduleTemplateFactory->create($request);
        $view->assignMultiple([
            'currentAction' => 'managePageTrees',
            'demoExists' => $demoExists,
            'demoFrontendExists' => $demoFrontendExists,
            'routeIdentifier' => 'styleguide_pagetrees',
        ]);
        $view->setTitle(
            $languageService->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:styleguide'),
            $languageService->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:action.managePageTrees'),
        );
        $view->makeDocHeaderModuleMenu();
        // Add back button to return to main module
        $this->addDocHeaderBackButton($view);
        $this->addDocHeaderShortcutButton($view);

        return $view->renderResponse('Backend/ManagePageTrees');
    }

    private function tcaCreateAction(): ResponseInterface
    {
        if (count($this->recordFinder->findUidsOfStyleguideEntryPages())) {
            // Tell something was done here
            $json = [
                'title' => $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:tcaCreateActionFailedTitle'),
                'body' => $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:tcaCreateActionFailedBody'),
                'status' => ContextualFeedbackSeverity::ERROR,
            ];
        } else {
            $this->generator->create();
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
        $this->generator->delete();
        $json = [
            'title' => $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:tcaDeleteActionOkTitle'),
            'body' => $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:tcaDeleteActionOkBody'),
            'status' => ContextualFeedbackSeverity::OK,
        ];
        return new JsonResponse($json);
    }

    private function frontendCreateWithSetsAction(): ResponseInterface
    {
        if (count($this->recordFinder->findUidsOfFrontendPages())) {
            $json = [
                'title' => $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:frontendCreateActionFailedTitle'),
                'body' => $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:frontendCreateActionFailedBody'),
                'status' => ContextualFeedbackSeverity::ERROR,
            ];
        } else {
            $this->frontend->create('', 1, true);
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
        if (count($this->recordFinder->findUidsOfFrontendPages())) {
            $json = [
                'title' => $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:frontendCreateActionFailedTitle'),
                'body' => $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:frontendCreateActionFailedBody'),
                'status' => ContextualFeedbackSeverity::ERROR,
            ];
        } else {
            $this->frontend->create();
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
        $this->frontend->delete();
        $json = [
            'title' => $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:frontendDeleteActionOkTitle'),
            'body' => $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:frontendDeleteActionOkBody'),
            'status' => ContextualFeedbackSeverity::OK,
        ];
        return new JsonResponse($json);
    }

    private function addDocHeaderBackButton(ModuleTemplate $moduleTemplate): void
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $backButton = $buttonBar->makeLinkButton()
            ->setHref((string)$this->uriBuilder->buildUriFromRoute('styleguide'))
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
            ->setShowLabelText(true)
            ->setIcon($this->iconFactory->getIcon('actions-view-go-back', IconSize::SMALL));
        $buttonBar->addButton($backButton);
    }

    private function addDocHeaderShortcutButton(ModuleTemplate $moduleTemplate): void
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setDisplayName(sprintf(
                '%s - %s',
                $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:styleguide'),
                $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:action.managePageTrees')
            ))
            ->setRouteIdentifier('styleguide_pagetrees')
            ->setArguments(['action' => 'managePageTrees']);
        $buttonBar->addButton($shortcutButton);
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
