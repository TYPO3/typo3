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

namespace TYPO3\CMS\Core\Configuration\FlexForm;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureIdentifierInitializedEvent;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;
use TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureIdentifierInitializedEvent;
use TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureParsedEvent;
use TYPO3\CMS\Core\Configuration\Event\BeforeTcaOverridesEvent;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidDataStructureException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidIdentifierException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidTcaException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidTcaSchemaException;
use TYPO3\CMS\Core\Configuration\Tca\TcaMigration;
use TYPO3\CMS\Core\Configuration\Tca\TcaPreparation;
use TYPO3\CMS\Core\Schema\Field\FlexFormFieldType;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Unified service class for TCA type="flex" operations.
 *
 * This service provides comprehensive FlexForm handling capabilities that work with both:
 * - TCA Schema objects (high-level, schema-aware operations)
 * - Raw TCA configuration arrays (low-level operations during schema building)
 *
 * Note: Using the raw TCA configuration is not recommended and only available to support
 * FlexFormTools during schema building. For extensions this might be the case on using
 * {@see BeforeTcaOverridesEvent} or {@see AfterTcaCompilationEvent}.
 *
 * Usage examples:
 * ```php
 * // With TCA Schema (typical application usage)
 * $flexFormTools->getDataStructureIdentifier($fieldTca, $table, $field, $row, $tcaSchema);
 *
 * // With raw TCA array (only during schema)
 * $flexFormTools->getDataStructureIdentifier($fieldTca, $table, $field, $row, $rawTcaArray);
 * ```
 *
 * The service automatically detects the input type and uses the appropriate resolution strategy.
 */
#[Autoconfigure(public: true)]
readonly class FlexFormTools
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private TcaMigration $tcaMigration,
        private TcaPreparation $tcaPreparation,
    ) {}

    /**
     * The method locates a specific data structure from given TCA and row combination
     * and returns an identifier string that can be handed around, and can be resolved
     * to a single data structure later without giving $row and $tca data again.
     *
     * Note: The returned syntax is meant to only specify the target location of the data structure.
     * It SHOULD NOT be abused and enriched with data from the record that is dealt with. For
     * instance, it is not allowed to add source record specific date like the "uid" or the "pid"!
     * If that is done, it is up to the hook consumer to take care of possible side effects, e.g. if
     * the DataHandler copies or moves records around and those references change.
     *
     * This method gets: Source data that influences the target location of a data structure
     * This method returns: Target specification of the data structure
     *
     * This method is "paired" with method parseDataStructureByIdentifier() that
     * will resolve the returned syntax again and returns the data structure itself.
     *
     * Both methods can be extended via events to return and accept additional
     * identifier strings if needed, and to transmit further information within the identifier strings.
     *
     * Important: The TCA for data structure definitions MUST be overridden by 'columnsOverrides'
     * as the "ds" config is a string, containing the data structure or a file pointer.
     *
     * Note: This method and the resolving methods below are well unit tested and document all
     * nasty details this way.
     *
     * @param array $fieldTca Full TCA of the field in question that has type=flex set
     * @param string $tableName The table name of the TCA field
     * @param string $fieldName The field name
     * @param array $row The data row
     * @param array|TcaSchema|null $schema Either be the Tca Schema object or raw TCA configuration. Only omit in
     *                                     case handling is done via events. Otherwise, this will throw an exception
     *                                     on resolving the default identifier {@see InvalidTcaSchemaException}.
     *                                     Using the raw TCA configuration is furthermore not recommended and only
     *                                     available to support FlexFormTools during schema building. For extensions
     *                                     this might be the case on using {@see BeforeTcaOverridesEvent} or
     *                                     {@see AfterTcaCompilationEvent}.
     *
     * @return string Identifier JSON string
     * @throws \RuntimeException If TCA is misconfigured
     * @throws InvalidTcaException
     */
    public function getDataStructureIdentifier(array $fieldTca, string $tableName, string $fieldName, array $row, array|TcaSchema|null $schema = null): string
    {
        $dataStructureIdentifier = $this->eventDispatcher
            ->dispatch(new BeforeFlexFormDataStructureIdentifierInitializedEvent($fieldTca, $tableName, $fieldName, $row))
            ->getIdentifier() ?? $this->getDefaultDataStructureIdentifier($tableName, $fieldName, $row, $schema);
        $dataStructureIdentifier = $this->eventDispatcher
            ->dispatch(new AfterFlexFormDataStructureIdentifierInitializedEvent($fieldTca, $tableName, $fieldName, $row, $dataStructureIdentifier))
            ->getIdentifier();
        return json_encode($dataStructureIdentifier, JSON_THROW_ON_ERROR);
    }

    /**
     * Parse a data structure identified by $identifier to the final data structure array.
     * This method is called after getDataStructureIdentifier(), finds the data structure
     * and returns it.
     *
     * Events allow to manipulate the find logic and to post process the data structure array.
     *
     * Important: The TCA for data structure definitions MUST be overridden by 'columnsOverrides'
     * as the "ds" config is a string, containing the data structure or a file pointer.
     *
     * After the data structure definition is found, the method resolves:
     * - FILE:EXT: prefix of the data structure itself - the ds is in a file
     * - FILE:EXT: prefix for sheets - if single sheets are in files
     * - Create a sDEF sheet if the data structure has non, yet.
     * - TCA Migration and Preparation is done for the resolved fields
     *
     * After that method is run, the data structure is fully resolved to an array,
     * and same base normalization is done: If the ds did not contain a sheet,
     * it will have one afterward as "sDEF".
     *
     * This method gets: Target specification of the data structure.
     * This method returns: The normalized data structure parsed to an array.
     *
     * @param string $identifier JSON string to find the data structure location
     * @param array|TcaSchema|null $schema Either be the Tca Schema object or raw TCA configuration. Only omit in
     *                                     case handling is done via events. Otherwise, this will throw an exception
     *                                     on resolving the default identifier {@see InvalidTcaSchemaException}.
     *                                     Using the raw TCA configuration is furthermore not recommended and only
     *                                     available to support FlexFormTools during schema building. For extensions
     *                                     this might be the case on using {@see BeforeTcaOverridesEvent} or
     *                                     {@see AfterTcaCompilationEvent}.
     *
     * @return array Parsed and normalized data structure
     * @throws InvalidIdentifierException
     * @throws InvalidTcaSchemaException
     * @throws InvalidDataStructureException
     */
    public function parseDataStructureByIdentifier(string $identifier, array|TcaSchema|null $schema = null): array
    {
        // Throw an exception for an empty string. This might be a valid use case for new
        // records in some situations, so this is catchable to give callers a chance to deal with that.
        if ($identifier === '') {
            throw new InvalidIdentifierException(
                'Empty string given to parseDataStructureByIdentifier(). This exception might '
                . ' be caught to handle some new record situations properly',
                1478100828
            );
        }
        $parsedIdentifier = json_decode($identifier, true);
        if (!is_array($parsedIdentifier) || $parsedIdentifier === []) {
            // If there is some identifier and it can't be decoded, programming error -> not catchable
            throw new \RuntimeException(
                'Identifier could not be decoded to an array.',
                1478345642
            );
        }
        $dataStructure = $this->eventDispatcher
            ->dispatch(new BeforeFlexFormDataStructureParsedEvent($parsedIdentifier))
            ->getDataStructure() ?? $this->getDefaultStructureForIdentifier($parsedIdentifier, $schema);
        $dataStructure = $this->convertDataStructureToArray($dataStructure);
        $dataStructure = $this->ensureDefaultSheet($dataStructure);
        $dataStructure = $this->resolveFileDirectives($dataStructure);
        $dataStructure = $this->checkMigratePrepareFlexTca($dataStructure);
        return $this->eventDispatcher
            ->dispatch(new AfterFlexFormDataStructureParsedEvent($dataStructure, $parsedIdentifier))
            ->getDataStructure();
    }

    /**
     * Clean up FlexForm value XML to hold only the values it may according to its Data Structure.
     * The order of tags will follow that of the data structure.
     *
     * @param array|TcaSchema $schema Main schema only, no sub schema! Using the raw TCA configuration is
     *                                furthermore not recommended and only available to support FlexFormTools
     *                                during schema building. For extensions this might be the case on
     *                                using {@see BeforeTcaOverridesEvent} or {@see AfterTcaCompilationEvent}.
     *
     * @internal Signature may change, for instance to split 'DS finding' and flexArray2Xml(),
     *           which would allow broader use of the method. It is currently consumed by
     *           cleanup:flexforms CLI only.
     */
    public function cleanFlexFormXML(string $table, string $field, array $row, array|TcaSchema $schema): string
    {
        if ((is_array($schema) && !isset($schema['columns'][$field]['config'])) || ($schema instanceof TcaSchema && !$schema->hasField($field)) || !isset($row[$field])) {
            throw new \RuntimeException('Can not clean up FlexForm XML for a column not declared in TCA or not in record.', 1697554398);
        }
        try {
            $fieldTca = is_array($schema) ? ['config' => $schema['columns'][$field]['config']] : ['config' => $schema->getField($field)->getConfiguration()];
            $dataStructureArray = $this->parseDataStructureByIdentifier($this->getDataStructureIdentifier($fieldTca, $table, $field, $row, $schema), $schema);
        } catch (InvalidIdentifierException) {
            // Data structure can not be resolved or parsed. Reset value to empty string.
            return '';
        }
        $valueArray = GeneralUtility::xml2array($row[$field]);
        if (!is_array($valueArray)) {
            // Current flex form values can not be parsed to an array. The entire thing is invalid. Reset to empty string.
            return '';
        }
        if (!is_array($dataStructureArray['sheets'] ?? false)) {
            // We might return empty string instead of throwing here, unsure.
            throw new \RuntimeException('Data structure should always declare at least one sheet', 1697555523);
        }
        $newValueArray = [];
        foreach ($dataStructureArray['sheets'] as $sheetKey => $sheetData) {
            foreach (($sheetData['ROOT']['el'] ?? []) as $sheetElementKey => $sheetElementData) {
                // For all elements allowed in Data Structure.
                if (($sheetElementData['type'] ?? '') === 'array') {
                    // This is a section.
                    if (!is_array($sheetElementData['el'] ?? false) || !is_array($valueArray['data'][$sheetKey]['lDEF'][$sheetElementKey]['el'] ?? false)) {
                        // No possible containers defined for this section in DS, or no values set for this section.
                        continue;
                    }
                    foreach ($valueArray['data'][$sheetKey]['lDEF'][$sheetElementKey]['el'] as $valueSectionContainerKey => $valueSectionContainers) {
                        // We have containers for this section in values.
                        if (!is_array($valueSectionContainers ?? false)) {
                            // Values don't validate to an array, skip.
                            continue;
                        }
                        foreach ($valueSectionContainers as $valueContainerType => $valueContainerElements) {
                            // For all value containers in this section.
                            if (!is_array($sheetElementData['el'][$valueContainerType]['el'] ?? false)) {
                                // There is no DS for this container type, skip.
                                continue;
                            }
                            foreach (array_keys($sheetElementData['el'][$valueContainerType]['el']) as $containerElement) {
                                // Container type of this value container exists in DS. Iterate DS container to pick allowed single elements.
                                if (isset($valueContainerElements['el'][$containerElement]['vDEF'])) {
                                    $newValueArray['data'][$sheetKey]['lDEF'][$sheetElementKey]['el'][$valueSectionContainerKey][$valueContainerType]['el'][$containerElement]['vDEF'] =
                                        $valueContainerElements['el'][$containerElement]['vDEF'];
                                }
                            }
                        }
                        if (isset($valueSectionContainers['_TOGGLE'])) {
                            // This was removed in TYPO3 v13, see #102551
                            unset($newValueArray['data'][$sheetKey]['lDEF'][$sheetElementKey]['el'][$valueSectionContainerKey]['_TOGGLE']);
                        }
                    }
                } elseif (isset($valueArray['data'][$sheetKey]['lDEF'][$sheetElementKey]['vDEF'])) {
                    // Not a section but a simple field. Keep value if set.
                    $newValueArray['data'][$sheetKey]['lDEF'][$sheetElementKey]['vDEF'] = $valueArray['data'][$sheetKey]['lDEF'][$sheetElementKey]['vDEF'];
                }
            }
        }
        return $this->flexArray2Xml($newValueArray);
    }

    /**
     * Convert FlexForm data array to XML
     *
     * @internal
     */
    public function flexArray2Xml(array $array): string
    {
        // Map the weird keys from the internal array to tags and attributes.
        $options = [
            'parentTagMap' => [
                'data' => 'sheet',
                'sheet' => 'language',
                'language' => 'field',
                'el' => 'field',
                'field' => 'value',
                'field:el' => 'el',
                'el:_IS_NUM' => 'section',
                'section' => 'itemType',
            ],
            'disableTypeAttrib' => 2,
        ];
        return '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>' . LF .
            GeneralUtility::array2xml($array, '', 0, 'T3FlexForms', 4, $options);
    }

    /**
     * Parses the flexForm XML string and converts it to an array.
     * The resulting array will be multidimensional, as a value "bla.blubb"
     * results in two levels, and a value "bla.blubb.bla" results in three levels.
     */
    public function convertFlexFormContentToArray(string $flexFormContent): array
    {
        $settings = [];
        $flexFormArray = GeneralUtility::xml2array($flexFormContent);
        $flexFormArray = $flexFormArray['data'] ?? [];
        foreach ($flexFormArray as $languages) {
            if (!is_array($languages['lDEF'] ?? false)) {
                continue;
            }
            foreach ($languages['lDEF'] as $valueKey => $valueDefinition) {
                if (!str_contains($valueKey, '.')) {
                    $settings[$valueKey] = $this->walkFlexFormNode($valueDefinition);
                } else {
                    $valueKeyParts = explode('.', $valueKey);
                    $currentNode = &$settings;
                    foreach ($valueKeyParts as $valueKeyPart) {
                        $currentNode = &$currentNode[$valueKeyPart];
                    }
                    if (is_array($valueDefinition)) {
                        if (array_key_exists('vDEF', $valueDefinition)) {
                            $currentNode = $valueDefinition['vDEF'];
                        } else {
                            $currentNode = $this->walkFlexFormNode($valueDefinition);
                        }
                    } else {
                        $currentNode = $valueDefinition;
                    }
                }
            }
        }
        return $settings;
    }

    /**
     * Parses the flexForm XML string and converts it to an array.
     * The resulting array will be multidimensional. Sheets are
     * respected to support property paths in multiple sheets.
     *
     * A value such as "settings.pageId" results in three levels:
     * "'sDEF' => ['settings' => ['pageId' => 123]]" and a value such
     * as "settings.storages.newsPid" results in four levels:
     * "'sDEF' => ['settings' => ['storages' => ['newsPid' => 123]]]"
     *
     * @param string $flexFormContent flexForm xml string
     */
    public function convertFlexFormContentToSheetsArray(string $flexFormContent): array
    {
        $settings = [];
        $flexFormArray = GeneralUtility::xml2array($flexFormContent);
        $flexFormArray = $flexFormArray['data'] ?? [];
        foreach ($flexFormArray as $sheetName => $sheet) {
            foreach ($sheet as $language => $fields) {
                if ($language !== 'lDEF') {
                    continue;
                }
                foreach ($fields as $valueKey => $valueDefinition) {
                    if (!str_contains($valueKey, '.')) {
                        $settings[$sheetName][$valueKey] = $this->walkFlexFormNode($valueDefinition);
                    } else {
                        $valueKeyParts = explode('.', $valueKey);
                        $currentNode = &$settings[$sheetName];
                        foreach ($valueKeyParts as $valueKeyPart) {
                            $currentNode = &$currentNode[$valueKeyPart];
                        }
                        if (is_array($valueDefinition)) {
                            if (array_key_exists('vDEF', $valueDefinition)) {
                                $currentNode = $valueDefinition['vDEF'];
                            } else {
                                $currentNode = $this->walkFlexFormNode($valueDefinition);
                            }
                        } else {
                            $currentNode = $valueDefinition;
                        }
                    }
                }
            }
        }
        return $settings;
    }

    /**
     * Finds data structure in TCA, defined in column config 'ds'
     *
     * fieldTca = [
     *     'config' => [
     *         'type' => 'flex',
     *         'ds' => '<T3DataStructure>...' OR 'FILE:...',
     *     ]
     * ]
     *
     * This method returns an array of the form:
     * [
     *     'type' => 'tca',
     *     'tableName' => $tableName,
     *     'fieldName' => $fieldName,
     *     'dataStructureKey' => $key,
     * ];
     *
     * Example:
     * [
     *     'type' => 'tca',
     *     'tableName' => 'tt_content',
     *     'fieldName' => 'pi_flexform',
     *     'dataStructureKey' => 'default',
     * ];
     *
     * In case the TCA table supports record types and the given $row uses a record type with a custom
     * data structure (via columnsOverrides) the record type is used as "dataStructureKey".
     *
     *  Example:
     *  [
     *      'type' => 'tca',
     *      'tableName' => 'tt_content',
     *      'fieldName' => 'pi_flexform',
     *      'dataStructureKey' => 'powermail_pi1',
     *  ];
     *
     * @return array Identifier as array, see example above
     * @throws InvalidTcaException
     * @throws InvalidTcaSchemaException
     */
    protected function getDefaultDataStructureIdentifier(string $tableName, string $fieldName, array $row, array|TcaSchema|null $schema = null): array
    {
        if ($schema === null) {
            throw new InvalidTcaSchemaException('Can not resolve default data structure without TCA.', 1753182123);
        }

        $defaultIdentifier = [
            'type' => 'tca',
            'tableName' => $tableName,
            'fieldName' => $fieldName,
            'dataStructureKey' => null,
        ];

        return is_array($schema)
            ? $this->getDataStructureIdentifierFromRawTca($schema, $tableName, $fieldName, $row, $defaultIdentifier)
            : $this->getDataStructureIdentifierFromTcaSchema($schema, $tableName, $fieldName, $row, $defaultIdentifier);
    }

    /**
     * Finds and returns the data structure from TCA -  defined in column config 'ds'
     *
     * fieldTca = [
     *     'config' => [
     *         'type' => 'flex',
     *         'ds' => '<T3DataStructure>...' OR 'FILE:...',
     *     ]
     * ]
     *
     * Based on an identifier, e.g.:
     * [
     *     'type' => 'tca',
     *     'tableName' => 'tt_content',
     *     'fieldName' => 'pi_flexform',
     *     'dataStructureKey' => 'default',
     * ];
     *
     * this method returns '<T3DataStructure>...' OR 'FILE:...'.
     *
     * In case the TCA table supports record types and the "dataStructureKey" points to a record type,
     * which is only the case if a record type defines a custom flex config (via columnsOverrides), this
     * custom data structure is returned.
     *
     * @return string resolved data structure
     * @throws InvalidTcaSchemaException
     */
    protected function getDefaultStructureForIdentifier(array $identifier, array|TcaSchema|null $schema = null): string
    {
        // For the default only type "tca" is handled. Custom types need to be handled by corresponding events.
        if (($identifier['type'] ?? '') !== 'tca') {
            throw new InvalidIdentifierException(
                'Identifier ' . json_encode($identifier) . ' could not be resolved',
                1478104554
            );
        }

        $tableName = (string)($identifier['tableName'] ?? '');
        $fieldName = (string)($identifier['fieldName'] ?? '');
        $dataStructureKey = (string)($identifier['dataStructureKey'] ?? '');

        if ($tableName === '' || $fieldName === '' || $dataStructureKey === '') {
            throw new \RuntimeException(
                'Incomplete "tca" based identifier: ' . json_encode($identifier),
                1478113471
            );
        }

        if ($schema === null) {
            throw new InvalidTcaSchemaException('Can not resolve default data structure without TCA.', 1753182125);
        }

        $dataStructure = is_array($schema)
            ? $this->resolveDataStructureFromRawTca($schema, $fieldName, $dataStructureKey)
            : $this->resolveDataStructureFromTcaSchema($schema, $tableName, $fieldName, $dataStructureKey);

        if ($dataStructure === '') {
            throw new InvalidIdentifierException(
                'Specified identifier ' . json_encode($identifier) . ' does not resolve to a valid data structure',
                1732199538
            );
        }

        return $dataStructure;
    }

    protected function convertDataStructureToArray(string|array $dataStructure): array
    {
        if (is_array($dataStructure)) {
            return $dataStructure;
        }
        // Resolve FILE: prefix pointing to a DS in a file
        if (str_starts_with(trim($dataStructure), 'FILE:')) {
            $fileName = substr(trim($dataStructure), 5);
            $file = GeneralUtility::getFileAbsFileName($fileName);
            if (empty($file) || !is_file($file)) {
                throw new InvalidIdentifierException(
                    'Data structure file "' . $fileName . '" could not be resolved to an existing file',
                    1478105826
                );
            }
            $dataStructure = (string)file_get_contents($file);
        }
        // Parse main structure
        $dataStructure = GeneralUtility::xml2array($dataStructure);
        // Throw if it still is not an array, probably because GeneralUtility::xml2array() failed.
        // This also may happen if artificial identifiers were constructed which don't resolve. The
        // flex form "exclude" access rights systems does that -> catchable
        if (!is_array($dataStructure)) {
            throw new InvalidIdentifierException(
                'Parse error: Data structure could not be resolved to a valid structure.',
                1478106090
            );
        }

        return $dataStructure;
    }

    /**
     * Ensures a data structure has a default sheet, and no duplicate data
     */
    protected function ensureDefaultSheet(array $dataStructure): array
    {
        if (isset($dataStructure['ROOT']) && isset($dataStructure['sheets'])) {
            throw new \RuntimeException(
                'Parsed data structure has both ROOT and sheets on top level. That is invalid.',
                1440676540
            );
        }
        if (isset($dataStructure['ROOT']) && is_array($dataStructure['ROOT'])) {
            $dataStructure['sheets']['sDEF']['ROOT'] = $dataStructure['ROOT'];
            unset($dataStructure['ROOT']);
        }
        return $dataStructure;
    }

    /**
     * Resolve FILE:EXT and EXT: for single sheets
     */
    protected function resolveFileDirectives(array $dataStructure): array
    {
        if (isset($dataStructure['sheets']) && is_array($dataStructure['sheets'])) {
            foreach ($dataStructure['sheets'] as $sheetName => $sheetStructure) {
                if (!is_array($sheetStructure)) {
                    if (str_starts_with(trim($sheetStructure), 'FILE:')) {
                        $file = GeneralUtility::getFileAbsFileName(substr(trim($sheetStructure), 5));
                    } else {
                        $file = GeneralUtility::getFileAbsFileName(trim($sheetStructure));
                    }
                    if ($file && @is_file($file)) {
                        $sheetStructure = GeneralUtility::xml2array((string)file_get_contents($file));
                    }
                }
                $dataStructure['sheets'][$sheetName] = $sheetStructure;
            }
        }
        return $dataStructure;
    }

    /**
     * Check for invalid flex form structures, migrate and prepare single fields.
     * @throws InvalidDataStructureException
     */
    private function checkMigratePrepareFlexTca(array $dataStructure): array
    {
        if (!is_array($dataStructure['sheets'] ?? null)) {
            return $dataStructure;
        }
        $newStructure = $dataStructure;
        foreach ($dataStructure['sheets'] as $sheetName => $sheetStructure) {
            if (!is_array($sheetStructure['ROOT']['el'])) {
                continue;
            }
            foreach ($sheetStructure['ROOT']['el'] as $sheetElementName => $sheetElementConfig) {
                if (!is_array($sheetElementConfig)) {
                    continue;
                }
                if (($sheetElementConfig['type'] ?? null) === 'array' xor ($sheetElementConfig['section'] ?? null) === '1') {
                    // Section element, but type=array without section=1 or vice versa is not ok
                    throw new InvalidDataStructureException(
                        'Broken data structure on field name ' . $sheetElementName . '. section without type or vice versa is not allowed',
                        1440685208
                    );
                }
                if (($sheetElementConfig['type'] ?? null) === 'array' && ($sheetElementConfig['section'] ?? null) === '1') {
                    // Section element
                    if (!is_array($sheetElementConfig['el'] ?? null)) {
                        continue;
                    }
                    foreach ($sheetElementConfig['el'] as $containerName => $containerConfig) {
                        if (!is_array($containerConfig['el'] ?? null)) {
                            continue;
                        }
                        foreach ($containerConfig['el'] as $containerElementName => $containerElementConfig) {
                            if (!is_array($containerElementConfig)) {
                                continue;
                            }
                            if (
                                // inline, file, group and category are always DB relations
                                in_array($containerElementConfig['config']['type'] ?? [], ['inline', 'file', 'folder', 'group', 'category'], true)
                                // MM is not allowed (usually type=select, otherwise the upper check should kick in)
                                || isset($containerElementConfig['config']['MM'])
                                // foreign_table is not allowed (usually type=select, otherwise the upper check should kick in)
                                || isset($containerElementConfig['config']['foreign_table'])
                            ) {
                                // Nesting types that use DB relations in container sections is not supported.
                                throw new InvalidDataStructureException(
                                    'Invalid flex form data structure on field name "' . $containerElementName . '" with element "' . $sheetElementName . '"'
                                    . ' in section container "' . $containerName . '": Nesting elements that have database relations in flex form'
                                    . ' sections is not allowed.',
                                    1458745468
                                );
                            }
                            if (($containerElementConfig['type'] ?? null) === 'array' && ($containerElementConfig['section'] ?? null) === '1') {
                                // Nesting sections is not supported. Throw an exception if configured.
                                throw new InvalidDataStructureException(
                                    'Invalid flex form data structure on field name "' . $containerElementName . '" with element "' . $sheetElementName . '"'
                                    . ' in section container "' . $containerName . '": Nesting sections in container elements'
                                    . ' sections is not allowed.',
                                    1458745712
                                );
                            }
                            $containerElementConfig = $this->migrateFlexField($containerElementName, $containerElementConfig);
                            $containerElementConfig = $this->prepareFlexField($containerElementName, $containerElementConfig);
                            $newStructure['sheets'][$sheetName]['ROOT']['el'][$sheetElementName]['el'][$containerName]['el'][$containerElementName] = $containerElementConfig;
                        }
                    }
                } else {
                    // Normal element
                    $sheetElementConfig = $this->migrateFlexField($sheetElementName, $sheetElementConfig);
                    $sheetElementConfig = $this->prepareFlexField($sheetElementName, $sheetElementConfig);
                    $newStructure['sheets'][$sheetName]['ROOT']['el'][$sheetElementName] = $sheetElementConfig;
                }
            }
        }
        return $newStructure;
    }

    private function migrateFlexField(string $fieldName, array $fieldConfig): array
    {
        // TcaMigration of this field. Call the TcaMigration and log any deprecations.
        $dummyTca = [
            'dummyTable' => [
                'columns' => [
                    $fieldName => $fieldConfig,
                ],
            ],
        ];
        $tcaProcessingResult = $this->tcaMigration->migrate($dummyTca);
        // Messages are reset on each `migrate()` execution
        $messages = $tcaProcessingResult->getMessages();
        if (!empty($messages)) {
            $context = 'FlexFormTools did an on-the-fly migration of a flex form data structure. This is deprecated and will be removed.'
                . ' Merge the following changes into the flex form definition "' . $fieldName . '":';
            array_unshift($messages, $context);
            trigger_error(implode(LF, $messages), E_USER_DEPRECATED);
        }
        return $tcaProcessingResult->getTca()['dummyTable']['columns'][$fieldName];
    }

    private function prepareFlexField(string $fieldName, array $fieldConfig): array
    {
        $dummyTca = [
            'dummyTable' => [
                'columns' => [
                    $fieldName => $fieldConfig,
                ],
            ],
        ];
        $preparedTca = $this->tcaPreparation->prepare($dummyTca, true);
        return $preparedTca['dummyTable']['columns'][$fieldName];
    }

    /**
     * Resolve data structure identifier from raw TCA configuration.
     */
    private function getDataStructureIdentifierFromRawTca(array $schema, string $tableName, string $fieldName, array $row, array $defaultIdentifier): array
    {
        // Check for record type specific configuration
        if (isset($schema['ctrl']['type'])) {
            $recordType = $row[$schema['ctrl']['type']] ?? '';
            if (isset($schema['types'][$recordType]) && ($fieldConfig = $this->getRecordTypeSpecificFieldConfig($schema, $recordType, $fieldName)) !== []) {
                if ($fieldConfig['config']['type'] === 'flex' && $fieldConfig['config']['ds'] !== '') {
                    $defaultIdentifier['dataStructureKey'] = $recordType;
                    return $defaultIdentifier;
                }

                throw new InvalidTcaException(
                    'TCA misconfiguration in table "' . $tableName . '" field "' . $fieldName . '" with record type "' . $recordType . '"'
                    . ' The field is either not configured as type="flex" or no valid data structure is defined for this record type.',
                    1751796941
                );
            }
        }

        // Fall back to base field configuration
        $baseField = $schema['columns'][$fieldName]['config'] ?? [];
        if (($baseField['type'] ?? '') === 'flex' && ($baseField['ds'] ?? '') !== '') {
            $defaultIdentifier['dataStructureKey'] = 'default';
            return $defaultIdentifier;
        }

        throw new InvalidTcaException(
            'TCA misconfiguration in table "' . $tableName . '" field "' . $fieldName . '" config section:'
            . ' The field is either not configured as type="flex" or no valid data structure is defined.',
            1732198005
        );
    }

    /**
     * Resolve data structure identifier from TCA Schema.
     */
    private function getDataStructureIdentifierFromTcaSchema(TcaSchema $schema, string $tableName, string $fieldName, array $row, array $defaultIdentifier): array
    {
        if ($schema->getName() !== $tableName) {
            throw new InvalidTcaSchemaException('Given Tca Schema does not match table ' . $tableName . ' from data structure identifier.', 1753182124);
        }

        // Check for record type specific configuration
        if ($schema->supportsSubSchema()) {
            $recordType = $row[$schema->getSubSchemaTypeInformation()->getFieldName()] ?? '';
            if ($schema->hasSubSchema($recordType) && ($subSchema = $schema->getSubSchema($recordType))->hasField($fieldName)) {
                $flexField = $subSchema->getField($fieldName);
                if ($flexField instanceof FlexFormFieldType && $flexField->getDataStructure() !== '') {
                    $defaultIdentifier['dataStructureKey'] = $recordType;
                    return $defaultIdentifier;
                }

                throw new InvalidTcaException(
                    'TCA misconfiguration in table "' . $tableName . '" field "' . $fieldName . '" with record type "' . $recordType . '"'
                    . ' The field is either not configured as type="flex" or no valid data structure is defined for this record type.',
                    1751796940
                );
            }
        }

        // Fall back to base field
        $baseField = $schema->getField($fieldName);
        if ($baseField instanceof FlexFormFieldType && $baseField->getDataStructure() !== '') {
            $defaultIdentifier['dataStructureKey'] = 'default';
            return $defaultIdentifier;
        }

        throw new InvalidTcaException(
            'TCA misconfiguration in table "' . $tableName . '" field "' . $fieldName . '" config section:'
            . ' The field is either not configured as type="flex" or no valid data structure is defined.',
            1732198004
        );
    }

    /**
     * Resolve data structure from raw TCA configuration.
     */
    private function resolveDataStructureFromRawTca(array $schema, string $fieldName, string $dataStructureKey): string
    {
        // Try record type specific configuration first
        if (isset($schema['ctrl']['type'], $schema['types'][$dataStructureKey])
            && ($fieldConfig = $this->getRecordTypeSpecificFieldConfig($schema, $dataStructureKey, $fieldName)) !== []
            && ($fieldConfig['config']['type'] ?? '') === 'flex'
            && is_string($fieldConfig['config']['ds'] ?? false)
        ) {
            return $fieldConfig['config']['ds'];
        }

        // Fall back to default configuration
        if ($dataStructureKey === 'default') {
            $baseField = $schema['columns'][$fieldName]['config'] ?? [];
            if (($baseField['type'] ?? '') === 'flex' && is_string($baseField['ds'] ?? false)) {
                return $baseField['ds'];
            }
        }

        return '';
    }

    /**
     * Resolve data structure from TCA Schema.
     */
    private function resolveDataStructureFromTcaSchema(TcaSchema $schema, string $table, string $field, string $dataStructureKey): string
    {
        if ($schema->getName() !== $table) {
            throw new InvalidTcaSchemaException('Given Tca Schema does not match table ' . $table . ' from data structure identifier.', 1753182126);
        }

        // Try record type specific configuration first
        if ($schema->supportsSubSchema()
            && $schema->hasSubSchema($dataStructureKey)
            && ($subSchema = $schema->getSubSchema($dataStructureKey))->hasField($field)
            && ($flexField = $subSchema->getField($field)) instanceof FlexFormFieldType
        ) {
            return $flexField->getDataStructure();
        }

        // Fall back to default configuration
        if ($dataStructureKey === 'default' && ($flexField = $schema->getField($field)) instanceof FlexFormFieldType) {
            return $flexField->getDataStructure();
        }

        return '';
    }

    /**
     * Returns the record type specific configuration, also already taking columnsOverrides into account.
     * In case the field is not defined for the record type, no configuration is returned.
     */
    protected function getRecordTypeSpecificFieldConfig(array $tcaForTable, string $recordType, string $fieldName): array
    {
        $recordTypeConfig = $tcaForTable['types'][$recordType];
        $showItemArray = GeneralUtility::trimExplode(',', $recordTypeConfig['showitem'] ?? '', true);
        foreach ($showItemArray as $aShowItemFieldString) {
            [$name, , $paletteName] = GeneralUtility::trimExplode(';', $aShowItemFieldString . ';;;');
            if ($name === '--div--') {
                continue;
            }
            if ($name === '--palette--' && !empty($paletteName)) {
                if (!isset($tcaForTable['palettes'][$paletteName]['showitem'])) {
                    continue;
                }
                $palettesArray = GeneralUtility::trimExplode(',', $tcaForTable['palettes'][$paletteName]['showitem']);
                foreach ($palettesArray as $aPalettesString) {
                    [$name] = GeneralUtility::trimExplode(';', $aPalettesString . ';;');
                    if ($name === $fieldName && isset($tcaForTable['columns'][$name])) {
                        return array_replace_recursive($tcaForTable['columns'][$name], $recordTypeConfig['columnsOverrides'][$name] ?? []);
                    }
                }
            } elseif ($name === $fieldName && isset($tcaForTable['columns'][$name])) {
                return array_replace_recursive($tcaForTable['columns'][$name], $recordTypeConfig['columnsOverrides'][$name] ?? []);
            }
        }
        return [];
    }

    /**
     * Parses a flexForm node recursively and takes care of sections etc.
     * Helper method of convertFlexFormContentToArray() and convertFlexFormContentToSheetsArray().
     */
    private function walkFlexFormNode(mixed $nodeArray): mixed
    {
        if (!is_array($nodeArray)) {
            return $nodeArray;
        }
        $result = [];
        foreach ($nodeArray as $nodeKey => $nodeValue) {
            if ($nodeKey === 'vDEF') {
                return $nodeValue;
            }
            if (in_array($nodeKey, ['el', '_arrayContainer'])) {
                return $this->walkFlexFormNode($nodeValue);
            }
            if (($nodeKey[0] ?? '') === '_') {
                continue;
            }
            if (strpos((string)$nodeKey, '.')) {
                $nodeKeyParts = explode('.', $nodeKey);
                $currentNode = &$result;
                $nodeKeyPartsCount = count($nodeKeyParts);
                for ($i = 0; $i < $nodeKeyPartsCount - 1; $i++) {
                    $currentNode = &$currentNode[$nodeKeyParts[$i]];
                }
                $newNode = [next($nodeKeyParts) => $nodeValue];
                $subVal = $this->walkFlexFormNode($newNode);
                $currentNode[key($subVal)] = current($subVal);
            } elseif (is_array($nodeValue)) {
                if (array_key_exists('vDEF', $nodeValue)) {
                    $result[$nodeKey] = $nodeValue['vDEF'];
                } else {
                    $result[$nodeKey] = $this->walkFlexFormNode($nodeValue);
                }
            } else {
                $result[$nodeKey] = $nodeValue;
            }
        }
        return $result;
    }
}
