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

namespace TYPO3\CMS\Dashboard\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Dashboard\DashboardInitializationService;

/**
 * @internal
 */
#[AsController]
class DashboardController
{
    public function __construct(
        protected readonly PageRenderer $pageRenderer,
        protected readonly DashboardInitializationService $dashboardInitializationService,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
    ) {}

    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->dashboardInitializationService->initializeDashboards($request, $this->getBackendUser());

        $view = $this->moduleTemplateFactory->create($request);
        $this->preparePageRenderer();
        $this->addFrontendResources();
        $view->setTitle($this->getLanguageService()->sL('LLL:EXT:dashboard/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'));
        $view->getDocHeaderComponent()->disable();

        return $view->renderResponse('Dashboard/Main');
    }

    /**
     * Adds CSS and JS files that are necessary for widgets to the page renderer
     */
    protected function addFrontendResources(): void
    {
        $javaScriptRenderer = $this->pageRenderer->getJavaScriptRenderer();
        foreach ($this->dashboardInitializationService->getJavaScriptModuleInstructions() as $instruction) {
            $javaScriptRenderer->addJavaScriptModuleInstruction($instruction);
        }
        foreach ($this->dashboardInitializationService->getCssFiles() as $cssFile) {
            $this->pageRenderer->addCssFile($cssFile);
        }
        foreach ($this->dashboardInitializationService->getJsFiles() as $jsFile) {
            $this->pageRenderer->addJsFile($jsFile);
        }
    }

    /**
     * Add the CSS and JS of the dashboard module to the page renderer
     */
    protected function preparePageRenderer(): void
    {
        $this->pageRenderer->loadJavaScriptModule('@typo3/dashboard/dashboard.js');
        $this->pageRenderer->addCssFile('EXT:dashboard/Resources/Public/Css/dashboard.css');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:dashboard/Resources/Private/Language/locallang.xlf');
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
