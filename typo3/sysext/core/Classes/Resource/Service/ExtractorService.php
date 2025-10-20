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

namespace TYPO3\CMS\Core\Resource\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\CMS\Core\Resource\Index\ExtractorInterface;
use TYPO3\CMS\Core\Resource\Index\ExtractorRegistry;

/**
 * Service class to extract metadata
 */
#[Autoconfigure(public: true)]
readonly class ExtractorService
{
    public function __construct(
        private ExtractorRegistry $extractorRegistry,
    ) {}

    public function extractMetaData(File $fileObject): array
    {
        $newMetaData = $extractedMetaData = [];
        // Loop through available extractors and fetch metadata for the given file.
        $extractionServices = $this->extractorRegistry->getExtractorsWithDriverSupport($fileObject->getStorage()->getDriverType());
        foreach ($extractionServices as $extractorService) {
            if ($this->isFileTypeSupportedByExtractor($fileObject, $extractorService)
                && $extractorService->canProcess($fileObject)
            ) {
                $metaDataFromExtractor = $extractorService->extractMetaData($fileObject, $extractedMetaData);
                if (!empty($metaDataFromExtractor)) {
                    $extractedMetaData[] = $metaDataFromExtractor;
                    $newMetaData[$extractorService->getPriority()][] = $metaDataFromExtractor;
                }
            }
        }
        // Sort metadata by priority so that merging happens in order of precedence.
        ksort($newMetaData);
        // Merge the collected metadata.
        $metaData = [[]];
        foreach ($newMetaData as $dataFromExtractors) {
            foreach ($dataFromExtractors as $data) {
                $metaData[] = $data;
            }
        }
        return array_filter(array_merge(...$metaData));
    }

    /**
     * Check whether the extractor service supports this file according to file type restrictions.
     */
    private function isFileTypeSupportedByExtractor(File $file, ExtractorInterface $extractor): bool
    {
        $supportedFileTypes = $extractor->getFileTypeRestrictions();
        if ($supportedFileTypes === []) {
            return true;
        }
        foreach ($supportedFileTypes as $supportedFileType) {
            if (is_int($supportedFileType)) {
                $supportedFileType = FileType::tryFrom($supportedFileType);
            }
            if ($supportedFileType->value === $file->getType()) {
                return true;
            }
        }
        return false;
    }
}
