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

namespace TYPO3\CMS\Lowlevel\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\View\ArrayBrowser;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\ProviderRegistry;

/**
 * View configuration arrays in the backend
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
class ConfigurationController
{
    protected ProviderRegistry $configurationProviderRegistry;
    protected PageRenderer $pageRenderer;
    protected UriBuilder $uriBuilder;
    protected ModuleTemplateFactory $moduleTemplateFactory;

    public function __construct(
        ProviderRegistry $configurationProviderRegistry,
        PageRenderer $pageRenderer,
        UriBuilder $uriBuilder,
        ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->configurationProviderRegistry = $configurationProviderRegistry;
        $this->pageRenderer = $pageRenderer;
        $this->uriBuilder = $uriBuilder;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    /**
     * Main controller action determines get/post values, takes care of
     * stored backend user settings for this module, determines tree
     * and renders it.
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     * @throws \RuntimeException
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $backendUser = $this->getBackendUser();
        $queryParams = $request->getQueryParams();
        $postValues = $request->getParsedBody();
        $moduleState = $backendUser->uc['moduleData']['system_config'] ?? [];

        $configurationProviderIdentifier = (string)($queryParams['tree'] ?? $moduleState['tree'] ?? '');

        if ($configurationProviderIdentifier === ''
            && $this->configurationProviderRegistry->getFirstProvider() !== null
        ) {
            $configurationProviderIdentifier = $this->configurationProviderRegistry->getFirstProvider()->getIdentifier();
        }

        if (!$this->configurationProviderRegistry->hasProvider($configurationProviderIdentifier)) {
            throw new \InvalidArgumentException(
                'No provider found for identifier: ' . $configurationProviderIdentifier,
                1606306196
            );
        }

        $configurationProvider = $this->configurationProviderRegistry->getProvider($configurationProviderIdentifier);
        $moduleState['tree'] = $configurationProviderIdentifier;

        $configurationArray = $configurationProvider->getConfiguration();

        // Search string given or regex search enabled?
        $searchString = trim((string)($postValues['searchString'] ?? ''));
        $moduleState['regexSearch'] = (bool)($postValues['regexSearch'] ?? $moduleState['regexSearch'] ?? false);

        // Prepare array renderer class, apply search and expand / collapse states
        $arrayBrowser = GeneralUtility::makeInstance(ArrayBrowser::class, $request->getAttribute('route'));
        $arrayBrowser->regexMode = $moduleState['regexSearch'];
        $node = $queryParams['node'] ?? null;
        if ($searchString) {
            $arrayBrowser->depthKeys = $arrayBrowser->getSearchKeys($configurationArray, '', $searchString, []);
        } elseif (is_array($node)) {
            $newExpandCollapse = $arrayBrowser->depthKeys($node, $moduleState['node_' . $configurationProviderIdentifier] ?? []);
            $arrayBrowser->depthKeys = $newExpandCollapse;
            $moduleState['node_' . $configurationProviderIdentifier] = $newExpandCollapse;
        } else {
            $arrayBrowser->depthKeys = $moduleState['node_' . $configurationProviderIdentifier] ?? [];
        }

        // Store new state
        $backendUser->uc['moduleData']['system_config'] = $moduleState;
        $backendUser->writeUC();

        // Render main body
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->getRequest()->setControllerExtensionName('lowlevel');
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
            'EXT:lowlevel/Resources/Private/Templates/Backend/Configuration.html'
        ));
        $view->assignMultiple([
            'treeName' => $configurationProvider->getLabel(),
            'searchString' => $searchString,
            'regexSearch' => $moduleState['regexSearch'],
            'tree' => $arrayBrowser->tree($configurationArray, ''),
        ]);

        // Prepare module setup
        $moduleTemplate->setContent($view->render());
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Lowlevel/ConfigurationView');

        // Shortcut in doc header
        $shortcutButton = $moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeShortcutButton();
        $shortcutButton
            ->setRouteIdentifier('system_config')
            ->setDisplayName($configurationProvider->getLabel())
            ->setArguments(['tree' => $configurationProviderIdentifier]);
        $moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton($shortcutButton);

        // Main drop down in doc header
        $menu = $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('tree');

        $context = '';
        foreach ($this->configurationProviderRegistry->getProviders() as $provider) {
            $menuItem = $menu->makeMenuItem();
            $menuItem
                ->setHref((string)$this->uriBuilder->buildUriFromRoute('system_config', ['tree' => $provider->getIdentifier()]))
                ->setTitle($provider->getLabel());
            if ($configurationProvider === $provider) {
                $menuItem->setActive(true);
                $context = $menuItem->getTitle();
            }
            $menu->addMenuItem($menuItem);
        }

        $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
        $moduleTemplate->setTitle(
            $this->getLanguageService()->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang_mod_configuration.xlf:mlang_tabs_tab'),
            $context
        );

        return new HtmlResponse($moduleTemplate->renderContent());
    }

    /**
     * Returns the Backend User
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns the Language Service
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
