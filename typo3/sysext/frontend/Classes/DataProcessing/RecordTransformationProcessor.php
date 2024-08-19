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

namespace TYPO3\CMS\Frontend\DataProcessing;

use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Creates Record objects out of full data sets (= DB entries).
 * This is typically useful in conjunction with the DatabaseQueryProcessor.
 * Can also be used to transform the current data array of FLUIDTEMPLATE.
 *
 * The variable that contains the record(s) from a previous data processor,
 * or from a FLUIDTEMPLATE view. Default is `data`.
 *
 * variableName = items
 *
 * The name of the database table of the records. Leave empty to auto-resolve
 * the table from current ContentObjectRenderer.
 *
 * table = tt_content
 *
 * The target variable where the resolved record objects are contained.
 * Can be set to `data` to override the input data array of FLUIDTEMPLATE.
 * If empty, "record" or "records" (if multiple records are given) is used.
 *
 * as = myRecords
 *
 * Example TypoScript configuration:
 *
 * page = PAGE
 * page {
 *     10 = PAGEVIEW
 *     10 {
 *         paths.10 = EXT:my_site_package/Resources/Private/Templates/
 *         dataProcessing {
 *             10 = database-query
 *             10 {
 *                 as = mainContent
 *                 table = tt_content
 *                 select.where = colPos=0
 *                 dataProcessing {
 *                     10 = record-transformation
 *                     10 {
 *                         as = myContent
 *                     }
 *                 }
 *             }
 *         }
 *     }
 * }
 *
 * which transforms all content elements fetched by the DatabaseQueryProcessor an provides them as "myContent".
 */
readonly class RecordTransformationProcessor implements DataProcessorInterface
{
    public function __construct(
        protected RecordFactory $recordFactory,
    ) {}

    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        if (isset($processorConfiguration['if.']) && !$cObj->checkIf($processorConfiguration['if.'])) {
            return $processedData;
        }
        // `data` is the default variable name for the FLUIDTEMPLATE record
        // and processed records of the DatabaseQueryProcessor.
        $defaultVariableName = 'data';
        $variableName = $cObj->stdWrapValue('variableName', $processorConfiguration, $defaultVariableName);
        $input = $processedData[$variableName] ?? $processedData;
        // We can only deal with arrays here.
        if (!is_array($input)) {
            return $processedData;
        }
        $table = $cObj->stdWrapValue('table', $processorConfiguration, $cObj->getCurrentTable());
        $output = [];
        if (array_is_list($input)) {
            foreach ($input as $record) {
                $output[] = $this->recordFactory->createFromDatabaseRow($table, $record);
            }
            $defaultTargetVariableName = 'records';
        } else {
            $output = $this->recordFactory->createFromDatabaseRow($table, $input);
            $defaultTargetVariableName = 'record';
        }
        $targetVariableName = $cObj->stdWrapValue('as', $processorConfiguration, $defaultTargetVariableName);
        $processedData[$targetVariableName] = $output;
        return $processedData;
    }
}
