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

namespace TYPO3\CMS\Reports\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Reports\Service\StatusService;

/**
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
final readonly class StatusReportController
{
    public function __construct(
        protected ModuleTemplateFactory $moduleTemplateFactory,
        protected StatusService $statusService,
        protected UriBuilder $uriBuilder,
        protected IconFactory $iconFactory,
    ) {}

    /**
     * Main action - displays the system status report
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $statusCollection = $this->statusService->getSystemStatus($request);
        $this->statusService->collectAndStoreSystemStatus($request);

        // Apply sorting to collection and the providers
        $statusCollection = $this->statusService->sortStatusProviders($statusCollection);
        foreach ($statusCollection as &$statuses) {
            $statuses = $this->statusService->sortStatuses($statuses);
        }
        unset($statuses);

        $languageService = $this->getLanguageService();
        $view = $this->moduleTemplateFactory->create($request);
        $view->setTitle(
            $languageService->sL('LLL:EXT:reports/Resources/Private/Language/locallang.xlf:mlang_tabs_tab'),
            $languageService->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_report_title')
        );
        $view->makeDocHeaderModuleMenu();

        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
        // Back button to parent module
        $backButton = $buttonBar
            ->makeLinkButton()
            ->setHref((string)$this->uriBuilder->buildUriFromRoute('system_reports'))
            ->setIcon($this->iconFactory->getIcon('actions-view-go-back', IconSize::SMALL))
            ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
            ->setShowLabelText(true);
        $buttonBar->addButton($backButton, ButtonBar::BUTTON_POSITION_LEFT, 1);

        // Shortcut button
        $shortcutButton = $buttonBar
            ->makeShortcutButton()
            ->setRouteIdentifier('system_reports_status')
            ->setDisplayName($languageService->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_report_title'));
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);

        return $view->assignMultiple([
            'statusCollection' => $statusCollection,
            'severityIconMapping' => [
                ContextualFeedbackSeverity::NOTICE->value => 'actions-info',
                ContextualFeedbackSeverity::INFO->value => 'actions-info',
                ContextualFeedbackSeverity::OK->value => 'actions-check',
                ContextualFeedbackSeverity::WARNING->value => 'actions-exclamation',
                ContextualFeedbackSeverity::ERROR->value => 'actions-exclamation',
            ],
        ])->renderResponse('StatusReport');
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
