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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Event\CacheWarmupEvent;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Schema\Exception\FieldTypeNotAvailableException;
use TYPO3\CMS\Core\Schema\Exception\UndefinedSchemaException;
use TYPO3\CMS\Core\Schema\Field\FieldCollection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Create Schema objects from information stored in TCA.
 *
 * A TcaSchema contains:
 * - a list of all fields as defined in [columns]
 * - a list of "capabilities" (parts defined in the [ctrl] section)
 * - a list of sub-schemata (if there is a [ctrl][type] definition, then sub-schemata are instances of TcaSchema itself again)
 * - a list of possible relations of other schemata pointing to this schema ("Passive Relations")
 *
 * As the relations need to be fully resolved first (done in RelationMapBuilder),
 * the TcaSchemaFactory does two-step processing:
 * 1a. Traverse TCA (and, if type=flex parts are registered), and find relations of all TCA parts pointing to each other
 * 1b. Store this in a RelationMap object as a multi-level array.
 * ---
 * 2. Loop through all TCA tables one by one
 * 2a. Build field objects for the TCA table.
 * 2b. Detect "sub schemata" (if [ctrl][type] is set), build the field objects only relevant for the sub-schema
 * 2c. Build the sub-schema
 * 2d. Build the main schema
 *
 * This is done that way to ensure that all objects can not be modified anymore, and we have a complete
 * representation of the data structures available.
 *
 * @internal This is an experimental implementation and might change until TYPO3 v13 LTS
 */
#[Autoconfigure(public: true, shared: true)]
class TcaSchemaFactory
{
    protected array $schemata = [];

    public function __construct(
        protected readonly RelationMapBuilder $relationMapBuilder,
        protected readonly FieldTypeFactory $fieldTypeFactory,
        #[Autowire(expression: 'service("package-dependent-cache-identifier").withPrefix("TcaSchema").toString()')]
        protected readonly string $cacheIdentifier,
        #[Autowire(service: 'cache.core')]
        protected readonly PhpFrontend $cache,
    ) {}

    /**
     * Get a schema from the loaded TCA. Ensure to check for a schema with ->has() before
     * calling ->get().
     */
    public function get(string $schemaName): TcaSchema
    {
        if (!$this->has($schemaName)) {
            throw new UndefinedSchemaException('No TCA schema exists for the name "' . $schemaName . '".', 1661540376);
        }
        if (str_contains($schemaName, '.')) {
            [$mainSchema, $subSchema] = explode('.', $schemaName, 2);
            return $this->get($mainSchema)->getSubSchema($subSchema);
        }
        return $this->schemata[$schemaName];
    }

    /**
     * Checks if a schema exists, does not build the schema if not needed, thus it's very slim
     * and only creates a schema if a sub-schema is requested.
     */
    public function has(string $schemaName): bool
    {
        if (str_contains($schemaName, '.')) {
            [$mainSchema, $subSchema] = explode('.', $schemaName, 2);
            if (!$this->has($mainSchema)) {
                return false;
            }
            return $this->get($mainSchema)->hasSubSchema($subSchema);
        }
        return isset($this->schemata[$schemaName]);
    }

    /**
     * Returns all main schemata
     *
     * @return SchemaCollection<string, TcaSchema>
     */
    public function all(): SchemaCollection
    {
        return new SchemaCollection($this->schemata);
    }

    /**
     * Only used for functional tests, which override TCA on the fly for specific test cases.
     * Modifying TCA other than in Configuration/TCA/Overrides must be avoided in production code.
     *
     * @internal only used for TYPO3 Core internally, never use it in public!
     */
    public function rebuild(array $fullTca): void
    {
        $this->schemata = [];
        $relationMap = $this->relationMapBuilder->buildFromStructure($fullTca);
        foreach (array_keys($fullTca) as $table) {
            $this->build($table, $fullTca, $relationMap);
        }
    }

    /**
     * Load TCA and populate all schema - throws away existing schema if $force is set.
     *
     * @internal only used for TYPO3 Core internally, never use it in public!
     */
    public function load(array $tca, bool $force = false): void
    {
        if (!$force && $this->schemata !== []) {
            return;
        }
        if (!$force && $this->cache->has($this->cacheIdentifier)) {
            $this->schemata = $this->cache->require($this->cacheIdentifier);
            return;
        }

        $this->rebuild($tca);
        $this->cache->set($this->cacheIdentifier, 'return ' . var_export($this->schemata, true) . ';');
    }

    /**
     * Builds a schema from a TCA table, if a sub-schema is requested, it will build the main schema and
     * all sub-schematas first.
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
    protected function build(string $schemaName, array $fullTca, RelationMap $relationMap): TcaSchema
    {
        if (str_contains($schemaName, '.')) {
            [$mainSchema, $subSchema] = explode('.', $schemaName, 2);
            $mainSchema = $this->build($mainSchema, $fullTca, $relationMap);
            return $mainSchema->getSubSchema($subSchema);
        }

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

                $subSchemata[$subSchemaName] = new TcaSchema(
                    $schemaName . '.' . $subSchemaName,
                    new FieldCollection($subSchemaFields),
                    // Merge parts from the "types" section into the ctrl section of the main schema
                    array_replace_recursive($schemaConfiguration, $subSchemaDefinition),
                );
            }
        }
        $schema = new TcaSchema(
            $schemaName,
            new FieldCollection($allFields),
            $schemaConfiguration,
            $subSchemata !== [] ? new SchemaCollection($subSchemata) : null,
            $relationMap->getPassiveRelations($schemaName)
        );

        $this->schemata[$schemaName] = $schema;
        return $schema;
    }

    protected function findRelevantFieldsForSubSchema(array $tcaForTable, string $subSchemaName): array
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
     * Handles the label and possible columnsOverrides
     */
    protected function getFinalFieldConfiguration(string $fieldName, array $schemaConfiguration, array $subSchemaConfiguration, ?string $fieldLabel): array
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

    public function warmupCaches(CacheWarmupEvent $event): void
    {
        if ($event->hasGroup('system')) {
            $this->schemata = [];
            $this->load($GLOBALS['TCA'], true);
        }
    }
}
