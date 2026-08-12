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

use TYPO3\CMS\Core\Schema\Exception\FieldTypeNotAvailableException;
use TYPO3\CMS\Core\Schema\Exception\UndefinedFieldException;
use TYPO3\CMS\Core\Schema\Field\FieldCollection;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Struct\WizardStep;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class builds a TCA schema for a given TCA
 * This is done the following way:
 *
 *  As the relations need to be fully resolved first (done in RelationMapBuilder),
 *  the TcaSchemaFactory does two-step processing:
 *  1a. Traverse TCA (and, if type=flex parts are registered), and find relations of all TCA parts pointing to each other
 *  1b. Store this in a RelationMap object as a multi-level array.
 *  ---
 *  2. Loop through all TCA tables one by one
 *  2a. Build field objects for the TCA table.
 *  2b. Detect "sub schemata" (if [ctrl][type] is set), build the field objects only relevant for the sub-schema
 *  2c. Build the sub-schema
 *  2d. Build the main schema
 *
 * @internal Not part of TYPO3's API.
 */
final readonly class TcaSchemaBuilder
{
    public function __construct(
        private RelationMapBuilder $relationMapBuilder,
        private FieldTypeFactory $fieldTypeFactory,
    ) {}

    public function buildFromStructure(array $fullTca): SchemaCollection
    {
        $schemata = [];
        ksort($fullTca);
        $relationMap = $this->relationMapBuilder->buildFromStructure($fullTca);
        foreach (array_keys($fullTca) as $table) {
            $schemata[$table] = $this->build($table, $fullTca, $relationMap);
        }
        return new SchemaCollection($schemata);
    }

    /**
     * Builds a schema from a TCA table including all of its sub-schemata.
     *
     * First builds all fields, then the schema and attach the fields, so all parts can never be
     * modified (except for adding sub-schema - this might be removed at some point hopefully).
     *
     * Then, resolves the sub-schema and the relevant fields for there with columnsOverrides taken into
     * account.
     *
     * As it is crucial to understand, parts such as FlexForms (incl. Sheet, SectionContainers and their Fields)
     * NEED to be resolved first, because they need to be attached.
     */
    private function build(string $schemaName, array $fullTca, RelationMap $relationMap): TcaSchema
    {
        // Collect all fields
        $allFields = [];
        $schemaDefinition = $fullTca[$schemaName];
        foreach ($schemaDefinition['columns'] ?? [] as $fieldName => $fieldConfiguration) {
            try {
                $field = $this->fieldTypeFactory->createFieldType(
                    $fieldName,
                    $fieldConfiguration,
                    $schemaName,
                    $relationMap
                );
            } catch (FieldTypeNotAvailableException) {
                continue;
            }

            $allFields[$fieldName] = $field;
        }

        $schemaConfiguration = $schemaDefinition['ctrl'] ?? [];
        // Store "palettes" information into the ctrl section
        if (is_array($schemaDefinition['palettes'] ?? null)) {
            $schemaConfiguration['palettes'] = $schemaDefinition['palettes'];
        }

        // Resolve all sub schemas and collect their fields while keeping the system fields
        $subSchemata = [];
        if (isset($schemaDefinition['ctrl']['type'])) {
            foreach ($schemaDefinition['types'] ?? [] as $subSchemaName => $subSchemaDefinition) {
                $subSchemaName = (string)$subSchemaName;
                $subSchemaFields = [];
                $subSchemaFieldInformation = $this->findRelevantFieldsForSubSchema($schemaDefinition, $subSchemaName);
                foreach ($subSchemaFieldInformation as $fieldName => $subSchemaFieldConfiguration) {
                    try {
                        $field = $this->fieldTypeFactory->createFieldType(
                            $fieldName,
                            $subSchemaFieldConfiguration,
                            $subSchemaName,
                            // Interesting side-note: The relations stay the same as it is not possible to modify
                            // this for a subtype.
                            $relationMap,
                            $schemaName
                        );
                    } catch (FieldTypeNotAvailableException) {
                        continue;
                    }

                    $subSchemaFields[$fieldName] = $field;
                }

                $subSchemaFieldCollection = new FieldCollection($subSchemaFields);
                $subSchemata[$subSchemaName] = new TcaSchema(
                    $schemaName . '.' . $subSchemaName,
                    $subSchemaFieldCollection,
                    // Merge parts from the "types" section into the ctrl section of the main schema
                    array_replace_recursive($schemaConfiguration, $subSchemaDefinition),
                    null,
                    [],
                    $this->getOrderedWizardSteps($subSchemaDefinition, $subSchemaFieldCollection, $subSchemaName)
                );
            }
        } elseif (($schemaDefinition['types'] ?? []) !== []) {
            // Merge parts from the "types" section into the ctrl section of the main schema
            $schemaConfiguration = array_replace_recursive(
                $schemaConfiguration,
                array_first($schemaDefinition['types'])
            );
        }
        return new TcaSchema(
            $schemaName,
            new FieldCollection($allFields),
            $schemaConfiguration,
            $subSchemata !== [] ? new SchemaCollection($subSchemata) : null,
            $relationMap->getPassiveRelations($schemaName)
        );
    }

    private function findRelevantFieldsForSubSchema(array $tcaForTable, string $subSchemaName): array
    {
        $fields = [];
        if (!isset($tcaForTable['types'][$subSchemaName])) {
            throw new \InvalidArgumentException('Subschema "' . $subSchemaName . '" not found.', 1715269835);
        }
        $subSchemaConfig = $tcaForTable['types'][$subSchemaName];
        $showItemArray = GeneralUtility::trimExplode(',', $subSchemaConfig['showitem'] ?? '', true);
        foreach ($showItemArray as $aShowItemFieldString) {
            [$fieldName, $fieldLabel, $paletteName] = GeneralUtility::trimExplode(';', $aShowItemFieldString . ';;;');
            if ($fieldName === '--div--') {
                // tabs are not of interest here
                continue;
            }
            if ($fieldName === '--palette--' && !empty($paletteName)) {
                // showitem references to a palette field. unpack the palette and process
                // label overrides that may be in there.
                if (!isset($tcaForTable['palettes'][$paletteName]['showitem'])) {
                    // No palette with this name found? Skip it.
                    continue;
                }
                $palettesArray = GeneralUtility::trimExplode(
                    ',',
                    $tcaForTable['palettes'][$paletteName]['showitem']
                );
                foreach ($palettesArray as $aPalettesString) {
                    [$fieldName, $fieldLabel] = GeneralUtility::trimExplode(';', $aPalettesString . ';;');
                    if (isset($tcaForTable['columns'][$fieldName])) {
                        $fields[$fieldName] = $this->getFinalFieldConfiguration($fieldName, $tcaForTable, $subSchemaConfig, $fieldLabel);
                    }
                }
            } elseif (isset($tcaForTable['columns'][$fieldName])) {
                $fields[$fieldName] = $this->getFinalFieldConfiguration($fieldName, $tcaForTable, $subSchemaConfig, $fieldLabel);
            }
        }
        return $fields;
    }

    /**
     * Handle label and possible columnsOverrides
     */
    private function getFinalFieldConfiguration(string $fieldName, array $schemaConfiguration, array $subSchemaConfiguration, ?string $fieldLabel): array
    {
        $fieldConfiguration = $schemaConfiguration['columns'][$fieldName] ?? [];
        if (isset($subSchemaConfiguration['columnsOverrides'][$fieldName])) {
            $fieldConfiguration = array_replace_recursive($fieldConfiguration, $subSchemaConfiguration['columnsOverrides'][$fieldName]);
        }
        if (!empty($fieldLabel)) {
            $fieldConfiguration['label'] = $fieldLabel;
        }
        return $fieldConfiguration;
    }

    /**
     * @throws UndefinedFieldException
     */
    private function getOrderedWizardSteps(array $schemaDefinition, FieldCollection $fieldCollection, string $subSchemaName): array
    {
        if (!isset($schemaDefinition['wizardSteps'])) {
            return [];
        }

        $wizardSteps = [];
        $dependencyOrderingService = GeneralUtility::makeInstance(DependencyOrderingService::class);
        $orderedWizardSteps = $dependencyOrderingService->orderByDependencies($schemaDefinition['wizardSteps']);

        foreach ($orderedWizardSteps as $stepIdentifier => $wizardStep) {
            $fields = $wizardStep['fields'] ?? throw new \UnexpectedValueException('Wizard step fields are missing', 1774356281);
            $undefinedFields = array_diff($fields, $fieldCollection->getNames());
            if ($undefinedFields !== []) {
                throw new UndefinedFieldException(sprintf('Wizard step fields: "%s" are not configured in TCA schema: "%s"', implode(',', $undefinedFields), $subSchemaName), 1774355993);
            }

            $wizardFieldCollection = array_filter(iterator_to_array($fieldCollection), fn(FieldTypeInterface $field) => in_array($field->getName(), $fields));
            $wizardSteps[$stepIdentifier] = new WizardStep($stepIdentifier, $wizardStep['title'] ?? '', new FieldCollection($wizardFieldCollection));
        }
        return $wizardSteps;
    }
}
