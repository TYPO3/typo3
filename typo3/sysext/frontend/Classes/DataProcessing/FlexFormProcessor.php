<?php

declare(strict_types=1);

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

use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentDataProcessor;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * This data processor converts the XML structure of a given FlexForm field
 * into a fluid readable array.
 *
 * Options:
 * fieldname - The name of the field containing the FlexForm to be converted
 * as        - The variable, the generated array should be assigned to
 *
 * Example of a minimal TypoScript configuration, which processes the field
 * `pi_flexform` and assigns the array to the `flexFormData` variable:
 *
 * 10 = TYPO3\CMS\Frontend\DataProcessing\FlexFormProcessor
 *
 * Example of an advanced TypoScript configuration, which processes the field
 * `my_flexform_field` and assigns the array to the `myOutputVariable` variable:
 *
 * 10 = TYPO3\CMS\Frontend\DataProcessing\FlexFormProcessor
 * 10 {
 *   fieldName = my_flexform_field
 *   as = myOutputVariable
 * }
 */
class FlexFormProcessor implements DataProcessorInterface
{
    /**
     * @param ContentObjectRenderer $cObj The data of the content element or page
     * @param array $contentObjectConfiguration The configuration of Content Object
     * @param array $processorConfiguration The configuration of this processor
     * @param array $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
     * @return array the processed data as key/value store
     */
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {

        // The field name to process
        $fieldName = $cObj->stdWrapValue('fieldName', $processorConfiguration, 'pi_flexform');

        if (!isset($processedData['data'][$fieldName])) {
            return $processedData;
        }

        // Process FlexForm
        $originalValue = $processedData['data'][$fieldName];
        if (!is_string($originalValue)) {
            return $processedData;
        }
        $flexFormData = GeneralUtility::makeInstance(FlexFormService::class)
            ->convertFlexFormContentToArray($originalValue);

        // Set the target variable
        $targetVariableName = $cObj->stdWrapValue('as', $processorConfiguration, 'flexFormData');

        if (isset($processorConfiguration['dataProcessing.']) && is_array($processorConfiguration['dataProcessing.'])) {
            $flexFormData = $this->processAdditionalDataProcessors($flexFormData, $processorConfiguration);
        }

        $processedData[$targetVariableName] = $flexFormData;

        return $processedData;
    }

    /**
     * Recursively process sub processors of a data processor
     *
     * @param array $data
     * @param array $processorConfiguration
     * @return array
     */
    public function processAdditionalDataProcessors(array $data, array $processorConfiguration): array
    {
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->start([$data]);
        return GeneralUtility::makeInstance(ContentDataProcessor::class)->process(
            $contentObjectRenderer,
            $processorConfiguration,
            $data
        );
    }
}
