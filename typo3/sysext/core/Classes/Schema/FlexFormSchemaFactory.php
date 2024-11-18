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
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidSinglePointerFieldException;
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
        try {
            $dataStructureIdentifier = $this->flexFormTools->getDataStructureIdentifier(
                ['config' => $field->getConfiguration()],
                $record->getMainType(),
                $field->getName(),
                $record->toArray()
            );
        } catch (InvalidSinglePointerFieldException) {
            return null;
        }

        $resolvedDataStructure = $this->flexFormTools->parseDataStructureByIdentifier($dataStructureIdentifier);

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
                            $fieldsInSectionContainer[$fieldNameInSectionContainer] = $this->fieldTypeFactory->createFieldType(
                                $fieldNameInSectionContainer,
                                $sectionContainerConfig ?? [],
                                $record->getMainType(),
                                $relationMap,
                                null,
                                $field->getName()
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
                        $record->getMainType(),
                        $relationMap,
                        null,
                        $field->getName()
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
        return new FlexFormSchema($dataStructureIdentifier, $sheets);
    }
}
