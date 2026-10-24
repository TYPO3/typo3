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
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\AbstractInvalidDataStructureException;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Schema\Exception\FieldTypeNotAvailableException;
use TYPO3\CMS\Core\Schema\Exception\UndefinedSchemaException;
use TYPO3\CMS\Core\Schema\Field\FieldCollection;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Field\FlexFormFieldType;
use TYPO3\CMS\Core\Schema\Struct\FlexSectionContainer;
use TYPO3\CMS\Core\Schema\Struct\FlexSheet;

/**
 * Parses all possibles schemas of all sheets of a field.
 */
#[Autoconfigure(public: true, shared: true)]
final readonly class FlexFormSchemaFactory
{
    public function __construct(
        private FlexFormTools $flexFormTools,
        private FieldTypeFactory $fieldTypeFactory,
        private TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * Currently this mixes Schema and Record Information, and could be handled in a cleaner way.
     * This method signature will most likely change.
     *
     * The relation map is not evaluated, relations of the sheet fields are resolved from their configuration.
     */
    public function getSchemaForRecord(RawRecord $record, FlexFormFieldType $field, ?RelationMap $relationMap = null): ?FlexFormSchema
    {
        return $this->getSchemaForRow($record->getMainType(), $field, $record->toArray());
    }

    /**
     * Resolves the data structure of a flex field for a given database row, and creates
     * schema field types for all fields of its sheets, including their relations.
     *
     * Returns null if no valid data structure can be resolved for the row. Fields of
     * a type that is not available are omitted.
     */
    public function getSchemaForRow(string $tableName, FlexFormFieldType $field, array $row): ?FlexFormSchema
    {
        try {
            $schema = $this->tcaSchemaFactory->get($tableName);
            $dataStructureIdentifier = $this->flexFormTools->getDataStructureIdentifier(
                ['config' => $field->getConfiguration()],
                $tableName,
                $field->getName(),
                $row,
                $schema
            );
            $resolvedDataStructure = $this->flexFormTools->parseDataStructureByIdentifier($dataStructureIdentifier, $schema);
        } catch (AbstractInvalidDataStructureException|UndefinedSchemaException) {
            return null;
        }

        $sheets = [];
        foreach ($resolvedDataStructure['sheets'] ?? [] as $sheetIdentifier => $sheetData) {
            $fields = [];
            $sections = [];
            foreach ($sheetData['ROOT']['el'] ?? [] as $flexFieldName => $flexFieldConfig) {
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
                        foreach ($sectionContainerDetails['el'] ?? [] as $fieldNameInSectionContainer => $sectionContainerConfig) {
                            $sectionContainerField = $this->createSheetField($tableName, $field, $fieldNameInSectionContainer, $sectionContainerConfig ?? []);
                            if ($sectionContainerField !== null) {
                                $fieldsInSectionContainer[$fieldNameInSectionContainer] = $sectionContainerField;
                            }
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
                    $sheetField = $this->createSheetField($tableName, $field, $fieldIdentifier, $flexFieldConfig ?? []);
                    if ($sheetField !== null) {
                        $fields[$fieldIdentifier] = $sheetField;
                    }
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
        return new FlexFormSchema($dataStructureIdentifier, $sheets);
    }

    /**
     * Relations of a field within a data structure only depend on its own configuration,
     * and nothing can point to a field within a data structure. A relation map with this
     * one field is therefore all the field type needs to resolve its relations.
     */
    private function createSheetField(string $tableName, FlexFormFieldType $flexField, string $fieldName, array $fieldConfiguration): ?FieldTypeInterface
    {
        $relationMap = new RelationMap();
        $relationMap->add($tableName, $flexField->getName(), $fieldConfiguration['config'] ?? []);
        try {
            return $this->fieldTypeFactory->createFieldType(
                $fieldName,
                $fieldConfiguration,
                $tableName,
                $relationMap,
                null,
                $flexField->getName()
            );
        } catch (FieldTypeNotAvailableException) {
            return null;
        }
    }
}
