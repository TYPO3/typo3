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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\FluidViewAdapter;
use TYPO3\CMS\Core\View\ViewInterface as CoreViewInterface;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3Fluid\Fluid\View\TemplateView as FluidTemplateView;

/**
 * Creates a View for backend usage. This is a low level factory. Extensions typically use ModuleTemplate instead.
 *
 * @internal
 */
final class BackendViewFactory
{
    public function __construct(
        protected readonly RenderingContextFactory $renderingContextFactory,
        protected readonly PackageManager $packageManager,
    ) {
    }

    /**
     * This backend view is capable of overriding templates, partials and layouts via TSconfig.
     */
    public function create(ServerRequestInterface $request, string $basePackageName = ''): CoreViewInterface
    {
        $templatePaths = [
            'templateRootPaths' => [],
            'layoutRootPaths' => [],
            'partialRootPaths' => [],
        ];
        if (empty($basePackageName)) {
            $basePackageName = 'typo3/cms-backend';
        }
        if ($basePackageName !== 'typo3/cms-backend') {
            // Always add EXT:backend/Resources/Private/ as first default path to resolve
            // default Layouts/Module.html and its partials
            $backendPackagePath = $this->packageManager->getPackage('typo3/cms-backend')->getPackagePath();
            $templatePaths['layoutRootPaths'][] = $backendPackagePath . 'Resources/Private/Layouts';
            $templatePaths['partialRootPaths'][] = $backendPackagePath . 'Resources/Private/Partials';
        }
        // @todo: Argument $basePackageName could be dropped if the $request route attribute would carry the package object
        $packagePath = $this->packageManager->getPackage($basePackageName)->getPackagePath();
        $templatePaths['templateRootPaths'][] = $packagePath . 'Resources/Private/Templates';
        $templatePaths['layoutRootPaths'][] = $packagePath . 'Resources/Private/Layouts';
        $templatePaths['partialRootPaths'][] = $packagePath . 'Resources/Private/Partials';

        // @todo: This assumes the pageId is *always* given as 'id' in request.
        // @todo: It would be cool if a middleware adds final pageTS - already overlayed by userTS - as attribute to request, to use it here.
        $pageId = $request->getParsedBody()['id'] ?? $request->getQueryParams()['id'] ?? 0;
        $pageTs = BackendUtility::getPagesTSconfig($pageId);
        if (is_array($pageTs['templates.'][$basePackageName . '.'] ?? false)) {
            $overrides =  $pageTs['templates.'][$basePackageName . '.'];
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

        $renderingContext = $this->renderingContextFactory->create($templatePaths);
        $renderingContext->setRequest($request);
        $fluidView = new FluidTemplateView($renderingContext);
        return new FluidViewAdapter($fluidView);
    }
}
