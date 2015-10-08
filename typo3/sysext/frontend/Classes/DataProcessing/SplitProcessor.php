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

/**
 * This data processor can be used for processing data for the content elements which have split contents in one field
 * like e.g. "bullets". It will split the field data in an array ready to be iterated over in Fluid.
 *
 * Example field data:
 *
 * This is bullet 1, This is bullet 2, This is bullet 3
 *
 * Example TypoScript configuration:
 *
 * 10 = TYPO3\CMS\Frontend\DataProcessing\SplitProcessor
 * 10 {
 *   if.isTrue.field = bodytext
 *   delimiter = ,
 *   fieldName = bodytext
 *   removeEmptyEntries = 1
 *   filterIntegers = 1
 *   filterUnique = 1
 *   as = bullets
 * }
 *
 * whereas "bullets" can be used as a variable {bullets} inside Fluid for iteration.
 */
class SplitProcessor implements DataProcessorInterface
{
    /**
     * Process field data to split in an array
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

        // The field name to process
        $fieldName = $cObj->stdWrapValue('fieldName', $processorConfiguration);
        if (empty($fieldName)) {
            return $processedData;
        }

        $originalValue = $cObj->data[$fieldName];

        // Set the target variable
        $targetVariableName = $cObj->stdWrapValue('as', $processorConfiguration, $fieldName);

        // Set the delimiter which is "LF" by default
        $delimiter = $cObj->stdWrapValue('delimiter', $processorConfiguration, LF);

        // Filter integers
        $filterIntegers = (bool)$cObj->stdWrapValue('filterIntegers', $processorConfiguration, false);

        // Filter unique
        $filterUnique = (bool)$cObj->stdWrapValue('filterUnique', $processorConfiguration, false);

        // Remove empty entries
        $removeEmptyEntries = (bool)$cObj->stdWrapValue('removeEmptyEntries', $processorConfiguration, false);

        if ($filterIntegers === true) {
            $processedData[$targetVariableName] = GeneralUtility::intExplode($delimiter, $originalValue, $removeEmptyEntries);
        } else {
            $processedData[$targetVariableName] = GeneralUtility::trimExplode($delimiter, $originalValue, $removeEmptyEntries);
        }

        if ($filterUnique === true) {
            $processedData[$targetVariableName] = array_unique($processedData[$targetVariableName]);
        }

        return $processedData;
    }
}
