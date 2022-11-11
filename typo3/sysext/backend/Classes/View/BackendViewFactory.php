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

namespace TYPO3\CMS\Backend\View;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\View\FluidViewAdapter;
use TYPO3\CMS\Core\View\ViewInterface as CoreViewInterface;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3Fluid\Fluid\View\TemplateView as FluidTemplateView;

/**
 * Creates a View for backend usage. This is a low level factory. Extensions typically use ModuleTemplate instead.
 */
final class BackendViewFactory
{
    public function __construct(
        protected readonly RenderingContextFactory $renderingContextFactory,
        protected readonly PackageManager $packageManager,
    ) {
    }

    /**
     * This backend view is capable of overriding templates, partials and layouts via TsConfig
     * based on the composer package name of the route and optional additional package names.
     */
    public function create(ServerRequestInterface $request, array $packageNames = []): CoreViewInterface
    {
        if (empty($packageNames)) {
            // Extensions *may* provide path lookup package names as second argument. In most cases, this is not
            // needed, and the package name will be fetched from current route. However, there are scenarios
            // where extensions 'hook' into existing functionality of a different extension that defined a
            // route, and then deliver own templates from the own extension. In those cases, they need to
            // supply an additional base package name.
            // Examples are backend toolbar items: The toolbar items are rendered through a typo3/cms-backend
            // route, so this is picked as base from the route. workspaces delivers an additional toolbar item,
            // so 'typo3/cms-workspaces' needs to be added as additional path to look up. The dashboard extension
            // and FormEngine have similar cases.
            /** @var Route $route */
            $route = $request->getAttribute('route');
            $packageNameFromRoute = $route->getOption('packageName');
            if (!empty($packageNameFromRoute)) {
                $packageNames[] = $packageNameFromRoute;
            }
        }
        // Always add EXT:backend/Resources/Private/ as first default path to resolve
        // default Layouts/Module.html and its partials.
        if (!in_array('typo3/cms-backend', $packageNames, true)) {
            array_unshift($packageNames, 'typo3/cms-backend');
        }

        // @todo: This assumes the pageId is *always* given as 'id' in request.
        // @todo: It would be cool if a middleware adds final pageTS - already overlayed by userTS - as attribute to request, to use it here.
        $pageTs = [];
        $pageId = $request->getParsedBody()['id'] ?? $request->getQueryParams()['id'] ?? 0;
        if (MathUtility::canBeInterpretedAsInteger($pageId)) {
            // Some BE controllers misuse the 'id' argument for something else than the page-uid (especially filelist module).
            // We check if 'id' is an integer here to skip pageTsConfig calculation if that is the case.
            // @todo: Mid-term, misuses should vanish, making 'id' a Backend convention. Affected is
            //        at least ext:filelist, plus record linking modals that use 'pid'.
            $pageTs = BackendUtility::getPagesTSconfig((int)$pageId);
        }

        $templatePaths = [
            'templateRootPaths' => [],
            'layoutRootPaths' => [],
            'partialRootPaths' => [],
        ];
        foreach ($packageNames as $packageName) {
            // Add paths for package.
            $packagePath = $this->packageManager->getPackage($packageName)->getPackagePath();
            $templatePaths['templateRootPaths'][] = $packagePath . 'Resources/Private/Templates';
            $templatePaths['layoutRootPaths'][] = $packagePath . 'Resources/Private/Layouts';
            $templatePaths['partialRootPaths'][] = $packagePath . 'Resources/Private/Partials';
            // Add possible overrides.
            if (is_array($pageTs['templates.'][$packageName . '.'] ?? false)) {
                $overrides = $pageTs['templates.'][$packageName . '.'];
                ksort($overrides);
                foreach ($overrides as $override) {
                    $pathParts = GeneralUtility::trimExplode(':', $override, true);
                    if (count($pathParts) < 2) {
                        throw new \RuntimeException(
                            'When overriding template paths, the syntax is "composer-package-name:path", example: "typo3/cms-seo:Resources/Private/TemplateOverrides/typo3/cms-backend"',
                            1643798660
                        );
                    }
                    $composerPackageName = $pathParts[0];
                    $overridePackagePath = $this->packageManager->getPackage($composerPackageName)->getPackagePath();
                    $overridePath = rtrim($pathParts[1], '/');
                    $templatePaths['templateRootPaths'][] = $overridePackagePath . $overridePath . '/Templates';
                    $templatePaths['layoutRootPaths'][] = $overridePackagePath . $overridePath . '/Layouts';
                    $templatePaths['partialRootPaths'][] = $overridePackagePath . $overridePath . '/Partials';
                }
            }
        }

        $renderingContext = $this->renderingContextFactory->create($templatePaths);
        $renderingContext->setRequest($request);
        $fluidView = new FluidTemplateView($renderingContext);
        return new FluidViewAdapter($fluidView);
    }
}
