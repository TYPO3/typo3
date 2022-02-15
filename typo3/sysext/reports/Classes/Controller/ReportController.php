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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Reports\ReportInterface;
use TYPO3\CMS\Reports\RequestAwareReportInterface;

/**
 * The "Reports" backend module.
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class ReportController
{
    protected UriBuilder $uriBuilder;
    protected ModuleTemplateFactory $moduleTemplateFactory;
    protected IconRegistry $iconRegistry;

    public function __construct(
        UriBuilder $uriBuilder,
        ModuleTemplateFactory $moduleTemplateFactory,
        IconRegistry $iconRegistry
    ) {
        $this->uriBuilder = $uriBuilder;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->iconRegistry = $iconRegistry;
    }

    /**
     * Main dispatcher.
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $validRegisteredReports = $this->getValidReportCombinations();
        $queryParams = $request->getQueryParams();
        $backendUserUc = $this->getBackendUser()->uc['reports']['selection'] ?? [];
        // This can be 'index' for "overview", or 'detail' to render one specific report specified by 'extension' and 'report'
        $mainView = $queryParams['action'] ?? $backendUserUc['action'] ?? 'detail';
        if (count($validRegisteredReports) === 0 || $mainView === 'index') {
            // Render overview directly if there are no reports at all to have some info box about that,
            // or if that view has been requested explicitly.
            $this->updateBackendUserUc('index');
            return $this->indexAction($request);
        }

        // For fallbacks if backendUser->uc() pointer is invalid or called first time.
        $firstReport = $validRegisteredReports[0];
        $extension = $request->getQueryParams()['extension'] ?? $backendUserUc['extension'] ?? $firstReport['extension'];
        $report = $request->getQueryParams()['report'] ?? $backendUserUc['report'] ?? $firstReport['report'];
        if (!in_array(['extension' => $extension, 'report' => $report], $validRegisteredReports)) {
            // If a selected report has been removed meanwhile (e.g. extension deleted), fall back to first one.
            $extension = $firstReport['extension'];
            $report = $firstReport['report'];
        }
        if (($backendUserUc['action'] ?? '') !== 'detail'
            || ($backendUserUc['extension'] ?? '') !== $extension
            || ($backendUserUc['report'] ?? '') !== $report
        ) {
            // Update uc if view changed to render same view on next call.
            $this->updateBackendUserUc('detail', $extension, $report);
        }
        return $this->detailAction($request, $extension, $report);
    }

    /**
     * Render index "overview".
     */
    protected function indexAction(ServerRequestInterface $request): ResponseInterface
    {
        $languageService = $this->getLanguageService();
        $registeredReports = $this->getRegisteredReportsArray();
        foreach ($registeredReports as $extension => $reportModules) {
            foreach ($reportModules as $module => $configuration) {
                $icon = $configuration['icon'] ?? 'EXT:reports/Resources/Public/Icons/Extension.png';
                $isRegisteredIcon = $registeredReports[$extension][$module]['isIconIdentifier'] = $this->iconRegistry->isRegistered($icon);
                if (!$isRegisteredIcon) {
                    // @todo: Deprecate icons from non extension resources
                    $registeredReports[$extension][$module]['icon'] = PathUtility::isExtensionPath($icon) ? PathUtility::getPublicResourceWebPath($icon) : PathUtility::getAbsoluteWebPath($icon);
                }
            }
        }
        $view = $this->moduleTemplateFactory->create($request, 'typo3/cms-reports');
        $view->assignMultiple([
            'reports' => $registeredReports,
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
    protected function detailAction(ServerRequestInterface $request, string $extension, string $report): ResponseInterface
    {
        $languageService = $this->getLanguageService();
        $registeredReports = $this->getRegisteredReportsArray();
        $reportClass = $registeredReports[$extension][$report]['report'];
        $reportInstance = GeneralUtility::makeInstance($reportClass);
        if ($reportInstance instanceof RequestAwareReportInterface) {
            $content = $reportInstance->getReport($request);
        } else {
            $content = $reportInstance->getReport();
        }

        $view = $this->moduleTemplateFactory->create($request, 'typo3/cms-reports');
        $view->assignMultiple([
            'content' => $content,
            'report' => $registeredReports[$extension][$report],
        ]);
        $view->setTitle(
            $languageService->sL('LLL:EXT:reports/Resources/Private/Language/locallang.xlf:mlang_tabs_tab'),
            $languageService->sL($registeredReports[$extension][$report]['title'] ?? '')
        );
        $validRegisteredReports = $this->getValidReportCombinations();
        if (count($validRegisteredReports) > 1) {
            // Add the main module drop-down only if there are more than one reports registered.
            // This also means the "overview" view is not selectable with default core.
            $this->addMainMenu($view, $extension, $report);
        }
        $this->addShortcutButton(
            $view,
            $languageService->sL($registeredReports[$extension][$report]['title'] ?? ''),
            ['action' => 'detail', 'extension' => $extension, 'report' => $report]
        );
        return $view->renderResponse('Report/Detail');
    }

    protected function addMainMenu(ModuleTemplate $view, string $extension = '', string $report = ''): void
    {
        $languageService = $this->getLanguageService();
        $menu = $view->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('WebFuncJumpMenu');
        $menuItem = $menu->makeMenuItem()
            ->setHref(
                $this->uriBuilder->buildUriFromRoute('system_reports', ['action' => 'index'])
            )
            ->setTitle($languageService->sL('LLL:EXT:reports/Resources/Private/Language/locallang.xlf:reports_overview'));
        $menu->addMenuItem($menuItem);
        foreach ($this->getRegisteredReportsArray() as $extKey => $reports) {
            foreach ($reports as $reportName => $reportConfiguration) {
                $menuItem = $menu->makeMenuItem()
                    ->setHref($this->uriBuilder->buildUriFromRoute(
                        'system_reports',
                        ['action' => 'detail', 'extension' => $extKey, 'report' => $reportName]
                    ))
                    ->setTitle($this->getLanguageService()->sL($reportConfiguration['title'] ?? 'default'));
                if ($extension === $extKey && $report === $reportName) {
                    $menuItem->setActive(true);
                }
                $menu->addMenuItem($menuItem);
            }
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
    protected function updateBackendUserUc(string $action, string $extension = '', string $report = ''): void
    {
        $backendUser = $this->getBackendUser();
        $backendUser->uc['reports']['selection'] = [
            'action' => $action,
            'extension' => $extension,
            'report' => $report,
        ];
        $backendUser->writeUC();
    }

    protected function getValidReportCombinations(): array
    {
        $validReports = [];
        foreach ($this->getRegisteredReportsArray() as $extension => $reports) {
            if (!is_array($reports)) {
                continue;
            }
            foreach ($reports as $reportName => $reportConfiguration) {
                $reportClass = $reportConfiguration['report'] ?? '';
                if (!empty($reportClass)
                    && class_exists($reportClass)
                    && is_subclass_of($reportClass, ReportInterface::class)
                ) {
                    $validReports[] = [
                        'extension' => $extension,
                        'report' => $reportName,
                    ];
                }
            }
        }
        return $validReports;
    }

    protected function getRegisteredReportsArray(): array
    {
        return $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports'] ?? [];
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
