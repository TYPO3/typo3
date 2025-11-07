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
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
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
            $languageService->translate('title', 'reports.modules.overview'),
            $languageService->translate('title', 'reports.modules.status')
        );
        $view->makeDocHeaderModuleMenu();
        $view->getDocHeaderComponent()->setShortcutContext(
            routeIdentifier: 'system_reports_status',
            displayName: $languageService->translate('title', 'reports.modules.status')
        );

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
