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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Reports\Registry\ReportRegistry;
use TYPO3\CMS\Reports\RequestAwareReportInterface;

/**
 * The "Reports" backend module.
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class ReportController
{
    public function __construct(
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly IconRegistry $iconRegistry,
        protected readonly ReportRegistry $reportRegistry
    ) {}

    /**
     * Main dispatcher.
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $allReports = $this->reportRegistry->getReports();
        $queryParams = $request->getQueryParams();
        $backendUserUc = $this->getBackendUser()->uc['reports']['selection'] ?? [];
        // This can be 'index' for "overview", or 'detail' to render one specific report specified by 'report'
        $mainView = $queryParams['action'] ?? $backendUserUc['action'] ?? 'detail';
        if ($mainView === 'index' || count($allReports) === 0) {
            // Render overview directly if there are no reports at all to have some info box about that,
            // or if that view has been requested explicitly.
            $this->updateBackendUserUc('index');
            return $this->indexAction($request);
        }

        // For fallbacks if backendUser->uc() pointer is invalid or called first time.
        $firstReportIdentifier = array_keys($allReports)[0];
        $reportIdentifier = $request->getQueryParams()['report'] ?? $backendUserUc['report'] ?? $firstReportIdentifier;
        if (!$this->reportRegistry->hasReport($reportIdentifier)) {
            // If a selected report has been removed meanwhile (e.g. extension deleted), fall back to first one.
            $reportIdentifier = $firstReportIdentifier;
        }
        if (($backendUserUc['action'] ?? '') !== 'detail'
            || ($backendUserUc['report'] ?? '') !== $reportIdentifier
        ) {
            // Update uc if view changed to render same view on next call.
            $this->updateBackendUserUc('detail', $reportIdentifier);
        }
        return $this->detailAction($request, $reportIdentifier);
    }

    /**
     * Render index "overview".
     */
    protected function indexAction(ServerRequestInterface $request): ResponseInterface
    {
        $languageService = $this->getLanguageService();

        $view = $this->moduleTemplateFactory->create($request);
        $view->assignMultiple([
            'reports' => $this->reportRegistry->getReports(),
        ]);
        $view->setTitle(
            $languageService->sL('LLL:EXT:reports/Resources/Private/Language/locallang.xlf:mlang_tabs_tab'),
            $languageService->sL('LLL:EXT:reports/Resources/Private/Language/locallang.xlf:reports_overview')
        );
        $this->addMainMenu($view);
        $this->addShortcutButton(
            $view,
            $languageService->sL('LLL:EXT:reports/Resources/Private/Language/locallang.xlf:reports_overview'),
            ['action' => 'index']
        );
        return $view->renderResponse('Report/Index');
    }

    /**
     * Render a single report.
     */
    protected function detailAction(ServerRequestInterface $request, string $report): ResponseInterface
    {
        $languageService = $this->getLanguageService();
        $reportInstance = $this->reportRegistry->getReport($report);
        $content = $reportInstance instanceof RequestAwareReportInterface ? $reportInstance->getReport($request) : $reportInstance->getReport();

        $view = $this->moduleTemplateFactory->create($request);
        $view->assignMultiple([
            'content' => $content,
            'report' => $reportInstance,
        ]);
        $view->setTitle(
            $languageService->sL('LLL:EXT:reports/Resources/Private/Language/locallang.xlf:mlang_tabs_tab'),
            $languageService->sL($reportInstance->getTitle())
        );
        $allReports = $this->reportRegistry->getReports();
        if (count($allReports) > 1) {
            // Add the main module drop-down only if there are more than one reports registered.
            // This also means the "overview" view is not selectable with default core.
            $this->addMainMenu($view, $report);
        }
        $this->addShortcutButton(
            $view,
            $languageService->sL($reportInstance->getTitle()),
            ['action' => 'detail', 'report' => $reportInstance->getIdentifier()]
        );
        return $view->renderResponse('Report/Detail');
    }

    protected function addMainMenu(ModuleTemplate $view, string $activeReportIdentifier = ''): void
    {
        $languageService = $this->getLanguageService();
        $menu = $view->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('WebFuncJumpMenu');
        $menuItem = $menu->makeMenuItem()
            ->setHref(
                (string)$this->uriBuilder->buildUriFromRoute('system_reports', ['action' => 'index'])
            )
            ->setTitle($languageService->sL('LLL:EXT:reports/Resources/Private/Language/locallang.xlf:reports_overview'));
        $menu->addMenuItem($menuItem);
        foreach ($this->reportRegistry->getReports() as $report) {
            $menuItem = $menu->makeMenuItem()
                ->setHref((string)$this->uriBuilder->buildUriFromRoute(
                    'system_reports',
                    ['action' => 'detail', 'report' => $report->getIdentifier()]
                ))
                ->setTitle($this->getLanguageService()->sL($report->getTitle()));
            if ($activeReportIdentifier === $report->getIdentifier()) {
                $menuItem->setActive(true);
            }
            $menu->addMenuItem($menuItem);
        }
        $view->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    protected function addShortcutButton(ModuleTemplate $view, string $title, array $arguments): void
    {
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('system_reports')
            ->setDisplayName($title)
            ->setArguments($arguments);
        $buttonBar->addButton($shortcutButton);
    }

    /**
     * Save selected action / extension / report combination to user uc to render this again on next module call.
     */
    protected function updateBackendUserUc(string $action, string $report = ''): void
    {
        $backendUser = $this->getBackendUser();
        $backendUser->uc['reports']['selection'] = [
            'action' => $action,
            'report' => $report,
        ];
        $backendUser->writeUC();
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
