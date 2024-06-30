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
use TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureIdentifierInitializedEvent;
use TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureParsedEvent;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidCombinedPointerFieldException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidIdentifierException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidSinglePointerFieldException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidTcaException;
use TYPO3\CMS\Core\Configuration\Tca\TcaMigration;
use TYPO3\CMS\Core\Configuration\Tca\TcaPreparation;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service class to help with TCA type="flex" details.
 *
 * This service provides various helpers to determine the data structure of flex form
 * fields and to maintain integrity of flex form related details in general.
 */
#[Autoconfigure(public: true)]
readonly class FlexFormTools
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private TcaMigration $tcaMigration,
    ) {}

    /**
     * The method locates a specific data structure from given TCA and row combination
     * and returns an identifier string that can be handed around, and can be resolved
     * to a single data structure later without giving $row and $tca data again.
     *
     * Note: The returned syntax is meant to only specify the target location of the data structure.
     * It SHOULD NOT be abused and enriched with data from the record that is dealt with. For
     * instance, it is now allowed to add source record specific date like the uid or the pid!
     * If that is done, it is up to the hook consumer to take care of possible side effects, eg. if
     * the data handler copies or moves records around and those references change.
     *
     * This method gets: Source data that influences the target location of a data structure
     * This method returns: Target specification of the data structure
     *
     * This method is "paired" with method getFlexFormDataStructureByIdentifier() that
     * will resolve the returned syntax again and returns the data structure itself.
     *
     * Both methods can be extended via events to return and accept additional
     * identifier strings if needed, and to transmit further information within the identifier strings.
     *
     * Note that the TCA for data structure definitions MUST NOT be overridden by
     * 'columnsOverrides' or by parent TCA in an inline relation! This would create a huge mess.
     *
     * Note: This method and the resolving methods below are well unit tested and document all
     * nasty details this way.
     *
     * @param array $fieldTca Full TCA of the field in question that has type=flex set
     * @param string $tableName The table name of the TCA field
     * @param string $fieldName The field name
     * @param array $row The data row
     * @return string Identifier JSON string
     * @throws \RuntimeException If TCA is misconfigured
     * @throws InvalidCombinedPointerFieldException
     * @throws InvalidSinglePointerFieldException
     * @throws InvalidTcaException
     */
    public function getDataStructureIdentifier(array $fieldTca, string $tableName, string $fieldName, array $row): string
    {
        $dataStructureIdentifier = $this->eventDispatcher
            ->dispatch(new BeforeFlexFormDataStructureIdentifierInitializedEvent($fieldTca, $tableName, $fieldName, $row))
            ->getIdentifier() ?? $this->getDefaultIdentifier($fieldTca, $tableName, $fieldName, $row);
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
     * Hooks allow to manipulate the find logic and to post process the data structure array.
     *
     * Note that the TCA for data structure definitions MUST NOT be overridden by
     * 'columnsOverrides' or by parent TCA in an inline relation! This would create a huge mess.
     *
     * After the data structure definition is found, the method resolves:
     * * FILE:EXT: prefix of the data structure itself - the ds is in a file
     * * FILE:EXT: prefix for sheets - if single sheets are in files
     * * Create an sDEF sheet if the data structure has non, yet.
     *
     * After that method is run, the data structure is fully resolved to an array,
     * and same base normalization is done: If the ds did not contain a sheet,
     * it will have one afterward as "sDEF".
     *
     * This method gets: Target specification of the data structure.
     * This method returns: The normalized data structure parsed to an array.
     *
     * Read the unit tests for nasty details.
     *
     * @param string $identifier JSON string to find the data structure location
     * @return array Parsed and normalized data structure
     * @throws InvalidIdentifierException
     */
    public function parseDataStructureByIdentifier(string $identifier): array
    {
        // Throw an exception for an empty string. This might be a valid use case for new
        // records in some situations, so this is catchable to give callers a chance to deal with that.
        if (empty($identifier)) {
            throw new InvalidIdentifierException(
                'Empty string given to parseFlexFormDataStructureByIdentifier(). This exception might '
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
            ->getDataStructure() ?? $this->getDefaultStructureForIdentifier($parsedIdentifier);
        $dataStructure = $this->convertDataStructureToArray($dataStructure);
        $dataStructure = $this->ensureDefaultSheet($dataStructure);
        $dataStructure = $this->resolveFileDirectives($dataStructure);
        $dataStructure = $this->migrateAndPrepareFlexTca($dataStructure);
        return $this->eventDispatcher
            ->dispatch(new AfterFlexFormDataStructureParsedEvent($dataStructure, $parsedIdentifier))
            ->getDataStructure();
    }

    /**
     * Clean up FlexForm value XML to hold only the values it may according to its Data Structure.
     * The order of tags will follow that of the data structure.
     *
     * @internal Signature may change, for instance to split 'DS finding' and flexArray2Xml(),
     *           which would allow broader use of the method. It is currently consumed by
     *           cleanup:flexforms CLI only.
     */
    public function cleanFlexFormXML(string $table, string $field, array $row): string
    {
        if (!is_array($GLOBALS['TCA'][$table]['columns'][$field]['config'] ?? false) || !isset($row[$field])) {
            throw new \RuntimeException('Can not clean up FlexForm XML for a column not declared in TCA or not in record.', 1697554398);
        }
        try {
            $dataStructureArray = $this->parseDataStructureByIdentifier($this->getDataStructureIdentifier($GLOBALS['TCA'][$table]['columns'][$field], $table, $field, $row));
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
     * Recursively migrate flex form TCA
     *
     * @internal
     */
    protected function migrateFlexFormTcaRecursive(array $structure): array
    {
        $newStructure = [];
        foreach ($structure as $key => $value) {
            if ($key === 'el' && is_array($value)) {
                $newSubStructure = [];
                foreach ($value as $subKey => $subValue) {
                    // On-the-fly migration for flex form "TCA". Call the TcaMigration and log any deprecations.
                    $dummyTca = [
                        'dummyTable' => [
                            'columns' => [
                                'dummyField' => $subValue,
                            ],
                        ],
                    ];
                    $migratedTca = $this->tcaMigration->migrate($dummyTca);
                    $messages = $this->tcaMigration->getMessages();
                    if (!empty($messages)) {
                        $context = 'FlexFormTools did an on-the-fly migration of a flex form data structure. This is deprecated and will be removed.'
                            . ' Merge the following changes into the flex form definition "' . $subKey . '":';
                        array_unshift($messages, $context);
                        trigger_error(implode(LF, $messages), E_USER_DEPRECATED);
                    }
                    $newSubStructure[$subKey] = $migratedTca['dummyTable']['columns']['dummyField'];
                }
                $value = $newSubStructure;
            }
            if (is_array($value)) {
                $value = $this->migrateFlexFormTcaRecursive($value);
            }
            $newStructure[$key] = $value;
        }
        return $newStructure;
    }

    /**
     * Returns the default data structure identifier.
     *
     * @param array $fieldTca Full TCA of the field in question that has type=flex set
     * @param string $tableName The table name of the TCA field
     * @param string $fieldName The field name
     * @param array $row The data row
     * @throws InvalidCombinedPointerFieldException
     * @throws InvalidSinglePointerFieldException
     * @throws InvalidTcaException
     */
    protected function getDefaultIdentifier(array $fieldTca, string $tableName, string $fieldName, array $row): array
    {
        $tcaDataStructureArray = $fieldTca['config']['ds'] ?? null;
        if (is_array($tcaDataStructureArray)) {
            $dataStructureIdentifier = $this->getDataStructureIdentifierFromTcaArray(
                $fieldTca,
                $tableName,
                $fieldName,
                $row
            );
        } else {
            throw new \RuntimeException(
                'TCA misconfiguration in table "' . $tableName . '" field "' . $fieldName . '" config section:'
                . ' The field is configured as type="flex" and no "ds_pointerField" is defined and "ds" is not an array.'
                . ' Either configure a default data structure in [\'ds\'][\'default\'] or add a "ds_pointerField" lookup mechanism'
                . ' that specifies the data structure',
                1463826960
            );
        }
        return $dataStructureIdentifier;
    }

    /**
     * Find matching data structure in TCA ds array.
     *
     * Data structure is defined in 'ds' config array, optionally with 'ds_pointerField'.
     *
     * fieldTca = [
     *     'config' => [
     *         'type' => 'flex',
     *         'ds' => [
     *             'aName' => '<T3DataStructure>...' OR 'FILE:...'
     *         ],
     *         'ds_pointerField' => 'optionalSetting,upToTwoCommaSeparatedFieldNames',
     *     ]
     * ]
     *
     * This method returns an array of the form:
     * [
     *     'type' => 'Tca:',
     *     'tableName' => $tableName,
     *     'fieldName' => $fieldName,
     *     'dataStructureKey' => $key,
     * ];
     *
     * Example:
     * [
     *     'type' => 'Tca:',
     *     'tableName' => 'tt_content',
     *     'fieldName' => 'pi_flexform',
     *     'dataStructureKey' => 'powermail_pi1,list',
     * ];
     *
     * @param array $fieldTca Full TCA of the field in question that has type=flex set
     * @param string $tableName The table name of the TCA field
     * @param string $fieldName The field name
     * @param array $row The data row
     * @return array Identifier as array, see example above
     * @throws InvalidCombinedPointerFieldException
     * @throws InvalidSinglePointerFieldException
     * @throws InvalidTcaException
     */
    protected function getDataStructureIdentifierFromTcaArray(array $fieldTca, string $tableName, string $fieldName, array $row): array
    {
        $dataStructureIdentifier = [
            'type' => 'tca',
            'tableName' => $tableName,
            'fieldName' => $fieldName,
            'dataStructureKey' => null,
        ];
        $tcaDataStructurePointerField = $fieldTca['config']['ds_pointerField'] ?? null;
        if ($tcaDataStructurePointerField === null) {
            // No ds_pointerField set -> use 'default' as ds array key if exists.
            if (isset($fieldTca['config']['ds']['default'])) {
                $dataStructureIdentifier['dataStructureKey'] = 'default';
            } else {
                // A tca is configured as flex without ds_pointerField. A 'default' key must exist, otherwise
                // this is a configuration error.
                // May happen with an unloaded extension -> catchable
                throw new InvalidTcaException(
                    'TCA misconfiguration in table "' . $tableName . '" field "' . $fieldName . '" config section:'
                    . ' The field is configured as type="flex" and no "ds_pointerField" is defined. Either configure'
                    . ' a default data structure in [\'ds\'][\'default\'] or add a "ds_pointerField" lookup mechanism'
                    . ' that specifies the data structure',
                    1463652560
                );
            }
        } else {
            // ds_pointerField is set, it can be a comma separated list of two fields, explode it.
            $pointerFieldArray = GeneralUtility::trimExplode(',', $tcaDataStructurePointerField, true);
            // Obvious configuration error, either one or two fields must be declared
            $pointerFieldsCount = count($pointerFieldArray);
            if ($pointerFieldsCount !== 1 && $pointerFieldsCount !== 2) {
                // If it's there, it must be correct -> not catchable
                throw new \RuntimeException(
                    'TCA misconfiguration in table "' . $tableName . '" field "' . $fieldName . '" config section:'
                    . ' ds_pointerField must be either a single field name, or a comma separated list of two fields,'
                    . ' the invalid configuration string provided was: "' . $tcaDataStructurePointerField . '"',
                    1463577497
                );
            }
            // Verify first field exists in row array. If not, this is a hard error: Any extension that sets a
            // ds_pointerField to some field name should take care that field does exist, too. They are a pair,
            // so there shouldn't be a situation where the field does not exist. Throw an exception if that is violated.
            if (!isset($row[$pointerFieldArray[0]])) {
                // If it's declared, it must exist -> not catchable
                throw new \RuntimeException(
                    'TCA misconfiguration in table "' . $tableName . '" field "' . $fieldName . '" config section:'
                    . ' ds_pointerField "' . $pointerFieldArray[0] . '" points to a field name that does not exist.',
                    1463578899
                );
            }
            // Similar situation for the second field: If it is set, the field must exist.
            if (isset($pointerFieldArray[1]) && !isset($row[$pointerFieldArray[1]])) {
                // If it's declared, it must exist -> not catchable
                throw new \RuntimeException(
                    'TCA misconfiguration in table "' . $tableName . '" field "' . $fieldName . '" config section:'
                    . ' Second part "' . $pointerFieldArray[1] . '" of ds_pointerField with full value "'
                    . $tcaDataStructurePointerField . '" points to a field name that does not exist.',
                    1463578900
                );
            }
            if ($pointerFieldsCount === 1) {
                if (isset($fieldTca['config']['ds'][$row[$pointerFieldArray[0]]])) {
                    // Field value points directly to an existing key in tca ds
                    $dataStructureIdentifier['dataStructureKey'] = $row[$pointerFieldArray[0]];
                } elseif (isset($fieldTca['config']['ds']['default'])) {
                    // Field value does not exit in tca ds, fall back to default key if exists
                    $dataStructureIdentifier['dataStructureKey'] = 'default';
                } else {
                    // The value of the ds_pointerField field points to a key in the ds array that does
                    // not exist, and there is no fallback either. This can happen if an extension brings
                    // new flex form definitions and that extension is unloaded later. "Old" records of the
                    // extension could then still point to the no longer existing key in ds. We throw a
                    // specific exception here to give controllers an opportunity to catch this case.
                    throw new InvalidSinglePointerFieldException(
                        'Field value of field "' . $pointerFieldArray[0] . '" of database record with uid "'
                        . $row['uid'] . '" from table "' . $tableName . '" points to a "ds" key ' . $row[$pointerFieldArray[0]]
                        . ' but this key does not exist and there is no "default" fallback.',
                        1463653197
                    );
                }
            } else {
                // Two comma separated field names
                if (isset($fieldTca['config']['ds'][$row[$pointerFieldArray[0]] . ',' . $row[$pointerFieldArray[1]]])) {
                    // firstValue,secondValue
                    $dataStructureIdentifier['dataStructureKey'] = $row[$pointerFieldArray[0]] . ',' . $row[$pointerFieldArray[1]];
                } elseif (isset($fieldTca['config']['ds'][$row[$pointerFieldArray[0]] . ',*'])) {
                    // firstValue,*
                    $dataStructureIdentifier['dataStructureKey'] = $row[$pointerFieldArray[0]] . ',*';
                } elseif (isset($fieldTca['config']['ds']['*,' . $row[$pointerFieldArray[1]]])) {
                    // *,secondValue
                    $dataStructureIdentifier['dataStructureKey'] = '*,' . $row[$pointerFieldArray[1]];
                } elseif (isset($fieldTca['config']['ds'][$row[$pointerFieldArray[0]]])) {
                    // firstValue
                    $dataStructureIdentifier['dataStructureKey'] = $row[$pointerFieldArray[0]];
                } elseif (isset($fieldTca['config']['ds']['default'])) {
                    // Fall back to default
                    $dataStructureIdentifier['dataStructureKey'] = 'default';
                } else {
                    // No ds_pointerField value could be determined and 'default' does not exist as
                    // fallback. This is the same case as the above scenario, throw a
                    // InvalidCombinedPointerFieldException here, too.
                    throw new InvalidCombinedPointerFieldException(
                        'Field combination of fields "' . $pointerFieldArray[0] . '" and "' . $pointerFieldArray[1] . '" of database'
                        . 'record with uid "' . $row['uid'] . '" from table "' . $tableName . '" with values "' . $row[$pointerFieldArray[0]] . '"'
                        . ' and "' . $row[$pointerFieldArray[1]] . '" could not be resolved to any registered data structure and '
                        . ' no "default" fallback exists.',
                        1463678524
                    );
                }
            }
        }
        return $dataStructureIdentifier;
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
                throw new \RuntimeException(
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

    protected function getDefaultStructureForIdentifier(array $identifier): string
    {
        if (($identifier['type'] ?? '') === 'tca') {
            // Handle "tca" type, see getDataStructureIdentifierFromTcaArray
            if (empty($identifier['tableName']) || empty($identifier['fieldName']) || empty($identifier['dataStructureKey'])) {
                throw new \RuntimeException(
                    'Incomplete "tca" based identifier: ' . json_encode($identifier),
                    1478113471
                );
            }
            $table = $identifier['tableName'];
            $field = $identifier['fieldName'];
            $dataStructureKey = $identifier['dataStructureKey'];
            if (!isset($GLOBALS['TCA'][$table]['columns'][$field]['config']['ds'][$dataStructureKey])
                || !is_string($GLOBALS['TCA'][$table]['columns'][$field]['config']['ds'][$dataStructureKey])
            ) {
                // This may happen for elements pointing to an unloaded extension -> catchable
                throw new InvalidIdentifierException(
                    'Specified identifier ' . json_encode($identifier) . ' does not resolve to a valid'
                    . ' TCA array value',
                    1478105491
                );
            }
            $dataStructure = $GLOBALS['TCA'][$table]['columns'][$field]['config']['ds'][$dataStructureKey];
        } else {
            throw new InvalidIdentifierException(
                'Identifier ' . json_encode($identifier) . ' could not be resolved',
                1478104554
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
     * Call TCA migration and TCA preparation on flex elements.
     */
    protected function migrateAndPrepareFlexTca(array $dataStructure): array
    {
        if (isset($dataStructure['sheets']) && is_array($dataStructure['sheets'])) {
            foreach ($dataStructure['sheets'] as $sheetName => $sheetStructure) {
                if (is_array($dataStructure['sheets'][$sheetName])) {
                    // @todo Use TcaPreparation instead of duplicating the code.
                    // @todo Actually, the type category preparation is different for FlexForm as is doesn't support manyToMany.
                    // @todo The difficulty for type file is the difference of the field name. For FlexForm it is not the column name of TCA, but the sub key.
                    $dataStructure['sheets'][$sheetName] = $this->migrateFlexFormTcaRecursive($dataStructure['sheets'][$sheetName]);
                    $dataStructure['sheets'][$sheetName] = $this->prepareCategoryFields($dataStructure['sheets'][$sheetName]);
                    $dataStructure['sheets'][$sheetName] = $this->prepareFileFields($dataStructure['sheets'][$sheetName]);
                }
            }
        }
        return $dataStructure;
    }

    /**
     * Prepare type=category fields if given.
     *
     * NOTE: manyToMany relationships are not supported!
     *
     * @param array $dataStructureSheets
     * @return array The processed $dataStructureSheets
     */
    protected function prepareCategoryFields(array $dataStructureSheets): array
    {
        if ($dataStructureSheets === []) {
            // Early return in case the no sheets are given
            return $dataStructureSheets;
        }
        foreach ($dataStructureSheets as &$structure) {
            if (!is_array($structure['el'] ?? false) || $structure['el'] === []) {
                // Skip if no elements (fields) are defined
                continue;
            }
            foreach ($structure['el'] as $fieldName => &$fieldConfig) {
                if (($fieldConfig['config']['type'] ?? '') !== 'category') {
                    // Skip if type is not "category"
                    continue;
                }
                // Add a default label if none is defined
                if (!isset($fieldConfig['label'])) {
                    $fieldConfig['label'] = 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_category.categories';
                }
                // Initialize default column configuration and merge it with already defined
                $fieldConfig['config']['size'] ??= 20;
                // Force foreign_table_* fields for type category
                $fieldConfig['config']['foreign_table'] = 'sys_category';
                $fieldConfig['config']['foreign_table_where'] = ' AND {#sys_category}.{#sys_language_uid} IN (-1, 0)';
                if (empty($fieldConfig['config']['relationship'])) {
                    // Fall back to "oneToMany" when no relationship is given
                    $fieldConfig['config']['relationship'] = 'oneToMany';
                }
                if (!in_array($fieldConfig['config']['relationship'], ['oneToOne', 'oneToMany'], true)) {
                    throw new \UnexpectedValueException(
                        '"relationship" must be one of "oneToOne" or "oneToMany", "manyToMany" is not supported as "relationship"' .
                        ' for field ' . $fieldName . ' of type "category" in flexform.',
                        1627640208
                    );
                }
                // Set the maxitems value (necessary for DataHandling and FormEngine)
                if ($fieldConfig['config']['relationship'] === 'oneToOne') {
                    // In case relationship is set to "oneToOne", maxitems must be 1.
                    if ((int)($fieldConfig['config']['maxitems'] ?? 0) > 1) {
                        throw new \UnexpectedValueException(
                            $fieldName . ' is defined as type category with an "oneToOne" relationship. ' .
                            'Therefore maxitems must be 1. Otherwise, use oneToMany as relationship instead.',
                            1627640209
                        );
                    }
                    $fieldConfig['config']['maxitems'] = 1;
                } else {
                    // In case maxitems is not set or set to 0, set the default value "99999"
                    if (!($fieldConfig['config']['maxitems'] ?? false)) {
                        $fieldConfig['config']['maxitems'] = 99999;
                    } elseif ((int)($fieldConfig['config']['maxitems'] ?? 0) === 1) {
                        throw new \UnexpectedValueException(
                            'Can not use maxitems=1 for field ' . $fieldName . ' with "relationship" set to "oneToMany". Use "oneToOne" instead.',
                            1627640210
                        );
                    }
                }
                // Add the default value if not set
                if (!isset($fieldConfig['config']['default'])
                    && $fieldConfig['config']['relationship'] !== 'oneToMany'
                ) {
                    $fieldConfig['config']['default'] = 0;
                }
            }
        }
        return $dataStructureSheets;
    }

    /**
     * Prepare type=file fields if given.
     *
     * @return array The processed $dataStructureSheets
     */
    protected function prepareFileFields(array $dataStructureSheets): array
    {
        if ($dataStructureSheets === []) {
            // Early return in case the no sheets are given
            return $dataStructureSheets;
        }
        foreach ($dataStructureSheets as &$structure) {
            if (!is_array($structure['el'] ?? false) || $structure['el'] === []) {
                // Skip if no elements (fields) are defined
                continue;
            }
            foreach ($structure['el'] as $fieldName => &$fieldConfig) {
                if (($fieldConfig['config']['type'] ?? '') !== 'file') {
                    // Skip if type is not "file"
                    continue;
                }
                $fieldConfig['config'] = array_replace_recursive(
                    $fieldConfig['config'],
                    [
                        'foreign_table' => 'sys_file_reference',
                        'foreign_field' => 'uid_foreign',
                        'foreign_sortby' => 'sorting_foreign',
                        'foreign_table_field' => 'tablenames',
                        'foreign_match_fields' => [
                            'fieldname' => $fieldName,
                        ],
                        'foreign_label' => 'uid_local',
                        'foreign_selector' => 'uid_local',
                    ]
                );

                if (!empty(($allowed = ($fieldConfig['config']['allowed'] ?? null)))) {
                    $fieldConfig['config']['allowed'] = TcaPreparation::prepareFileExtensions($allowed);
                }
                if (!empty(($disallowed = ($fieldConfig['config']['disallowed'] ?? null)))) {
                    $fieldConfig['config']['disallowed'] = TcaPreparation::prepareFileExtensions($disallowed);
                }
            }
        }
        return $dataStructureSheets;
    }
}
