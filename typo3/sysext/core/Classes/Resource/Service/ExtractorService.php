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

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\ExtractorInterface;
use TYPO3\CMS\Core\Resource\Index\ExtractorRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service class to extract metadata
 */
class ExtractorService
{
    /**
     * @var ExtractorInterface[][]
     */
    private $extractionServices;

    /**
     * @param File $fileObject
     * @return array
     */
    public function extractMetaData(File $fileObject): array
    {
        $newMetaData = [];
        // Loop through available extractors and fetch metadata for the given file.
        foreach ($this->getExtractionServices($fileObject->getStorage()->getDriverType()) as $service) {
            foreach ($service ?? [] as $extractorService) {
                if ($this->isFileTypeSupportedByExtractor($fileObject, $extractorService) &&
                    $extractorService->canProcess($fileObject)) {
                    $newMetaData[$extractorService->getPriority()] = $extractorService->extractMetaData($fileObject, $newMetaData);
                }
            }
        }
        // Sort metadata by priority so that merging happens in order of precedence.
        ksort($newMetaData);
        // Merge the collected metadata.
        $metaData = [[]];
        foreach ($newMetaData as $data) {
            $metaData[] = $data;
        }
        return array_filter(array_merge(...$metaData));
    }

    /**
     * Get available extraction services
     *
     * @param string $driverType
     * @return ExtractorInterface[]
     */
    protected function getExtractionServices(string $driverType): array
    {
        if (empty($this->extractionServices[$driverType])) {
            $this->extractionServices[$driverType] = $this->getExtractorRegistry()->getExtractorsWithDriverSupport($driverType);
        }
        return $this->extractionServices[$driverType];
    }

    /**
     * Check whether the extractor service supports this file according to file type restrictions.
     *
     * @param File $file
     * @param ExtractorInterface $extractor
     * @return bool
     */
    private function isFileTypeSupportedByExtractor(File $file, ExtractorInterface $extractor): bool
    {
        $isSupported = true;
        $fileTypeRestrictions = $extractor->getFileTypeRestrictions();
        if (!empty($fileTypeRestrictions) && !in_array($file->getType(), $fileTypeRestrictions, true)) {
            $isSupported = false;
        }
        return $isSupported;
    }

    /**
     * Returns an instance of the FileIndexRepository
     *
     * @return ExtractorRegistry
     */
    protected function getExtractorRegistry(): ExtractorRegistry
    {
        return GeneralUtility::makeInstance(ExtractorRegistry::class);
    }
}
