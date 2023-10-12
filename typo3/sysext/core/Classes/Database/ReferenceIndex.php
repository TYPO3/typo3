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

namespace TYPO3\CMS\Core\Database;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LogLevel;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\ProgressListenerInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\Platform\PlatformInformation;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\Event\IsTableExcludedFromReferenceIndexEvent;
use TYPO3\CMS\Core\DataHandling\SoftReference\SoftReferenceParserFactory;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Reference index processing and relation extraction
 *
 * @internal Extensions shouldn't fiddle with the reference index themselves, it's task of DataHandler to do this.
 */
class ReferenceIndex
{
    /**
     * Definition of tables to exclude from the ReferenceIndex
     *
     * Only tables which do not contain any relations and never did so far since references also won't be deleted for
     * these. Since only tables with an entry in $GLOBALS['TCA] are handled by ReferenceIndex there is no need to add
     * *_mm-tables.
     *
     * Implemented as array with fields as keys and booleans as values for fast isset() lookup instead of slow in_array()
     */
    protected array $excludedTables = [
        'sys_log' => true,
        'tx_extensionmanager_domain_model_extension' => true,
    ];

    /**
     * Definition of fields to exclude from ReferenceIndex in *every* table.
     * Implemented as array with fields as keys and booleans as values for fast isset() lookup instead of slow in_array().
     */
    protected array $excludedColumns = [
        'uid' => true,
        'perms_userid' => true,
        'perms_groupid' => true,
        'perms_user' => true,
        'perms_group' => true,
        'perms_everybody' => true,
        'pid' => true,
    ];

    /** Holds the FlexForm references of a record */
    protected array $temp_flexRelations = [];
    /** An index of all found references of a single record */
    protected array $relations = [];
    /** Number which we can increase if a change in the code means we will have to force a re-generation of the index. */
    protected int $hashVersion = 1;
    /** Current workspace id */
    protected int $workspaceId = 0;

    /**
     * A list of fields that may contain relations per TCA table.
     * This is either ['*'] or an array of single field names. The list
     * depends on TCA and is built when a first table row is handled.
     */
    protected array $tableRelationFieldCache = [];

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SoftReferenceParserFactory $softReferenceParserFactory,
        private readonly ConnectionPool $connectionPool,
        private readonly Registry $registry,
    ) {
    }

    /**
     * Call this function to update the sys_refindex table for a record (even one just deleted)
     * NOTICE: Currently, references updated for a deleted-flagged record will not include those from within FlexForm
     * fields in some cases where the data structure is defined by another record since the resolving process ignores
     * deleted records! This will also result in bad cleaning up in DataHandler I think... Anyway, that's the story of
     * FlexForms; as long as the DS can change, lots of references can get lost in no time.
     *
     * @return array Statistics about how many index records were added, deleted and not altered.
     */
    public function updateRefIndexTable(string $tableName, int $uid, bool $testOnly = false, int $workspaceUid = 0): array
    {
        $this->workspaceId = $workspaceUid;

        $result = [
            'keptNodes' => 0,
            'deletedNodes' => 0,
            'addedNodes' => 0,
        ];

        // Not a valid uid, the table is excluded, or can not contain relations.
        if ($uid < 1 || $this->shouldExcludeTableFromReferenceIndex($tableName) || !$this->hasTableRelationFields($tableName)) {
            return $result;
        }

        $connection = $this->connectionPool->getConnectionForTable('sys_refindex');

        // Get current index from Database with hash as index. sys_refindex is not a TCA table, so no restrictions.
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $queryResult = $queryBuilder->select('hash')->from('sys_refindex')->where(
            $queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter($tableName)),
            $queryBuilder->expr()->eq('recuid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
            $queryBuilder->expr()->eq('workspace', $queryBuilder->createNamedParameter($this->workspaceId, Connection::PARAM_INT))
        )->executeQuery();
        $currentRelationHashes = [];
        while ($relation = $queryResult->fetchAssociative()) {
            $currentRelationHashes[$relation['hash']] = true;
        }

        // Handle this record.
        $existingRecord = $this->getRecord($tableName, $uid);
        if ($existingRecord) {
            // Table has relation fields and record exists - get relations
            $this->relations = [];
            $relations = $this->generateDataUsingRecord($tableName, $existingRecord);
            // Traverse the generated index:
            foreach ($relations as &$relation) {
                if (!is_array($relation)) {
                    continue;
                }
                // Exclude any relations TO a specific table
                if (($relation['ref_table'] ?? '') && $this->shouldExcludeTableFromReferenceIndex($relation['ref_table'])) {
                    continue;
                }
                $relation['hash'] = md5(implode('///', $relation) . '///' . $this->hashVersion);
                // First, check if already indexed and if so, unset that row (so in the end we know which rows to remove!)
                if (isset($currentRelationHashes[$relation['hash']])) {
                    unset($currentRelationHashes[$relation['hash']]);
                    $result['keptNodes']++;
                    $relation['_ACTION'] = 'KEPT';
                } else {
                    // If new, add it:
                    if (!$testOnly) {
                        $connection->insert('sys_refindex', $relation);
                    }
                    $result['addedNodes']++;
                    $relation['_ACTION'] = 'ADDED';
                }
            }
            $result['relations'] = $relations;
        }

        // If any existing are left, they are not in the current set anymore, and removed
        $numberOfLeftOverRelationHashes = count($currentRelationHashes);
        $result['deletedNodes'] = $numberOfLeftOverRelationHashes;
        if ($numberOfLeftOverRelationHashes > 0 && !$testOnly) {
            $maxBindParameters = PlatformInformation::getMaxBindParameters($connection->getDatabasePlatform());
            $chunks = array_chunk(array_keys($currentRelationHashes), $maxBindParameters - 10, true);
            foreach ($chunks as $chunk) {
                if (empty($chunk)) {
                    continue;
                }
                $queryBuilder = $connection->createQueryBuilder();
                $queryBuilder
                    ->delete('sys_refindex')
                    ->where(
                        $queryBuilder->expr()->in('hash', $queryBuilder->createNamedParameter($chunk, Connection::PARAM_STR_ARRAY))
                    )
                    ->executeStatement();
            }
        }

        return $result;
    }

    /**
     * Returns the amount of references for the given record
     */
    public function getNumberOfReferencedRecords(string $tableName, int $uid): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');
        return (int)$queryBuilder
            ->count('*')->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->eq(
                    'ref_table',
                    $queryBuilder->createNamedParameter($tableName)
                ),
                $queryBuilder->expr()->eq(
                    'ref_uid',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                )
            )->executeQuery()->fetchOne();
    }

    /**
     * Calculate the relations for a record of a given table
     *
     * @param string $tableName Table being processed
     * @param array $record Record from $tableName
     */
    protected function generateDataUsingRecord(string $tableName, array $record): array
    {
        $this->relations = [];
        // Get all relations from record:
        $recordRelations = $this->getRelations($tableName, $record);
        // Traverse those relations, compile records to insert in table:
        foreach ($recordRelations as $fieldName => $fieldRelations) {
            // Based on type
            switch ($fieldRelations['type'] ?? '') {
                case 'db':
                    $this->createEntryDataForDatabaseRelationsUsingRecord($tableName, $record, $fieldName, '', $fieldRelations['itemArray']);
                    break;
                case 'flex':
                    // DB references in FlexForms
                    if (is_array($fieldRelations['flexFormRels']['db'])) {
                        foreach ($fieldRelations['flexFormRels']['db'] as $flexPointer => $subList) {
                            $this->createEntryDataForDatabaseRelationsUsingRecord($tableName, $record, $fieldName, $flexPointer, $subList);
                        }
                    }
                    // Soft references in FlexForms
                    // @todo #65464 Test correct handling of soft references in FlexForms
                    if (is_array($fieldRelations['flexFormRels']['softrefs'])) {
                        foreach ($fieldRelations['flexFormRels']['softrefs'] as $flexPointer => $subList) {
                            $this->createEntryDataForSoftReferencesUsingRecord($tableName, $record, $fieldName, $flexPointer, $subList['keys']);
                        }
                    }
                    break;
            }
            // Soft references in the field
            if (is_array($fieldRelations['softrefs']['keys'] ?? false)) {
                $this->createEntryDataForSoftReferencesUsingRecord($tableName, $record, $fieldName, '', $fieldRelations['softrefs']['keys']);
            }
        }

        return array_filter($this->relations);
    }

    /**
     * Create array with field/value pairs ready to insert in database
     *
     * @param string $tableName Tablename of source record (where reference is located)
     * @param array $record Record from $table
     * @param string $fieldName Fieldname of source record (where reference is located)
     * @param string $flexPointer Pointer to location inside FlexForm structure where reference is located in [$field]
     * @param string $referencedTable In database references the tablename the reference points to. Keyword "_STRING" indicates special usage (typ. SoftReference) in $referenceString
     * @param int $referencedUid In database references the UID of the record (zero $referencedTable is "_STRING")
     * @param string $referenceString For "_STRING" references: The string.
     * @param int $sort The sorting order of references if many (the "group" or "select" TCA types). -1 if no sorting order is specified.
     * @param string $softReferenceKey If the reference is a soft reference, this is the soft reference parser key. Otherwise empty.
     * @param string $softReferenceId Soft reference ID for key. Might be useful for replace operations.
     * @return array|bool Array to insert in DB or false if record should not be processed
     */
    protected function createEntryDataUsingRecord(string $tableName, array $record, string $fieldName, string $flexPointer, string $referencedTable, int $referencedUid, string $referenceString = '', int $sort = -1, string $softReferenceKey = '', string $softReferenceId = '')
    {
        $currentWorkspace = $this->workspaceId;
        if (BackendUtility::isTableWorkspaceEnabled($tableName)) {
            $fieldConfig = $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'];
            if (isset($record['t3ver_wsid']) && (int)$record['t3ver_wsid'] !== $currentWorkspace && empty($fieldConfig['MM'])) {
                // The given record is workspace-enabled but doesn't live in the selected workspace. Don't add index, it's not actually there.
                // We still add those rows if the record is a local side live record of an MM relation and can be a target of a workspace record.
                // See workspaces ManyToMany Modify addCategoryRelation for details on this case.
                return false;
            }
        }
        return [
            'tablename' => $tableName,
            'recuid' => $record['uid'],
            'field' => $fieldName,
            'flexpointer' => $flexPointer,
            'softref_key' => $softReferenceKey,
            'softref_id' => $softReferenceId,
            'sorting' => $sort,
            'workspace' => $currentWorkspace,
            'ref_table' => $referencedTable,
            'ref_uid' => $referencedUid,
            'ref_string' => mb_substr($referenceString, 0, 1024),
        ];
    }

    /**
     * Add database references to ->relations array based on fetched record
     *
     * @param string $tableName Tablename of source record (where reference is located)
     * @param array $record Record from $tableName
     * @param string $fieldName Fieldname of source record (where reference is located)
     * @param string $flexPointer Pointer to location inside FlexForm structure where reference is located in $fieldName
     * @param array $items Data array with database relations (table/id)
     */
    protected function createEntryDataForDatabaseRelationsUsingRecord(string $tableName, array $record, string $fieldName, string $flexPointer, array $items)
    {
        foreach ($items as $sort => $i) {
            $this->relations[] = $this->createEntryDataUsingRecord($tableName, $record, $fieldName, $flexPointer, $i['table'], (int)$i['id'], '', $sort);
        }
    }

    /**
     * Add SoftReference references to ->relations array based on fetched record
     *
     * @param string $tableName Tablename of source record (where reference is located)
     * @param array $record Record from $tableName
     * @param string $fieldName Fieldname of source record (where reference is located)
     * @param string $flexPointer Pointer to location inside FlexForm structure where reference is located in $fieldName
     * @param array $keys Data array with soft reference keys
     */
    protected function createEntryDataForSoftReferencesUsingRecord(string $tableName, array $record, string $fieldName, string $flexPointer, array $keys)
    {
        foreach ($keys as $spKey => $elements) {
            if (is_array($elements)) {
                foreach ($elements as $subKey => $el) {
                    if (is_array($el['subst'] ?? false)) {
                        switch ((string)$el['subst']['type']) {
                            case 'db':
                                [$referencedTable, $referencedUid] = explode(':', $el['subst']['recordRef']);
                                $this->relations[] = $this->createEntryDataUsingRecord($tableName, $record, $fieldName, $flexPointer, $referencedTable, (int)$referencedUid, '', -1, $spKey, (string)$subKey);
                                break;
                            case 'string':
                                $this->relations[] = $this->createEntryDataUsingRecord($tableName, $record, $fieldName, $flexPointer, '_STRING', 0, $el['subst']['tokenValue'], -1, $spKey, (string)$subKey);
                                break;
                        }
                    }
                }
            }
        }
    }

    /**
     * Returns relation information for a $table/$row-array
     * Traverses all fields in input row which are configured in TCA/columns
     * It looks for hard relations to records in the TCA types "select" and "group"
     *
     * @param string $table Table name
     * @param array $row Row from table
     * @param string $onlyField Specific field to fetch for.
     * @return array Array with information about relations
     * @see export_addRecord()
     */
    public function getRelations($table, $row, $onlyField = '')
    {
        // Initialize:
        $uid = $row['uid'];
        $outRow = [];
        foreach ($row as $field => $value) {
            if ($this->shouldExcludeTableColumnFromReferenceIndex($table, $field, $onlyField) === false) {
                $conf = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
                // Add a softref definition for link fields if the TCA does not specify one already
                if ($conf['type'] === 'link' && empty($conf['softref'])) {
                    $conf['softref'] = 'typolink';
                }
                // Add a softref definition for email fields
                if ($conf['type'] === 'email') {
                    $conf['softref'] = 'email[subst]';
                }
                // Add DB:
                $resultsFromDatabase = $this->getRelations_procDB($value, $conf, $uid, $table, $row);
                if (!empty($resultsFromDatabase)) {
                    // Create an entry for the field with all DB relations:
                    $outRow[$field] = [
                        'type' => 'db',
                        'itemArray' => $resultsFromDatabase,
                    ];
                }
                // For "flex" fieldtypes we need to traverse the structure looking for db references of course!
                if ($conf['type'] === 'flex' && is_string($value) && $value !== '') {
                    // Get current value array:
                    // NOTICE: failure to resolve Data Structures can lead to integrity problems with the reference index. Please look up
                    // the note in the JavaDoc documentation for the function FlexFormTools->getDataStructureIdentifier()
                    $currentValueArray = GeneralUtility::xml2array($value);
                    // Traversing the XML structure, processing:
                    if (is_array($currentValueArray)) {
                        $this->temp_flexRelations = [
                            'db' => [],
                            'softrefs' => [],
                        ];
                        // Create and call iterator object:
                        $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
                        $flexFormTools->traverseFlexFormXMLData($table, $field, $row, $this, 'getRelations_flexFormCallBack');
                        // Create an entry for the field:
                        $outRow[$field] = [
                            'type' => 'flex',
                            'flexFormRels' => $this->temp_flexRelations,
                        ];
                    }
                }
                // Soft References:
                if ((string)$value !== '') {
                    $softRefValue = $value;
                    if (!empty($conf['softref'])) {
                        foreach ($this->softReferenceParserFactory->getParsersBySoftRefParserList($conf['softref']) as $softReferenceParser) {
                            $parserResult = $softReferenceParser->parse($table, $field, $uid, $softRefValue);
                            if ($parserResult->hasMatched()) {
                                $outRow[$field]['softrefs']['keys'][$softReferenceParser->getParserKey()] = $parserResult->getMatchedElements();
                                if ($parserResult->hasContent()) {
                                    $softRefValue = $parserResult->getContent();
                                }
                            }
                        }
                    }
                    if (!empty($outRow[$field]['softrefs']) && (string)$value !== (string)$softRefValue && str_contains($softRefValue, '{softref:')) {
                        $outRow[$field]['softrefs']['tokenizedContent'] = $softRefValue;
                    }
                }
            }
        }
        return $outRow;
    }

    /**
     * Callback function for traversing the FlexForm structure in relation to finding DB references!
     *
     * @param array $dsArr Data structure for the current value
     * @param mixed $dataValue Current value
     * @param array $PA Additional configuration used in calling function
     * @param string $structurePath Path of value in DS structure
     * @see DataHandler::checkValue_flex_procInData_travDS()
     * @see FlexFormTools::traverseFlexFormXMLData()
     */
    public function getRelations_flexFormCallBack($dsArr, $dataValue, $PA, $structurePath)
    {
        // Removing "data/" in the beginning of path (which points to location in data array)
        $structurePath = substr($structurePath, 5) . '/';
        $dsConf = $dsArr['config'];
        // Implode parameter values:
        [$table, $uid, $field] = [
            $PA['table'],
            $PA['uid'],
            $PA['field'],
        ];
        // Add a softref definition for link fields if the TCA does not specify one already
        if (($dsConf['type'] ?? '') === 'link' && empty($dsConf['softref'])) {
            $dsConf['softref'] = 'typolink';
        }
        // Add a softref definition for email fields
        if (($dsConf['type'] ?? '') === 'email') {
            $dsConf['softref'] = 'email[subst]';
        }
        // Add DB:
        $resultsFromDatabase = $this->getRelations_procDB($dataValue, $dsConf, $uid, $table);
        if (!empty($resultsFromDatabase)) {
            // Create an entry for the field with all DB relations:
            $this->temp_flexRelations['db'][$structurePath] = $resultsFromDatabase;
        }
        // Soft References:
        if (is_array($dataValue) || (string)$dataValue !== '') {
            $softRefValue = $dataValue;
            foreach ($this->softReferenceParserFactory->getParsersBySoftRefParserList($dsConf['softref'] ?? '') as $softReferenceParser) {
                $parserResult = $softReferenceParser->parse($table, $field, $uid, $softRefValue, $structurePath);
                if ($parserResult->hasMatched()) {
                    $this->temp_flexRelations['softrefs'][$structurePath]['keys'][$softReferenceParser->getParserKey()] = $parserResult->getMatchedElements();
                    if ($parserResult->hasContent()) {
                        $softRefValue = $parserResult->getContent();
                    }
                }
            }
            if (!empty($this->temp_flexRelations['softrefs']) && (string)$dataValue !== (string)$softRefValue) {
                $this->temp_flexRelations['softrefs'][$structurePath]['tokenizedContent'] = $softRefValue;
            }
        }
    }

    /**
     * Check field configuration if it is a DB relation field and extract DB relations if any
     *
     * @param string $value Field value
     * @param array $conf Field configuration array of type "TCA/columns
     * @param int $uid Field uid
     * @param string $table Table name
     * @return array|bool If field type is OK it will return an array with the database relations. Else FALSE
     */
    protected function getRelations_procDB($value, $conf, $uid, $table = '', array $row = [])
    {
        // Get IRRE relations
        if (empty($conf)) {
            return false;
        }
        if (($conf['type'] === 'inline' || $conf['type'] === 'file') && !empty($conf['foreign_table']) && empty($conf['MM'])) {
            $dbAnalysis = GeneralUtility::makeInstance(RelationHandler::class);
            $dbAnalysis->setUseLiveReferenceIds(false);
            $dbAnalysis->setWorkspaceId($this->workspaceId);
            $dbAnalysis->start($value, $conf['foreign_table'], '', $uid, $table, $conf);
            return $dbAnalysis->itemArray;
            // DB record lists:
        }
        if ($this->isDbReferenceField($conf)) {
            $allowedTables = $conf['type'] === 'group' ? $conf['allowed'] : $conf['foreign_table'];
            if ($conf['MM_opposite_field'] ?? false) {
                // Never handle sys_refindex when looking at MM from foreign side
                return [];
            }

            $dbAnalysis = GeneralUtility::makeInstance(RelationHandler::class);
            $dbAnalysis->setWorkspaceId($this->workspaceId);
            $dbAnalysis->start($value, $allowedTables, $conf['MM'] ?? '', $uid, $table, $conf);
            $itemArray = $dbAnalysis->itemArray;

            if (ExtensionManagementUtility::isLoaded('workspaces')
                && $this->workspaceId > 0
                && !empty($conf['MM'] ?? '')
                && !empty($conf['allowed'] ?? '')
                && empty($conf['MM_opposite_field'] ?? '')
                && (int)($row['t3ver_wsid'] ?? 0) === 0
            ) {
                // When dealing with local side mm relations in workspace 0, there may be workspace records on the foreign
                // side, for instance when those got an additional category. See ManyToMany Modify addCategoryRelations test.
                // In those cases, the full set of relations must be written to sys_refindex as workspace rows.
                // But, if the relations in this workspace and live are identical, no sys_refindex workspace rows
                // have to be added.
                $dbAnalysis = GeneralUtility::makeInstance(RelationHandler::class);
                $dbAnalysis->setWorkspaceId(0);
                $dbAnalysis->start($value, $allowedTables, $conf['MM'], $uid, $table, $conf);
                $itemArrayLive = $dbAnalysis->itemArray;
                if ($itemArrayLive === $itemArray) {
                    $itemArray = false;
                }
            }
            return $itemArray;
        }
        return false;
    }

    /*******************************
     *
     * Setting values
     *
     *******************************/

    /**
     * Setting the value of a reference or removing it completely.
     * Usage: For lowlevel clean up operations!
     * WARNING: With this you can set values that are not allowed in the database since it will bypass all checks for validity!
     * Hence it is targeted at clean-up operations. Please use DataHandler in the usual ways if you wish to manipulate references.
     * Since this interface allows updates to soft reference values (which DataHandler does not directly) you may like to use it
     * for that as an exception to the warning above.
     * Notice; If you want to remove multiple references from the same field, you MUST start with the one having the highest
     * sorting number. If you don't the removal of a reference with a lower number will recreate an index in which the remaining
     * references in that field has new hash-keys due to new sorting numbers - and you will get errors for the remaining operations
     * which cannot find the hash you feed it!
     * To ensure proper working only admin-BE_USERS in live workspace should use this function
     *
     * @param string $hash 32-byte hash string identifying the record from sys_refindex which you wish to change the value for
     * @param mixed $newValue Value you wish to set for reference. If NULL, the reference is removed (unless a soft-reference in which case it can
     *                        only be set to a blank string). If you wish to set a database reference, use the format "[table]:[uid]".
     *                        Any other case, the input value is set as-is
     * @param bool $returnDataArray Return $dataArray only, do not submit it to database.
     * @param bool $bypassWorkspaceAdminCheck If set, it will bypass check for workspace-zero and admin user
     * @return string|bool|array FALSE (=OK), error message string or array (if $returnDataArray is set!)
     */
    public function setReferenceValue($hash, $newValue, $returnDataArray = false, $bypassWorkspaceAdminCheck = false)
    {
        $backendUser = $this->getBackendUser();
        if ($backendUser->workspace === 0 && $backendUser->isAdmin() || $bypassWorkspaceAdminCheck) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');
            $queryBuilder->getRestrictions()->removeAll();

            // Get current index from Database
            $referenceRecord = $queryBuilder
                ->select('*')
                ->from('sys_refindex')
                ->where(
                    $queryBuilder->expr()->eq('hash', $queryBuilder->createNamedParameter($hash))
                )
                ->setMaxResults(1)
                ->executeQuery()
                ->fetchAssociative();

            // Check if reference existed.
            if (!is_array($referenceRecord)) {
                return 'ERROR: No reference record with hash="' . $hash . '" was found!';
            }

            if (empty($GLOBALS['TCA'][$referenceRecord['tablename']])) {
                return 'ERROR: Table "' . $referenceRecord['tablename'] . '" was not in TCA!';
            }

            // Get that record from database
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable($referenceRecord['tablename']);
            $queryBuilder->getRestrictions()->removeAll();
            $record = $queryBuilder
                ->select('*')
                ->from($referenceRecord['tablename'])
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($referenceRecord['recuid'], Connection::PARAM_INT)
                    )
                )
                ->setMaxResults(1)
                ->executeQuery()
                ->fetchAssociative();

            if (is_array($record)) {
                // Get relation for single field from record
                $recordRelations = $this->getRelations($referenceRecord['tablename'], $record, $referenceRecord['field']);
                if ($fieldRelation = $recordRelations[$referenceRecord['field']]) {
                    // Initialize data array that is to be sent to DataHandler afterwards:
                    $dataArray = [];
                    // Based on type
                    switch ((string)$fieldRelation['type']) {
                        case 'db':
                            $error = $this->setReferenceValue_dbRels($referenceRecord, $fieldRelation['itemArray'], $newValue, $dataArray);
                            if ($error) {
                                return $error;
                            }
                            break;
                        case 'flex':
                            // DB references in FlexForms
                            if (is_array($fieldRelation['flexFormRels']['db'][$referenceRecord['flexpointer']])) {
                                $error = $this->setReferenceValue_dbRels($referenceRecord, $fieldRelation['flexFormRels']['db'][$referenceRecord['flexpointer']], $newValue, $dataArray, $referenceRecord['flexpointer']);
                                if ($error) {
                                    return $error;
                                }
                            }
                            // Soft references in FlexForms
                            if ($referenceRecord['softref_key'] && is_array($fieldRelation['flexFormRels']['softrefs'][$referenceRecord['flexpointer']]['keys'][$referenceRecord['softref_key']])) {
                                $error = $this->setReferenceValue_softreferences($referenceRecord, $fieldRelation['flexFormRels']['softrefs'][$referenceRecord['flexpointer']], $newValue, $dataArray, $referenceRecord['flexpointer']);
                                if ($error) {
                                    return $error;
                                }
                            }
                            break;
                    }
                    // Soft references in the field:
                    if ($referenceRecord['softref_key'] && is_array($fieldRelation['softrefs']['keys'][$referenceRecord['softref_key']])) {
                        $error = $this->setReferenceValue_softreferences($referenceRecord, $fieldRelation['softrefs'], $newValue, $dataArray);
                        if ($error) {
                            return $error;
                        }
                    }
                    // Data Array, now ready to be sent to DataHandler
                    if ($returnDataArray) {
                        return $dataArray;
                    }
                    // Execute CMD array:
                    $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
                    $dataHandler->dontProcessTransformations = true;
                    $dataHandler->bypassWorkspaceRestrictions = true;
                    // Otherwise this may lead to permission issues if user is not admin
                    $dataHandler->bypassAccessCheckForRecords = true;
                    // Check has been done previously that there is a backend user which is Admin and also in live workspace
                    $dataHandler->start($dataArray, []);
                    $dataHandler->process_datamap();
                    // Return errors if any:
                    if (!empty($dataHandler->errorLog)) {
                        return LF . 'DataHandler:' . implode(LF . 'DataHandler:', $dataHandler->errorLog);
                    }
                }
            }
        } else {
            return 'ERROR: BE_USER object is not admin OR not in workspace 0 (Live)';
        }

        return false;
    }

    /**
     * Setting a value for a reference for a DB field:
     *
     * @param array $refRec sys_refindex record
     * @param array $itemArray Array of references from that field
     * @param string|null $newValue Value to substitute current value with (or NULL to unset it)
     * @param array $dataArray Data array in which the new value is set (passed by reference)
     * @param string $flexPointer Flexform pointer, if in a flex form field.
     * @return string Error message if any, otherwise FALSE = OK
     */
    protected function setReferenceValue_dbRels($refRec, $itemArray, $newValue, &$dataArray, $flexPointer = '')
    {
        if ((int)$itemArray[$refRec['sorting']]['id'] === (int)$refRec['ref_uid'] && (string)$itemArray[$refRec['sorting']]['table'] === (string)$refRec['ref_table']) {
            // Setting or removing value:
            // Remove value:
            if ($newValue === null) {
                unset($itemArray[$refRec['sorting']]);
            } else {
                [$itemArray[$refRec['sorting']]['table'], $itemArray[$refRec['sorting']]['id']] = explode(':', $newValue);
            }
            // Traverse and compile new list of records:
            $saveValue = [];
            foreach ($itemArray as $pair) {
                $saveValue[] = $pair['table'] . '_' . $pair['id'];
            }
            // Set in data array:
            if ($flexPointer) {
                $dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']]['data'] = ArrayUtility::setValueByPath(
                    [],
                    substr($flexPointer, 0, -1),
                    implode(',', $saveValue)
                );
            } else {
                $dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']] = implode(',', $saveValue);
            }
        } else {
            return 'ERROR: table:id pair "' . $refRec['ref_table'] . ':' . $refRec['ref_uid'] . '" did not match that of the record ("' . $itemArray[$refRec['sorting']]['table'] . ':' . $itemArray[$refRec['sorting']]['id'] . '") in sorting index "' . $refRec['sorting'] . '"';
        }

        return false;
    }

    /**
     * Setting a value for a soft reference token
     *
     * @param array $refRec sys_refindex record
     * @param array $softref Array of soft reference occurrences
     * @param string $newValue Value to substitute current value with
     * @param array $dataArray Data array in which the new value is set (passed by reference)
     * @param string $flexPointer Flexform pointer, if in a flex form field.
     * @return string Error message if any, otherwise FALSE = OK
     */
    protected function setReferenceValue_softreferences($refRec, $softref, $newValue, &$dataArray, $flexPointer = '')
    {
        if (!is_array($softref['keys'][$refRec['softref_key']][$refRec['softref_id']])) {
            return 'ERROR: Soft reference parser key "' . $refRec['softref_key'] . '" or the index "' . $refRec['softref_id'] . '" was not found.';
        }

        // Set new value:
        $softref['keys'][$refRec['softref_key']][$refRec['softref_id']]['subst']['tokenValue'] = '' . $newValue;
        // Traverse softreferences and replace in tokenized content to rebuild it with new value inside:
        foreach ($softref['keys'] as $sfIndexes) {
            foreach ($sfIndexes as $data) {
                $softref['tokenizedContent'] = str_replace('{softref:' . $data['subst']['tokenID'] . '}', $data['subst']['tokenValue'], $softref['tokenizedContent']);
            }
        }
        // Set in data array:
        if (!str_contains($softref['tokenizedContent'], '{softref:')) {
            if ($flexPointer) {
                $dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']]['data'] = ArrayUtility::setValueByPath(
                    [],
                    substr($flexPointer, 0, -1),
                    $softref['tokenizedContent']
                );
            } else {
                $dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']] = $softref['tokenizedContent'];
            }
        } else {
            return 'ERROR: After substituting all found soft references there were still soft reference tokens in the text. (theoretically this does not have to be an error if the string "{softref:" happens to be in the field for another reason.)';
        }

        return false;
    }

    /*******************************
     *
     * Helper functions
     *
     *******************************/

    /**
     * Returns TRUE if the TCA/columns field type is a DB reference field
     *
     * @param array $configuration Config array for TCA/columns field
     * @return bool TRUE if DB reference field (group/db or select with foreign-table)
     */
    protected function isDbReferenceField(array $configuration): bool
    {
        return
            $configuration['type'] === 'group'
            || (
                in_array($configuration['type'], ['select', 'category', 'inline', 'file'], true)
                && !empty($configuration['foreign_table'])
            );
    }

    /**
     * Returns TRUE if the TCA/columns field type is a reference field
     *
     * @param array $configuration Config array for TCA/columns field
     * @return bool TRUE if reference field
     */
    protected function isReferenceField(array $configuration): bool
    {
        return
            $this->isDbReferenceField($configuration)
            || $configuration['type'] === 'link'
            || $configuration['type'] === 'email'
            || $configuration['type'] === 'flex'
            || isset($configuration['softref'])
        ;
    }

    /**
     * Early check to see if a table has any possible relation fields at all.
     * This is true if there are columns with type group, select and friends,
     * or if a table has a column with a 'softref' defined.
     */
    protected function hasTableRelationFields(string $tableName): bool
    {
        if (empty($GLOBALS['TCA'][$tableName]['columns'])) {
            return false;
        }
        foreach ($GLOBALS['TCA'][$tableName]['columns'] as $fieldDefinition) {
            if (empty($fieldDefinition['config'])) {
                continue;
            }
            if ($this->isReferenceField($fieldDefinition['config'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns all fields of a table which could contain a relation
     *
     * @param string $tableName Name of the table
     * @return array Fields which may contain relations
     */
    protected function fetchTableRelationFields(string $tableName): array
    {
        if (!empty($this->tableRelationFieldCache[$tableName])) {
            return $this->tableRelationFieldCache[$tableName];
        }
        if (!isset($GLOBALS['TCA'][$tableName]['columns'])) {
            return [];
        }
        $fields = [];
        foreach ($GLOBALS['TCA'][$tableName]['columns'] as $field => $fieldDefinition) {
            if (is_array($fieldDefinition['config'])) {
                // Check for flex field
                if (isset($fieldDefinition['config']['type']) && $fieldDefinition['config']['type'] === 'flex') {
                    // Fetch all fields if the is a field of type flex in the table definition because the complete row is passed to
                    // FlexFormTools->getDataStructureIdentifier() in the end and might be needed in ds_pointerField or a hook
                    $this->tableRelationFieldCache[$tableName] = ['*'];
                    return ['*'];
                }
                // Only fetch this field if it can contain a reference
                if ($this->isReferenceField($fieldDefinition['config'])) {
                    $fields[] = $field;
                }
            }
        }
        $this->tableRelationFieldCache[$tableName] = $fields;
        return $fields;
    }

    /**
     * Updating Index (External API)
     *
     * @param ProgressListenerInterface|null $progressListener If set, the current progress is added to the listener
     * @return array Header and body status content
     * @todo: Consider moving this together with the helper methods to a dedicated class.
     */
    public function updateIndex(bool $testOnly, ?ProgressListenerInterface $progressListener = null): array
    {
        $errors = [];
        $numberOfHandledRecords = 0;

        $isWorkspacesLoaded = ExtensionManagementUtility::isLoaded('workspaces');
        $tcaTableNames = array_keys($GLOBALS['TCA']);
        sort($tcaTableNames);

        $progressListener?->log('Remember to create missing tables and columns before running this.', LogLevel::WARNING);

        // Remove dangling workspace sys_refindex rows
        $listOfActiveWorkspaces = $this->getListOfActiveWorkspaces();
        $numberOfUnusedWorkspaceRows = $testOnly
            ? $this->getNumberOfUnusedWorkspaceRowsInReferenceIndex($listOfActiveWorkspaces)
            : $this->removeUnusedWorkspaceRowsFromReferenceIndex($listOfActiveWorkspaces);
        if ($numberOfUnusedWorkspaceRows > 0) {
            $error = 'Index table hosted ' . $numberOfUnusedWorkspaceRows . ' indexes for non-existing or deleted workspaces, now removed.';
            $errors[] = $error;
            $progressListener?->log($error, LogLevel::WARNING);
        }

        // Remove sys_refindex rows of tables no longer defined in TCA
        $numberOfRowsOfOldTables = $testOnly
            ? $this->getNumberOfUnusedTablesInReferenceIndex($tcaTableNames)
            : $this->removeReferenceIndexDataFromUnusedDatabaseTables($tcaTableNames);
        if ($numberOfRowsOfOldTables > 0) {
            $error = 'Index table hosted ' . $numberOfRowsOfOldTables . ' indexes for non-existing tables, now removed';
            $errors[] = $error;
            $progressListener?->log($error, LogLevel::WARNING);
        }

        // Main loop traverses all records of all TCA tables
        foreach ($tcaTableNames as $tableName) {
            $tableTca = $GLOBALS['TCA'][$tableName];

            // Count number of records in table to have a correct $numberOfHandledRecords in the end
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
            $queryBuilder->getRestrictions()->removeAll();
            $numberOfRecordsInTargetTable = $queryBuilder
                ->count('uid')
                ->from($tableName)
                ->executeQuery()
                ->fetchOne();

            $progressListener?->start($numberOfRecordsInTargetTable, $tableName);

            if ($numberOfRecordsInTargetTable === 0
                || $this->shouldExcludeTableFromReferenceIndex($tableName)
                || !$this->hasTableRelationFields($tableName)
            ) {
                // Table is empty, should be excluded, or can not have relations. Blindly remove any existing sys_refindex rows.
                $numberOfHandledRecords += $numberOfRecordsInTargetTable;
                $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');
                $queryBuilder->getRestrictions()->removeAll();
                if ($testOnly) {
                    $countDeleted = $queryBuilder
                        ->count('hash')
                        ->from('sys_refindex')
                        ->where(
                            $queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter($tableName))
                        )
                        ->executeQuery()
                        ->fetchOne();
                } else {
                    $countDeleted = $queryBuilder
                        ->delete('sys_refindex')
                        ->where(
                            $queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter($tableName))
                        )
                        ->executeStatement();
                }
                if ($countDeleted > 0) {
                    $error = 'Index table hosted ' . $countDeleted . ' ignored or outdated indexed, now removed.';
                    $errors[] = $error;
                    $progressListener?->log($error, LogLevel::WARNING);
                }
                $progressListener?->finish();
                continue;
            }

            // Delete lost indexes of table: sys_refindex rows where the uid no longer exists in target table.
            $subQueryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
            $subQueryBuilder->getRestrictions()->removeAll();
            $subQueryBuilder
                ->select('uid')
                ->from($tableName, 'sub_' . $tableName)
                ->where(
                    $subQueryBuilder->expr()->eq('sub_' . $tableName . '.uid', $subQueryBuilder->quoteIdentifier('sys_refindex.recuid'))
                );
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');
            $queryBuilder->getRestrictions()->removeAll();
            if ($testOnly) {
                $numberOfRefindexRowsWithoutExistingTableRow = $queryBuilder
                    ->count('hash')
                    ->from('sys_refindex')
                    ->where(
                        $queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter($tableName)),
                        'NOT EXISTS (' . $subQueryBuilder->getSQL() . ')'
                    )
                    ->executeQuery()
                    ->fetchOne();
            } else {
                $numberOfRefindexRowsWithoutExistingTableRow = $queryBuilder
                    ->delete('sys_refindex')
                    ->where(
                        $queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter($tableName)),
                        'NOT EXISTS (' . $subQueryBuilder->getSQL() . ')'
                    )
                    ->executeStatement();
            }
            if ($numberOfRefindexRowsWithoutExistingTableRow > 0) {
                $error = 'Table ' . $tableName . ' has ' . $numberOfRefindexRowsWithoutExistingTableRow . ' lost indexes which are now deleted';
                $errors[] = $error;
                $progressListener?->log($error, LogLevel::WARNING);
            }

            // Some additional magic is needed if the table has a field that is the local side of
            // a mm relation. See the variable usage below for details.
            $tableHasLocalSideMmRelation = false;
            foreach (($tableTca['columns'] ?? []) as $fieldConfig) {
                if (!empty($fieldConfig['config']['MM'] ?? '')
                    && !empty($fieldConfig['config']['allowed'] ?? '')
                    && empty($fieldConfig['config']['MM_opposite_field'] ?? '')
                ) {
                    $tableHasLocalSideMmRelation = true;
                }
            }

            $fields = ['uid'];
            if (BackendUtility::isTableWorkspaceEnabled($tableName)) {
                $fields[] = 't3ver_wsid';
            }

            // Traverse all records in tables, including deleted records
            // @todo: Potential optimization - Fetch list of 'deleted=1' records first and delete
            //        all their sys_refindex rows, maybe use a sub select in one query, or chunking?
            //        Then only fetch deleted=0 records below. Needs investigation on MM
            //        workspace records, though?
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
            $queryBuilder->getRestrictions()->removeAll();
            $queryResult = $queryBuilder
                ->select(...$fields)
                ->from($tableName)
                ->orderBy('uid')
                ->executeQuery();

            while ($record = $queryResult->fetchAssociative()) {
                $progressListener?->advance();

                if ($isWorkspacesLoaded && $tableHasLocalSideMmRelation && (int)($record['t3ver_wsid'] ?? 0) === 0) {
                    // If we have a record that can be the local side of a workspace relation, workspace records
                    // may point to it, even though the record has no workspace overlay. See workspace ManyToMany
                    // Modify addCategoryRelation as example. In those cases, we need to iterate all active workspaces
                    // and update refindex for all foreign workspace records that point to it.
                    foreach ($listOfActiveWorkspaces as $workspaceId) {
                        $result = $this->updateRefIndexTable($tableName, (int)$record['uid'], $testOnly, $workspaceId);
                        $numberOfHandledRecords++;
                        if ($result['addedNodes'] || $result['deletedNodes']) {
                            $error = 'Record ' . $tableName . ':' . $record['uid'] . ' had ' . $result['addedNodes'] . ' added indexes and ' . $result['deletedNodes'] . ' deleted indexes';
                            $errors[] = $error;
                            $progressListener?->log($error, LogLevel::WARNING);
                        }
                    }
                } else {
                    $result = $this->updateRefIndexTable($tableName, (int)$record['uid'], $testOnly, (int)($record['t3ver_wsid'] ?? 0));
                    $numberOfHandledRecords++;
                    if ($result['addedNodes'] || $result['deletedNodes']) {
                        $error = 'Record ' . $tableName . ':' . $record['uid'] . ' had ' . $result['addedNodes'] . ' added indexes and ' . $result['deletedNodes'] . ' deleted indexes';
                        $errors[] = $error;
                        $progressListener?->log($error, LogLevel::WARNING);
                    }
                }
            }

            $progressListener?->finish();
        }

        $errorCount = count($errors);
        $recordsCheckedString = $numberOfHandledRecords . ' records from ' . count($tcaTableNames) . ' tables were checked/updated.';
        if ($errorCount) {
            $progressListener?->log($recordsCheckedString . ' Updates: ' . $errorCount, LogLevel::WARNING);
        } else {
            $progressListener?->log($recordsCheckedString . ' Index Integrity was perfect!');
        }
        if (!$testOnly) {
            $this->registry->set('core', 'sys_refindex_lastUpdate', $GLOBALS['EXEC_TIME']);
        }
        return ['resultText' => trim($recordsCheckedString), 'errors' => $errors];
    }

    /**
     * Create list of non-deleted "active" workspace uids. This contains at least 0 "live workspace".
     *
     * @return int[]
     */
    private function getListOfActiveWorkspaces(): array
    {
        if (!ExtensionManagementUtility::isLoaded('workspaces')) {
            // If ext:workspaces is not loaded, "0" is the only valid one.
            return [0];
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_workspace');
        // Workspaces can't be 'hidden', so we only use deleted restriction here.
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $result = $queryBuilder->select('uid')->from('sys_workspace')->orderBy('uid')->executeQuery();
        // "0", plus non-deleted workspaces are active
        $activeWorkspaces = [0];
        while ($row = $result->fetchFirstColumn()) {
            $activeWorkspaces[] = (int)$row[0];
        }
        return $activeWorkspaces;
    }

    /**
     * Helper method of updateIndex() to find number of rows in sys_refindex that
     * relate to a non-existing or deleted workspace record, even if workspaces is
     * not loaded at all, but has been loaded somewhere in the past and sys_refindex
     * rows have been created.
     */
    private function getNumberOfUnusedWorkspaceRowsInReferenceIndex(array $activeWorkspaces): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');
        $queryBuilder->getRestrictions()->removeAll();
        $numberOfInvalidWorkspaceRecords = $queryBuilder
            ->count('hash')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->notIn('workspace', $queryBuilder->createNamedParameter($activeWorkspaces, Connection::PARAM_INT_ARRAY))
            )
            ->executeQuery()
            ->fetchOne();
        return (int)$numberOfInvalidWorkspaceRecords;
    }

    /**
     * Delete sys_refindex rows of deleted / not existing workspace records, or all if ext:workspace is not loaded.
     */
    private function removeUnusedWorkspaceRowsFromReferenceIndex(array $activeWorkspaces): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder
            ->delete('sys_refindex')
            ->where(
                $queryBuilder->expr()->notIn('workspace', $queryBuilder->createNamedParameter($activeWorkspaces, Connection::PARAM_INT_ARRAY))
            )
            ->executeStatement();
    }

    /**
     * When a TCA table with references has been removed, there may be old sys_refindex
     * rows for it. The query finds the number of affected rows.
     */
    protected function getNumberOfUnusedTablesInReferenceIndex(array $tableNames): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');
        $queryBuilder->getRestrictions()->removeAll();
        $numberOfRowsOfUnusedTables = $queryBuilder
            ->count('hash')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->notIn('tablename', $queryBuilder->createNamedParameter($tableNames, Connection::PARAM_STR_ARRAY))
            )
            ->executeQuery()
            ->fetchOne();
        return (int)$numberOfRowsOfUnusedTables;
    }

    /**
     * When a TCA table with references has been removed, there may be old sys_refindex
     * rows for it. The query deletes those.
     */
    protected function removeReferenceIndexDataFromUnusedDatabaseTables(array $tableNames): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder
            ->delete('sys_refindex')
            ->where(
                $queryBuilder->expr()->notIn('tablename', $queryBuilder->createNamedParameter($tableNames, Connection::PARAM_STR_ARRAY))
            )
            ->executeStatement();
    }

    /**
     * Get one record from database.
     *
     * @return array|false
     */
    protected function getRecord(string $tableName, int $uid)
    {
        // Fetch fields of the table which might contain relations
        $tableRelationFields = $this->fetchTableRelationFields($tableName);

        if ($tableRelationFields === []) {
            // Return if there are no fields which could contain relations
            return $this->relations;
        }
        if ($tableRelationFields !== ['*']) {
            // Only fields that might contain relations are fetched
            $tableRelationFields[] = 'uid';
            if (BackendUtility::isTableWorkspaceEnabled($tableName)) {
                $tableRelationFields = array_merge($tableRelationFields, ['t3ver_wsid', 't3ver_state']);
            }
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->select(...array_unique($tableRelationFields))
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                )
            );
        // Do not fetch soft deleted records
        $deleteField = (string)($GLOBALS['TCA'][$tableName]['ctrl']['delete'] ?? '');
        if ($deleteField !== '') {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    $deleteField,
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                )
            );
        }
        return $queryBuilder->executeQuery()->fetchAssociative();
    }

    /**
     * Checks if a given table should be excluded from ReferenceIndex
     */
    protected function shouldExcludeTableFromReferenceIndex(string $tableName): bool
    {
        if (isset($this->excludedTables[$tableName])) {
            return $this->excludedTables[$tableName];
        }
        $event = new IsTableExcludedFromReferenceIndexEvent($tableName);
        $event = $this->eventDispatcher->dispatch($event);
        $this->excludedTables[$tableName] = $event->isTableExcluded();
        return $this->excludedTables[$tableName];
    }

    /**
     * Checks if a given column in a given table should be excluded in the ReferenceIndex process
     *
     * @param string $tableName Name of the table
     * @param string $column Name of the column
     * @param string $onlyColumn Name of a specific column to fetch
     * @return bool true if it should be excluded
     */
    protected function shouldExcludeTableColumnFromReferenceIndex(string $tableName, string $column, string $onlyColumn): bool
    {
        if (isset($this->excludedColumns[$column])) {
            return true;
        }
        if (is_array($GLOBALS['TCA'][$tableName]['columns'][$column] ?? false)
            && (!$onlyColumn || $onlyColumn === $column)
        ) {
            return false;
        }
        return true;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
