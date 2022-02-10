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

namespace TYPO3\CMS\Install\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Package\FailsafePackageManager;
use TYPO3\CMS\Core\Page\ImportMap;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\Exception\ConfigurationChangedException;
use TYPO3\CMS\Install\Service\Exception\TemplateFileChangedException;
use TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService;
use TYPO3\CMS\Install\Service\SilentTemplateFileUpgradeService;

/**
 * Layout controller
 *
 * Renders a first "load the Javascript in <head>" view, and the
 * main layout of the install tool in second action.
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class LayoutController extends AbstractController
{
    private FailsafePackageManager $packageManager;
    private SilentConfigurationUpgradeService $silentConfigurationUpgradeService;
    private SilentTemplateFileUpgradeService $silentTemplateFileUpgradeService;

    public function __construct(
        FailsafePackageManager $packageManager,
        SilentConfigurationUpgradeService $silentConfigurationUpgradeService,
        SilentTemplateFileUpgradeService $silentTemplateFileUpgradeService
    ) {
        $this->packageManager = $packageManager;
        $this->silentConfigurationUpgradeService = $silentConfigurationUpgradeService;
        $this->silentTemplateFileUpgradeService = $silentTemplateFileUpgradeService;
    }

    /**
     * The init action renders an HTML response with HTML view having <head> section
     * containing resources to main .js routing.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function initAction(ServerRequestInterface $request): ResponseInterface
    {
        $bust = $GLOBALS['EXEC_TIME'];
        if (!Environment::getContext()->isDevelopment()) {
            $bust = GeneralUtility::hmac((string)(new Typo3Version()) . Environment::getProjectPath());
        }

        $packages = [
            $this->packageManager->getPackage('core'),
            $this->packageManager->getPackage('backend'),
            $this->packageManager->getPackage('install'),
        ];
        $importMap = new ImportMap($packages);
        $sitePath = $request->getAttribute('normalizedParams')->getSitePath();
        $initModule = $sitePath . $importMap->resolveImport('@typo3/install/init-install.js');

        $view = $this->initializeView($request);
        $view->assignMultiple([
            // time is used as cache bust for js and css resources
            'bust' => $bust,
            'siteName' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
            'initModule' => $initModule,
            'importmap' => $importMap->render($sitePath, 'rAnd0m'),
        ]);
        return new HtmlResponse(
            $view->render('Layout/Init'),
            200,
            [
                'Cache-Control' => 'no-cache, must-revalidate',
                'Pragma' => 'no-cache',
            ]
        );
    }

    /**
     * Return a json response with the main HTML layout body: Toolbar, main menu and
     * doc header in standalone, doc header only in backend context. Silent updaters
     * are executed before this main view is loaded.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function mainLayoutAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeView($request);
        $view->assign('moduleName', 'tools_tools' . ($request->getQueryParams()['install']['module'] ?? 'layout'));
        return new JsonResponse([
            'success' => true,
            'html' => $view->render('Layout/MainLayout'),
        ]);
    }

    /**
     * Execute silent configuration update. May be called multiple times until success = true is returned.
     *
     * @return ResponseInterface success = true if no change has been done
     */
    public function executeSilentConfigurationUpdateAction(): ResponseInterface
    {
        $success = true;
        try {
            $this->silentConfigurationUpgradeService->execute();
        } catch (ConfigurationChangedException $e) {
            $success = false;
        }
        return new JsonResponse([
            'success' => $success,
        ]);
    }

    /**
     * Execute silent template files update. May be called multiple times until success = true is returned.
     *
     * @return ResponseInterface success = true if no change has been done
     */
    public function executeSilentTemplateFileUpdateAction(): ResponseInterface
    {
        $success = true;
        try {
            $this->silentTemplateFileUpgradeService->execute();
        } catch (TemplateFileChangedException $e) {
            $success = false;
        }
        return new JsonResponse([
            'success' => $success,
        ]);
    }

    /**
     * Synchronize TYPO3_CONF_VARS['EXTENSIONS'] with possibly new defaults from extensions
     * ext_conf_template.txt files. This make LocalConfiguration the only source of truth for
     * extension configuration and it is always up to date, also if an extension has been
     * updated.
     *
     * @return ResponseInterface
     */
    public function executeSilentExtensionConfigurationSynchronizationAction(): ResponseInterface
    {
        $extensionConfiguration = new ExtensionConfiguration();
        $extensionConfiguration->synchronizeExtConfTemplateWithLocalConfigurationOfAllExtensions();
        return new JsonResponse([
            'success' => true,
        ]);
    }
}
