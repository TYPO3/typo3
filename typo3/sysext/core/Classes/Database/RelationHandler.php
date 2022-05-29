<?php

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Database\Platform\PlatformInformation;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\DataHandling\PlainDataResolver;
use TYPO3\CMS\Core\DataHandling\ReferenceIndexUpdater;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Load database groups (relations)
 * Used to process the relations created by the TCA element types "group" and "select" for database records.
 * Manages MM-relations as well.
 */
class RelationHandler
{
    /**
     * $fetchAllFields if false getFromDB() fetches only uid, pid, thumbnail and label fields (as defined in TCA)
     *
     * @var bool
     */
    protected $fetchAllFields = true;

    /**
     * If set, values that are not ids in tables are normally discarded. By this options they will be preserved.
     *
     * @var bool
     */
    public $registerNonTableValues = false;

    /**
     * Contains the table names as keys. The values are the id-values for each table.
     * Should ONLY contain proper table names.
     *
     * @var array
     */
    public $tableArray = [];

    /**
     * Contains items in a numeric array (table/id for each). Tablenames here might be "_NO_TABLE". Keeps
     * the sorting of thee retrieved items.
     *
     * @var array<int, array<string, mixed>>
     */
    public $itemArray = [];

    /**
     * Array for NON-table elements
     *
     * @var array
     */
    public $nonTableArray = [];

    /**
     * @var array
     */
    public $additionalWhere = [];

    /**
     * Deleted-column is added to additionalWhere... if this is set...
     *
     * @var bool
     */
    public $checkIfDeleted = true;

    /**
     * Will contain the first table name in the $tablelist (for positive ids)
     *
     * @var string
     */
    protected $firstTable = '';

    /**
     * If TRUE, uid_local and uid_foreign are switched, and the current table
     * is inserted as tablename - this means you display a foreign relation "from the opposite side"
     *
     * @var bool
     */
    protected $MM_is_foreign = false;

    /**
     * Is empty by default; if MM_is_foreign is set and there is more than one table
     * allowed (on the "local" side), then it contains the first table (as a fallback)
     * @var string
     */
    protected $MM_isMultiTableRelationship = '';

    /**
     * Current table => Only needed for reverse relations
     *
     * @var string
     */
    protected $currentTable;

    /**
     * If a record should be undeleted
     * (so do not use the $useDeleteClause on \TYPO3\CMS\Backend\Utility\BackendUtility)
     *
     * @var bool
     */
    public $undeleteRecord;

    /**
     * Array of fields value pairs that should match while SELECT
     * and will be written into MM table if $MM_insert_fields is not set
     *
     * @var array
     */
    protected $MM_match_fields = [];

    /**
     * This is set to TRUE if the MM table has a UID field.
     *
     * @var bool
     */
    protected $MM_hasUidField;

    /**
     * Array of fields and value pairs used for insert in MM table
     *
     * @var array
     */
    protected $MM_insert_fields = [];

    /**
     * Extra MM table where
     *
     * @var string
     */
    protected $MM_table_where = '';

    /**
     * Usage of an MM field on the opposite relation.
     *
     * @var array
     */
    protected $MM_oppositeUsage;

    /**
     * If false, reference index is not updated.
     *
     * @var bool
     * @deprecated since v11, will be removed in v12
     */
    protected $updateReferenceIndex = true;

    /**
     * @var ReferenceIndexUpdater|null
     */
    protected $referenceIndexUpdater;

    /**
     * @var bool
     */
    protected $useLiveParentIds = true;

    /**
     * @var bool
     */
    protected $useLiveReferenceIds = true;

    /**
     * @var int|null
     */
    protected $workspaceId;

    /**
     * @var bool
     */
    protected $purged = false;

    /**
     * This array will be filled by getFromDB().
     *
     * @var array
     */
    public $results = [];

    /**
     * Gets the current workspace id.
     *
     * @return int
     */
    protected function getWorkspaceId(): int
    {
        $backendUser = $GLOBALS['BE_USER'] ?? null;
        if (!isset($this->workspaceId)) {
            $this->workspaceId = $backendUser instanceof BackendUserAuthentication ? (int)($backendUser->workspace) : 0;
        }
        return $this->workspaceId;
    }

    /**
     * Sets the current workspace id.
     *
     * @param int $workspaceId
     */
    public function setWorkspaceId($workspaceId): void
    {
        $this->workspaceId = (int)$workspaceId;
    }

    /**
     * Setter to carry the 'deferred' reference index updater registry around.
     *
     * @param ReferenceIndexUpdater $updater
     * @internal Used internally within DataHandler only
     */
    public function setReferenceIndexUpdater(ReferenceIndexUpdater $updater): void
    {
        $this->referenceIndexUpdater = $updater;
    }

    /**
     * Whether item array has been purged in this instance.
     *
     * @return bool
     */
    public function isPurged()
    {
        return $this->purged;
    }

    /**
     * Initialization of the class.
     *
     * @param string $itemlist List of group/select items
     * @param string $tablelist Comma list of tables, first table takes priority if no table is set for an entry in the list.
     * @param string $MMtable Name of a MM table.
     * @param int|string $MMuid Local UID for MM lookup. May be a string for newly created elements.
     * @param string $currentTable Current table name
     * @param array $conf TCA configuration for current field
     */
    public function start($itemlist, $tablelist, $MMtable = '', $MMuid = 0, $currentTable = '', $conf = [])
    {
        $conf = (array)$conf;
        // SECTION: MM reverse relations
        $this->MM_is_foreign = (bool)($conf['MM_opposite_field'] ?? false);
        $this->MM_table_where = $conf['MM_table_where'] ?? null;
        $this->MM_hasUidField = $conf['MM_hasUidField'] ?? null;
        $this->MM_match_fields = (isset($conf['MM_match_fields']) && is_array($conf['MM_match_fields'])) ? $conf['MM_match_fields'] : [];
        $this->MM_insert_fields = (isset($conf['MM_insert_fields']) && is_array($conf['MM_insert_fields'])) ? $conf['MM_insert_fields'] : $this->MM_match_fields;
        $this->currentTable = $currentTable;
        if (!empty($conf['MM_oppositeUsage']) && is_array($conf['MM_oppositeUsage'])) {
            $this->MM_oppositeUsage = $conf['MM_oppositeUsage'];
        }
        $mmOppositeTable = '';
        if ($this->MM_is_foreign) {
            $allowedTableList = $conf['type'] === 'group' ? $conf['allowed'] : $conf['foreign_table'];
            // Normally, $conf['allowed'] can contain a list of tables,
            // but as we are looking at a MM relation from the foreign side,
            // it only makes sense to allow one table in $conf['allowed'].
            [$mmOppositeTable] = GeneralUtility::trimExplode(',', $allowedTableList);
            // Only add the current table name if there is more than one allowed
            // field. We must be sure this has been done at least once before accessing
            // the "columns" part of TCA for a table.
            $mmOppositeAllowed = (string)($GLOBALS['TCA'][$mmOppositeTable]['columns'][$conf['MM_opposite_field'] ?? '']['config']['allowed'] ?? '');
            if ($mmOppositeAllowed !== '') {
                $mmOppositeAllowedTables = explode(',', $mmOppositeAllowed);
                if ($mmOppositeAllowed === '*' || count($mmOppositeAllowedTables) > 1) {
                    $this->MM_isMultiTableRelationship = $mmOppositeAllowedTables[0];
                }
            }
        }
        // SECTION:	normal MM relations
        // If the table list is "*" then all tables are used in the list:
        if (trim($tablelist) === '*') {
            $tablelist = implode(',', array_keys($GLOBALS['TCA']));
        }
        // The tables are traversed and internal arrays are initialized:
        $tempTableArray = GeneralUtility::trimExplode(',', $tablelist, true);
        foreach ($tempTableArray as $val) {
            $tName = trim($val);
            $this->tableArray[$tName] = [];
            $deleteField = $GLOBALS['TCA'][$tName]['ctrl']['delete'] ?? false;
            if ($this->checkIfDeleted && $deleteField) {
                $fieldN = $tName . '.' . $deleteField;
                if (!isset($this->additionalWhere[$tName])) {
                    $this->additionalWhere[$tName] = '';
                }
                $this->additionalWhere[$tName] .= ' AND ' . $fieldN . '=0';
            }
        }
        if (is_array($this->tableArray)) {
            reset($this->tableArray);
        } else {
            // No tables
            return;
        }
        // Set first and second tables:
        // Is the first table
        $this->firstTable = (string)key($this->tableArray);
        next($this->tableArray);
        // Now, populate the internal itemArray and tableArray arrays:
        // If MM, then call this function to do that:
        if ($MMtable) {
            if ($MMuid) {
                $this->readMM($MMtable, $MMuid, $mmOppositeTable);
                $this->purgeItemArray();
            } else {
                // Revert to readList() for new records in order to load possible default values from $itemlist
                $this->readList($itemlist, $conf);
                $this->purgeItemArray();
            }
        } elseif ($MMuid && ($conf['foreign_field'] ?? false)) {
            // If not MM but foreign_field, the read the records by the foreign_field
            $this->readForeignField($MMuid, $conf);
        } else {
            // If not MM, then explode the itemlist by "," and traverse the list:
            $this->readList($itemlist, $conf);
            // Do automatic default_sortby, if any
            if (isset($conf['foreign_default_sortby']) && $conf['foreign_default_sortby']) {
                $this->sortList($conf['foreign_default_sortby']);
            }
        }
    }

    /**
     * Sets $fetchAllFields
     *
     * @param bool $allFields enables fetching of all fields in getFromDB()
     */
    public function setFetchAllFields($allFields)
    {
        $this->fetchAllFields = (bool)$allFields;
    }

    /**
     * Sets whether the reference index shall be updated.
     *
     * @param bool $updateReferenceIndex Whether the reference index shall be updated
     * @deprecated since v11, will be removed in v12
     */
    public function setUpdateReferenceIndex($updateReferenceIndex)
    {
        trigger_error(
            'Calling RelationHandler->setUpdateReferenceIndex() is deprecated. Use setReferenceIndexUpdater() instead.',
            E_USER_DEPRECATED
        );
        $this->updateReferenceIndex = (bool)$updateReferenceIndex;
    }

    /**
     * @param bool $useLiveParentIds
     */
    public function setUseLiveParentIds($useLiveParentIds)
    {
        $this->useLiveParentIds = (bool)$useLiveParentIds;
    }

    /**
     * @param bool $useLiveReferenceIds
     */
    public function setUseLiveReferenceIds($useLiveReferenceIds)
    {
        $this->useLiveReferenceIds = (bool)$useLiveReferenceIds;
    }

    /**
     * Explodes the item list and stores the parts in the internal arrays itemArray and tableArray from MM records.
     *
     * @param string $itemlist Item list
     * @param array $configuration Parent field configuration
     */
    protected function readList($itemlist, array $configuration)
    {
        if (trim((string)$itemlist) !== '') {
            // Changed to trimExplode 31/3 04; HMENU special type "list" didn't work
            // if there were spaces in the list... I suppose this is better overall...
            $tempItemArray = GeneralUtility::trimExplode(',', $itemlist);
            // If the second table is set and the ID number is less than zero (later)
            // then the record is regarded to come from the second table...
            $secondTable = (string)(key($this->tableArray) ?? '');
            foreach ($tempItemArray as $key => $val) {
                // Will be set to "true" if the entry was a real table/id
                $isSet = false;
                // Extract table name and id. This is in the formula [tablename]_[id]
                // where table name MIGHT contain "_", hence the reversion of the string!
                $val = strrev($val);
                $parts = explode('_', $val, 2);
                $theID = strrev($parts[0]);
                // Check that the id IS an integer:
                if (MathUtility::canBeInterpretedAsInteger($theID)) {
                    // Get the table name: If a part of the exploded string, use that.
                    // Otherwise if the id number is LESS than zero, use the second table, otherwise the first table
                    $theTable = trim($parts[1] ?? '')
                        ? strrev(trim($parts[1] ?? ''))
                        : ($secondTable && $theID < 0 ? $secondTable : $this->firstTable);
                    // If the ID is not blank and the table name is among the names in the inputted tableList
                    if ((string)$theID != '' && $theID && $theTable && isset($this->tableArray[$theTable])) {
                        // Get ID as the right value:
                        $theID = $secondTable ? abs((int)$theID) : (int)$theID;
                        // Register ID/table name in internal arrays:
                        $this->itemArray[$key]['id'] = $theID;
                        $this->itemArray[$key]['table'] = $theTable;
                        $this->tableArray[$theTable][] = $theID;
                        // Set update-flag
                        $isSet = true;
                    }
                }
                // If it turns out that the value from the list was NOT a valid reference to a table-record,
                // then we might still set it as a NO_TABLE value:
                if (!$isSet && $this->registerNonTableValues) {
                    $this->itemArray[$key]['id'] = $tempItemArray[$key];
                    $this->itemArray[$key]['table'] = '_NO_TABLE';
                    $this->nonTableArray[] = $tempItemArray[$key];
                }
            }

            // Skip if not dealing with IRRE in a CSV list on a workspace
            if (!isset($configuration['type']) || $configuration['type'] !== 'inline'
                || empty($configuration['foreign_table']) || !empty($configuration['foreign_field'])
                || !empty($configuration['MM']) || count($this->tableArray) !== 1 || empty($this->tableArray[$configuration['foreign_table']])
                || $this->getWorkspaceId() === 0 || !BackendUtility::isTableWorkspaceEnabled($configuration['foreign_table'])
            ) {
                return;
            }

            // Fetch live record data
            if ($this->useLiveReferenceIds) {
                foreach ($this->itemArray as &$item) {
                    $item['id'] = $this->getLiveDefaultId($item['table'], $item['id']);
                }
            } else {
                // Directly overlay workspace data
                $this->itemArray = [];
                $foreignTable = $configuration['foreign_table'];
                $ids = $this->getResolver($foreignTable, $this->tableArray[$foreignTable])->get();
                foreach ($ids as $id) {
                    $this->itemArray[] = [
                        'id' => $id,
                        'table' => $foreignTable,
                    ];
                }
            }
        }
    }

    /**
     * Does a sorting on $this->itemArray depending on a default sortby field.
     * This is only used for automatic sorting of comma separated lists.
     * This function is only relevant for data that is stored in comma separated lists!
     *
     * @param string $sortby The default_sortby field/command (e.g. 'price DESC')
     */
    protected function sortList($sortby)
    {
        // Sort directly without fetching additional data
        if ($sortby === 'uid') {
            usort(
                $this->itemArray,
                static function ($a, $b) {
                    return $a['id'] < $b['id'] ? -1 : 1;
                }
            );
        } elseif (count($this->tableArray) === 1) {
            reset($this->tableArray);
            $table = (string)key($this->tableArray);
            $connection = $this->getConnectionForTableName($table);
            $maxBindParameters = PlatformInformation::getMaxBindParameters($connection->getDatabasePlatform());

            foreach (array_chunk(current($this->tableArray), $maxBindParameters - 10, true) as $chunk) {
                if (empty($chunk)) {
                    continue;
                }
                $this->itemArray = [];
                $this->tableArray = [];
                $queryBuilder = $connection->createQueryBuilder();
                $queryBuilder->getRestrictions()->removeAll();
                $queryBuilder->select('uid')
                    ->from($table)
                    ->where(
                        $queryBuilder->expr()->in(
                            'uid',
                            $queryBuilder->createNamedParameter($chunk, Connection::PARAM_INT_ARRAY)
                        )
                    );
                foreach (QueryHelper::parseOrderBy((string)$sortby) as $orderPair) {
                    [$fieldName, $order] = $orderPair;
                    $queryBuilder->addOrderBy($fieldName, $order);
                }
                $statement = $queryBuilder->executeQuery();
                while ($row = $statement->fetchAssociative()) {
                    $this->itemArray[] = ['id' => $row['uid'], 'table' => $table];
                    $this->tableArray[$table][] = $row['uid'];
                }
            }
        }
    }

    /**
     * Reads the record tablename/id into the internal arrays itemArray and tableArray from MM records.
     *
     * @todo: The source record is not checked for correct workspace. Say there is a category 5 in
     *        workspace 1. setWorkspace(0) is called, after that readMM('sys_category_record_mm', 5 ...).
     *        readMM will *still* return the list of records connected to this workspace 1 item,
     *        even though workspace 0 has been set.
     *
     * @param string $tableName MM Tablename
     * @param int|string $uid Local UID
     * @param string $mmOppositeTable Opposite table name
     */
    protected function readMM($tableName, $uid, $mmOppositeTable)
    {
        $key = 0;
        $theTable = null;
        $queryBuilder = $this->getConnectionForTableName($tableName)
            ->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->select('*')->from($tableName);
        // In case of a reverse relation
        if ($this->MM_is_foreign) {
            $uidLocal_field = 'uid_foreign';
            $uidForeign_field = 'uid_local';
            $sorting_field = 'sorting_foreign';
            if ($this->MM_isMultiTableRelationship) {
                // Be backwards compatible! When allowing more than one table after
                // having previously allowed only one table, this case applies.
                if ($this->currentTable == $this->MM_isMultiTableRelationship) {
                    $expression = $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->eq(
                            'tablenames',
                            $queryBuilder->createNamedParameter($this->currentTable, \PDO::PARAM_STR)
                        ),
                        $queryBuilder->expr()->eq(
                            'tablenames',
                            $queryBuilder->createNamedParameter('', \PDO::PARAM_STR)
                        )
                    );
                } else {
                    $expression = $queryBuilder->expr()->eq(
                        'tablenames',
                        $queryBuilder->createNamedParameter($this->currentTable, \PDO::PARAM_STR)
                    );
                }
                $queryBuilder->andWhere($expression);
            }
            $theTable = $mmOppositeTable;
        } else {
            // Default
            $uidLocal_field = 'uid_local';
            $uidForeign_field = 'uid_foreign';
            $sorting_field = 'sorting';
        }
        if ($this->MM_table_where) {
            if (GeneralUtility::makeInstance(Features::class)->isFeatureEnabled('runtimeDbQuotingOfTcaConfiguration')) {
                $queryBuilder->andWhere(
                    QueryHelper::stripLogicalOperatorPrefix(str_replace('###THIS_UID###', (string)$uid, QueryHelper::quoteDatabaseIdentifiers($queryBuilder->getConnection(), $this->MM_table_where)))
                );
            } else {
                $queryBuilder->andWhere(
                    QueryHelper::stripLogicalOperatorPrefix(str_replace('###THIS_UID###', (string)$uid, $this->MM_table_where))
                );
            }
        }
        foreach ($this->MM_match_fields as $field => $value) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq($field, $queryBuilder->createNamedParameter($value, \PDO::PARAM_STR))
            );
        }
        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq(
                $uidLocal_field,
                $queryBuilder->createNamedParameter((int)$uid, \PDO::PARAM_INT)
            )
        );
        $queryBuilder->orderBy($sorting_field);
        $queryBuilder->addOrderBy($uidForeign_field);
        $statement = $queryBuilder->executeQuery();
        while ($row = $statement->fetchAssociative()) {
            // Default
            if (!$this->MM_is_foreign) {
                // If tablesnames columns exists and contain a name, then this value is the table, else it's the firstTable...
                $theTable = !empty($row['tablenames']) ? $row['tablenames'] : $this->firstTable;
            }
            if (($row[$uidForeign_field] || $theTable === 'pages') && $theTable && isset($this->tableArray[$theTable])) {
                $this->itemArray[$key]['id'] = $row[$uidForeign_field];
                $this->itemArray[$key]['table'] = $theTable;
                $this->tableArray[$theTable][] = $row[$uidForeign_field];
            } elseif ($this->registerNonTableValues) {
                $this->itemArray[$key]['id'] = $row[$uidForeign_field];
                $this->itemArray[$key]['table'] = '_NO_TABLE';
                $this->nonTableArray[] = $row[$uidForeign_field];
            }
            $key++;
        }
    }

    /**
     * Writes the internal itemArray to MM table:
     *
     * @param string $MM_tableName MM table name
     * @param int $uid Local UID
     * @param bool $prependTableName If set, then table names will always be written.
     */
    public function writeMM($MM_tableName, $uid, $prependTableName = false)
    {
        $connection = $this->getConnectionForTableName($MM_tableName);
        $expressionBuilder = $connection->createQueryBuilder()->expr();

        // In case of a reverse relation
        if ($this->MM_is_foreign) {
            $uidLocal_field = 'uid_foreign';
            $uidForeign_field = 'uid_local';
            $sorting_field = 'sorting_foreign';
        } else {
            // default
            $uidLocal_field = 'uid_local';
            $uidForeign_field = 'uid_foreign';
            $sorting_field = 'sorting';
        }
        // If there are tables...
        $tableC = count($this->tableArray);
        if ($tableC) {
            // Boolean: does the field "tablename" need to be filled?
            $prep = $tableC > 1 || $prependTableName || $this->MM_isMultiTableRelationship;
            $c = 0;
            $additionalWhere_tablenames = '';
            if ($this->MM_is_foreign && $prep) {
                $additionalWhere_tablenames = $expressionBuilder->eq(
                    'tablenames',
                    $expressionBuilder->literal($this->currentTable)
                );
            }
            $additionalWhere = $expressionBuilder->andX();
            // Add WHERE clause if configured
            if ($this->MM_table_where) {
                $additionalWhere->add(
                    QueryHelper::stripLogicalOperatorPrefix(
                        str_replace('###THIS_UID###', (string)$uid, $this->MM_table_where)
                    )
                );
            }
            // Select, update or delete only those relations that match the configured fields
            foreach ($this->MM_match_fields as $field => $value) {
                $additionalWhere->add($expressionBuilder->eq($field, $expressionBuilder->literal($value)));
            }

            $queryBuilder = $connection->createQueryBuilder();
            $queryBuilder->getRestrictions()->removeAll();
            $queryBuilder->select($uidForeign_field)
                ->from($MM_tableName)
                ->where($queryBuilder->expr()->eq(
                    $uidLocal_field,
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                ))
                ->orderBy($sorting_field);

            if ($prep) {
                $queryBuilder->addSelect('tablenames');
            }
            if ($this->MM_hasUidField) {
                $queryBuilder->addSelect('uid');
            }
            if ($additionalWhere_tablenames) {
                $queryBuilder->andWhere($additionalWhere_tablenames);
            }
            if ($additionalWhere->count()) {
                $queryBuilder->andWhere($additionalWhere);
            }

            $result = $queryBuilder->executeQuery();
            $oldMMs = [];
            // This array is similar to $oldMMs but also holds the uid of the MM-records, if any (configured by MM_hasUidField).
            // If the UID is present it will be used to update sorting and delete MM-records.
            // This is necessary if the "multiple" feature is used for the MM relations.
            // $oldMMs is still needed for the in_array() search used to look if an item from $this->itemArray is in $oldMMs
            $oldMMs_inclUid = [];
            while ($row = $result->fetchAssociative()) {
                if (!$this->MM_is_foreign && $prep) {
                    $oldMMs[] = [$row['tablenames'], $row[$uidForeign_field]];
                } else {
                    $oldMMs[] = $row[$uidForeign_field];
                }
                $oldMMs_inclUid[] = (int)($row['uid'] ?? 0);
            }
            // For each item, insert it:
            foreach ($this->itemArray as $val) {
                $c++;
                if ($prep || $val['table'] === '_NO_TABLE') {
                    // Insert current table if needed
                    if ($this->MM_is_foreign) {
                        $tablename = $this->currentTable;
                    } else {
                        $tablename = $val['table'];
                    }
                } else {
                    $tablename = '';
                }
                if (!$this->MM_is_foreign && $prep) {
                    $item = [$val['table'], $val['id']];
                } else {
                    $item = $val['id'];
                }
                if (in_array($item, $oldMMs)) {
                    $oldMMs_index = array_search($item, $oldMMs);
                    // In principle, selecting on the UID is all we need to do
                    // if a uid field is available since that is unique!
                    // But as long as it "doesn't hurt" we just add it to the where clause. It should all match up.
                    $queryBuilder = $connection->createQueryBuilder();
                    $queryBuilder->update($MM_tableName)
                        ->set($sorting_field, $c)
                        ->where(
                            $expressionBuilder->eq(
                                $uidLocal_field,
                                $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                            ),
                            $expressionBuilder->eq(
                                $uidForeign_field,
                                $queryBuilder->createNamedParameter($val['id'], \PDO::PARAM_INT)
                            )
                        );

                    if ($additionalWhere->count()) {
                        $queryBuilder->andWhere($additionalWhere);
                    }
                    if ($this->MM_hasUidField) {
                        $queryBuilder->andWhere(
                            $expressionBuilder->eq(
                                'uid',
                                $queryBuilder->createNamedParameter($oldMMs_inclUid[$oldMMs_index], \PDO::PARAM_INT)
                            )
                        );
                    }
                    if ($tablename) {
                        $queryBuilder->andWhere(
                            $expressionBuilder->eq(
                                'tablenames',
                                $queryBuilder->createNamedParameter($tablename, \PDO::PARAM_STR)
                            )
                        );
                    }

                    $queryBuilder->executeStatement();
                    // Remove the item from the $oldMMs array so after this
                    // foreach loop only the ones that need to be deleted are in there.
                    unset($oldMMs[$oldMMs_index]);
                    // Remove the item from the $oldMMs_inclUid array so after this
                    // foreach loop only the ones that need to be deleted are in there.
                    unset($oldMMs_inclUid[$oldMMs_index]);
                } else {
                    $insertFields = $this->MM_insert_fields;
                    $insertFields[$uidLocal_field] = $uid;
                    $insertFields[$uidForeign_field] = $val['id'];
                    $insertFields[$sorting_field] = $c;
                    if ($tablename) {
                        $insertFields['tablenames'] = $tablename;
                        $insertFields = $this->completeOppositeUsageValues($tablename, $insertFields);
                    }
                    $connection->insert($MM_tableName, $insertFields);
                    if ($this->MM_is_foreign) {
                        $this->updateRefIndex($val['table'], $val['id']);
                    }
                }
            }
            // Delete all not-used relations:
            if (is_array($oldMMs) && !empty($oldMMs)) {
                $queryBuilder = $connection->createQueryBuilder();
                $removeClauses = $queryBuilder->expr()->orX();
                $updateRefIndex_records = [];
                foreach ($oldMMs as $oldMM_key => $mmItem) {
                    // If UID field is present, of course we need only use that for deleting.
                    if ($this->MM_hasUidField) {
                        $removeClauses->add($queryBuilder->expr()->eq(
                            'uid',
                            $queryBuilder->createNamedParameter($oldMMs_inclUid[$oldMM_key], \PDO::PARAM_INT)
                        ));
                    } else {
                        if (is_array($mmItem)) {
                            $removeClauses->add(
                                $queryBuilder->expr()->andX(
                                    $queryBuilder->expr()->eq(
                                        'tablenames',
                                        $queryBuilder->createNamedParameter($mmItem[0], \PDO::PARAM_STR)
                                    ),
                                    $queryBuilder->expr()->eq(
                                        $uidForeign_field,
                                        $queryBuilder->createNamedParameter($mmItem[1], \PDO::PARAM_INT)
                                    )
                                )
                            );
                        } else {
                            $removeClauses->add(
                                $queryBuilder->expr()->eq(
                                    $uidForeign_field,
                                    $queryBuilder->createNamedParameter($mmItem, \PDO::PARAM_INT)
                                )
                            );
                        }
                    }
                    if ($this->MM_is_foreign) {
                        if (is_array($mmItem)) {
                            $updateRefIndex_records[] = [$mmItem[0], $mmItem[1]];
                        } else {
                            $updateRefIndex_records[] = [$this->firstTable, $mmItem];
                        }
                    }
                }

                $queryBuilder->delete($MM_tableName)
                    ->where(
                        $queryBuilder->expr()->eq(
                            $uidLocal_field,
                            $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                        ),
                        $removeClauses
                    );

                if ($additionalWhere_tablenames) {
                    $queryBuilder->andWhere($additionalWhere_tablenames);
                }
                if ($additionalWhere->count()) {
                    $queryBuilder->andWhere($additionalWhere);
                }

                $queryBuilder->executeStatement();

                // Update ref index:
                foreach ($updateRefIndex_records as $pair) {
                    $this->updateRefIndex($pair[0], $pair[1]);
                }
            }
            // Update ref index; In DataHandler it is not certain that this will happen because
            // if only the MM field is changed the record itself is not updated and so the ref-index is not either.
            // This could also have been fixed in updateDB in DataHandler, however I decided to do it here ...
            $this->updateRefIndex($this->currentTable, $uid);
        }
    }

    /**
     * Remaps MM table elements from one local uid to another
     * Does NOT update the reference index for you, must be called subsequently to do that!
     *
     * @param string $MM_tableName MM table name
     * @param int $uid Local, current UID
     * @param int $newUid Local, new UID
     * @param bool $prependTableName If set, then table names will always be written.
     * @deprecated since v11, will be removed with v12.
     */
    public function remapMM($MM_tableName, $uid, $newUid, $prependTableName = false)
    {
        trigger_error(
            'Method ' . __METHOD__ . ' of class ' . __CLASS__ . ' is deprecated since v11 and will be removed in v12.',
            E_USER_DEPRECATED
        );

        // In case of a reverse relation
        if ($this->MM_is_foreign) {
            $uidLocal_field = 'uid_foreign';
        } else {
            // default
            $uidLocal_field = 'uid_local';
        }
        // If there are tables...
        $tableC = count($this->tableArray);
        if ($tableC) {
            $queryBuilder = $this->getConnectionForTableName($MM_tableName)
                ->createQueryBuilder();
            $queryBuilder->update($MM_tableName)
                ->set($uidLocal_field, (int)$newUid)
                ->where($queryBuilder->expr()->eq(
                    $uidLocal_field,
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                ));
            // Boolean: does the field "tablename" need to be filled?
            $prep = $tableC > 1 || $prependTableName || $this->MM_isMultiTableRelationship;
            if ($this->MM_is_foreign && $prep) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->eq(
                        'tablenames',
                        $queryBuilder->createNamedParameter($this->currentTable, \PDO::PARAM_STR)
                    )
                );
            }
            // Add WHERE clause if configured
            if ($this->MM_table_where) {
                $queryBuilder->andWhere(
                    QueryHelper::stripLogicalOperatorPrefix(str_replace('###THIS_UID###', (string)$uid, $this->MM_table_where))
                );
            }
            // Select, update or delete only those relations that match the configured fields
            foreach ($this->MM_match_fields as $field => $value) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->eq($field, $queryBuilder->createNamedParameter($value, \PDO::PARAM_STR))
                );
            }
            $queryBuilder->execute();
        }
    }

    /**
     * Reads items from a foreign_table, that has a foreign_field (uid of the parent record) and
     * stores the parts in the internal array itemArray and tableArray.
     *
     * @param int|string $uid The uid of the parent record (this value is also on the foreign_table in the foreign_field)
     * @param array $conf TCA configuration for current field
     */
    protected function readForeignField($uid, $conf)
    {
        if ($this->useLiveParentIds) {
            $uid = $this->getLiveDefaultId($this->currentTable, $uid);
        }

        $key = 0;
        $uid = (int)$uid;
        // skip further processing if $uid does not
        // point to a valid parent record
        if ($uid === 0) {
            return;
        }

        $foreign_table = $conf['foreign_table'];
        $foreign_table_field = $conf['foreign_table_field'] ?? '';
        $useDeleteClause = !$this->undeleteRecord;
        $foreign_match_fields = is_array($conf['foreign_match_fields'] ?? false) ? $conf['foreign_match_fields'] : [];
        $queryBuilder = $this->getConnectionForTableName($foreign_table)
            ->createQueryBuilder();
        $queryBuilder->getRestrictions()
            ->removeAll();
        // Use the deleteClause (e.g. "deleted=0") on this table
        if ($useDeleteClause) {
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        }

        $queryBuilder->select('uid')
            ->from($foreign_table);

        // Search for $uid in foreign_field, and if we have symmetric relations, do this also on symmetric_field
        if (!empty($conf['symmetric_field'])) {
            $queryBuilder->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq(
                        $conf['foreign_field'],
                        $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        $conf['symmetric_field'],
                        $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                    )
                )
            );
        } else {
            $queryBuilder->where($queryBuilder->expr()->eq(
                $conf['foreign_field'],
                $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
            ));
        }
        // If it's requested to look for the parent uid AND the parent table,
        // add an additional SQL-WHERE clause
        if ($foreign_table_field && $this->currentTable) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    $foreign_table_field,
                    $queryBuilder->createNamedParameter($this->currentTable, \PDO::PARAM_STR)
                )
            );
        }
        // Add additional where clause if foreign_match_fields are defined
        foreach ($foreign_match_fields as $field => $value) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq($field, $queryBuilder->createNamedParameter($value, \PDO::PARAM_STR))
            );
        }
        // Select children from the live(!) workspace only
        if (BackendUtility::isTableWorkspaceEnabled($foreign_table)) {
            $queryBuilder->getRestrictions()->add(
                GeneralUtility::makeInstance(WorkspaceRestriction::class, (int)$this->getWorkspaceId())
            );
        }
        // Get the correct sorting field
        // Specific manual sortby for data handled by this field
        $sortby = '';
        if (!empty($conf['foreign_sortby'])) {
            if (!empty($conf['symmetric_sortby']) && !empty($conf['symmetric_field'])) {
                // Sorting depends on, from which side of the relation we're looking at it
                // This requires bypassing automatic quoting and setting of the default sort direction
                // @TODO: Doctrine: generalize to standard SQL to guarantee database independency
                $queryBuilder->add(
                    'orderBy',
                    'CASE
						WHEN ' . $queryBuilder->expr()->eq($conf['foreign_field'], $uid) . '
						THEN ' . $queryBuilder->quoteIdentifier($conf['foreign_sortby']) . '
						ELSE ' . $queryBuilder->quoteIdentifier($conf['symmetric_sortby']) . '
					END'
                );
            } else {
                // Regular single-side behaviour
                $sortby = $conf['foreign_sortby'];
            }
        } elseif (!empty($conf['foreign_default_sortby'])) {
            // Specific default sortby for data handled by this field
            $sortby = $conf['foreign_default_sortby'];
        } elseif (!empty($GLOBALS['TCA'][$foreign_table]['ctrl']['sortby'])) {
            // Manual sortby for all table records
            $sortby = $GLOBALS['TCA'][$foreign_table]['ctrl']['sortby'];
        } elseif (!empty($GLOBALS['TCA'][$foreign_table]['ctrl']['default_sortby'])) {
            // Default sortby for all table records
            $sortby = $GLOBALS['TCA'][$foreign_table]['ctrl']['default_sortby'];
        }

        if (!empty($sortby)) {
            foreach (QueryHelper::parseOrderBy($sortby) as $orderPair) {
                [$fieldName, $sorting] = $orderPair;
                $queryBuilder->addOrderBy($fieldName, $sorting);
            }
        }

        // Get the rows from storage
        $rows = [];
        $result = $queryBuilder->executeQuery();
        while ($row = $result->fetchAssociative()) {
            $rows[(int)$row['uid']] = $row;
        }
        if (!empty($rows)) {
            // Retrieve the parsed and prepared ORDER BY configuration for the resolver
            $sortby = $queryBuilder->getQueryPart('orderBy');
            $ids = $this->getResolver($foreign_table, array_keys($rows), $sortby)->get();
            foreach ($ids as $id) {
                $this->itemArray[$key]['id'] = $id;
                $this->itemArray[$key]['table'] = $foreign_table;
                $this->tableArray[$foreign_table][] = $id;
                $key++;
            }
        }
    }

    /**
     * Write the sorting values to a foreign_table, that has a foreign_field (uid of the parent record)
     *
     * @param array $conf TCA configuration for current field
     * @param int $parentUid The uid of the parent record
     * @param int $updateToUid If this is larger than zero it will be used as foreign UID instead of the given $parentUid (on Copy)
     * @param bool $skipSorting @deprecated since v11, will be dropped with v12. Simplify the if below when removing argument.
     */
    public function writeForeignField($conf, $parentUid, $updateToUid = 0, $skipSorting = null)
    {
        // @deprecated since v11, will be removed with v12.
        if ($skipSorting !== null) {
            trigger_error(
                'Calling ' . __METHOD__ . ' with 4th argument $skipSorting is deprecated and will be removed in v12.',
                E_USER_DEPRECATED
            );
        }
        $skipSorting = (bool)$skipSorting;

        if ($this->useLiveParentIds) {
            $parentUid = $this->getLiveDefaultId($this->currentTable, $parentUid);
            if (!empty($updateToUid)) {
                $updateToUid = $this->getLiveDefaultId($this->currentTable, $updateToUid);
            }
        }

        // Ensure all values are set.
        $conf += [
            'foreign_table' => '',
            'foreign_field' => '',
            'symmetric_field' => '',
            'foreign_table_field' => '',
            'foreign_match_fields' => [],
        ];

        $c = 0;
        $foreign_table = $conf['foreign_table'];
        $foreign_field = $conf['foreign_field'];
        $symmetric_field = $conf['symmetric_field'] ?? '';
        $foreign_table_field = $conf['foreign_table_field'];
        $foreign_match_fields = $conf['foreign_match_fields'];
        // If there are table items and we have a proper $parentUid
        if (MathUtility::canBeInterpretedAsInteger($parentUid) && !empty($this->tableArray)) {
            // If updateToUid is not a positive integer, set it to '0', so it will be ignored
            if (!(MathUtility::canBeInterpretedAsInteger($updateToUid) && $updateToUid > 0)) {
                $updateToUid = 0;
            }
            $considerWorkspaces = BackendUtility::isTableWorkspaceEnabled($foreign_table);
            $fields = 'uid,pid,' . $foreign_field;
            // Consider the symmetric field if defined:
            if ($symmetric_field) {
                $fields .= ',' . $symmetric_field;
            }
            // Consider workspaces if defined and currently used:
            if ($considerWorkspaces) {
                $fields .= ',t3ver_wsid,t3ver_state,t3ver_oid';
            }
            // Update all items
            foreach ($this->itemArray as $val) {
                $uid = $val['id'];
                $table = $val['table'];
                $row = [];
                // Fetch the current (not overwritten) relation record if we should handle symmetric relations
                if ($symmetric_field || $considerWorkspaces) {
                    $row = BackendUtility::getRecord($table, $uid, $fields, '', true);
                    if (empty($row)) {
                        continue;
                    }
                }
                $isOnSymmetricSide = false;
                if ($symmetric_field) {
                    $isOnSymmetricSide = self::isOnSymmetricSide((string)$parentUid, $conf, $row);
                }
                $updateValues = $foreign_match_fields;
                // No update to the uid is requested, so this is the normal behaviour
                // just update the fields and care about sorting
                if (!$updateToUid) {
                    // Always add the pointer to the parent uid
                    if ($isOnSymmetricSide) {
                        $updateValues[$symmetric_field] = $parentUid;
                    } else {
                        $updateValues[$foreign_field] = $parentUid;
                    }
                    // If it is configured in TCA also to store the parent table in the child record, just do it
                    if ($foreign_table_field && $this->currentTable) {
                        $updateValues[$foreign_table_field] = $this->currentTable;
                    }
                    // Update sorting columns if not to be skipped.
                    // @deprecated since v11, will be removed with v12. Drop if() below, assume $skipSorting false, keep body.
                    if (!$skipSorting) {
                        // Get the correct sorting field
                        // Specific manual sortby for data handled by this field
                        $sortby = '';
                        if ($conf['foreign_sortby'] ?? false) {
                            $sortby = $conf['foreign_sortby'];
                        } elseif ($GLOBALS['TCA'][$foreign_table]['ctrl']['sortby'] ?? false) {
                            // manual sortby for all table records
                            $sortby = $GLOBALS['TCA'][$foreign_table]['ctrl']['sortby'];
                        }
                        // Apply sorting on the symmetric side
                        // (it depends on who created the relation, so what uid is in the symmetric_field):
                        if ($isOnSymmetricSide && isset($conf['symmetric_sortby']) && $conf['symmetric_sortby']) {
                            $sortby = $conf['symmetric_sortby'];
                        } else {
                            $tempSortBy = [];
                            foreach (QueryHelper::parseOrderBy($sortby) as $orderPair) {
                                [$fieldName, $order] = $orderPair;
                                if ($order !== null) {
                                    $tempSortBy[] = implode(' ', $orderPair);
                                } else {
                                    $tempSortBy[] = $fieldName;
                                }
                            }
                            $sortby = implode(',', $tempSortBy);
                        }
                        if ($sortby) {
                            $updateValues[$sortby] = ++$c;
                        }
                    }
                } else {
                    if ($isOnSymmetricSide) {
                        $updateValues[$symmetric_field] = $updateToUid;
                    } else {
                        $updateValues[$foreign_field] = $updateToUid;
                    }
                }
                // Update accordant fields in the database:
                if (!empty($updateValues)) {
                    // Update tstamp if any foreign field value has changed
                    if (!empty($GLOBALS['TCA'][$table]['ctrl']['tstamp'])) {
                        $updateValues[$GLOBALS['TCA'][$table]['ctrl']['tstamp']] = $GLOBALS['EXEC_TIME'];
                    }
                    $this->getConnectionForTableName($table)
                        ->update(
                            $table,
                            $updateValues,
                            ['uid' => (int)$uid]
                        );
                    $this->updateRefIndex($table, $uid);
                }
            }
        }
    }

    /**
     * After initialization you can extract an array of the elements from the object. Use this function for that.
     *
     * @param bool $prependTableName If set, then table names will ALWAYS be prepended (unless its a _NO_TABLE value)
     * @return array A numeric array.
     */
    public function getValueArray($prependTableName = false)
    {
        // INIT:
        $valueArray = [];
        $tableC = count($this->tableArray);
        // If there are tables in the table array:
        if ($tableC) {
            // If there are more than ONE table in the table array, then always prepend table names:
            $prep = $tableC > 1 || $prependTableName;
            // Traverse the array of items:
            foreach ($this->itemArray as $val) {
                $valueArray[] = ($prep && $val['table'] !== '_NO_TABLE' ? $val['table'] . '_' : '') . $val['id'];
            }
        }
        // Return the array
        return $valueArray;
    }

    /**
     * Reads all records from internal tableArray into the internal ->results array
     * where keys are table names and for each table, records are stored with uids as their keys.
     * If $this->fetchAllFields is false you can save a little memory
     * since only uid,pid and a few other fields are selected.
     *
     * @return array
     */
    public function getFromDB()
    {
        // Traverses the tables listed:
        foreach ($this->tableArray as $table => $ids) {
            if (is_array($ids) && !empty($ids)) {
                $connection = $this->getConnectionForTableName($table);
                $maxBindParameters = PlatformInformation::getMaxBindParameters($connection->getDatabasePlatform());

                foreach (array_chunk($ids, $maxBindParameters - 10, true) as $chunk) {
                    $queryBuilder = $connection->createQueryBuilder();
                    $queryBuilder->getRestrictions()->removeAll();
                    $queryBuilder->select('*')
                        ->from($table)
                        ->where($queryBuilder->expr()->in(
                            'uid',
                            $queryBuilder->createNamedParameter($chunk, Connection::PARAM_INT_ARRAY)
                        ));
                    if ($this->additionalWhere[$table] ?? false) {
                        $queryBuilder->andWhere(
                            QueryHelper::stripLogicalOperatorPrefix($this->additionalWhere[$table])
                        );
                    }
                    $statement = $queryBuilder->executeQuery();
                    while ($row = $statement->fetchAssociative()) {
                        $this->results[$table][$row['uid']] = $row;
                    }
                }
            }
        }
        return $this->results;
    }

    /**
     * This method is typically called after getFromDB().
     * $this->results holds a list of resolved and valid relations,
     * $this->itemArray hold a list of "selected" relations from the incoming selection array.
     * The difference is that "itemArray" may hold a single table/uid combination multiple times,
     * for instance in a type=group relation having multiple=true, while "results" hold each
     * resolved relation only once.
     * The methods creates a sanitized "itemArray" from resolved "results" list, normalized
     * the return array to always contain both table name and uid, and keep incoming
     * "itemArray" sort order and keeps "multiple" selections.
     *
     * In addition, the item array contains the full record to be used later-on and save database queries.
     * This method keeps the ordering intact.
     *
     * @return array
     */
    public function getResolvedItemArray(): array
    {
        $itemArray = [];
        foreach ($this->itemArray as $item) {
            if (isset($this->results[$item['table']][$item['id']])) {
                $itemArray[] = [
                    'table' => $item['table'],
                    'uid' => $item['id'],
                    'record' => $this->results[$item['table']][$item['id']],
                ];
            }
        }
        return $itemArray;
    }

    /**
     * Counts the items in $this->itemArray and puts this value in an array by default.
     *
     * @param bool $returnAsArray Whether to put the count value in an array
     * @return mixed The plain count as integer or the same inside an array
     */
    public function countItems($returnAsArray = true)
    {
        $count = count($this->itemArray);
        if ($returnAsArray) {
            $count = [$count];
        }
        return $count;
    }

    /**
     * Update Reference Index (sys_refindex) for a record.
     * Should be called any almost any update to a record which could affect references inside the record.
     * If used from within DataHandler, only registers a row for update for later processing.
     *
     * @param string $table Table name
     * @param int $uid Record uid
     * @return array Result from ReferenceIndex->updateRefIndexTable() updated directly, else empty array
     */
    protected function updateRefIndex($table, $uid): array
    {
        if (!$this->updateReferenceIndex) {
            return [];
        }
        if ($this->referenceIndexUpdater) {
            // Add to update registry if given
            $this->referenceIndexUpdater->registerForUpdate((string)$table, (int)$uid, $this->getWorkspaceId());
            $statisticsArray = [];
        } else {
            // @deprecated else branch can be dropped when setUpdateReferenceIndex() is dropped.
            // Update reference index directly if enabled
            $referenceIndex = GeneralUtility::makeInstance(ReferenceIndex::class);
            if (BackendUtility::isTableWorkspaceEnabled($table)) {
                $referenceIndex->setWorkspaceId($this->getWorkspaceId());
            }
            $statisticsArray = $referenceIndex->updateRefIndexTable($table, $uid);
        }
        return $statisticsArray;
    }

    /**
     * Converts elements in the local item array to use version ids instead of
     * live ids, if possible. The most common use case is, to call that prior
     * to processing with MM relations in a workspace context. For tha special
     * case, ids on both side of the MM relation must use version ids if
     * available.
     *
     * @return bool Whether items have been converted
     */
    public function convertItemArray()
    {
        // conversion is only required in a workspace context
        // (the case that version ids are submitted in a live context are rare)
        if ($this->getWorkspaceId() === 0) {
            return false;
        }

        $hasBeenConverted = false;
        foreach ($this->tableArray as $tableName => $ids) {
            if (empty($ids) || !BackendUtility::isTableWorkspaceEnabled($tableName)) {
                continue;
            }

            // convert live ids to version ids if available
            $convertedIds = $this->getResolver($tableName, $ids)
                ->setKeepDeletePlaceholder(false)
                ->setKeepMovePlaceholder(false)
                ->processVersionOverlays($ids);
            foreach ($this->itemArray as $index => $item) {
                if ($item['table'] !== $tableName) {
                    continue;
                }
                $currentItemId = $item['id'];
                if (
                    !isset($convertedIds[$currentItemId])
                    || $currentItemId === $convertedIds[$currentItemId]
                ) {
                    continue;
                }
                // adjust local item to use resolved version id
                $this->itemArray[$index]['id'] = $convertedIds[$currentItemId];
                $hasBeenConverted = true;
            }
            // update per-table reference for ids
            if ($hasBeenConverted) {
                $this->tableArray[$tableName] = array_values($convertedIds);
            }
        }

        return $hasBeenConverted;
    }

    /**
     * @todo: It *should* be possible to drop all three 'purge' methods by using
     *        a clever join within readMM - that sounds doable now with pid -1 and
     *        ws-pair records being gone since v11. It would resolve this indirect
     *        callback logic and would reduce some queries. The (workspace) mm tests
     *        should be complete enough now to verify if a change like that would do.
     *
     * @param int|null $workspaceId
     * @return bool Whether items have been purged
     * @internal
     */
    public function purgeItemArray($workspaceId = null)
    {
        if ($workspaceId === null) {
            $workspaceId = $this->getWorkspaceId();
        } else {
            $workspaceId = (int)$workspaceId;
        }

        // Ensure, only live relations are in the items Array
        if ($workspaceId === 0) {
            $purgeCallback = 'purgeVersionedIds';
        } else {
            // Otherwise, ensure that live relations are purged if version exists
            $purgeCallback = 'purgeLiveVersionedIds';
        }

        $itemArrayHasBeenPurged = $this->purgeItemArrayHandler($purgeCallback);
        $this->purged = ($this->purged || $itemArrayHasBeenPurged);
        return $itemArrayHasBeenPurged;
    }

    /**
     * Removes items having a delete placeholder from $this->itemArray
     *
     * @return bool Whether items have been purged
     */
    public function processDeletePlaceholder()
    {
        if (!$this->useLiveReferenceIds || $this->getWorkspaceId() === 0) {
            return false;
        }

        return $this->purgeItemArrayHandler('purgeDeletePlaceholder');
    }

    /**
     * Handles a purge callback on $this->itemArray
     *
     * @param string $purgeCallback
     * @return bool Whether items have been purged
     */
    protected function purgeItemArrayHandler($purgeCallback)
    {
        $itemArrayHasBeenPurged = false;

        foreach ($this->tableArray as $itemTableName => $itemIds) {
            if (empty($itemIds) || !BackendUtility::isTableWorkspaceEnabled($itemTableName)) {
                continue;
            }

            $purgedItemIds = [];
            $callable =[$this, $purgeCallback];
            if (is_callable($callable)) {
                $purgedItemIds = $callable($itemTableName, $itemIds);
            }

            $removedItemIds = array_diff($itemIds, $purgedItemIds);
            foreach ($removedItemIds as $removedItemId) {
                $this->removeFromItemArray($itemTableName, $removedItemId);
            }
            $this->tableArray[$itemTableName] = $purgedItemIds;
            if (!empty($removedItemIds)) {
                $itemArrayHasBeenPurged = true;
            }
        }

        return $itemArrayHasBeenPurged;
    }

    /**
     * Purges ids that are versioned.
     *
     * @param string $tableName
     * @param array $ids
     * @return array
     */
    protected function purgeVersionedIds($tableName, array $ids)
    {
        $ids = $this->sanitizeIds($ids);
        $ids = (array)array_combine($ids, $ids);
        $connection = $this->getConnectionForTableName($tableName);
        $maxBindParameters = PlatformInformation::getMaxBindParameters($connection->getDatabasePlatform());

        foreach (array_chunk($ids, $maxBindParameters - 10, true) as $chunk) {
            $queryBuilder = $connection->createQueryBuilder();
            $queryBuilder->getRestrictions()->removeAll();
            $result = $queryBuilder->select('uid', 't3ver_oid', 't3ver_state')
                ->from($tableName)
                ->where(
                    $queryBuilder->expr()->in(
                        'uid',
                        $queryBuilder->createNamedParameter($chunk, Connection::PARAM_INT_ARRAY)
                    ),
                    $queryBuilder->expr()->neq(
                        't3ver_wsid',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    )
                )
                ->orderBy('t3ver_state', 'DESC')
                ->executeQuery();

            while ($version = $result->fetchAssociative()) {
                $versionId = $version['uid'];
                if (isset($ids[$versionId])) {
                    unset($ids[$versionId]);
                }
            }
        }

        return array_values($ids);
    }

    /**
     * Purges ids that are live but have an accordant version.
     *
     * @param string $tableName
     * @param array $ids
     * @return array
     */
    protected function purgeLiveVersionedIds($tableName, array $ids)
    {
        $ids = $this->sanitizeIds($ids);
        $ids = (array)array_combine($ids, $ids);
        $connection = $this->getConnectionForTableName($tableName);
        $maxBindParameters = PlatformInformation::getMaxBindParameters($connection->getDatabasePlatform());

        foreach (array_chunk($ids, $maxBindParameters - 10, true) as $chunk) {
            $queryBuilder = $connection->createQueryBuilder();
            $queryBuilder->getRestrictions()->removeAll();
            $result = $queryBuilder->select('uid', 't3ver_oid', 't3ver_state')
                ->from($tableName)
                ->where(
                    $queryBuilder->expr()->in(
                        't3ver_oid',
                        $queryBuilder->createNamedParameter($chunk, Connection::PARAM_INT_ARRAY)
                    ),
                    $queryBuilder->expr()->neq(
                        't3ver_wsid',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    )
                )
                ->orderBy('t3ver_state', 'DESC')
                ->executeQuery();

            while ($version = $result->fetchAssociative()) {
                $versionId = $version['uid'];
                $liveId = $version['t3ver_oid'];
                if (isset($ids[$liveId]) && isset($ids[$versionId])) {
                    unset($ids[$liveId]);
                }
            }
        }

        return array_values($ids);
    }

    /**
     * Purges ids that have a delete placeholder
     *
     * @param string $tableName
     * @param array $ids
     * @return array
     */
    protected function purgeDeletePlaceholder($tableName, array $ids)
    {
        $ids = $this->sanitizeIds($ids);
        $ids = array_combine($ids, $ids) ?: [];
        $connection = $this->getConnectionForTableName($tableName);
        $maxBindParameters = PlatformInformation::getMaxBindParameters($connection->getDatabasePlatform());

        foreach (array_chunk($ids, $maxBindParameters - 10, true) as $chunk) {
            $queryBuilder = $connection->createQueryBuilder();
            $queryBuilder->getRestrictions()->removeAll();
            $result = $queryBuilder->select('uid', 't3ver_oid', 't3ver_state')
                ->from($tableName)
                ->where(
                    $queryBuilder->expr()->in(
                        't3ver_oid',
                        $queryBuilder->createNamedParameter($chunk, Connection::PARAM_INT_ARRAY)
                    ),
                    $queryBuilder->expr()->eq(
                        't3ver_wsid',
                        $queryBuilder->createNamedParameter(
                            $this->getWorkspaceId(),
                            \PDO::PARAM_INT
                        )
                    ),
                    $queryBuilder->expr()->eq(
                        't3ver_state',
                        $queryBuilder->createNamedParameter(
                            (string)VersionState::cast(VersionState::DELETE_PLACEHOLDER),
                            \PDO::PARAM_INT
                        )
                    )
                )
                ->executeQuery();

            while ($version = $result->fetchAssociative()) {
                $liveId = $version['t3ver_oid'];
                if (isset($ids[$liveId])) {
                    unset($ids[$liveId]);
                }
            }
        }

        return array_values($ids);
    }

    protected function removeFromItemArray($tableName, $id)
    {
        foreach ($this->itemArray as $index => $item) {
            if ($item['table'] === $tableName && (string)$item['id'] === (string)$id) {
                unset($this->itemArray[$index]);
                return true;
            }
        }
        return false;
    }

    /**
     * Checks, if we're looking from the "other" side, the symmetric side, to a symmetric relation.
     *
     * @param string $parentUid The uid of the parent record
     * @param array $parentConf The TCA configuration of the parent field embedding the child records
     * @param array $childRec The record row of the child record
     * @return bool Returns TRUE if looking from the symmetric ("other") side to the relation.
     */
    protected static function isOnSymmetricSide($parentUid, $parentConf, $childRec)
    {
        return MathUtility::canBeInterpretedAsInteger($childRec['uid'])
            && $parentConf['symmetric_field']
            && $parentUid == $childRec[$parentConf['symmetric_field']];
    }

    /**
     * Completes MM values to be written by values from the opposite relation.
     * This method used MM insert field or MM match fields if defined.
     *
     * @param string $tableName Name of the opposite table
     * @param array $referenceValues Values to be written
     * @return array Values to be written, possibly modified
     */
    protected function completeOppositeUsageValues($tableName, array $referenceValues)
    {
        if (empty($this->MM_oppositeUsage[$tableName]) || count($this->MM_oppositeUsage[$tableName]) > 1) {
            // @todo: count($this->MM_oppositeUsage[$tableName]) > 1 is buggy.
            //        Scenario: Suppose a foreign table has two (!) fields that link to a sys_category. Relations can
            //        then be correctly set for both fields when editing the foreign records. But when editing a sys_category
            //        record (local side) and adding a relation to a table that has two category relation fields, the 'fieldname'
            //        entry in mm-table can not be decided and ends up empty. Neither of the foreign table fields then recognize
            //        the relation as being set.
            //        One simple solution is to either simply pick the *first* field, or set *both* relations, but this
            //        is a) guesswork and b) it may be that in practice only *one* field is actually shown due to record
            //        types "showitem".
            //        Brain melt increases with tt_content field 'selected_category' in combination with
            //        'category_field' for record types 'menu_categorized_pages' and 'menu_categorized_content' next
            //        to casual 'categories' field. However, 'selected_category' is a 'oneToMany' and not a 'manyToMany'.
            //        Hard nut ...
            return $referenceValues;
        }

        $fieldName = $this->MM_oppositeUsage[$tableName][0];
        if (empty($GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'])) {
            return $referenceValues;
        }

        $configuration = $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'];
        if (!empty($configuration['MM_insert_fields'])) {
            // @todo: MM_insert_fields does not make sense and should be probably dropped altogether.
            //        No core usages, not even with sys_category. There is no point in having data fields that
            //        are filled with static content, especially since the mm table can't be edited directly.
            $referenceValues = array_merge($configuration['MM_insert_fields'], $referenceValues);
        } elseif (!empty($configuration['MM_match_fields'])) {
            // @todo: In the end, MM_match_fields does not make sense. The 'tablename' and 'fieldname' restriction
            //        in addition to uid_local and uid_foreign used when multiple 'foreign' tables and/or multiple fields
            //        of one table refer to a single 'local' table having an mm table with these four fields, is already
            //        clear when looking at 'MM_oppositeUsage' of the local table. 'MM_match_fields' should thus probably
            //        fall altogether. The only information carried here are the field names of 'tablename' and 'fieldname'
            //        within the mm table itself, which we should hard code. This is partially assumed in DefaultTcaSchema
            //        already.
            $referenceValues = array_merge($configuration['MM_match_fields'], $referenceValues);
        }

        return $referenceValues;
    }

    /**
     * Gets the record uid of the live default record. If already
     * pointing to the live record, the submitted record uid is returned.
     *
     * @param string $tableName
     * @param int|string $id
     * @return int
     */
    protected function getLiveDefaultId($tableName, $id)
    {
        $liveDefaultId = BackendUtility::getLiveVersionIdOfRecord($tableName, $id);
        if ($liveDefaultId === null) {
            $liveDefaultId = $id;
        }
        return (int)$liveDefaultId;
    }

    /**
     * Removes empty values (null, '0', 0, false).
     *
     * @param int[] $ids
     * @return array
     */
    protected function sanitizeIds(array $ids): array
    {
        return array_filter($ids);
    }

    /**
     * @param string $tableName
     * @param int[] $ids
     * @param array $sortingStatement
     * @return PlainDataResolver
     */
    protected function getResolver($tableName, array $ids, array $sortingStatement = null)
    {
        $resolver = GeneralUtility::makeInstance(
            PlainDataResolver::class,
            $tableName,
            $ids,
            $sortingStatement
        );
        $resolver->setWorkspaceId($this->getWorkspaceId());
        $resolver->setKeepDeletePlaceholder(true);
        $resolver->setKeepLiveIds($this->useLiveReferenceIds);
        return $resolver;
    }

    /**
     * @param string $tableName
     * @return Connection
     */
    protected function getConnectionForTableName(string $tableName)
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($tableName);
    }
}
