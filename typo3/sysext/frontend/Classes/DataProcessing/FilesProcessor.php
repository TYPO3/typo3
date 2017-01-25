<?php
namespace TYPO3\CMS\Frontend\DataProcessing;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;
use TYPO3\CMS\Frontend\Resource\FileCollector;

/**
 * This data processor can be used for processing data for record which contain
 * relations to sys_file records (e.g. sys_file_reference records) or for fetching
 * files directly from UIDs or from folders or collections.
 *
 *
 * Example TypoScript configuration:
 *
 * 10 = TYPO3\CMS\Frontend\DataProcessing\FilesProcessor
 * 10 {
 *   references.fieldName = image
 *   collections = 13,15
 *   as = myfiles
 * }
 *
 * whereas "myfiles" can further be used as a variable {myfiles} inside a Fluid template for iteration.
 */
class FilesProcessor implements DataProcessorInterface
{
    /**
     * Process data of a record to resolve File objects to the view
     *
     * @param ContentObjectRenderer $cObj The data of the content element or page
     * @param array $contentObjectConfiguration The configuration of Content Object
     * @param array $processorConfiguration The configuration of this processor
     * @param array $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
     * @return array the processed data as key/value store
     */
    public function process(ContentObjectRenderer $cObj, array $contentObjectConfiguration, array $processorConfiguration, array $processedData)
    {
        if (isset($processorConfiguration['if.']) && !$cObj->checkIf($processorConfiguration['if.'])) {
            return $processedData;
        }

        // gather data
        /** @var FileCollector $fileCollector */
        $fileCollector = GeneralUtility::makeInstance(FileCollector::class);

        // references / relations
        if (!empty($processorConfiguration['references.'])) {
            $referenceConfiguration = $processorConfiguration['references.'];
            $relationField = $cObj->stdWrapValue('fieldName', $referenceConfiguration);

            // If no reference fieldName is set, there's nothing to do
            if (!empty($relationField)) {
                // Fetch the references of the default element
                $relationTable = $cObj->stdWrapValue('table', $referenceConfiguration, $cObj->getCurrentTable());
                if (!empty($relationTable)) {
                    $fileCollector->addFilesFromRelation($relationTable, $relationField, $cObj->data);
                }
            }
        }

        // files
        $files = $cObj->stdWrapValue('files', $processorConfiguration);
        if ($files) {
            $files = GeneralUtility::intExplode(',', $files, true);
            $fileCollector->addFiles($files);
        }

        // collections
        $collections = $cObj->stdWrapValue('collections', $processorConfiguration);
        if (!empty($collections)) {
            $collections = GeneralUtility::trimExplode(',', $collections, true);
            $fileCollector->addFilesFromFileCollections($collections);
        }

        // folders
        $folders = $cObj->stdWrapValue('folders', $processorConfiguration);
        if (!empty($folders)) {
            $folders = GeneralUtility::trimExplode(',', $folders, true);
            $fileCollector->addFilesFromFolders($folders, !empty($processorConfiguration['folders.']['recursive']));
        }

        // make sure to sort the files
        $sortingProperty = $cObj->stdWrapValue('sorting', $processorConfiguration);
        if ($sortingProperty) {
            $sortingDirection = $cObj->stdWrapValue(
                'direction',
                isset($processorConfiguration['sorting.']) ? $processorConfiguration['sorting.'] : [],
                'ascending'
            );

            $fileCollector->sort($sortingProperty, $sortingDirection);
        }

        // set the files into a variable, default "files"
        $targetVariableName = $cObj->stdWrapValue('as', $processorConfiguration, 'files');
        $processedData[$targetVariableName] = $fileCollector->getFiles();

        return $processedData;
    }
}
