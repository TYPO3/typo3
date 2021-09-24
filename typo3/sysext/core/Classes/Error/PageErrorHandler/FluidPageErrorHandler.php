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
use TYPO3\CMS\Fluid\View\TemplateView;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * An error handler that renders a fluid template.
 * This is typically configured via the "Sites configuration" module in the backend.
 */
class FluidPageErrorHandler implements PageErrorHandlerInterface
{
    /**
     * @var ViewInterface
     */
    protected $view;

    /**
     * @var int
     */
    protected $statusCode;

    /**
     * FluidPageErrorHandler constructor.
     * @param int $statusCode
     * @param array $configuration
     */
    public function __construct(int $statusCode, array $configuration)
    {
        $this->statusCode = $statusCode;
        $this->view = GeneralUtility::makeInstance(TemplateView::class);
        if (!empty($configuration['errorFluidTemplatesRootPath'])) {
            $this->view->setTemplateRootPaths([$configuration['errorFluidTemplatesRootPath']]);
        }
        if (!empty($configuration['errorFluidLayoutsRootPath'])) {
            $this->view->setLayoutRootPaths([$configuration['errorFluidLayoutsRootPath']]);
        }
        if (!empty($configuration['errorFluidPartialsRootPath'])) {
            $this->view->setPartialRootPaths([$configuration['errorFluidPartialsRootPath']]);
        }
        $this->view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($configuration['errorFluidTemplate']));
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $message
     * @param array $reasons
     * @return ResponseInterface
     */
    public function handlePageError(ServerRequestInterface $request, string $message, array $reasons = []): ResponseInterface
    {
        $this->view->assignMultiple([
            'request' => $request,
            'message' => $message,
            'reasons' => $reasons,
        ]);
        return new HtmlResponse($this->view->render(), $this->statusCode);
    }
}
