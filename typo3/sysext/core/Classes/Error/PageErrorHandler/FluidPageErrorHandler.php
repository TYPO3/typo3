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

namespace TYPO3\CMS\Core\Error\PageErrorHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

/**
 * An error handler that renders a fluid template.
 * This is typically configured via the "Sites configuration" module in the backend.
 */
class FluidPageErrorHandler implements PageErrorHandlerInterface
{
    /**
     * @todo: Change this "API" to not pollute __construct() anymore
     */
    public function __construct(
        protected int $statusCode,
        protected array $configuration
    ) {}

    public function handlePageError(ServerRequestInterface $request, string $message, array $reasons = []): ResponseInterface
    {
        $configuration = $this->configuration;
        $templateRootPaths = null;
        if (is_string($configuration['errorFluidTemplatesRootPath']) && !empty($configuration['errorFluidTemplatesRootPath'])) {
            $templateRootPaths = [$configuration['errorFluidTemplatesRootPath']];
        }
        $layoutRootPaths = null;
        if (is_string($configuration['errorFluidLayoutsRootPath']) && !empty($configuration['errorFluidLayoutsRootPath'])) {
            $layoutRootPaths = [$configuration['errorFluidLayoutsRootPath']];
        }
        $partialRootPaths = null;
        if (is_string($configuration['errorFluidPartialsRootPath']) && !empty($configuration['errorFluidPartialsRootPath'])) {
            $partialRootPaths = [$configuration['errorFluidPartialsRootPath']];
        }
        $templatePathAndFilename = null;
        if (is_string($configuration['errorFluidTemplate']) && !empty($configuration['errorFluidTemplate'])) {
            $templatePathAndFilename = GeneralUtility::getFileAbsFileName($configuration['errorFluidTemplate']);
        }
        $viewFactoryDate = new ViewFactoryData(
            templateRootPaths: $templateRootPaths,
            partialRootPaths: $partialRootPaths,
            layoutRootPaths: $layoutRootPaths,
            templatePathAndFilename: $templatePathAndFilename,
            request: $request,
        );
        $viewFactory = GeneralUtility::makeInstance(ViewFactoryInterface::class);
        $view = $viewFactory->create($viewFactoryDate);
        $view->assignMultiple([
            'request' => $request,
            'message' => $message,
            'reasons' => $reasons,
        ]);
        return new HtmlResponse($view->render(), $this->statusCode);
    }
}
