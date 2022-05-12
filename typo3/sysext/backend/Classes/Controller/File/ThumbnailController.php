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
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class ThumbnailController
{
    /**
     * @var array
     */
    protected $defaultConfiguration = [
        'width' => 64,
        'height' => 64,
        'crop' => null,
    ];

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function render(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $parameters = $this->extractParameters($request->getQueryParams());
            $response = $this->generateThumbnail(
                $parameters['fileId'] ?? null,
                $parameters['configuration'] ?? []
            );
        } catch (Exception $exception) {
            // catch and handle only resource related exceptions
            $response = $this->generateNotFoundResponse();
        }

        return $response;
    }

    /**
     * @param array $queryParameters
     * @return array|null
     */
    protected function extractParameters(array $queryParameters)
    {
        $expectedHash = GeneralUtility::hmac(
            $queryParameters['parameters'] ?? '',
            ThumbnailController::class
        );
        if (!hash_equals($expectedHash, $queryParameters['hmac'] ?? '')) {
            throw new \InvalidArgumentException(
                'HMAC could not be verified',
                1534484203
            );
        }

        return json_decode($queryParameters['parameters'] ?? null, true);
    }

    /**
     * @param mixed|int $fileId
     * @param array $configuration
     * @return ResponseInterface
     * @throws \TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException
     */
    protected function generateThumbnail($fileId, array $configuration): ResponseInterface
    {
        $file = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObject($fileId);
        if ($file->isMissing()) {
            return $this->generateNotFoundResponse();
        }

        $context = $configuration['_context'] ?? ProcessedFile::CONTEXT_IMAGEPREVIEW;
        unset($configuration['_context']);

        $processingConfiguration = $this->defaultConfiguration;
        ArrayUtility::mergeRecursiveWithOverrule(
            $processingConfiguration,
            $configuration
        );

        $processedImage = $file->process(
            $context,
            $processingConfiguration
        );
        if ($processedImage->isImage()) {
            return new RedirectResponse(
                GeneralUtility::locationHeaderUrl($processedImage->getPublicUrl() ?? '')
            );
        }

        $iconIdentifier = GeneralUtility::makeInstance(IconFactory::class)
            ->getIconForResource($processedImage->getOriginalFile())->getIdentifier();
        $fileName = 'EXT:core/Resources/Public/Icons/T3Icons/mimetypes/' . $iconIdentifier . '.svg';
        $file = GeneralUtility::getFileAbsFileName($fileName);
        if (file_exists($file)) {
            return new RedirectResponse(
                GeneralUtility::locationHeaderUrl(PathUtility::getPublicResourceWebPath($fileName))
            );
        }

        return $this->generateNotFoundResponse();
    }

    /**
     * @return ResponseInterface
     */
    protected function generateNotFoundResponse(): ResponseInterface
    {
        return new HtmlResponse('', 404);
    }
}
