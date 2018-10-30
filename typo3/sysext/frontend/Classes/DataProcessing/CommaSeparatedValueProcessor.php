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

use TYPO3\CMS\Core\Utility\CsvUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * This data processor will take field data formatted as a string, where each line, separated by line feed,
 * represents a row. By default columns are separated by the delimiter character "comma ,",
 * and can be enclosed by the character 'quotation mark "', like the default in a regular CSV file.
 *
 * An example of such a field is "bodytext" in the CType "table".
 *
 * The table data is transformed to a multi dimensional array, taking the delimiter and enclosure into account,
 * before it is passed to the view.
 *
 * Example field data:
 *
 * This is row 1 column 1|This is row 1 column 2|This is row 1 column 3
 * This is row 2 column 1|This is row 2 column 2|This is row 2 column 3
 * This is row 3 column 1|This is row 3 column 2|This is row 3 column 3
 *
 * Example TypoScript configuration:
 *
 * 10 = TYPO3\CMS\Frontend\DataProcessing\CommaSeparatedValueProcessor
 * 10 {
 *   if.isTrue.field = bodytext
 *   fieldName = bodytext
 *   fieldDelimiter = |
 *   fieldEnclosure =
 *   maximumColumns = 2
 *   as = table
 * }
 *
 * whereas "table" can be used as a variable {table} inside Fluid for iteration.
 *
 * Using maximumColumns limits the amount of columns in the multi dimensional array.
 * In the example, field data of the last column will be stripped off.
 *
 * Multi line cells are taken into account.
 */
class CommaSeparatedValueProcessor implements DataProcessorInterface
{
    /**
     * Process CSV field data to split into a multi dimensional array
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

        // Set the maximum amount of columns
        $maximumColumns = $cObj->stdWrapValue('maximumColumns', $processorConfiguration, 0);

        // Set the field delimiter which is "," by default
        $fieldDelimiter = $cObj->stdWrapValue('fieldDelimiter', $processorConfiguration, ',');

        // Set the field enclosure which is " by default
        $fieldEnclosure = $cObj->stdWrapValue('fieldEnclosure', $processorConfiguration, '"');

        $processedData[$targetVariableName] = CsvUtility::csvToArray(
            $originalValue,
            $fieldDelimiter,
            $fieldEnclosure,
            (int)$maximumColumns
        );

        return $processedData;
    }
}
