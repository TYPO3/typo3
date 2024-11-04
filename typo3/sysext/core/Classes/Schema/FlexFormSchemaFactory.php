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

namespace TYPO3\CMS\Core\Schema;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Schema\Field\FieldCollection;
use TYPO3\CMS\Core\Schema\Field\FlexFormFieldType;
use TYPO3\CMS\Core\Schema\Struct\FlexSectionContainer;
use TYPO3\CMS\Core\Schema\Struct\FlexSheet;

/**
 * Parses all possibles schemas of all sheets of a field.
 *
 * @internal This is an experimental implementation and might change until TYPO3 v13 LTS
 */
#[Autoconfigure(public: true, shared: true)]
final readonly class FlexFormSchemaFactory
{
    public function __construct(
        protected FlexFormTools $flexFormTools,
        protected FieldTypeFactory $fieldTypeFactory
    ) {}

    /**
     * Currently this mixes Schema and Record Information, and could be handled in a cleaner way.
     * This method signature will most likely change.
     */
    public function getSchemaForRecord(RawRecord $record, FlexFormFieldType $field, RelationMap $relationMap): ?FlexFormSchema
    {
        $allSchemata = $this->createSchemataForFlexField(
            $field->getConfiguration(),
            $record->getMainType(),
            $field->getName(),
            $relationMap
        );

        $structIdentifierParts = [
            $record->getMainType(),
            $field->getName(),
        ];
        $alternativeIdentifierParts = $structIdentifierParts;
        if ($record->getRecordType()) {
            $structIdentifierParts[] = $record->getRecordType();
            $alternativeIdentifierParts[] = '*,' . $record->getRecordType();
        }

        $structIdentifier = implode('/', $structIdentifierParts);
        $alternativeIdentifier = implode('/', $alternativeIdentifierParts);

        foreach ($allSchemata as $schema) {
            if ($schema->getName() === $structIdentifier || $schema->getName() === $alternativeIdentifier) {
                return $schema;
            }
        }
        return null;
    }

    /**
     * @return FlexFormSchema[]
     */
    protected function createSchemataForFlexField(array $tcaConfig, string $tableName, string $fieldName, RelationMap $relationMap): array
    {
        // Create schema for each possibility we have
        $flexSchemas = [];
        foreach ($tcaConfig['ds'] as $dataStructureKey => $dataStructure) {
            $dataStructureIdentifier = [
                'type' => 'tca',
                'tableName' => $tableName,
                'fieldName' => $fieldName,
                'dataStructureKey' => $dataStructureKey,
            ];
            $flexStructureAsArray = $this->flexFormTools->parseDataStructureByIdentifier(json_encode($dataStructureIdentifier));
            // Create all fields, then the sheets with the fields in it, then the actual FlexFormSchema
            $sheets = [];
            foreach ($flexStructureAsArray['sheets'] as $sheetIdentifier => $sheetData) {
                $fields = [];
                $sections = [];
                foreach ($sheetData['ROOT']['el'] as $flexFieldName => $flexFieldConfig) {
                    $fieldIdentifier = $sheetIdentifier . '/' . $flexFieldName;
                    if (($flexFieldConfig['type'] ?? '') === 'array' && ($flexFieldConfig['section'] ?? false)) {
                        // We are inside a section, now loop over the section containers
                        $sectionContainers = [];
                        foreach ($flexFieldConfig['el'] ?? [] as $sectionContainerIdentifier => $sectionContainerDetails) {
                            // Sections can only have section containers
                            if (($sectionContainerDetails['type'] ?? '') !== 'array') {
                                continue;
                            }
                            $sectionFieldIdentifier = $fieldIdentifier . '/' . $sectionContainerIdentifier;
                            $fieldsInSectionContainer = [];
                            $sectionContainerTitle = $sectionContainerDetails['title'] ?? '';
                            // Collect all elements within this section container
                            foreach ($sectionContainerDetails['el'] as $fieldNameInSectionContainer => $sectionContainerConfig) {
                                $fieldsInSectionContainer[$fieldNameInSectionContainer] = $this->fieldTypeFactory->createFieldType(
                                    $fieldNameInSectionContainer,
                                    $sectionContainerConfig ?? [],
                                    $tableName,
                                    $relationMap,
                                    null,
                                    $fieldName
                                );
                            }
                            $sectionContainers[$sectionFieldIdentifier] = new FlexSectionContainer(
                                $sectionFieldIdentifier,
                                $sectionContainerTitle,
                                '',
                                new FieldCollection($fieldsInSectionContainer)
                            );
                        }
                        $sections[$fieldIdentifier] = $sectionContainers;
                    } else {
                        $fields[$fieldIdentifier] = $this->fieldTypeFactory->createFieldType(
                            $fieldIdentifier,
                            $flexFieldConfig ?? [],
                            $tableName,
                            $relationMap,
                            null,
                            $fieldName
                        );
                    }
                }
                $fields = new FieldCollection($fields);
                $sheets[$sheetIdentifier] = new FlexSheet(
                    $sheetIdentifier,
                    $sheetData['ROOT']['sheetTitle'] ?? '',
                    $sheetData['ROOT']['sheetDescription'] ?? '',
                    $fields,
                    $sections
                );
            }
            $flexSchemas[] = new FlexFormSchema($tableName . '/' . $fieldName . '/' . $dataStructureKey, $sheets);
        }
        return $flexSchemas;
    }
}
