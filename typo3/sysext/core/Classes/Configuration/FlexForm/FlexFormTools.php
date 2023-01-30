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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureIdentifierInitializedEvent;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;
use TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureIdentifierInitializedEvent;
use TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureParsedEvent;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidCombinedPointerFieldException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidIdentifierException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowLoopException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowRootException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidPointerFieldValueException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidSinglePointerFieldException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidTcaException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Migrations\TcaMigration;
use TYPO3\CMS\Core\Preparations\TcaPreparation;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Contains functions for manipulating flex form data
 *
 * The data structure identifier (array) has several commonly used
 * elements (keys), but is generally undefined. Users of this system
 * should be extra careful of what array keys are defined or not.
 *
 * Recommended keys include:
 *  - type
 *  - tableName
 *  - fieldName
 *  - dataStructureKey
 */
class FlexFormTools
{
    /**
     * If set, section indexes are re-numbered before processing
     *
     * @var bool
     */
    public $reNumberIndexesOfSectionData = false;

    /**
     * Options for array2xml() for flexform.
     * This will map the weird keys from the internal array to tags that could potentially be checked with a DTD/schema
     *
     * @var array
     */
    public $flexArray2Xml_options = [
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

    /**
     * Reference to object called
     *
     * @var object
     */
    public $callBackObj;

    /**
     * Used for accumulation of clean XML
     *
     * @var array
     */
    public $cleanFlexFormXML = [];

    public function __construct(
        private ?EventDispatcherInterface $eventDispatcher = null,
    ) {
        $this->eventDispatcher ??= GeneralUtility::makeInstance(EventDispatcherInterface::class);
    }

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
     * @throws InvalidParentRowException in getDataStructureIdentifierFromRecord
     * @throws InvalidParentRowLoopException in getDataStructureIdentifierFromRecord
     * @throws InvalidParentRowRootException in getDataStructureIdentifierFromRecord
     * @throws InvalidPointerFieldValueException in getDataStructureIdentifierFromRecord
     * @throws InvalidTcaException in getDataStructureIdentifierFromRecord
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
     * Returns the default data structure identifier.
     *
     * @param array $fieldTca Full TCA of the field in question that has type=flex set
     * @param string $tableName The table name of the TCA field
     * @param string $fieldName The field name
     * @param array $row The data row
     */
    protected function getDefaultIdentifier(array $fieldTca, string $tableName, string $fieldName, array $row): array
    {
        $tcaDataStructureArray = $fieldTca['config']['ds'] ?? null;
        $tcaDataStructurePointerField = $fieldTca['config']['ds_pointerField'] ?? null;
        if (!is_array($tcaDataStructureArray) && $tcaDataStructurePointerField) {
            // "ds" is not an array, but "ds_pointerField" is set -> data structure is found in different table
            $dataStructureIdentifier = $this->getDataStructureIdentifierFromRecord(
                $fieldTca,
                $tableName,
                $fieldName,
                $row
            );
        } elseif (is_array($tcaDataStructureArray)) {
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
     * The data structure is located in a record. This method resolves the record and
     * returns an array to identify that record.
     *
     * The example setup below looks in current row for a tx_templavoila_ds value. If not found,
     * it will search the rootline (the table is a tree, typically pages) until a value in
     * tx_templavoila_next_ds or tx_templavoila_ds is found. That value should then be an
     * integer, that points to a record in tx_templavoila_datastructure, and then the data
     * structure is found in field dataprot:
     *
     * fieldTca = [
     *     'config' => [
     *         'type' => 'flex',
     *         'ds_pointerField' => 'tx_templavoila_ds',
     *         'ds_pointerField_searchParent' => 'pid',
     *         'ds_pointerField_searchParent_subField' => 'tx_templavoila_next_ds',
     *         'ds_tableField' => 'tx_templavoila_datastructure:dataprot',
     *     ]
     * ]
     *
     * More simple scenario without tree traversal and having a valid data structure directly
     * located in field theFlexDataStructureField.
     *
     * fieldTca = [
     *     'config' => [
     *         'type' => 'flex',
     *         'ds_pointerField' => 'theFlexDataStructureField',
     *     ]
     * ]
     *
     * Example return array:
     * [
     *     'type' => 'record',
     *     'tableName' => 'tx_templavoila_datastructure',
     *     'uid' => 42,
     *     'fieldName' => 'dataprot',
     * ];
     *
     * @param array $fieldTca Full TCA of the field in question that has type=flex set
     * @param string $tableName The table name of the TCA field
     * @param string $fieldName The field name
     * @param array $row The data row
     * @return array Identifier as array, see example above
     * @throws InvalidParentRowException
     * @throws InvalidParentRowLoopException
     * @throws InvalidParentRowRootException
     * @throws InvalidPointerFieldValueException
     * @throws InvalidTcaException
     */
    protected function getDataStructureIdentifierFromRecord(array $fieldTca, string $tableName, string $fieldName, array $row): array
    {
        $pointerFieldName = $finalPointerFieldName = $fieldTca['config']['ds_pointerField'];
        if (!array_key_exists($pointerFieldName, $row)) {
            // Pointer field does not exist in row at all -> throw
            throw new InvalidTcaException(
                'No data structure for field "' . $fieldName . '" in table "' . $tableName . '" found, no "ds" array'
                . ' configured and given row does not have a field with ds_pointerField name "' . $pointerFieldName . '".',
                1464115059
            );
        }
        $pointerValue = $row[$pointerFieldName];
        // If set, this is typically set to "pid"
        $parentFieldName = $fieldTca['config']['ds_pointerField_searchParent'] ?? null;
        $pointerSubFieldName = $fieldTca['config']['ds_pointerField_searchParent_subField'] ?? null;
        if (!$pointerValue && $parentFieldName) {
            // Fetch rootline until a valid pointer value is found
            $handledUids = [];
            while (!$pointerValue) {
                $handledUids[$row['uid']] = 1;
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
                $queryBuilder->getRestrictions()
                    ->removeAll()
                    ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                $queryBuilder->select('uid', $parentFieldName, $pointerFieldName);
                if (!empty($pointerSubFieldName)) {
                    $queryBuilder->addSelect($pointerSubFieldName);
                }
                $queryStatement = $queryBuilder->from($tableName)
                    ->where(
                        $queryBuilder->expr()->eq(
                            'uid',
                            $queryBuilder->createNamedParameter($row[$parentFieldName], Connection::PARAM_INT)
                        )
                    )
                    ->executeQuery();
                $rowCount = $queryBuilder
                    ->count('uid')
                    ->executeQuery()
                    ->fetchOne();
                if ($rowCount !== 1) {
                    throw new InvalidParentRowException(
                        'The data structure for field "' . $fieldName . '" in table "' . $tableName . '" has to be looked up'
                        . ' in field "' . $pointerFieldName . '". That field had no valid value, so a lookup in parent record'
                        . ' with uid "' . $row[$parentFieldName] . '" was done. This row however does not exist or was deleted.',
                        1463833794
                    );
                }
                $row = $queryStatement->fetchAssociative();
                if (isset($handledUids[$row[$parentFieldName]])) {
                    // Row has been fetched before already -> loop detected!
                    throw new InvalidParentRowLoopException(
                        'The data structure for field "' . $fieldName . '" in table "' . $tableName . '" has to be looked up'
                        . ' in field "' . $pointerFieldName . '". That field had no valid value, so a lookup in parent record'
                        . ' with uid "' . $row[$parentFieldName] . '" was done. A loop of records was detected, the tree is broken.',
                        1464110956
                    );
                }
                BackendUtility::workspaceOL($tableName, $row);
                // New pointer value: This is the "subField" value if given, else the field value
                // ds_pointerField_searchParent_subField is the "template on next level" structure from templavoila
                if ($pointerSubFieldName && $row[$pointerSubFieldName]) {
                    $finalPointerFieldName = $pointerSubFieldName;
                    $pointerValue = $row[$pointerSubFieldName];
                } else {
                    $pointerValue = $row[$pointerFieldName];
                }
                if (!$pointerValue && ((int)$row[$parentFieldName] === 0 || $row[$parentFieldName] === null)) {
                    // If on root level and still no valid pointer found -> exception
                    throw new InvalidParentRowRootException(
                        'The data structure for field "' . $fieldName . '" in table "' . $tableName . '" has to be looked up'
                        . ' in field "' . $pointerFieldName . '". That field had no valid value, so a lookup in parent record'
                        . ' with uid "' . $row[$parentFieldName] . '" was done. Root node with uid "' . $row['uid'] . '"'
                        . ' was fetched and still no valid pointer field value was found.',
                        1464112555
                    );
                }
            }
        }
        if (!$pointerValue) {
            // Still no valid pointer value -> exception, This still can be a data integrity issue, so throw a catchable exception
            throw new InvalidPointerFieldValueException(
                'No data structure for field "' . $fieldName . '" in table "' . $tableName . '" found, no "ds" array'
                . ' configured and data structure could be found by resolving parents. This is probably a TCA misconfiguration.',
                1464114011
            );
        }
        // Ok, finally we have the field value. This is now either a data structure directly, or a pointer to a file,
        // or the value can be interpreted as integer (is a uid) and "ds_tableField" is set, so this is the table, uid and field
        // where the final data structure can be found.
        if (MathUtility::canBeInterpretedAsInteger($pointerValue)) {
            if (!isset($fieldTca['config']['ds_tableField'])) {
                throw new InvalidTcaException(
                    'Invalid data structure pointer for field "' . $fieldName . '" in table "' . $tableName . '", the value'
                    . 'resolved to "' . $pointerValue . '" . which is an integer, so "ds_tableField" must be configured',
                    1464115639
                );
            }
            if (substr_count($fieldTca['config']['ds_tableField'], ':') !== 1) {
                // ds_tableField must be of the form "table:field"
                throw new InvalidTcaException(
                    'Invalid TCA configuration for field "' . $fieldName . '" in table "' . $tableName . '", the setting'
                    . '"ds_tableField" must be of the form "tableName:fieldName"',
                    1464116002
                );
            }
            [$foreignTableName, $foreignFieldName] = GeneralUtility::trimExplode(':', $fieldTca['config']['ds_tableField']);
            $dataStructureIdentifier = [
                'type' => 'record',
                'tableName' => $foreignTableName,
                'uid' => (int)$pointerValue,
                'fieldName' => $foreignFieldName,
            ];
        } else {
            $dataStructureIdentifier = [
                'type' => 'record',
                'tableName' => $tableName,
                'uid' => (int)$row['uid'],
                'fieldName' => $finalPointerFieldName,
            ];
        }
        return $dataStructureIdentifier;
    }

    /**
     * Find matching data structure in TCA ds array.
     *
     * Data structure is defined in 'ds' config array.
     * Also, there can be a ds_pointerField
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
     * it will have one afterwards as "sDEF"
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

        return $this->eventDispatcher
            ->dispatch(new AfterFlexFormDataStructureParsedEvent($dataStructure, $parsedIdentifier))
            ->getDataStructure();
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
        if (($identifier['type'] ?? '') === 'record') {
            // Handle "record" type, see getDataStructureIdentifierFromRecord()
            if (empty($identifier['tableName']) || empty($identifier['uid']) || empty($identifier['fieldName'])) {
                throw new \RuntimeException(
                    'Incomplete "record" based identifier: ' . json_encode($identifier),
                    1478113873
                );
            }
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($identifier['tableName']);
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $dataStructure = $queryBuilder
                ->select($identifier['fieldName'])
                ->from($identifier['tableName'])
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($identifier['uid'], Connection::PARAM_INT)
                    )
                )
                ->executeQuery()
                ->fetchOne();
        } elseif (($identifier['type'] ?? '') === 'tca') {
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
                $dataStructure = $this->removeElementTceFormsRecursive($dataStructure);

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
     * Handler for Flex Forms
     *
     * @param string $table The table name of the record
     * @param string $field The field name of the flexform field to work on
     * @param array $row The record data array
     * @param object $callBackObj Object in which the call back function is located
     * @param string $callBackMethod_value Method name of call back function in object for values
     * @return bool|string true on success, string if error happened (error string returned)
     */
    public function traverseFlexFormXMLData($table, $field, $row, $callBackObj, $callBackMethod_value)
    {
        $PA = [];
        if (!is_array($GLOBALS['TCA'][$table]) || !is_array($GLOBALS['TCA'][$table]['columns'][$field])) {
            return 'TCA table/field was not defined.';
        }
        $this->callBackObj = $callBackObj;

        // Get data structure. The methods may throw various exceptions, with some of them being
        // ok in certain scenarios, for instance on new record rows. Those are ok to "eat" here
        // and substitute with a dummy DS.
        $dataStructureArray = ['sheets' => ['sDEF' => []]];
        try {
            $dataStructureIdentifier = $this->getDataStructureIdentifier($GLOBALS['TCA'][$table]['columns'][$field], $table, $field, $row);
            $dataStructureArray = $this->parseDataStructureByIdentifier($dataStructureIdentifier);
        } catch (InvalidParentRowException|InvalidParentRowLoopException|InvalidParentRowRootException|InvalidPointerFieldValueException|InvalidIdentifierException $e) {
        }

        // Get flexform XML data
        $editData = GeneralUtility::xml2array($row[$field]);
        if (!is_array($editData)) {
            return 'Parsing error: ' . $editData;
        }
        // Check if $dataStructureArray['sheets'] is indeed an array before loop or it will crash with runtime error
        if (!is_array($dataStructureArray['sheets'])) {
            return 'Data Structure ERROR: sheets is defined but not an array for table ' . $table . (isset($row['uid']) ? ' and uid ' . $row['uid'] : '');
        }
        // Traverse languages:
        foreach ($dataStructureArray['sheets'] as $sheetKey => $sheetData) {
            // Render sheet:
            if (isset($sheetData['ROOT']['el']) && is_array($sheetData['ROOT']['el'])) {
                $PA['vKeys'] = ['DEF'];
                $PA['lKey'] = 'lDEF';
                $PA['callBackMethod_value'] = $callBackMethod_value;
                $PA['table'] = $table;
                $PA['field'] = $field;
                $PA['uid'] = $row['uid'];
                // Render flexform:
                $this->traverseFlexFormXMLData_recurse($sheetData['ROOT']['el'], $editData['data'][$sheetKey]['lDEF'] ?? [], $PA, 'data/' . $sheetKey . '/lDEF');
            } else {
                return 'Data Structure ERROR: No ROOT element found for sheet "' . $sheetKey . '".';
            }
        }
        return true;
    }

    /**
     * Recursively traversing flexform data according to data structure and element data
     *
     * @param array $dataStruct (Part of) data structure array that applies to the sub section of the flexform data we are processing
     * @param array $editData (Part of) edit data array, reflecting current part of data structure
     * @param array $PA Additional parameters passed.
     * @param string $path Telling the "path" to the element in the flexform XML
     */
    public function traverseFlexFormXMLData_recurse($dataStruct, $editData, &$PA, $path = ''): void
    {
        if (is_array($dataStruct)) {
            foreach ($dataStruct as $key => $value) {
                if (isset($value['type']) && $value['type'] === 'array') {
                    // Array (Section) traversal
                    if ($value['section'] ?? false) {
                        if (isset($editData[$key]['el']) && is_array($editData[$key]['el'])) {
                            if ($this->reNumberIndexesOfSectionData) {
                                $temp = [];
                                $c3 = 0;
                                foreach ($editData[$key]['el'] as $v3) {
                                    $temp[++$c3] = $v3;
                                }
                                $editData[$key]['el'] = $temp;
                            }
                            foreach ($editData[$key]['el'] as $k3 => $v3) {
                                if (is_array($v3)) {
                                    $cc = $k3;
                                    $theType = key($v3);
                                    $theDat = $v3[$theType];
                                    $newSectionEl = $value['el'][$theType];
                                    if (is_array($newSectionEl)) {
                                        $this->traverseFlexFormXMLData_recurse([$theType => $newSectionEl], [$theType => $theDat], $PA, $path . '/' . $key . '/el/' . $cc);
                                    }
                                }
                            }
                        }
                    } else {
                        // Array traversal
                        if (isset($editData[$key]['el'])) {
                            $this->traverseFlexFormXMLData_recurse($value['el'], $editData[$key]['el'], $PA, $path . '/' . $key . '/el');
                        }
                    }
                } elseif (isset($value['config']) && is_array($value['config'])) {
                    // Processing a field value:
                    foreach ($PA['vKeys'] as $vKey) {
                        $vKey = 'v' . $vKey;
                        // Call back
                        if (!empty($PA['callBackMethod_value']) && isset($editData[$key][$vKey])) {
                            $this->executeCallBackMethod($PA['callBackMethod_value'], [
                                $value,
                                $editData[$key][$vKey],
                                $PA,
                                $path . '/' . $key . '/' . $vKey,
                                $this,
                            ]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Execute method on callback object
     *
     * @param string $methodName Method name to call
     * @param array $parameterArray Parameters
     * @return mixed Result of callback object
     */
    protected function executeCallBackMethod($methodName, array $parameterArray)
    {
        return $this->callBackObj->$methodName(...$parameterArray);
    }

    /***********************************
     *
     * Processing functions
     *
     ***********************************/
    /**
     * Cleaning up FlexForm XML to hold only the values it may according to its Data Structure. Also the order of tags will follow that of the data structure.
     * BE CAREFUL: DO not clean records in workspaces unless IN the workspace! The Data Structure might resolve falsely on a workspace record when cleaned from Live workspace.
     *
     * @param string $table Table name
     * @param string $field Field name of the flex form field in which the XML is found that should be cleaned.
     * @param array $row The record
     * @return string Clean XML from FlexForm field
     */
    public function cleanFlexFormXML($table, $field, $row)
    {
        // New structure:
        $this->cleanFlexFormXML = [];
        // Create and call iterator object:
        $flexObj = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class);
        $flexObj->reNumberIndexesOfSectionData = true;
        $flexObj->traverseFlexFormXMLData($table, $field, $row, $this, 'cleanFlexFormXML_callBackFunction');
        return $this->flexArray2Xml($this->cleanFlexFormXML, true);
    }

    /**
     * Call back function for \TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools class
     * Basically just setting the value in a new array (thus cleaning because only values that are valid are visited!)
     *
     * @param array $dsArr Data structure for the current value
     * @param mixed $data Current value
     * @param array $PA Additional configuration used in calling function
     * @param string $path Path of value in DS structure
     * @param FlexFormTools $pObj caller
     */
    public function cleanFlexFormXML_callBackFunction($dsArr, $data, $PA, $path, $pObj)
    {
        // Just setting value in our own result array, basically replicating the structure:
        $this->cleanFlexFormXML = ArrayUtility::setValueByPath($this->cleanFlexFormXML, $path, $data);
    }

    /**
     * Convert FlexForm data array to XML
     *
     * @param array $array Array to output in <T3FlexForms> XML
     * @param bool $addPrologue If set, the XML prologue is returned as well.
     * @return string XML content.
     */
    public function flexArray2Xml($array, $addPrologue = false)
    {
        if ($GLOBALS['TYPO3_CONF_VARS']['BE']['flexformForceCDATA']) {
            $this->flexArray2Xml_options['useCDATA'] = 1;
        }
        $output = GeneralUtility::array2xml($array, '', 0, 'T3FlexForms', 4, $this->flexArray2Xml_options);
        if ($addPrologue) {
            $output = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>' . LF . $output;
        }
        return $output;
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
                } elseif ($fieldConfig['config']['relationship'] === 'oneToMany') {
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

    /**
     * Remove "TCEforms" key from all elements in data structure to simplify further parsing.
     *
     * Example config:
     * ['config']['ds']['sheets']['sDEF']['ROOT']['el']['anElement']['TCEforms']['label'] becomes
     * ['config']['ds']['sheets']['sDEF']['ROOT']['el']['anElement']['label']
     *
     * and
     *
     * ['ROOT']['TCEforms']['sheetTitle'] becomes
     * ['ROOT']['sheetTitle']
     *
     * @internal This method serves as a compatibility layer and will be removed in TYPO3 v13.
     */
    public function removeElementTceFormsRecursive(array $structure): array
    {
        $newStructure = [];
        foreach ($structure as $key => $value) {
            if ($key === 'ROOT' && is_array($value) && isset($value['TCEforms'])) {
                trigger_error(
                    'The tag "<TCEforms>" should not be set under the FlexForm definition "<ROOT>" anymore. It should be omitted while the underlying configuration ascends one level up. This compatibility layer will be removed in TYPO3 v13.',
                    E_USER_DEPRECATED
                );
                $value = array_merge($value, $value['TCEforms']);
                unset($value['TCEforms']);
            }
            if ($key === 'el' && is_array($value)) {
                $newSubStructure = [];
                foreach ($value as $subKey => $subValue) {
                    if (is_array($subValue) && count($subValue) === 1 && isset($subValue['TCEforms'])) {
                        trigger_error(
                            'The tag "<TCEforms>" was found in a FlexForm definition for the field "<' . $subKey . '>". It should be omitted while the underlying configuration ascends one level up. This compatibility layer will be removed in TYPO3 v13.',
                            E_USER_DEPRECATED
                        );
                        $newSubStructure[$subKey] = $subValue['TCEforms'];
                    } else {
                        $newSubStructure[$subKey] = $subValue;
                    }
                }
                $value = $newSubStructure;
            }
            if (is_array($value)) {
                $value = $this->removeElementTceFormsRecursive($value);
            }
            $newStructure[$key] = $value;
        }
        return $newStructure;
    }

    /**
     * Recursively migrate flex form TCA
     */
    public function migrateFlexFormTcaRecursive(array $structure): array
    {
        $newStructure = [];
        foreach ($structure as $key => $value) {
            if ($key === 'el' && is_array($value)) {
                $newSubStructure = [];
                $tcaMigration = GeneralUtility::makeInstance(TcaMigration::class);
                foreach ($value as $subKey => $subValue) {
                    // On-the-fly migration for flex form "TCA". Call the TcaMigration and log any deprecations.
                    $dummyTca = [
                        'dummyTable' => [
                            'columns' => [
                                'dummyField' => $subValue,
                            ],
                        ],
                    ];
                    $migratedTca = $tcaMigration->migrate($dummyTca);
                    $messages = $tcaMigration->getMessages();
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
}
