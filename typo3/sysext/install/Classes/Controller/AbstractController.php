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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\FluidViewAdapter;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3Fluid\Fluid\View\TemplateView as FluidTemplateView;

/**
 * Controller abstract for shared parts of the install tool.
 *
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class AbstractController
{
    /**
     * Helper method to initialize a view instance.
     */
    protected function initializeView(ServerRequestInterface $request): ViewInterface
    {
        $templatePaths = [
            'templateRootPaths' => ['EXT:install/Resources/Private/Templates'],
            'partialRootPaths' => ['EXT:install/Resources/Private/Partials'],
        ];
        $renderingContext = GeneralUtility::makeInstance(RenderingContextFactory::class)->create($templatePaths);
        $fluidView = new FluidTemplateView($renderingContext);
        $view = new FluidViewAdapter($fluidView);
        $view->assignMultiple([
            'controller' => $request->getQueryParams()['install']['controller'] ?? 'maintenance',
            'context' => $request->getQueryParams()['install']['context'] ?? '',
            'composerMode' => Environment::isComposerMode(),
            'currentTypo3Version' => (string)(new Typo3Version()),
        ]);
        return $view;
    }
}
