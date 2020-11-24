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

namespace TYPO3\CMS\Backend\Resource\Processing;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\Processing\ProcessorInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class DeferredBackendImageProcessor implements ProcessorInterface
{
    public function canProcessTask(TaskInterface $task): bool
    {
        $context = GeneralUtility::makeInstance(Context::class);
        return ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()
            && $task->getType() === 'Image'
            && in_array($task->getName(), ['Preview', 'CropScaleMask'], true)
            && (!$context->hasAspect('fileProcessing') || $context->getPropertyFromAspect('fileProcessing', 'deferProcessing'))
            && $task->getSourceFile()->getProperty('width') > 0
            && $task->getSourceFile()->getProperty('height') > 0
            // Let the local image processor update the properties in case the target file exists already
            && !$task->getSourceFile()->getStorage()->getProcessingFolder()->hasFile($task->getTargetFileName());
    }

    public function processTask(TaskInterface $task): void
    {
        $imageDimension = ImageDimension::fromProcessingTask($task);
        $processedFile = $task->getTargetFile();
        if (!$processedFile->isPersisted()) {
            // For now, we need to persist the processed file in the repository to be able to reference its uid
            // We could instead introduce a processing queue and persist the information there
            $processedFileRepository = GeneralUtility::makeInstance(ProcessedFileRepository::class);
            $processedFileRepository->add($processedFile);
        }
        $processedFile->setName($task->getTargetFileName());
        $processingUrl = (string)GeneralUtility::makeInstance(UriBuilder::class)
            ->buildUriFromRoute(
                'image_processing',
                [
                    'id' => $processedFile->getUid(),
                ]
            );
        $processedFile->updateProcessingUrl(GeneralUtility::locationHeaderUrl($processingUrl));
        $processedFile->updateProperties(
            [
                'width' => $imageDimension->getWidth(),
                'height' => $imageDimension->getHeight(),
                'size' => 0,
                'checksum' => $task->getConfigurationChecksum(),
            ]
        );
        $task->setExecuted(true);
    }
}
