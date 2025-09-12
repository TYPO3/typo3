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

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentDataProcessor;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;
use TYPO3\CMS\Frontend\Resource\FileCollector;

/**
 * This data processor converts the XML structure of a given FlexForm field
 * into a fluid readable array.
 *
 * Options:
 * fieldName      - The name of the field containing the FlexForm to be converted
 * references     - A key / value list for fields with file references to process
 * dataProcessing - Additional sub DataProcessors to process
 * as             - The variable, the generated array should be assigned to
 *
 * Example of a minimal TypoScript configuration, which processes the field
 * `pi_flexform` and assigns the array to the `flexFormData` variable:
 *
 * 10 = TYPO3\CMS\Frontend\DataProcessing\FlexFormProcessor
 *
 * Example of an advanced TypoScript configuration, which processes the field
 * `my_flexform_field`, resolves its FAL references and assigns the array to the
 * `myOutputVariable` variable:
 *
 * 10 = TYPO3\CMS\Frontend\DataProcessing\FlexFormProcessor
 * 10 {
 *   fieldName = my_flexform_field
 *   references {
 *       my_flex_form_group.my_flex_form_field = my_field_reference
 *   }
 *   dataProcessing {
 *     10 = TYPO3\CMS\Frontend\DataProcessing\FilesProcessor
 *     10 {
 *        references.fieldName = media
 *     }
 *   }
 *   as = myOutputVariable
 * }
 */
#[Autoconfigure(public: true)]
readonly class FlexFormProcessor implements DataProcessorInterface
{
    public function __construct(
        private FlexFormService $flexFormService,
    ) {}

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
        $flexFormData = $this->flexFormService->convertFlexFormContentToArray($originalValue);

        // Process FAL references
        if (isset($processorConfiguration['references.']) && is_array($processorConfiguration['references.'])) {
            $this->processFileReferences($cObj, $flexFormData, $processorConfiguration['references.']);
        }

        // Process additional DataProcessors
        if (isset($processorConfiguration['dataProcessing.']) && is_array($processorConfiguration['dataProcessing.'])) {
            // @todo: It looks as if data processors should retrieve the current request from the outside,
            //        this would avoid $cObj->getRequest() here.
            $flexFormData = $this->processAdditionalDataProcessors($flexFormData, $processorConfiguration, $cObj->getRequest());
        }

        // Set the target variable
        $targetVariableName = $cObj->stdWrapValue('as', $processorConfiguration, 'flexFormData');
        $processedData[$targetVariableName] = $flexFormData;

        return $processedData;
    }

    /**
     * Recursively process FAL references and replace them by FAL objects.
     */
    protected function processFileReferences(ContentObjectRenderer $cObj, array &$data, array $fields): void
    {
        foreach ($fields as $key => $field) {
            $key = rtrim($key, '.');

            if (!isset($data[$key])) {
                continue;
            }
            if (is_array($field)) {
                $this->processFileReferences($cObj, $data[$key], $field);
            } else {
                $fileCollector = GeneralUtility::makeInstance(FileCollector::class);
                $fileCollector->addFilesFromRelation($cObj->getCurrentTable(), $field, $cObj->data);

                $data[$key] = $fileCollector->getFiles();
            }
        }
    }

    /**
     * Recursively process sub processors of a data processor
     */
    protected function processAdditionalDataProcessors(array $data, array $processorConfiguration, ServerRequestInterface $request): array
    {
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        $contentObjectRenderer->start([$data], '');
        return GeneralUtility::makeInstance(ContentDataProcessor::class)->process(
            $contentObjectRenderer,
            $processorConfiguration,
            $data
        );
    }
}
