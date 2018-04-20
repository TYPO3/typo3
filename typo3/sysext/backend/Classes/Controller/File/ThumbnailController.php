<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Controller\File;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Class ThumbnailController
 */
class ThumbnailController
{
    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function render(ServerRequestInterface $request): ResponseInterface
    {
        $fileObject = $this->getFileObjectByCombinedIdentifier($request->getQueryParams()['fileIdentifier']);
        if (!$fileObject->isMissing()) {
            $processingInstructions = [
                'width' => 64,
                'height' => 64,
                'crop' => null,
            ];
            ArrayUtility::mergeRecursiveWithOverrule($processingInstructions, $request->getQueryParams()['processingInstructions']);
            $processedImage = $fileObject->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $processingInstructions);
            $filePath = $processedImage->getForLocalProcessing(false);
            return new Response($filePath, 200, [
                'Content-Type' => $processedImage->getMimeType()
            ]);
        }
        return new Response('', 404);
    }

    /**
     * @param string $combinedIdentifier
     * @return File
     * @throws \InvalidArgumentException
     */
    protected function getFileObjectByCombinedIdentifier(string $combinedIdentifier): File
    {
        return ResourceFactory::getInstance()->getFileObjectFromCombinedIdentifier($combinedIdentifier);
    }
}
