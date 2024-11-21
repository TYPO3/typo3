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

namespace TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGenerator;

use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGeneratorInterface;
use TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGeneratorResolver;
use TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGeneratorResolverAwareInterface;
use TYPO3\CMS\Styleguide\TcaDataGenerator\GeneratorNotFoundException;

/**
 * Generate data for type=flex fields
 *
 * @internal
 */
final class TypeFlex extends AbstractFieldGenerator implements FieldGeneratorInterface, FieldGeneratorResolverAwareInterface
{
    protected array $matchArray = [
        'fieldConfig' => [
            'config' => [
                'type' => 'flex',
            ],
        ],
    ];

    private ?FieldGeneratorResolver $fieldGeneratorResolver = null;

    public function __construct(
        private readonly FlexFormTools $flexFormTools,
        private readonly TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    public function setFieldGeneratorResolver(FieldGeneratorResolver $fieldGeneratorResolver): void
    {
        $this->fieldGeneratorResolver = $fieldGeneratorResolver;
    }

    public function generate(array $data): string
    {
        if ($this->fieldGeneratorResolver === null) {
            throw new \RuntimeException('Not initialized. Call setFieldGeneratorResolver() first.', 1726780937);
        }
        $schema = $this->tcaSchemaFactory->get($data['tableName']);

        // Parse the flex form
        $structureIdentifier = $this->flexFormTools->getDataStructureIdentifier($data['fieldConfig'], $data['tableName'], $data['fieldName'], [], $schema);
        $dataStructureArray = $this->flexFormTools->parseDataStructureByIdentifier($structureIdentifier, $schema);

        // Loop through this xml mess and call a generator for each found field
        $aFlexFieldData = $data;
        $resultArray = [];
        if (isset($dataStructureArray['sheets']) && is_array($dataStructureArray['sheets'])) {
            foreach ($dataStructureArray['sheets'] as $sheetName => $sheetArray) {
                if (isset($sheetArray['ROOT']['el']) && is_array($sheetArray['ROOT']['el'])) {
                    foreach ($sheetArray['ROOT']['el'] as $sheetElementName => $sheetElementArray) {
                        // Container section
                        if (isset($sheetElementArray['type']) && $sheetElementArray['type'] === 'array'
                            && isset($sheetElementArray['section']) && $sheetElementArray['section'] == 1
                            && isset($sheetElementArray['el']) && is_array($sheetElementArray['el'])
                        ) {
                            $containerCounter = 0;
                            foreach ($sheetElementArray['el'] as $containerName => $containerElementArray) {
                                if (!isset($containerElementArray['el']) || !is_array($containerElementArray['el'])) {
                                    continue;
                                }
                                $containerCounter++;
                                foreach ($containerElementArray['el'] as $containerSingleElementName => $containerSingleElementArray) {
                                    $aFlexFieldData['fieldName'] = $containerSingleElementName;
                                    $aFlexFieldData['fieldConfig'] = $containerSingleElementArray;
                                    try {
                                        $generator = $this->fieldGeneratorResolver->resolve($aFlexFieldData);
                                        $flexFieldValue = $generator->generate($aFlexFieldData);
                                        $resultArray['data'][$sheetName]['lDEF']
                                            [$sheetElementName]['el']
                                            [$containerCounter][$containerName]['el']
                                            [$containerSingleElementName]['vDEF'] = $flexFieldValue;
                                    } catch (GeneratorNotFoundException $e) {
                                        // No op if no matching generator was found
                                    }
                                    // Field handled, skip rest
                                    continue;
                                }
                            }
                        } else {
                            // Casual field
                            $aFlexFieldData['fieldName'] = $sheetElementName;
                            $aFlexFieldData['fieldConfig'] = $sheetElementArray;
                            try {
                                $generator = $this->fieldGeneratorResolver->resolve($aFlexFieldData);
                                $flexFieldValue = $generator->generate($aFlexFieldData);
                                $resultArray['data'][$sheetName]['lDEF'][$sheetElementName]['vDEF'] = $flexFieldValue;
                            } catch (GeneratorNotFoundException $e) {
                                // No op if no matching generator was found
                            }
                        }
                    }
                }
            }
        } elseif (isset($dataStructureArray['ROOT']['el']) && is_array($dataStructureArray['ROOT']['el'])) {
            foreach ($dataStructureArray['ROOT']['el'] as $elementName => $elementArray) {
                $aFlexFieldData['fieldName'] = $elementName;
                $aFlexFieldData['fieldConfig'] = $elementArray;
                try {
                    $generator = $this->fieldGeneratorResolver->resolve($aFlexFieldData);
                    $flexFieldValue = $generator->generate($aFlexFieldData);
                    $resultArray['data']['sDEF']['lDEF'][$elementName]['vDEF'] = $flexFieldValue;
                } catch (GeneratorNotFoundException $e) {
                    // No op if no matching generator was found
                }
            }
        }

        // Get string representation of result via FlexFormTools
        $resultString = '';
        if (!empty($resultArray)) {
            $resultString = $this->flexFormTools->flexArray2Xml($resultArray);
        }

        return $resultString;
    }
}
