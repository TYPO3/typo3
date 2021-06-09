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

namespace TYPO3\CMS\Backend\Controller\File;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Resource\Service\ImageProcessingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class ImageProcessController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ImageProcessingService
     */
    private $imageProcessingService;

    public function __construct(ImageProcessingService $imageProcessingService)
    {
        $this->imageProcessingService = $imageProcessingService;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request): ResponseInterface
    {
        $processedFileId = (int)($request->getQueryParams()['id'] ?? 0);
        try {
            $processedFile = $this->imageProcessingService->process($processedFileId);

            return new RedirectResponse(
                GeneralUtility::locationHeaderUrl($processedFile->getPublicUrl(true) ?? '')
            );
        } catch (\Throwable $e) {
            // Fatal error occurred, which will be responded as 404
            $this->logger->error('Processing of file with id {processed_file} failed', ['processed_file' => $processedFileId, 'exception' => $e]);
        }

        return new HtmlResponse('', 404);
    }
}
