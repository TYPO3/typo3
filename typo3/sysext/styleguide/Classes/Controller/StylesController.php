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
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Styleguide: Styles submodule
 *
 * @internal
 */
#[AsController]
final class StylesController
{
    /**
     * @var non-empty-array<int, string>
     */
    private array $allowedActions = [
        'stylesOverview',
        'colorTokens',
        'icons',
        'shadows',
        'surfaces',
        'typography',
    ];

    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
    ) {}

    /**
     * Main entry point dispatcher
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $queryAction = $queryParams['action'] ?? '';

        // Actions from styles navigation
        return match ($queryAction) {
            'colorTokens' => $this->renderColorTokensView($request),
            'icons' => $this->renderIconsView($request),
            'shadows' => $this->renderShadowsView($request),
            'surfaces' => $this->renderSurfacesView($request),
            'typography' => $this->renderTypographyView($request),
            // Fallback: Render overview
            default => $this->stylesOverviewAction($request),
        };
    }

    private function stylesOverviewAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->createModuleTemplate($request, 'stylesOverview');
        $view->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'stylesOverview',
            'routeIdentifier' => 'styleguide_styles',
        ]);
        return $view->renderResponse('Backend/StylesOverview');
    }

    private function renderColorTokensView(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->createModuleTemplate($request, 'colorTokens');
        $view->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'colorTokens',
            'routeIdentifier' => 'styleguide_styles',
            'neutralColors' => ['neutral'],
            'neutralSteps' => [0, 3, 4, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80, 85, 90, 95, 96, 97, 100],
            'accentColors' => ['blue', 'purple', 'teal', 'green', 'magenta', 'yellow', 'orange', 'red'],
            'accentSteps' => [10, 20, 30, 40, 50, 60, 70, 80, 90],
        ]);
        return $view->renderResponse('Backend/Styles/ColorTokens');
    }

    private function renderIconsView(ServerRequestInterface $request): ResponseInterface
    {
        $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
        $allIcons = $iconRegistry->getAllRegisteredIconIdentifiers();
        $overlays = array_filter(
            $allIcons,
            function ($key) {
                return str_starts_with($key, 'overlay');
            }
        );
        $view = $this->createModuleTemplate($request, 'icons');
        $view->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'icons',
            'routeIdentifier' => 'styleguide_styles',
            'allIcons' => $allIcons,
            'deprecatedIcons' => $iconRegistry->getDeprecatedIcons(),
            'overlays' => $overlays,
        ]);
        return $view->renderResponse('Backend/Styles/Icons');
    }

    private function renderShadowsView(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->createModuleTemplate($request, 'shadows');
        $view->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'shadows',
            'routeIdentifier' => 'styleguide_styles',
        ]);
        return $view->renderResponse('Backend/Styles/Shadows');
    }

    private function renderSurfacesView(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->createModuleTemplate($request, 'surfaces');
        $view->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'surfaces',
            'routeIdentifier' => 'styleguide_styles',
            'themeColors' => ['primary', 'secondary', 'info', 'success', 'warning', 'danger', 'notice', 'default'],
        ]);
        return $view->renderResponse('Backend/Styles/Surfaces');
    }

    private function renderTypographyView(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->createModuleTemplate($request, 'typography');
        $view->assignMultiple([
            'actions' => $this->allowedActions,
            'currentAction' => 'typography',
            'routeIdentifier' => 'styleguide_styles',
        ]);
        return $view->renderResponse('Backend/Styles/Typography');
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
            ->setRouteIdentifier('styleguide_styles')
            ->setArguments(['action' => $action]);
        $buttonBar->addButton($shortcutButton);
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
