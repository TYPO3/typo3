<?php
namespace TYPO3\CMS\Core\Database;

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

use Doctrine\DBAL\DBALException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Platform\PlatformInformation;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageRendererResolver;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * Reference index processing and relation extraction
 *
 * NOTICE: When the reference index is updated for an offline version the results may not be correct.
 * First, lets assumed that the reference update happens in LIVE workspace (ALWAYS update from Live workspace if you analyze whole database!)
 * Secondly, lets assume that in a Draft workspace you have changed the data structure of a parent page record - this is (in TemplaVoila) inherited by subpages.
 * When in the LIVE workspace the data structure for the records/pages in the offline workspace will not be evaluated to the right one simply because the data
 * structure is taken from a rootline traversal and in the Live workspace that will NOT include the changed DataStructure! Thus the evaluation will be based
 * on the Data Structure set in the Live workspace!
 * Somehow this scenario is rarely going to happen. Yet, it is an inconsistency and I see now practical way to handle it - other than simply ignoring
 * maintaining the index for workspace records. Or we can say that the index is precise for all Live elements while glitches might happen in an offline workspace?
 * Anyway, I just wanted to document this finding - I don't think we can find a solution for it. And its very TemplaVoila specific.
 */
class ReferenceIndex implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Definition of tables to exclude from the ReferenceIndex
     *
     * Only tables which do not contain any relations and never did so far since references also won't be deleted for
     * these. Since only tables with an entry in $GLOBALS['TCA] are handled by ReferenceIndex there is no need to add
     * *_mm-tables.
     *
     * Implemented as array with fields as keys and booleans as values for fast isset() lookup instead of slow in_array()
     *
     * @var array
     * @see updateRefIndexTable()
     * @see shouldExcludeTableFromReferenceIndex()
     */
    protected static $excludedTables = [
        'sys_log' => true,
        'sys_history' => true,
        'tx_extensionmanager_domain_model_extension' => true
    ];

    /**
     * Definition of fields to exclude from ReferenceIndex in *every* table
     *
     * Implemented as array with fields as keys and booleans as values for fast isset() lookup instead of slow in_array()
     *
     * @var array
     * @see getRelations()
     * @see fetchTableRelationFields()
     * @see shouldExcludeTableColumnFromReferenceIndex()
     */
    protected static $excludedColumns = [
        'uid' => true,
        'perms_userid' => true,
        'perms_groupid' => true,
        'perms_user' => true,
        'perms_group' => true,
        'perms_everybody' => true,
        'pid' => true
    ];

    /**
     * Fields of tables that could contain relations are cached per table. This is the prefix for the cache entries since
     * the runtimeCache has a global scope.
     *
     * @var string
     */
    protected static $cachePrefixTableRelationFields = 'core-refidx-tblRelFields-';

    /**
     * This array holds the FlexForm references of a record
     *
     * @var array
     * @see getRelations(),FlexFormTools::traverseFlexFormXMLData(),getRelations_flexFormCallBack()
     */
    public $temp_flexRelations = [];

    /**
     * This variable used to indicate whether referencing should take workspace overlays into account
     * It is not used since commit 0c34dac08605ba from 10.04.2006, the bug is investigated in https://forge.typo3.org/issues/65725
     *
     * @var bool
     * @see getRelations()
     */
    public $WSOL = false;

    /**
     * An index of all found references of a single record created in createEntryData() and accumulated in generateRefIndexData()
     *
     * @var array
     * @see createEntryData(),generateRefIndexData()
     */
    public $relations = [];

    /**
     * A cache to avoid that identical rows are refetched from the database
     *
     * @var array
     * @see getRecordRawCached()
     */
    protected $recordCache = [];

    /**
     * Number which we can increase if a change in the code means we will have to force a re-generation of the index.
     *
     * @var int
     * @see updateRefIndexTable()
     */
    public $hashVersion = 1;

    /**
     * Current workspace id
     *
     * @var int
     */
    protected $workspaceId = 0;

    /**
     * Runtime Cache to store and retrieve data computed for a single request
     *
     * @var \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface
     */
    protected $runtimeCache;

    /**
     * Enables $runtimeCache and $recordCache
     * @var bool
     */
    protected $useRuntimeCache = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->runtimeCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_runtime');
    }

    /**
     * Sets the current workspace id
     *
     * @param int $workspaceId
     * @see updateIndex()
     */
    public function setWorkspaceId($workspaceId)
    {
        $this->workspaceId = (int)$workspaceId;
    }

    /**
     * Gets the current workspace id
     *
     * @return int
     * @see updateRefIndexTable(),createEntryData()
     */
    public function getWorkspaceId()
    {
        return $this->workspaceId;
    }

    /**
     * Call this function to update the sys_refindex table for a record (even one just deleted)
     * NOTICE: Currently, references updated for a deleted-flagged record will not include those from within FlexForm
     * fields in some cases where the data structure is defined by another record since the resolving process ignores
     * deleted records! This will also result in bad cleaning up in DataHandler I think... Anyway, that's the story of
     * FlexForms; as long as the DS can change, lots of references can get lost in no time.
     *
     * @param string $tableName Table name
     * @param int $uid UID of record
     * @param bool $testOnly If set, nothing will be written to the index but the result value will still report statistics on what is added, deleted and kept. Can be used for mere analysis.
     * @return array Array with statistics about how many index records were added, deleted and not altered plus the complete reference set for the record.
     */
    public function updateRefIndexTable($tableName, $uid, $testOnly = false)
    {
        // First, secure that the index table is not updated with workspace tainted relations:
        $this->WSOL = false;

        // Init:
        $result = [
            'keptNodes' => 0,
            'deletedNodes' => 0,
            'addedNodes' => 0
        ];

        $uid = $uid ? (int)$uid : 0;
        if (!$uid) {
            return $result;
        }

        // If this table cannot contain relations, skip it
        if ($this->shouldExcludeTableFromReferenceIndex($tableName)) {
            return $result;
        }

        // Fetch tableRelationFields and save them in cache if not there yet
        $cacheId = static::$cachePrefixTableRelationFields . $tableName;
        if (!$this->useRuntimeCache || !$this->runtimeCache->has($cacheId)) {
            $tableRelationFields = $this->fetchTableRelationFields($tableName);
            $this->runtimeCache->set($cacheId, $tableRelationFields);
        } else {
            $tableRelationFields = $this->runtimeCache->get($cacheId);
        }

        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable('sys_refindex');

        // Get current index from Database with hash as index using $uidIndexField
        // no restrictions are needed, since sys_refindex is not a TCA table
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $queryResult = $queryBuilder->select('hash')->from('sys_refindex')->where(
            $queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter($tableName, \PDO::PARAM_STR)),
            $queryBuilder->expr()->eq('recuid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)),
            $queryBuilder->expr()->eq(
                'workspace',
                $queryBuilder->createNamedParameter($this->getWorkspaceId(), \PDO::PARAM_INT)
            )
        )->execute();
        $currentRelationHashes = [];
        while ($relation = $queryResult->fetch()) {
            $currentRelationHashes[$relation['hash']] = true;
        }

        // If the table has fields which could contain relations and the record does exist (including deleted-flagged)
        if ($tableRelationFields !== '') {
            $existingRecord = $this->getRecordRawCached($tableName, $uid);
            if ($existingRecord) {
                // Table has relation fields and record exists - get relations
                $this->relations = [];
                $relations = $this->generateDataUsingRecord($tableName, $existingRecord);
                if (!is_array($relations)) {
                    return $result;
                }
                // Traverse the generated index:
                foreach ($relations as &$relation) {
                    if (!is_array($relation)) {
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
        }

        // If any old are left, remove them:
        if (!empty($currentRelationHashes)) {
            $hashList = array_keys($currentRelationHashes);
            if (!empty($hashList)) {
                $result['deletedNodes'] = count($hashList);
                $result['deletedNodes_hashList'] = implode(',', $hashList);
                if (!$testOnly) {
                    $maxBindParameters = PlatformInformation::getMaxBindParameters($connection->getDatabasePlatform());
                    foreach (array_chunk($hashList, $maxBindParameters - 10, true) as $chunk) {
                        if (empty($chunk)) {
                            continue;
                        }
                        $queryBuilder = $connection->createQueryBuilder();
                        $queryBuilder
                            ->delete('sys_refindex')
                            ->where(
                                $queryBuilder->expr()->in(
                                    'hash',
                                    $queryBuilder->createNamedParameter($chunk, Connection::PARAM_STR_ARRAY)
                                )
                            )
                            ->execute();
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Returns array of arrays with an index of all references found in record from table/uid
     * If the result is used to update the sys_refindex table then ->WSOL must NOT be TRUE (no workspace overlay anywhere!)
     *
     * @param string $tableName Table name from $GLOBALS['TCA']
     * @param int $uid Record UID
     * @return array|null Index Rows
     */
    public function generateRefIndexData($tableName, $uid)
    {
        if (!isset($GLOBALS['TCA'][$tableName])) {
            return null;
        }

        $this->relations = [];

        $record = null;
        $uid = $uid ? (int)$uid : 0;
        if ($uid) {
            // Get raw record from DB
            $record = $this->getRecordRawCached($tableName, $uid);
        }

        if (!is_array($record)) {
            return null;
        }

        return $this->generateDataUsingRecord($tableName, $record);
    }

    /**
     * Returns the amount of references for the given record
     *
     * @param string $tableName
     * @param int $uid
     * @return int
     */
    public function getNumberOfReferencedRecords(string $tableName, int $uid): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_refindex');
        return (int)$queryBuilder
            ->count('*')->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->eq(
                    'ref_table',
                    $queryBuilder->createNamedParameter($tableName, \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'ref_uid',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'deleted',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            )->execute()->fetchColumn(0);
    }

    /**
     * Calculate the relations for a record of a given table
     *
     * @param string $tableName Table being processed
     * @param array $record Record from $tableName
     * @return array
     */
    protected function generateDataUsingRecord(string $tableName, array $record): array
    {
        $this->relations = [];
        $deleteField = $GLOBALS['TCA'][$tableName]['ctrl']['delete'];

        // Is the record deleted?
        $deleted = $deleteField && $record[$deleteField] ? 1 : 0;

        // Get all relations from record:
        $recordRelations = $this->getRelations($tableName, $record);
        // Traverse those relations, compile records to insert in table:
        foreach ($recordRelations as $fieldName => $fieldRelations) {
            // Based on type
            switch ((string)$fieldRelations['type']) {
                case 'db':
                    $this->createEntryDataForDatabaseRelationsUsingRecord($tableName, $record, $fieldName, '', $deleted, $fieldRelations['itemArray']);
                    break;
                case 'file_reference':
                    // not used (see getRelations()), but fallback to file
                case 'file':
                    $this->createEntryDataForFileRelationsUsingRecord($tableName, $record, $fieldName, '', $deleted, $fieldRelations['newValueFiles']);
                    break;
                case 'flex':
                    // DB references in FlexForms
                    if (is_array($fieldRelations['flexFormRels']['db'])) {
                        foreach ($fieldRelations['flexFormRels']['db'] as $flexPointer => $subList) {
                            $this->createEntryDataForDatabaseRelationsUsingRecord($tableName, $record, $fieldName, $flexPointer, $deleted, $subList);
                        }
                    }
                    // File references in FlexForms
                    // @todo #65463 Test correct handling of file references in FlexForms
                    if (is_array($fieldRelations['flexFormRels']['file'])) {
                        foreach ($fieldRelations['flexFormRels']['file'] as $flexPointer => $subList) {
                            $this->createEntryDataForFileRelationsUsingRecord($tableName, $record, $fieldName, $flexPointer, $deleted, $subList);
                        }
                    }
                    // Soft references in FlexForms
                    // @todo #65464 Test correct handling of soft references in FlexForms
                    if (is_array($fieldRelations['flexFormRels']['softrefs'])) {
                        foreach ($fieldRelations['flexFormRels']['softrefs'] as $flexPointer => $subList) {
                            $this->createEntryDataForSoftReferencesUsingRecord($tableName, $record, $fieldName, $flexPointer, $deleted, $subList['keys']);
                        }
                    }
                    break;
            }
            // Soft references in the field
            if (is_array($fieldRelations['softrefs'])) {
                $this->createEntryDataForSoftReferencesUsingRecord($tableName, $record, $fieldName, '', $deleted, $fieldRelations['softrefs']['keys']);
            }
        }

        return array_filter($this->relations);
    }

    /**
     * Create array with field/value pairs ready to insert in database.
     * The "hash" field is a fingerprint value across this table.
     *
     * @param string $table Tablename of source record (where reference is located)
     * @param int $uid UID of source record (where reference is located)
     * @param string $field Fieldname of source record (where reference is located)
     * @param string $flexPointer Pointer to location inside FlexForm structure where reference is located in [field]
     * @param int $deleted Whether record is deleted-flagged or not
     * @param string $ref_table For database references; the tablename the reference points to. Special keyword "_FILE" indicates that "ref_string" is a file reference either absolute or relative to Environment::getPublicPath(). Special keyword "_STRING" indicates some special usage (typ. softreference) where "ref_string" is used for the value.
     * @param int $ref_uid For database references; The UID of the record (zero "ref_table" is "_FILE" or "_STRING")
     * @param string $ref_string For "_FILE" or "_STRING" references: The filepath (relative to Environment::getPublicPath() or absolute) or other string.
     * @param int $sort The sorting order of references if many (the "group" or "select" TCA types). -1 if no sorting order is specified.
     * @param string $softref_key If the reference is a soft reference, this is the soft reference parser key. Otherwise empty.
     * @param string $softref_id Soft reference ID for key. Might be useful for replace operations.
     * @return array|null Array record to insert into table.
     */
    public function createEntryData($table, $uid, $field, $flexPointer, $deleted, $ref_table, $ref_uid, $ref_string = '', $sort = -1, $softref_key = '', $softref_id = '')
    {
        $uid = $uid ? (int)$uid : 0;
        if (!$uid) {
            return null;
        }
        return $this->createEntryDataUsingRecord(
            (string)$table,
            $this->getRecordRawCached($table, $uid),
            (string)$field,
            (string)$flexPointer,
            $deleted ? (int)$deleted : 0,
            (string)$ref_table,
            $ref_uid ? (int)$ref_uid : 0,
            (string)$ref_string,
            $sort ? (int)$sort : 0,
            (string)$softref_key,
            (string)$softref_id
        );
    }

    /**
     * Create array with field/value pairs ready to insert in database
     *
     * @param string $tableName Tablename of source record (where reference is located)
     * @param array $record Record from $table
     * @param string $fieldName Fieldname of source record (where reference is located)
     * @param string $flexPointer Pointer to location inside FlexForm structure where reference is located in [$field]
     * @param int $deleted Whether record is deleted-flagged or not
     * @param string $referencedTable In database references the tablename the reference points to. Keyword "_FILE" indicates that $referenceString is a file reference, keyword "_STRING" indicates special usage (typ. SoftReference) in $referenceString
     * @param int $referencedUid In database references the UID of the record (zero $referencedTable is "_FILE" or "_STRING")
     * @param string $referenceString For "_FILE" or "_STRING" references: The filepath (relative to Environment::getPublicPath() or absolute) or other string.
     * @param int $sort The sorting order of references if many (the "group" or "select" TCA types). -1 if no sorting order is specified.
     * @param string $softReferenceKey If the reference is a soft reference, this is the soft reference parser key. Otherwise empty.
     * @param string $softReferenceId Soft reference ID for key. Might be useful for replace operations.
     * @return array|bool Array to insert in DB or false if record should not be processed
     */
    protected function createEntryDataUsingRecord(string $tableName, array $record, string $fieldName, string $flexPointer, int $deleted, string $referencedTable, int $referencedUid, string $referenceString = '', int $sort = -1, string $softReferenceKey = '', string $softReferenceId = '')
    {
        $workspaceId = 0;
        if (BackendUtility::isTableWorkspaceEnabled($tableName)) {
            $workspaceId = $this->getWorkspaceId();
            if (isset($record['t3ver_wsid']) && (int)$record['t3ver_wsid'] !== $workspaceId) {
                // The given record is workspace-enabled but doesn't live in the selected workspace => don't add index as it's not actually there
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
            'deleted' => (int)$deleted,
            'workspace' => $workspaceId,
            'ref_table' => $referencedTable,
            'ref_uid' => $referencedUid,
            'ref_string' => mb_substr($referenceString, 0, 1024)
        ];
    }

    /**
     * Enter database references to ->relations array
     *
     * @param string $table Tablename of source record (where reference is located)
     * @param int $uid UID of source record (where reference is located)
     * @param string $fieldName Fieldname of source record (where reference is located)
     * @param string $flexPointer Pointer to location inside FlexForm structure where reference is located in [field]
     * @param int $deleted Whether record is deleted-flagged or not
     * @param array $items Data array with database relations (table/id)
     */
    public function createEntryData_dbRels($table, $uid, $fieldName, $flexPointer, $deleted, $items)
    {
        $uid = $uid ? (int)$uid : 0;
        if (!$uid) {
            return;
        }
        $this->createEntryDataForDatabaseRelationsUsingRecord(
            (string)$table,
            $this->getRecordRawCached($table, $uid),
            (string)$fieldName,
            (string)$flexPointer,
            $deleted ? (int)$deleted : 0,
            (array)$items
        );
    }

    /**
     * Add database references to ->relations array based on fetched record
     *
     * @param string $tableName Tablename of source record (where reference is located)
     * @param array $record Record from $tableName
     * @param string $fieldName Fieldname of source record (where reference is located)
     * @param string $flexPointer Pointer to location inside FlexForm structure where reference is located in $fieldName
     * @param int $deleted Whether record is deleted-flagged or not
     * @param array $items Data array with database relations (table/id)
     */
    protected function createEntryDataForDatabaseRelationsUsingRecord(string $tableName, array $record, string $fieldName, string $flexPointer, int $deleted, array $items)
    {
        foreach ($items as $sort => $i) {
            $this->relations[] = $this->createEntryDataUsingRecord($tableName, $record, $fieldName, $flexPointer, $deleted, $i['table'], (int)$i['id'], '', $sort);
        }
    }

    /**
     * Enter file references to ->relations array
     *
     * @param string $table Tablename of source record (where reference is located)
     * @param int $uid UID of source record (where reference is located)
     * @param string $fieldName Fieldname of source record (where reference is located)
     * @param string $flexPointer Pointer to location inside FlexForm structure where reference is located in [field]
     * @param int $deleted Whether record is deleted-flagged or not
     * @param array $items Data array with file relations
     */
    public function createEntryData_fileRels($table, $uid, $fieldName, $flexPointer, $deleted, $items)
    {
        $uid = $uid ? (int)$uid : 0;
        if (!$uid) {
            return;
        }
        $this->createEntryDataForFileRelationsUsingRecord(
            (string)$table,
            $this->getRecordRawCached($table, $uid),
            (string)$fieldName,
            (string)$flexPointer,
            $deleted ? (int)$deleted : 0,
            (array)$items
        );
    }

    /**
     * Add file references to ->relations array based on fetched record
     *
     * @param string $tableName Tablename of source record (where reference is located)
     * @param array $record Record from $tableName
     * @param string $fieldName Fieldname of source record (where reference is located)
     * @param string $flexPointer Pointer to location inside FlexForm structure where reference is located in $fieldName
     * @param int $deleted Whether record is deleted-flagged or not
     * @param array $items Data array with file relations
     */
    protected function createEntryDataForFileRelationsUsingRecord(string $tableName, array $record, string $fieldName, string $flexPointer, int $deleted, array $items)
    {
        foreach ($items as $sort => $i) {
            $filePath = $i['ID_absFile'];
            if (GeneralUtility::isFirstPartOfStr($filePath, Environment::getPublicPath())) {
                $filePath = PathUtility::stripPathSitePrefix($filePath);
            }
            $this->relations[] = $this->createEntryDataUsingRecord(
                $tableName,
                $record,
                $fieldName,
                $flexPointer,
                $deleted,
                '_FILE',
                0,
                $filePath,
                $sort
            );
        }
    }

    /**
     * Enter softref references to ->relations array
     *
     * @param string $table Tablename of source record (where reference is located)
     * @param int $uid UID of source record (where reference is located)
     * @param string $fieldName Fieldname of source record (where reference is located)
     * @param string $flexPointer Pointer to location inside FlexForm structure
     * @param int $deleted
     * @param array $keys Data array with soft reference keys
     */
    public function createEntryData_softreferences($table, $uid, $fieldName, $flexPointer, $deleted, $keys)
    {
        $uid = $uid ? (int)$uid : 0;
        if (!$uid || !is_array($keys)) {
            return;
        }
        $this->createEntryDataForSoftReferencesUsingRecord(
            (string)$table,
            $this->getRecordRawCached($table, $uid),
            (string)$fieldName,
            (string)$flexPointer,
            $deleted ? (int)$deleted : 0,
            (array)$keys
        );
    }

    /**
     * Add SoftReference references to ->relations array based on fetched record
     *
     * @param string $tableName Tablename of source record (where reference is located)
     * @param array $record Record from $tableName
     * @param string $fieldName Fieldname of source record (where reference is located)
     * @param string $flexPointer Pointer to location inside FlexForm structure where reference is located in $fieldName
     * @param int $deleted Whether record is deleted-flagged or not
     * @param array $keys Data array with soft reference keys
     */
    protected function createEntryDataForSoftReferencesUsingRecord(string $tableName, array $record, string $fieldName, string $flexPointer, int $deleted, array $keys)
    {
        foreach ($keys as $spKey => $elements) {
            if (is_array($elements)) {
                foreach ($elements as $subKey => $el) {
                    if (is_array($el['subst'])) {
                        switch ((string)$el['subst']['type']) {
                            case 'db':
                                list($referencedTable, $referencedUid) = explode(':', $el['subst']['recordRef']);
                                $this->relations[] = $this->createEntryDataUsingRecord($tableName, $record, $fieldName, $flexPointer, $deleted, $referencedTable, (int)$referencedUid, '', -1, $spKey, $subKey);
                                break;
                            case 'file_reference':
                                // not used (see getRelations()), but fallback to file
                            case 'file':
                                $this->relations[] = $this->createEntryDataUsingRecord($tableName, $record, $fieldName, $flexPointer, $deleted, '_FILE', 0, $el['subst']['relFileName'], -1, $spKey, $subKey);
                                break;
                            case 'string':
                                $this->relations[] = $this->createEntryDataUsingRecord($tableName, $record, $fieldName, $flexPointer, $deleted, '_STRING', 0, $el['subst']['tokenValue'], -1, $spKey, $subKey);
                                break;
                        }
                    }
                }
            }
        }
    }

    /*******************************
     *
     * Get relations from table row
     *
     *******************************/

    /**
     * Returns relation information for a $table/$row-array
     * Traverses all fields in input row which are configured in TCA/columns
     * It looks for hard relations to files and records in the TCA types "select" and "group"
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
                // Add files
                $resultsFromFiles = $this->getRelations_procFiles($value, $conf, $uid);
                if (!empty($resultsFromFiles)) {
                    // We have to fill different arrays here depending on the result.
                    // internal_type file is still a relation of type file and
                    // since http://forge.typo3.org/issues/49538 internal_type file_reference
                    // is a database relation to a sys_file record
                    $fileResultsFromFiles = [];
                    $dbResultsFromFiles = [];
                    foreach ($resultsFromFiles as $resultFromFiles) {
                        if (isset($resultFromFiles['table']) && $resultFromFiles['table'] === 'sys_file') {
                            $dbResultsFromFiles[] = $resultFromFiles;
                        } else {
                            // Creates an entry for the field with all the files:
                            $fileResultsFromFiles[] = $resultFromFiles;
                        }
                    }
                    if (!empty($fileResultsFromFiles)) {
                        $outRow[$field] = [
                            'type' => 'file',
                            'newValueFiles' => $fileResultsFromFiles
                        ];
                    }
                    if (!empty($dbResultsFromFiles)) {
                        $outRow[$field] = [
                            'type' => 'db',
                            'itemArray' => $dbResultsFromFiles
                        ];
                    }
                }
                // Add a softref definition for link fields if the TCA does not specify one already
                if ($conf['type'] === 'input' && $conf['renderType'] === 'inputLink' && empty($conf['softref'])) {
                    $conf['softref'] = 'typolink';
                }
                // Add DB:
                $resultsFromDatabase = $this->getRelations_procDB($value, $conf, $uid, $table);
                if (!empty($resultsFromDatabase)) {
                    // Create an entry for the field with all DB relations:
                    $outRow[$field] = [
                        'type' => 'db',
                        'itemArray' => $resultsFromDatabase
                    ];
                }
                // For "flex" fieldtypes we need to traverse the structure looking for file and db references of course!
                if ($conf['type'] === 'flex') {
                    // Get current value array:
                    // NOTICE: failure to resolve Data Structures can lead to integrity problems with the reference index. Please look up
                    // the note in the JavaDoc documentation for the function FlexFormTools->getDataStructureIdentifier()
                    $currentValueArray = GeneralUtility::xml2array($value);
                    // Traversing the XML structure, processing files:
                    if (is_array($currentValueArray)) {
                        $this->temp_flexRelations = [
                            'db' => [],
                            'file' => [],
                            'softrefs' => []
                        ];
                        // Create and call iterator object:
                        $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
                        $flexFormTools->traverseFlexFormXMLData($table, $field, $row, $this, 'getRelations_flexFormCallBack');
                        // Create an entry for the field:
                        $outRow[$field] = [
                            'type' => 'flex',
                            'flexFormRels' => $this->temp_flexRelations
                        ];
                    }
                }
                // Soft References:
                if ((string)$value !== '') {
                    $softRefValue = $value;
                    if (!empty($conf['softref'])) {
                        $softRefs = BackendUtility::explodeSoftRefParserList($conf['softref']);
                        foreach ($softRefs as $spKey => $spParams) {
                            $softRefObj = BackendUtility::softRefParserObj($spKey);
                            if (is_object($softRefObj)) {
                                $resultArray = $softRefObj->findRef($table, $field, $uid, $softRefValue, $spKey, $spParams);
                                if (is_array($resultArray)) {
                                    $outRow[$field]['softrefs']['keys'][$spKey] = $resultArray['elements'];
                                    if ((string)$resultArray['content'] !== '') {
                                        $softRefValue = $resultArray['content'];
                                    }
                                }
                            }
                        }
                    }
                    if (!empty($outRow[$field]['softrefs']) && (string)$value !== (string)$softRefValue && strpos($softRefValue, '{softref:') !== false) {
                        $outRow[$field]['softrefs']['tokenizedContent'] = $softRefValue;
                    }
                }
            }
        }
        return $outRow;
    }

    /**
     * Callback function for traversing the FlexForm structure in relation to finding file and DB references!
     *
     * @param array $dsArr Data structure for the current value
     * @param mixed $dataValue Current value
     * @param array $PA Additional configuration used in calling function
     * @param string $structurePath Path of value in DS structure
     * @see DataHandler::checkValue_flex_procInData_travDS(),FlexFormTools::traverseFlexFormXMLData()
     */
    public function getRelations_flexFormCallBack($dsArr, $dataValue, $PA, $structurePath)
    {
        // Removing "data/" in the beginning of path (which points to location in data array)
        $structurePath = substr($structurePath, 5) . '/';
        $dsConf = $dsArr['TCEforms']['config'];
        // Implode parameter values:
        list($table, $uid, $field) = [
            $PA['table'],
            $PA['uid'],
            $PA['field']
        ];
        // Add files
        $resultsFromFiles = $this->getRelations_procFiles($dataValue, $dsConf, $uid);
        if (!empty($resultsFromFiles)) {
            // We have to fill different arrays here depending on the result.
            // internal_type file is still a relation of type file and
            // since http://forge.typo3.org/issues/49538 internal_type file_reference
            // is a database relation to a sys_file record
            $fileResultsFromFiles = [];
            $dbResultsFromFiles = [];
            foreach ($resultsFromFiles as $resultFromFiles) {
                if (isset($resultFromFiles['table']) && $resultFromFiles['table'] === 'sys_file') {
                    $dbResultsFromFiles[] = $resultFromFiles;
                } else {
                    $fileResultsFromFiles[] = $resultFromFiles;
                }
            }
            if (!empty($fileResultsFromFiles)) {
                $this->temp_flexRelations['file'][$structurePath] = $fileResultsFromFiles;
            }
            if (!empty($dbResultsFromFiles)) {
                $this->temp_flexRelations['db'][$structurePath] = $dbResultsFromFiles;
            }
        }
        // Add a softref definition for link fields if the TCA does not specify one already
        if ($dsConf['type'] === 'input' && $dsConf['renderType'] === 'inputLink' && empty($dsConf['softref'])) {
            $dsConf['softref'] = 'typolink';
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
            $softRefs = BackendUtility::explodeSoftRefParserList($dsConf['softref']);
            if ($softRefs !== false) {
                foreach ($softRefs as $spKey => $spParams) {
                    $softRefObj = BackendUtility::softRefParserObj($spKey);
                    if (is_object($softRefObj)) {
                        $resultArray = $softRefObj->findRef($table, $field, $uid, $softRefValue, $spKey, $spParams, $structurePath);
                        if (is_array($resultArray) && is_array($resultArray['elements'])) {
                            $this->temp_flexRelations['softrefs'][$structurePath]['keys'][$spKey] = $resultArray['elements'];
                            if ((string)$resultArray['content'] !== '') {
                                $softRefValue = $resultArray['content'];
                            }
                        }
                    }
                }
            }
            if (!empty($this->temp_flexRelations['softrefs']) && (string)$dataValue !== (string)$softRefValue) {
                $this->temp_flexRelations['softrefs'][$structurePath]['tokenizedContent'] = $softRefValue;
            }
        }
    }

    /**
     * Check field configuration if it is a file relation field and extract file relations if any
     *
     * @param string $value Field value
     * @param array $conf Field configuration array of type "TCA/columns
     * @param int $uid Field uid
     * @return bool|array If field type is OK it will return an array with the files inside. Else FALSE
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Deprecation logged by TcaMigration class.
     */
    public function getRelations_procFiles($value, $conf, $uid)
    {
        if ($conf['type'] !== 'group' || ($conf['internal_type'] !== 'file' && $conf['internal_type'] !== 'file_reference')) {
            return false;
        }

        // Collect file values in array:
        if ($conf['MM']) {
            $theFileValues = [];
            $dbAnalysis = GeneralUtility::makeInstance(RelationHandler::class);
            $dbAnalysis->start('', 'files', $conf['MM'], $uid);
            foreach ($dbAnalysis->itemArray as $someval) {
                if ($someval['id']) {
                    $theFileValues[] = $someval['id'];
                }
            }
        } else {
            $theFileValues = explode(',', $value);
        }
        // Traverse the files and add them:
        $uploadFolder = $conf['internal_type'] === 'file' ? $conf['uploadfolder'] : '';
        $destinationFolder = $this->destPathFromUploadFolder($uploadFolder);
        $newValueFiles = [];
        foreach ($theFileValues as $file) {
            if (trim($file)) {
                $realFile = $destinationFolder . '/' . trim($file);
                $newValueFile = [
                    'filename' => PathUtility::basename($file),
                    'ID' => md5($realFile),
                    'ID_absFile' => $realFile
                ];
                // Set sys_file and id for referenced files
                if ($conf['internal_type'] === 'file_reference') {
                    try {
                        $file = ResourceFactory::getInstance()->retrieveFileOrFolderObject($file);
                        if ($file instanceof File || $file instanceof Folder) {
                            // For setting this as sys_file relation later, the keys filename, ID and ID_absFile
                            // have not to be included, because the are not evaluated for db relations.
                            $newValueFile = [
                                'table' => 'sys_file',
                                'id' => $file->getUid()
                            ];
                        }
                    } catch (\Exception $e) {
                    }
                }
                $newValueFiles[] = $newValueFile;
            }
        }
        return $newValueFiles;
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
    public function getRelations_procDB($value, $conf, $uid, $table = '')
    {
        // Get IRRE relations
        if (empty($conf)) {
            return false;
        }
        if ($conf['type'] === 'inline' && !empty($conf['foreign_table']) && empty($conf['MM'])) {
            $dbAnalysis = GeneralUtility::makeInstance(RelationHandler::class);
            $dbAnalysis->setUseLiveReferenceIds(false);
            $dbAnalysis->start($value, $conf['foreign_table'], '', $uid, $table, $conf);
            return $dbAnalysis->itemArray;
            // DB record lists:
        }
        if ($this->isDbReferenceField($conf)) {
            $allowedTables = $conf['type'] === 'group' ? $conf['allowed'] : $conf['foreign_table'];
            if ($conf['MM_opposite_field']) {
                return [];
            }
            $dbAnalysis = GeneralUtility::makeInstance(RelationHandler::class);
            $dbAnalysis->start($value, $allowedTables, $conf['MM'], $uid, $table, $conf);
            return $dbAnalysis->itemArray;
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
     * @param mixed $newValue Value you wish to set for reference. If NULL, the reference is removed (unless a soft-reference in which case it can only be set to a blank string). If you wish to set a database reference, use the format "[table]:[uid]". Any other case, the input value is set as-is
     * @param bool $returnDataArray Return $dataArray only, do not submit it to database.
     * @param bool $bypassWorkspaceAdminCheck If set, it will bypass check for workspace-zero and admin user
     * @return string|bool|array FALSE (=OK), error message string or array (if $returnDataArray is set!)
     */
    public function setReferenceValue($hash, $newValue, $returnDataArray = false, $bypassWorkspaceAdminCheck = false)
    {
        $backendUser = $this->getBackendUser();
        if ($backendUser->workspace === 0 && $backendUser->isAdmin() || $bypassWorkspaceAdminCheck) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_refindex');
            $queryBuilder->getRestrictions()->removeAll();

            // Get current index from Database
            $referenceRecord = $queryBuilder
                ->select('*')
                ->from('sys_refindex')
                ->where(
                    $queryBuilder->expr()->eq('hash', $queryBuilder->createNamedParameter($hash, \PDO::PARAM_STR))
                )
                ->setMaxResults(1)
                ->execute()
                ->fetch();

            // Check if reference existed.
            if (!is_array($referenceRecord)) {
                return 'ERROR: No reference record with hash="' . $hash . '" was found!';
            }

            if (empty($GLOBALS['TCA'][$referenceRecord['tablename']])) {
                return 'ERROR: Table "' . $referenceRecord['tablename'] . '" was not in TCA!';
            }

            // Get that record from database
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($referenceRecord['tablename']);
            $queryBuilder->getRestrictions()->removeAll();
            $record = $queryBuilder
                ->select('*')
                ->from($referenceRecord['tablename'])
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($referenceRecord['recuid'], \PDO::PARAM_INT)
                    )
                )
                ->setMaxResults(1)
                ->execute()
                ->fetch();

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
                        case 'file_reference':
                            // not used (see getRelations()), but fallback to file
                        case 'file':
                            $error = $this->setReferenceValue_fileRels($referenceRecord, $fieldRelation['newValueFiles'], $newValue, $dataArray);
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
                            // File references in FlexForms
                            if (is_array($fieldRelation['flexFormRels']['file'][$referenceRecord['flexpointer']])) {
                                $error = $this->setReferenceValue_fileRels($referenceRecord, $fieldRelation['flexFormRels']['file'][$referenceRecord['flexpointer']], $newValue, $dataArray, $referenceRecord['flexpointer']);
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
                    $dataHandler->bypassFileHandling = true;
                    // Otherwise this cannot update things in deleted records...
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
     * @param string $newValue Value to substitute current value with (or NULL to unset it)
     * @param array $dataArray Data array in which the new value is set (passed by reference)
     * @param string $flexPointer Flexform pointer, if in a flex form field.
     * @return string Error message if any, otherwise FALSE = OK
     */
    public function setReferenceValue_dbRels($refRec, $itemArray, $newValue, &$dataArray, $flexPointer = '')
    {
        if ((int)$itemArray[$refRec['sorting']]['id'] === (int)$refRec['ref_uid'] && (string)$itemArray[$refRec['sorting']]['table'] === (string)$refRec['ref_table']) {
            // Setting or removing value:
            // Remove value:
            if ($newValue === null) {
                unset($itemArray[$refRec['sorting']]);
            } else {
                list($itemArray[$refRec['sorting']]['table'], $itemArray[$refRec['sorting']]['id']) = explode(':', $newValue);
            }
            // Traverse and compile new list of records:
            $saveValue = [];
            foreach ($itemArray as $pair) {
                $saveValue[] = $pair['table'] . '_' . $pair['id'];
            }
            // Set in data array:
            if ($flexPointer) {
                $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
                $dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']]['data'] = [];
                $flexFormTools->setArrayValueByPath(substr($flexPointer, 0, -1), $dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']]['data'], implode(',', $saveValue));
            } else {
                $dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']] = implode(',', $saveValue);
            }
        } else {
            return 'ERROR: table:id pair "' . $refRec['ref_table'] . ':' . $refRec['ref_uid'] . '" did not match that of the record ("' . $itemArray[$refRec['sorting']]['table'] . ':' . $itemArray[$refRec['sorting']]['id'] . '") in sorting index "' . $refRec['sorting'] . '"';
        }

        return false;
    }

    /**
     * Setting a value for a reference for a FILE field:
     *
     * @param array $refRec sys_refindex record
     * @param array $itemArray Array of references from that field
     * @param string $newValue Value to substitute current value with (or NULL to unset it)
     * @param array $dataArray Data array in which the new value is set (passed by reference)
     * @param string $flexPointer Flexform pointer, if in a flex form field.
     * @return string Error message if any, otherwise FALSE = OK
     */
    public function setReferenceValue_fileRels($refRec, $itemArray, $newValue, &$dataArray, $flexPointer = '')
    {
        $ID_absFile = PathUtility::stripPathSitePrefix($itemArray[$refRec['sorting']]['ID_absFile']);
        if ($ID_absFile === (string)$refRec['ref_string'] && $refRec['ref_table'] === '_FILE') {
            // Setting or removing value:
            // Remove value:
            if ($newValue === null) {
                unset($itemArray[$refRec['sorting']]);
            } else {
                $itemArray[$refRec['sorting']]['filename'] = $newValue;
            }
            // Traverse and compile new list of records:
            $saveValue = [];
            foreach ($itemArray as $fileInfo) {
                $saveValue[] = $fileInfo['filename'];
            }
            // Set in data array:
            if ($flexPointer) {
                $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
                $dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']]['data'] = [];
                $flexFormTools->setArrayValueByPath(substr($flexPointer, 0, -1), $dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']]['data'], implode(',', $saveValue));
            } else {
                $dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']] = implode(',', $saveValue);
            }
        } else {
            return 'ERROR: either "' . $refRec['ref_table'] . '" was not "_FILE" or file Environment::getPublicPath()+"' . $refRec['ref_string'] . '" did not match that of the record ("' . $itemArray[$refRec['sorting']]['ID_absFile'] . '") in sorting index "' . $refRec['sorting'] . '"';
        }

        return false;
    }

    /**
     * Setting a value for a soft reference token
     *
     * @param array $refRec sys_refindex record
     * @param array $softref Array of soft reference occurencies
     * @param string $newValue Value to substitute current value with
     * @param array $dataArray Data array in which the new value is set (passed by reference)
     * @param string $flexPointer Flexform pointer, if in a flex form field.
     * @return string Error message if any, otherwise FALSE = OK
     */
    public function setReferenceValue_softreferences($refRec, $softref, $newValue, &$dataArray, $flexPointer = '')
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
        if (!strstr($softref['tokenizedContent'], '{softref:')) {
            if ($flexPointer) {
                $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
                $dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']]['data'] = [];
                $flexFormTools->setArrayValueByPath(substr($flexPointer, 0, -1), $dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']]['data'], $softref['tokenizedContent']);
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
    protected function isDbReferenceField(array $configuration)
    {
        return
            ($configuration['type'] === 'group' && $configuration['internal_type'] === 'db')
            || (
                ($configuration['type'] === 'select' || $configuration['type'] === 'inline')
                && !empty($configuration['foreign_table'])
            )
            ;
    }

    /**
     * Returns TRUE if the TCA/columns field type is a reference field
     *
     * @param array $configuration Config array for TCA/columns field
     * @return bool TRUE if reference field
     */
    public function isReferenceField(array $configuration)
    {
        return
            $this->isDbReferenceField($configuration)
            ||
            ($configuration['type'] === 'group' && ($configuration['internal_type'] === 'file' || $configuration['internal_type'] === 'file_reference')) // getRelations_procFiles
            ||
            ($configuration['type'] === 'input' && $configuration['renderType'] === 'inputLink') // getRelations_procDB
            ||
            $configuration['type'] === 'flex'
            ||
            isset($configuration['softref'])
            ;
    }

    /**
     * Returns all fields of a table which could contain a relation
     *
     * @param string $tableName Name of the table
     * @return string Fields which could contain a relation
     */
    protected function fetchTableRelationFields($tableName)
    {
        if (!isset($GLOBALS['TCA'][$tableName]['columns'])) {
            return '';
        }

        $fields = [];

        foreach ($GLOBALS['TCA'][$tableName]['columns'] as $field => $fieldDefinition) {
            if (is_array($fieldDefinition['config'])) {
                // Check for flex field
                if (isset($fieldDefinition['config']['type']) && $fieldDefinition['config']['type'] === 'flex') {
                    // Fetch all fields if the is a field of type flex in the table definition because the complete row is passed to
                    // FlexFormTools->getDataStructureIdentifier() in the end and might be needed in ds_pointerField or a hook
                    return '*';
                }
                // Only fetch this field if it can contain a reference
                if ($this->isReferenceField($fieldDefinition['config'])) {
                    $fields[] = $field;
                }
            }
        }

        return implode(',', $fields);
    }

    /**
     * Returns destination path to an upload folder given by $folder
     *
     * @param string $folder Folder relative to TYPO3's public folder ("site path")
     * @return string Input folder prefixed with Environment::getPublicPath(). No checking for existence is done. Output must be a folder without trailing slash.
     */
    public function destPathFromUploadFolder($folder)
    {
        return Environment::getPublicPath() . ($folder ? '/' . $folder : '');
    }

    /**
     * Updating Index (External API)
     *
     * @param bool $testOnly If set, only a test
     * @param bool $cli_echo If set, output CLI status
     * @return array Header and body status content
     */
    public function updateIndex($testOnly, $cli_echo = false)
    {
        $errors = [];
        $tableNames = [];
        $recCount = 0;
        $tableCount = 0;
        $headerContent = $testOnly ? 'Reference Index being TESTED (nothing written, remove the "--check" argument)' : 'Reference Index being Updated';
        if ($cli_echo) {
            echo '*******************************************' . LF . $headerContent . LF . '*******************************************' . LF;
        }
        // Traverse all tables:
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $refIndexConnectionName = empty($GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping']['sys_refindex'])
                ? ConnectionPool::DEFAULT_CONNECTION_NAME
                : $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping']['sys_refindex'];

        foreach ($GLOBALS['TCA'] as $tableName => $cfg) {
            if ($this->shouldExcludeTableFromReferenceIndex($tableName)) {
                continue;
            }
            $tableConnectionName = empty($GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'][$tableName])
                ? ConnectionPool::DEFAULT_CONNECTION_NAME
                : $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'][$tableName];

            $fields = ['uid'];
            if (BackendUtility::isTableWorkspaceEnabled($tableName)) {
                $fields[] = 't3ver_wsid';
            }
            // Traverse all records in tables, including deleted records
            $queryBuilder = $connectionPool->getQueryBuilderForTable($tableName);
            $queryBuilder->getRestrictions()->removeAll();
            try {
                $queryResult = $queryBuilder
                    ->select(...$fields)
                    ->from($tableName)
                    ->execute();
            } catch (DBALException $e) {
                // Table exists in TCA but does not exist in the database
                $msg = 'Table "' .
                        $tableName .
                        '" exists in TCA but does not exist in the database. You should run the Database Analyzer in the Install Tool to fix this.';
                $this->logger->error($msg, ['exception' => $e]);
                continue;
            }

            $tableNames[] = $tableName;
            $tableCount++;
            while ($record = $queryResult->fetch()) {
                $refIndexObj = GeneralUtility::makeInstance(self::class);
                if (isset($record['t3ver_wsid'])) {
                    $refIndexObj->setWorkspaceId($record['t3ver_wsid']);
                }
                $result = $refIndexObj->updateRefIndexTable($tableName, $record['uid'], $testOnly);
                $recCount++;
                if ($result['addedNodes'] || $result['deletedNodes']) {
                    $error = 'Record ' . $tableName . ':' . $record['uid'] . ' had ' . $result['addedNodes'] . ' added indexes and ' . $result['deletedNodes'] . ' deleted indexes';
                    $errors[] = $error;
                    if ($cli_echo) {
                        echo $error . LF;
                    }
                }
            }

            // Subselect based queries only work on the same connection
            if ($refIndexConnectionName !== $tableConnectionName) {
                $this->logger->error('Not checking table "' . $tableName . '" for lost indexes, "sys_refindex" table uses a different connection');
                continue;
            }

            // Searching for lost indexes for this table
            // Build sub-query to find lost records
            $subQueryBuilder = $connectionPool->getQueryBuilderForTable($tableName);
            $subQueryBuilder->getRestrictions()->removeAll();
            $subQueryBuilder
                ->select('uid')
                ->from($tableName, 'sub_' . $tableName)
                ->where(
                    $subQueryBuilder->expr()->eq(
                        'sub_' . $tableName . '.uid',
                        $queryBuilder->quoteIdentifier('sys_refindex.recuid')
                    )
                );

            // Main query to find lost records
            $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_refindex');
            $queryBuilder->getRestrictions()->removeAll();
            $lostIndexes = $queryBuilder
                ->count('hash')
                ->from('sys_refindex')
                ->where(
                    $queryBuilder->expr()->eq(
                        'tablename',
                        $queryBuilder->createNamedParameter($tableName, \PDO::PARAM_STR)
                    ),
                    'NOT EXISTS (' . $subQueryBuilder->getSQL() . ')'
                )
                ->execute()
                ->fetchColumn(0);

            if ($lostIndexes > 0) {
                $error = 'Table ' . $tableName . ' has ' . $lostIndexes . ' lost indexes which are now deleted';
                $errors[] = $error;
                if ($cli_echo) {
                    echo $error . LF;
                }
                if (!$testOnly) {
                    $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_refindex');
                    $queryBuilder->delete('sys_refindex')
                        ->where(
                            $queryBuilder->expr()->eq(
                                'tablename',
                                $queryBuilder->createNamedParameter($tableName, \PDO::PARAM_STR)
                            ),
                            'NOT EXISTS (' . $subQueryBuilder->getSQL() . ')'
                        )
                        ->execute();
                }
            }
        }

        // Searching lost indexes for non-existing tables
        $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_refindex');
        $queryBuilder->getRestrictions()->removeAll();
        $lostTables = $queryBuilder
            ->count('hash')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->notIn(
                    'tablename',
                    $queryBuilder->createNamedParameter($tableNames, Connection::PARAM_STR_ARRAY)
                )
            )->execute()
            ->fetchColumn(0);

        if ($lostTables > 0) {
            $error = 'Index table hosted ' . $lostTables . ' indexes for non-existing tables, now removed';
            $errors[] = $error;
            if ($cli_echo) {
                echo $error . LF;
            }
            if (!$testOnly) {
                $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_refindex');
                $queryBuilder->delete('sys_refindex')
                    ->where(
                        $queryBuilder->expr()->notIn(
                            'tablename',
                            $queryBuilder->createNamedParameter($tableNames, Connection::PARAM_STR_ARRAY)
                        )
                    )->execute();
            }
        }
        $errorCount = count($errors);
        $recordsCheckedString = $recCount . ' records from ' . $tableCount . ' tables were checked/updated.' . LF;
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            $errorCount ? implode('##LF##', $errors) : 'Index Integrity was perfect!',
            $recordsCheckedString,
            $errorCount ? FlashMessage::ERROR : FlashMessage::OK
        );

        $flashMessageRenderer = GeneralUtility::makeInstance(FlashMessageRendererResolver::class)->resolve();
        $bodyContent = $flashMessageRenderer->render([$flashMessage]);
        if ($cli_echo) {
            echo $recordsCheckedString . ($errorCount ? 'Updates: ' . $errorCount : 'Index Integrity was perfect!') . LF;
        }
        if (!$testOnly) {
            $registry = GeneralUtility::makeInstance(Registry::class);
            $registry->set('core', 'sys_refindex_lastUpdate', $GLOBALS['EXEC_TIME']);
        }
        return [$headerContent, $bodyContent, $errorCount];
    }

    /**
     * Gets one record from database and stores it in an internal cache (which expires along with object lifecycle) for faster retrieval
     *
     * Assumption:
     *
     * - This method is only used from within delegate methods and so only caches queries generated based on the record being indexed; the query
     *   to select origin side record is uncached
     * - Origin side records do not change in database while updating the reference index
     * - Origin record does not get removed while updating index
     * - Relations may change during indexing, which is why only the origin record is cached and all relations are re-process even when repeating
     *   indexing of the same origin record
     *
     * Please note that the cache is disabled by default but can be enabled using $this->enableRuntimeCaches()
     * due to possible side-effects while handling references that were changed during one single
     * request.
     *
     * @param string $tableName
     * @param int $uid
     * @return array|false
     */
    protected function getRecordRawCached(string $tableName, int $uid)
    {
        $recordCacheId = $tableName . ':' . $uid;
        if (!$this->useRuntimeCache || !isset($this->recordCache[$recordCacheId])) {

            // Fetch fields of the table which might contain relations
            $cacheId = static::$cachePrefixTableRelationFields . $tableName;
            if (!$this->useRuntimeCache || !$this->runtimeCache->has($cacheId)) {
                $tableRelationFields = $this->fetchTableRelationFields($tableName);
                if ($this->useRuntimeCache) {
                    $this->runtimeCache->set($cacheId, $tableRelationFields);
                }
            } else {
                $tableRelationFields = $this->runtimeCache->get($cacheId);
            }

            // Return if there are no fields which could contain relations
            if ($tableRelationFields === '') {
                return $this->relations;
            }

            if ($tableRelationFields === '*') {
                // If one field of a record is of type flex, all fields have to be fetched to be passed to FlexFormTools->getDataStructureIdentifier()
                $selectFields = '*';
            } else {
                // otherwise only fields that might contain relations are fetched
                $selectFields = 'uid,' . $tableRelationFields;
                $deleteField = $GLOBALS['TCA'][$tableName]['ctrl']['delete'];
                if ($deleteField) {
                    $selectFields .= ',' . $deleteField;
                }
                if (BackendUtility::isTableWorkspaceEnabled($tableName)) {
                    $selectFields .= ',t3ver_wsid';
                }
            }

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($tableName);
            $queryBuilder->getRestrictions()->removeAll();
            $row = $queryBuilder
                ->select(...GeneralUtility::trimExplode(',', $selectFields, true))
                ->from($tableName)
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                    )
                )
                ->execute()
                ->fetch();

            $this->recordCache[$recordCacheId] = $row;
        }
        return $this->recordCache[$recordCacheId];
    }

    /**
     * Checks if a given table should be excluded from ReferenceIndex
     *
     * @param string $tableName Name of the table
     * @return bool true if it should be excluded
     */
    protected function shouldExcludeTableFromReferenceIndex($tableName)
    {
        if (isset(static::$excludedTables[$tableName])) {
            return static::$excludedTables[$tableName];
        }

        // Only exclude tables from ReferenceIndex which do not contain any relations and never did since existing references won't be deleted!
        // There is no need to add tables without a definition in $GLOBALS['TCA] since ReferenceIndex only handles those.
        $excludeTable = false;
        $signalSlotDispatcher = GeneralUtility::makeInstance(Dispatcher::class);
        $signalSlotDispatcher->dispatch(__CLASS__, 'shouldExcludeTableFromReferenceIndex', [$tableName, &$excludeTable]);

        static::$excludedTables[$tableName] = $excludeTable;

        return static::$excludedTables[$tableName];
    }

    /**
     * Checks if a given column in a given table should be excluded in the ReferenceIndex process
     *
     * @param string $tableName Name of the table
     * @param string $column Name of the column
     * @param string $onlyColumn Name of a specific column to fetch
     * @return bool true if it should be excluded
     */
    protected function shouldExcludeTableColumnFromReferenceIndex($tableName, $column, $onlyColumn)
    {
        if (isset(static::$excludedColumns[$column])) {
            return true;
        }

        if (is_array($GLOBALS['TCA'][$tableName]['columns'][$column]) && (!$onlyColumn || $onlyColumn === $column)) {
            return false;
        }

        return true;
    }

    /**
     * Enables the runtime-based caches
     * Could lead to side effects, depending if the reference index instance is run multiple times
     * while records would be changed.
     */
    public function enableRuntimeCache()
    {
        $this->useRuntimeCache = true;
    }

    /**
     * Disables the runtime-based cache
     */
    public function disableRuntimeCache()
    {
        $this->useRuntimeCache = false;
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
