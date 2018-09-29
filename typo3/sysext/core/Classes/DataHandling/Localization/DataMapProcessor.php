<?php
namespace TYPO3\CMS\Core\DataHandling\Localization;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * This processor analyzes the provided data-map before actually being process
 * in the calling DataHandler instance. Field names that are configured to have
 * "allowLanguageSynchronization" enabled are either synchronized from there
 * relative parent records (could be a default language record, or a l10n_source
 * record) or to their dependent records (in case a default language record or
 * nested records pointing upwards with l10n_source).
 *
 * Except inline relational record editing, all modifications are applied to
 * the data-map directly, which ensures proper history entries as a side-effect.
 * For inline relational record editing, this processor either triggers the copy
 * or localize actions by instantiation a new local DataHandler instance.
 *
 * Namings in this class:
 * + forTableName, forId always refers to dependencies data is provided *for*
 * + fromTableName, fromId always refers to ancestors data is retrieved *from*
 */
class DataMapProcessor
{
    /**
     * @var array
     */
    protected $allDataMap = [];

    /**
     * @var array
     */
    protected $modifiedDataMap = [];

    /**
     * @var array
     */
    protected $sanitizationMap = [];

    /**
     * @var BackendUserAuthentication
     */
    protected $backendUser;

    /**
     * @var DataMapItem[]
     */
    protected $allItems = [];

    /**
     * @var DataMapItem[]
     */
    protected $nextItems = [];

    /**
     * Class generator
     *
     * @param array $dataMap The submitted data-map to be worked on
     * @param BackendUserAuthentication $backendUser Forwared backend-user scope
     * @return DataMapProcessor
     */
    public static function instance(array $dataMap, BackendUserAuthentication $backendUser)
    {
        return GeneralUtility::makeInstance(
            static::class,
            $dataMap,
            $backendUser
        );
    }

    /**
     * @param array $dataMap The submitted data-map to be worked on
     * @param BackendUserAuthentication $backendUser Forwared backend-user scope
     */
    public function __construct(array $dataMap, BackendUserAuthentication $backendUser)
    {
        $this->allDataMap = $dataMap;
        $this->modifiedDataMap = $dataMap;
        $this->backendUser = $backendUser;
    }

    /**
     * Processes the submitted data-map and returns the sanitized and enriched
     * version depending on accordant localization states and dependencies.
     *
     * @return array
     */
    public function process()
    {
        $iterations = 0;

        while (!empty($this->modifiedDataMap)) {
            $this->nextItems = [];
            foreach ($this->modifiedDataMap as $tableName => $idValues) {
                $this->collectItems($tableName, $idValues);
            }

            $this->modifiedDataMap = [];
            if (empty($this->nextItems)) {
                break;
            }

            if ($iterations++ === 0) {
                $this->sanitize($this->allItems);
            }
            $this->enrich($this->nextItems);
        }

        $this->allDataMap = $this->purgeDataMap($this->allDataMap);
        return $this->allDataMap;
    }

    /**
     * Purges superfluous empty data-map sections.
     *
     * @param array $dataMap
     * @return array
     */
    protected function purgeDataMap(array $dataMap): array
    {
        foreach ($dataMap as $tableName => $idValues) {
            foreach ($idValues as $id => $values) {
                if (empty($values)) {
                    unset($dataMap[$tableName][$id]);
                }
            }
            if (empty($dataMap[$tableName])) {
                unset($dataMap[$tableName]);
            }
        }
        return $dataMap;
    }

    /**
     * Create data map items of all affected rows
     *
     * @param string $tableName
     * @param array $idValues
     */
    protected function collectItems(string $tableName, array $idValues)
    {
        if (!$this->isApplicable($tableName)) {
            return;
        }

        $fieldNames = [
            'uid' => 'uid',
            'l10n_state' => 'l10n_state',
            'language' => $GLOBALS['TCA'][$tableName]['ctrl']['languageField'],
            'parent' => $GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'],
        ];
        if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['translationSource'])) {
            $fieldNames['source'] = $GLOBALS['TCA'][$tableName]['ctrl']['translationSource'];
        }

        $translationValues = $this->fetchTranslationValues(
            $tableName,
            $fieldNames,
            $this->filterNewItemIds(
                $tableName,
                $this->filterNumericIds(array_keys($idValues))
            )
        );

        $dependencies = $this->fetchDependencies(
            $tableName,
            $this->filterNewItemIds($tableName, array_keys($idValues))
        );

        foreach ($idValues as $id => $values) {
            $item = $this->findItem($tableName, $id);
            // build item if it has not been created in a previous iteration
            if ($item === null) {
                $recordValues = $translationValues[$id] ?? [];
                $item = DataMapItem::build(
                    $tableName,
                    $id,
                    $values,
                    $recordValues,
                    $fieldNames
                );

                // elements using "all language" cannot be localized
                if ($item->getLanguage() === -1) {
                    unset($item);
                    continue;
                }
                // must be any kind of localization and in connected mode
                if ($item->getLanguage() > 0 && empty($item->getParent())) {
                    unset($item);
                    continue;
                }
                // add dependencies
                if (!empty($dependencies[$id])) {
                    $item->setDependencies($dependencies[$id]);
                }
            }
            // add item to $this->allItems and $this->nextItems
            $this->addNextItem($item);
        }
    }

    /**
     * Sanitizes the submitted data-map items and removes fields which are not
     * defined as custom and thus rely on either parent or source values.
     *
     * @param DataMapItem[] $items
     */
    protected function sanitize(array $items)
    {
        foreach (['directChild', 'grandChild'] as $type) {
            foreach ($this->filterItemsByType($type, $items) as $item) {
                $this->sanitizeTranslationItem($item);
            }
        }
    }

    /**
     * Handle synchronization of an item list
     *
     * @param DataMapItem[] $items
     */
    protected function enrich(array $items)
    {
        foreach (['directChild', 'grandChild'] as $type) {
            foreach ($this->filterItemsByType($type, $items) as $item) {
                foreach ($item->getApplicableScopes() as $scope) {
                    $fromId = $item->getIdForScope($scope);
                    $fieldNames = $this->getFieldNamesForItemScope($item, $scope, !$item->isNew());
                    $this->synchronizeTranslationItem($item, $fieldNames, $fromId);
                }
                $this->populateTranslationItem($item);
                $this->finishTranslationItem($item);
            }
        }
        foreach ($this->filterItemsByType('parent', $items) as $item) {
            $this->populateTranslationItem($item);
        }
    }

    /**
     * Sanitizes the submitted data-map for a particular item and removes
     * fields which are not defined as custom and thus rely on either parent
     * or source values.
     *
     * @param DataMapItem $item
     */
    protected function sanitizeTranslationItem(DataMapItem $item)
    {
        $fieldNames = [];
        foreach ($item->getApplicableScopes() as $scope) {
            $fieldNames = array_merge(
                $fieldNames,
                $this->getFieldNamesForItemScope($item, $scope, false)
            );
        }

        $fieldNameMap = array_combine($fieldNames, $fieldNames);
        // separate fields, that are submitted in data-map, but not defined as custom
        $this->sanitizationMap[$item->getTableName()][$item->getId()] = array_intersect_key(
            $this->allDataMap[$item->getTableName()][$item->getId()],
            $fieldNameMap
        );
        // remove fields, that are submitted in data-map, but not defined as custom
        $this->allDataMap[$item->getTableName()][$item->getId()] = array_diff_key(
            $this->allDataMap[$item->getTableName()][$item->getId()],
            $fieldNameMap
        );
    }

    /**
     * Synchronize a single item
     *
     * @param DataMapItem $item
     * @param array $fieldNames
     * @param string|int $fromId
     */
    protected function synchronizeTranslationItem(DataMapItem $item, array $fieldNames, $fromId)
    {
        if (empty($fieldNames)) {
            return;
        }

        $fieldNameList = 'uid,' . implode(',', $fieldNames);

        $fromRecord = ['uid' => $fromId];
        if (MathUtility::canBeInterpretedAsInteger($fromId)) {
            $fromRecord = BackendUtility::getRecordWSOL(
                $item->getTableName(),
                $fromId,
                $fieldNameList
            );
        }

        $forRecord = [];
        if (!$item->isNew()) {
            $forRecord = BackendUtility::getRecordWSOL(
                $item->getTableName(),
                $item->getId(),
                $fieldNameList
            );
        }

        if (is_array($fromRecord) && is_array($forRecord)) {
            foreach ($fieldNames as $fieldName) {
                $this->synchronizeFieldValues(
                    $item,
                    $fieldName,
                    $fromRecord,
                    $forRecord
                );
            }
        }
    }

    /**
     * Populates values downwards, either from a parent language item or
     * a source language item to an accordant dependent translation item.
     *
     * @param DataMapItem $item
     */
    protected function populateTranslationItem(DataMapItem $item)
    {
        foreach ([DataMapItem::SCOPE_PARENT, DataMapItem::SCOPE_SOURCE] as $scope) {
            foreach ($item->findDependencies($scope) as $dependentItem) {
                // use suggested item, if it was submitted in data-map
                $suggestedDependentItem = $this->findItem(
                    $dependentItem->getTableName(),
                    $dependentItem->getId()
                );
                if ($suggestedDependentItem !== null) {
                    $dependentItem = $suggestedDependentItem;
                }
                foreach ([$scope, DataMapItem::SCOPE_EXCLUDE] as $dependentScope) {
                    $fieldNames = $this->getFieldNamesForItemScope(
                        $dependentItem,
                        $dependentScope,
                        false
                    );
                    $this->synchronizeTranslationItem(
                        $dependentItem,
                        $fieldNames,
                        $item->getId()
                    );
                }
            }
        }
    }

    /**
     * Finishes a translation item by updating states to be persisted.
     *
     * @param DataMapItem $item
     */
    protected function finishTranslationItem(DataMapItem $item)
    {
        if (
            $item->isParentType()
            || !State::isApplicable($item->getTableName())
        ) {
            return;
        }

        $this->allDataMap[$item->getTableName()][$item->getId()]['l10n_state'] = $item->getState()->export();
    }

    /**
     * Synchronize simple values like text and similar
     *
     * @param DataMapItem $item
     * @param string $fieldName
     * @param array $fromRecord
     * @param array $forRecord
     */
    protected function synchronizeFieldValues(DataMapItem $item, string $fieldName, array $fromRecord, array $forRecord)
    {
        // skip if this field has been processed already, assumed that proper sanitation happened
        if ($this->isSetInDataMap($item->getTableName(), $item->getId(), $fieldName)) {
            return;
        }

        $fromId = $fromRecord['uid'];
        // retrieve value from in-memory data-map
        if ($this->isSetInDataMap($item->getTableName(), $fromId, $fieldName)) {
            $fromValue = $this->allDataMap[$item->getTableName()][$fromId][$fieldName];
        } elseif (array_key_exists($fieldName, $fromRecord)) {
            // retrieve value from record
            $fromValue = $fromRecord[$fieldName];
        } else {
            // otherwise abort synchronization
            return;
        }

        // plain values
        if (!$this->isRelationField($item->getTableName(), $fieldName)) {
            $this->modifyDataMap(
                $item->getTableName(),
                $item->getId(),
                [$fieldName => $fromValue]
            );
        } elseif (!$this->isInlineRelationField($item->getTableName(), $fieldName)) {
            // direct relational values
            $this->synchronizeDirectRelations($item, $fieldName, $fromRecord);
        } else {
            // inline relational values
            $this->synchronizeInlineRelations($item, $fieldName, $fromRecord, $forRecord);
        }
    }

    /**
     * Synchronize select and group field localizations
     *
     * @param DataMapItem $item
     * @param string $fieldName
     * @param array $fromRecord
     */
    protected function synchronizeDirectRelations(DataMapItem $item, string $fieldName, array $fromRecord)
    {
        $configuration = $GLOBALS['TCA'][$item->getTableName()]['columns'][$fieldName];
        $isSpecialLanguageField = ($configuration['config']['special'] ?? null) === 'languages';

        $fromId = $fromRecord['uid'];
        if ($this->isSetInDataMap($item->getTableName(), $fromId, $fieldName)) {
            $fromValue = $this->allDataMap[$item->getTableName()][$fromId][$fieldName];
        } else {
            $fromValue = $fromRecord[$fieldName];
        }

        // non-MM relations are stored as comma separated values, just use them
        // if values are available in data-map already, just use them as well
        if (
            empty($configuration['config']['MM'])
            || $this->isSetInDataMap($item->getTableName(), $fromId, $fieldName)
            || $isSpecialLanguageField
        ) {
            $this->modifyDataMap(
                $item->getTableName(),
                $item->getId(),
                [$fieldName => $fromValue]
            );
            return;
        }
        // resolve the language special table name
        if ($isSpecialLanguageField) {
            $specialTableName = 'sys_language';
        }
        // fetch MM relations from storage
        $type = $configuration['config']['type'];
        $manyToManyTable = $configuration['config']['MM'];
        if ($type === 'group' && $configuration['config']['internal_type'] === 'db') {
            $tableNames = trim($configuration['config']['allowed'] ?? '');
        } elseif ($configuration['config']['type'] === 'select') {
            $tableNames = ($specialTableName ?? $configuration['config']['foreign_table'] ?? '');
        } else {
            return;
        }

        $relationHandler = $this->createRelationHandler();
        $relationHandler->start(
            '',
            $tableNames,
            $manyToManyTable,
            $fromId,
            $item->getTableName(),
            $configuration['config']
        );

        // provide list of relations, optionally prepended with table name
        // e.g. "13,19,23" or "tt_content_27,tx_extension_items_28"
        $this->modifyDataMap(
            $item->getTableName(),
            $item->getId(),
            [$fieldName => implode(',', $relationHandler->getValueArray())]
        );
    }

    /**
     * Handle synchronization of inline relations
     *
     * @param DataMapItem $item
     * @param string $fieldName
     * @param array $fromRecord
     * @param array $forRecord
     * @throws \RuntimeException
     */
    protected function synchronizeInlineRelations(DataMapItem $item, string $fieldName, array $fromRecord, array $forRecord)
    {
        $configuration = $GLOBALS['TCA'][$item->getTableName()]['columns'][$fieldName];
        $isLocalizationModeExclude = ($configuration['l10n_mode'] ?? null) === 'exclude';
        $foreignTableName = $configuration['config']['foreign_table'];

        $fieldNames = [
            'language' => $GLOBALS['TCA'][$foreignTableName]['ctrl']['languageField'] ?? null,
            'parent' => $GLOBALS['TCA'][$foreignTableName]['ctrl']['transOrigPointerField'] ?? null,
            'source' => $GLOBALS['TCA'][$foreignTableName]['ctrl']['translationSource'] ?? null,
        ];
        $isTranslatable = (!empty($fieldNames['language']) && !empty($fieldNames['parent']));

        $suggestedAncestorIds = $this->resolveSuggestedInlineRelations(
            $item,
            $fieldName,
            $fromRecord
        );
        $persistedIds = $this->resolvePersistedInlineRelations(
            $item,
            $fieldName,
            $forRecord
        );

        // The dependent ID map points from language parent/source record to
        // localization, thus keys: parents/sources & values: localizations
        $dependentIdMap = $this->fetchDependentIdMap($foreignTableName, $suggestedAncestorIds, $item->getLanguage());
        // filter incomplete structures - this is a drawback of DataHandler's remap stack, since
        // just created IRRE translations still belong to the language parent - filter them out
        $suggestedAncestorIds = array_diff($suggestedAncestorIds, array_values($dependentIdMap));
        // compile element differences to be resolved
        // remove elements that are persisted at the language translation, but not required anymore
        $removeIds = array_diff($persistedIds, array_values($dependentIdMap));
        // remove elements that are persisted at the language parent/source, but not required anymore
        $removeAncestorIds = array_diff(array_keys($dependentIdMap), $suggestedAncestorIds);
        // missing elements that are persisted at the language parent/source, but not translated yet
        $missingAncestorIds = array_diff($suggestedAncestorIds, array_keys($dependentIdMap));
        // persisted elements that should be copied or localized
        $createAncestorIds = $this->filterNumericIds($missingAncestorIds, true);
        // non-persisted elements that should be duplicated in data-map directly
        $populateAncestorIds = $this->filterNumericIds($missingAncestorIds, false);
        // this desired state map defines the final result of child elements in their parent translation
        $desiredIdMap = array_combine($suggestedAncestorIds, $suggestedAncestorIds);
        // update existing translations in the desired state map
        foreach ($dependentIdMap as $ancestorId => $translationId) {
            if (isset($desiredIdMap[$ancestorId])) {
                $desiredIdMap[$ancestorId] = $translationId;
            }
        }
        // no children to be synchronized, but element order could have been changed
        if (empty($removeAncestorIds) && empty($missingAncestorIds)) {
            $this->modifyDataMap(
                $item->getTableName(),
                $item->getId(),
                [$fieldName => implode(',', array_values($desiredIdMap))]
            );
            return;
        }
        // In case only missing elements shall be created, re-use previously sanitized
        // values IF the relation parent item is new and the count of missing relations
        // equals the count of previously sanitized relations.
        // This is caused during copy processes, when the child relations
        // already have been cloned in DataHandler::copyRecord_procBasedOnFieldType()
        // without the possibility to resolve the initial connections at this point.
        // Otherwise child relations would superfluously be duplicated again here.
        // @todo Invalid manually injected child relations cannot be determined here
        $sanitizedValue = $this->sanitizationMap[$item->getTableName()][$item->getId()][$fieldName] ?? null;
        if (
            !empty($missingAncestorIds) && $item->isNew() && $sanitizedValue !== null
            && count(GeneralUtility::trimExplode(',', $sanitizedValue, true)) === count($missingAncestorIds)
        ) {
            $this->modifyDataMap(
                $item->getTableName(),
                $item->getId(),
                [$fieldName => $sanitizedValue]
            );
            return;
        }

        $localCommandMap = [];
        foreach ($removeIds as $removeId) {
            $localCommandMap[$foreignTableName][$removeId]['delete'] = true;
        }
        foreach ($removeAncestorIds as $removeAncestorId) {
            $removeId = $dependentIdMap[$removeAncestorId];
            $localCommandMap[$foreignTableName][$removeId]['delete'] = true;
        }
        foreach ($createAncestorIds as $createAncestorId) {
            // if child table is not aware of localization, just copy
            if ($isLocalizationModeExclude || !$isTranslatable) {
                $localCommandMap[$foreignTableName][$createAncestorId]['copy'] = -$createAncestorId;
            } else {
                // otherwise, trigger the localization process
                $localCommandMap[$foreignTableName][$createAncestorId]['localize'] = $item->getLanguage();
            }
        }
        // execute copy, localize and delete actions on persisted child records
        if (!empty($localCommandMap)) {
            $localDataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $localDataHandler->start([], $localCommandMap, $this->backendUser);
            $localDataHandler->process_cmdmap();
            // update copied or localized ids
            foreach ($createAncestorIds as $createAncestorId) {
                if (empty($localDataHandler->copyMappingArray_merged[$foreignTableName][$createAncestorId])) {
                    $additionalInformation = '';
                    if (!empty($localDataHandler->errorLog)) {
                        $additionalInformation = ', reason "'
                        . implode(', ', $localDataHandler->errorLog) . '"';
                    }
                    throw new \RuntimeException(
                        'Child record was not processed' . $additionalInformation,
                        1486233164
                    );
                }
                $newLocalizationId = $localDataHandler->copyMappingArray_merged[$foreignTableName][$createAncestorId];
                $newLocalizationId = $localDataHandler->getAutoVersionId($foreignTableName, $newLocalizationId) ?? $newLocalizationId;
                $desiredIdMap[$createAncestorId] = $newLocalizationId;
            }
        }
        // populate new child records in data-map
        if (!empty($populateAncestorIds)) {
            foreach ($populateAncestorIds as $populateAncestorId) {
                $newLocalizationId = StringUtility::getUniqueId('NEW');
                $desiredIdMap[$populateAncestorId] = $newLocalizationId;
                $duplicatedValues = $this->duplicateFromDataMap(
                    $foreignTableName,
                    $populateAncestorId,
                    $item->getLanguage(),
                    $fieldNames,
                    !$isLocalizationModeExclude && $isTranslatable
                );
                $this->modifyDataMap(
                    $foreignTableName,
                    $newLocalizationId,
                    $duplicatedValues
                );
            }
        }
        // update inline parent field references - required to update pointer fields
        $this->modifyDataMap(
            $item->getTableName(),
            $item->getId(),
            [$fieldName => implode(',', array_values($desiredIdMap))]
        );
    }

    /**
     * Determines suggest inline relations of either translation parent or
     * source record from data-map or storage in case records have been
     * persisted already.
     *
     * @param DataMapItem $item
     * @param string $fieldName
     * @param array $fromRecord
     * @return int[]|string[]
     */
    protected function resolveSuggestedInlineRelations(DataMapItem $item, string $fieldName, array $fromRecord): array
    {
        $suggestedAncestorIds = [];
        $fromId = $fromRecord['uid'];
        $configuration = $GLOBALS['TCA'][$item->getTableName()]['columns'][$fieldName];
        $foreignTableName = $configuration['config']['foreign_table'];
        $manyToManyTable = ($configuration['config']['MM'] ?? '');

        // determine suggested elements of either translation parent or source record
        // from data-map, in case the accordant language parent/source record was modified
        if ($this->isSetInDataMap($item->getTableName(), $fromId, $fieldName)) {
            $suggestedAncestorIds = GeneralUtility::trimExplode(
                ',',
                $this->allDataMap[$item->getTableName()][$fromId][$fieldName],
                true
            );
        } elseif (MathUtility::canBeInterpretedAsInteger($fromId)) {
            // determine suggested elements of either translation parent or source record from storage
            $relationHandler = $this->createRelationHandler();
            $relationHandler->start(
                $fromRecord[$fieldName],
                $foreignTableName,
                $manyToManyTable,
                $fromId,
                $item->getTableName(),
                $configuration['config']
            );
            $suggestedAncestorIds = $this->mapRelationItemId($relationHandler->itemArray);
        }

        return array_filter($suggestedAncestorIds);
    }

    /**
     * Determine persisted inline relations for current data-map-item.
     *
     * @param DataMapItem $item
     * @param string $fieldName
     * @param array $forRecord
     * @return int[]
     */
    private function resolvePersistedInlineRelations(DataMapItem $item, string $fieldName, array $forRecord): array
    {
        $persistedIds = [];
        $configuration = $GLOBALS['TCA'][$item->getTableName()]['columns'][$fieldName];
        $foreignTableName = $configuration['config']['foreign_table'];
        $manyToManyTable = ($configuration['config']['MM'] ?? '');

        // determine persisted elements for the current data-map item
        if (!$item->isNew()) {
            $relationHandler = $this->createRelationHandler();
            $relationHandler->start(
                $forRecord[$fieldName] ?? '',
                $foreignTableName,
                $manyToManyTable,
                $item->getId(),
                $item->getTableName(),
                $configuration['config']
            );
            $persistedIds = $this->mapRelationItemId($relationHandler->itemArray);
        }

        return array_filter($persistedIds);
    }

    /**
     * Determines whether a combination of table name, id and field name is
     * set in data-map. This method considers null values as well, that would
     * not be considered by a plain isset() invocation.
     *
     * @param string $tableName
     * @param string|int $id
     * @param string $fieldName
     * @return bool
     */
    protected function isSetInDataMap(string $tableName, $id, string $fieldName)
    {
        return
            // directly look-up field name
            isset($this->allDataMap[$tableName][$id][$fieldName])
            // check existence of field name as key for null values
            || isset($this->allDataMap[$tableName][$id])
            && is_array($this->allDataMap[$tableName][$id])
            && array_key_exists($fieldName, $this->allDataMap[$tableName][$id]);
    }

    /**
     * Applies modifications to the data-map, calling this method is essential
     * to determine new data-map items to be process for synchronizing chained
     * record localizations.
     *
     * @param string $tableName
     * @param string|int $id
     * @param array $values
     * @throws \RuntimeException
     */
    protected function modifyDataMap(string $tableName, $id, array $values)
    {
        // avoid superfluous iterations by data-map changes with values
        // that actually have not been changed and were available already
        $sameValues = array_intersect_assoc(
            $this->allDataMap[$tableName][$id] ?? [],
            $values
        );
        if (!empty($sameValues)) {
            $fieldNames = implode(', ', array_keys($sameValues));
            throw new \RuntimeException(
                sprintf(
                    'Issued data-map change for table %s with same values '
                    . 'for these fields names %s',
                    $tableName,
                    $fieldNames
                ),
                1488634845
            );
        }

        $this->modifiedDataMap[$tableName][$id] = array_merge(
            $this->modifiedDataMap[$tableName][$id] ?? [],
            $values
        );
        $this->allDataMap[$tableName][$id] = array_merge(
            $this->allDataMap[$tableName][$id] ?? [],
            $values
        );
    }

    /**
     * @param DataMapItem $item
     */
    protected function addNextItem(DataMapItem $item)
    {
        $identifier = $item->getTableName() . ':' . $item->getId();
        if (!isset($this->allItems[$identifier])) {
            $this->allItems[$identifier] = $item;
        }
        $this->nextItems[$identifier] = $item;
    }

    /**
     * Fetches translation related field values for the items submitted in
     * the data-map.
     *
     * @param string $tableName
     * @param array $fieldNames
     * @param array $ids
     * @return array
     */
    protected function fetchTranslationValues(string $tableName, array $fieldNames, array $ids)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class, $this->backendUser->workspace, false));
        $statement = $queryBuilder
            ->select(...array_values($fieldNames))
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter($ids, Connection::PARAM_INT_ARRAY)
                )
            )
            ->execute();

        $translationValues = [];
        foreach ($statement as $record) {
            $translationValues[$record['uid']] = $record;
        }
        return $translationValues;
    }

    /**
     * Fetches translation dependencies for a given parent/source record ids.
     *
     * Existing records in database:
     * + [uid:5, l10n_parent=0, l10n_source=0, sys_language_uid=0]
     * + [uid:6, l10n_parent=5, l10n_source=5, sys_language_uid=1]
     * + [uid:7, l10n_parent=5, l10n_source=6, sys_language_uid=2]
     *
     * Input $ids and their results:
     * + [5]   -> [DataMapItem(6), DataMapItem(7)] # since 5 is parent/source
     * + [6]   -> [DataMapItem(7)]                 # since 6 is source
     * + [7]   -> []                               # since there's nothing
     *
     * @param string $tableName
     * @param int[]|string[] $ids
     * @return DataMapItem[][]
     */
    protected function fetchDependencies(string $tableName, array $ids)
    {
        if (!BackendUtility::isTableLocalizable($tableName)) {
            return [];
        }

        $fieldNames = [
            'uid' => 'uid',
            'l10n_state' => 'l10n_state',
            'language' => $GLOBALS['TCA'][$tableName]['ctrl']['languageField'],
            'parent' => $GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'],
        ];
        if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['translationSource'])) {
            $fieldNames['source'] = $GLOBALS['TCA'][$tableName]['ctrl']['translationSource'];
        }
        $fieldNamesMap = array_combine($fieldNames, $fieldNames);

        $persistedIds = $this->filterNumericIds($ids, true);
        $createdIds = $this->filterNumericIds($ids, false);
        $dependentElements = $this->fetchDependentElements($tableName, $persistedIds, $fieldNames);

        foreach ($createdIds as $createdId) {
            $data = $this->allDataMap[$tableName][$createdId] ?? null;
            if ($data === null) {
                continue;
            }
            $dependentElements[] = array_merge(
                ['uid' => $createdId],
                array_intersect_key($data, $fieldNamesMap)
            );
        }

        $dependencyMap = [];
        foreach ($dependentElements as $dependentElement) {
            $dependentItem = DataMapItem::build(
                $tableName,
                $dependentElement['uid'],
                [],
                $dependentElement,
                $fieldNames
            );

            if ($dependentItem->isDirectChildType()) {
                $dependencyMap[$dependentItem->getParent()][State::STATE_PARENT][] = $dependentItem;
            }
            if ($dependentItem->isGrandChildType()) {
                $dependencyMap[$dependentItem->getParent()][State::STATE_PARENT][] = $dependentItem;
                $dependencyMap[$dependentItem->getSource()][State::STATE_SOURCE][] = $dependentItem;
            }
        }
        return $dependencyMap;
    }

    /**
     * Fetches dependent records that depend on given record id's in in either
     * their parent or source field for translatable tables or their origin
     * field for non-translatable tables and creates an id mapping.
     *
     * This method expands the search criteria by expanding to ancestors.
     *
     * Existing records in database:
     * + [uid:5, l10n_parent=0, l10n_source=0, sys_language_uid=0]
     * + [uid:6, l10n_parent=5, l10n_source=5, sys_language_uid=1]
     * + [uid:7, l10n_parent=5, l10n_source=6, sys_language_uid=2]
     *
     * Input $ids and $desiredLanguage and their results:
     * + $ids=[5], $lang=1 -> [5 => 6] # since 5 is source of 6
     * + $ids=[5], $lang=2 -> []       # since 5 is parent of 7, but different language
     * + $ids=[6], $lang=1 -> []       # since there's nothing
     * + $ids=[6], $lang=2 -> [6 => 7] # since 6 has source 5, which is ancestor of 7
     * + $ids=[7], $lang=* -> []       # since there's nothing
     *
     * @param string $tableName
     * @param array $ids
     * @param int $desiredLanguage
     * @return array
     */
    protected function fetchDependentIdMap(string $tableName, array $ids, int $desiredLanguage)
    {
        $ids = $this->filterNumericIds($ids, true);
        $isTranslatable = BackendUtility::isTableLocalizable($tableName);
        $originFieldName = ($GLOBALS['TCA'][$tableName]['ctrl']['origUid'] ?? null);

        if (!$isTranslatable && $originFieldName === null) {
            return [];
        }

        if ($isTranslatable) {
            $fieldNames = [
                'uid' => 'uid',
                'l10n_state' => 'l10n_state',
                'language' => $GLOBALS['TCA'][$tableName]['ctrl']['languageField'],
                'parent' => $GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'],
            ];
            if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['translationSource'])) {
                $fieldNames['source'] = $GLOBALS['TCA'][$tableName]['ctrl']['translationSource'];
            }
        } else {
            $fieldNames = [
                'uid' => 'uid',
                'origin' => $originFieldName,
            ];
        }

        $fetchIds = $ids;
        if ($isTranslatable) {
            // expand search criteria via parent and source elements
            $translationValues = $this->fetchTranslationValues($tableName, $fieldNames, $ids);
            $ancestorIdMap = $this->buildElementAncestorIdMap($fieldNames, $translationValues);
            $fetchIds = array_unique(array_merge($ids, array_keys($ancestorIdMap)));
        }

        $dependentElements = $this->fetchDependentElements($tableName, $fetchIds, $fieldNames);

        $dependentIdMap = [];
        foreach ($dependentElements as $dependentElement) {
            $dependentId = $dependentElement['uid'];
            // implicit: use origin pointer if table cannot be translated
            if (!$isTranslatable) {
                $ancestorId = (int)$dependentElement[$fieldNames['origin']];
            // only consider element if it reflects the desired language
            } elseif ((int)$dependentElement[$fieldNames['language']] === $desiredLanguage) {
                $ancestorId = $this->resolveAncestorId($fieldNames, $dependentElement);
            } else {
                // otherwise skip the element completely
                continue;
            }
            // only keep ancestors that were initially requested before expanding
            if (in_array($ancestorId, $ids)) {
                $dependentIdMap[$ancestorId] = $dependentId;
            } elseif (!empty($ancestorIdMap[$ancestorId])) {
                // resolve from previously expanded search criteria
                $possibleChainedIds = array_intersect(
                    $ids,
                    $ancestorIdMap[$ancestorId]
                );
                if (!empty($possibleChainedIds)) {
                    $ancestorId = $possibleChainedIds[0];
                    $dependentIdMap[$ancestorId] = $dependentId;
                }
            }
        }
        return $dependentIdMap;
    }

    /**
     * Fetch all elements that depend on given record id's in either their
     * parent or source field for translatable tables or their origin field
     * for non-translatable tables.
     *
     * @param string $tableName
     * @param array $ids
     * @param array $fieldNames
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function fetchDependentElements(string $tableName, array $ids, array $fieldNames)
    {
        $ids = $this->filterNumericIds($ids, true);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class, $this->backendUser->workspace, false));

        $zeroParameter = $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT);
        $ids = array_filter($ids, [MathUtility::class, 'canBeInterpretedAsInteger']);
        $idsParameter = $queryBuilder->createNamedParameter($ids, Connection::PARAM_INT_ARRAY);

        // fetch by language dependency
        if (!empty($fieldNames['language']) && !empty($fieldNames['parent'])) {
            $ancestorPredicates = [
                $queryBuilder->expr()->in(
                    $fieldNames['parent'],
                    $idsParameter
                )
            ];
            if (!empty($fieldNames['source'])) {
                $ancestorPredicates[] = $queryBuilder->expr()->in(
                    $fieldNames['source'],
                    $idsParameter
                );
            }
            $predicates = [
                // must be any kind of localization
                $queryBuilder->expr()->gt(
                    $fieldNames['language'],
                    $zeroParameter
                ),
                // must be in connected mode
                $queryBuilder->expr()->gt(
                    $fieldNames['parent'],
                    $zeroParameter
                ),
                // any parent or source pointers
                $queryBuilder->expr()->orX(...$ancestorPredicates),
            ];
        } elseif (!empty($fieldNames['origin'])) {
            // fetch by origin dependency ("copied from")
            $predicates = [
                $queryBuilder->expr()->in(
                    $fieldNames['origin'],
                    $idsParameter
                )
            ];
        } else {
            // otherwise: stop execution
            throw new \InvalidArgumentException(
                'Invalid combination of query field names given',
                1487192370
            );
        }

        $statement = $queryBuilder
            ->select(...array_values($fieldNames))
            ->from($tableName)
            ->andWhere(...$predicates)
            ->execute();

        $dependentElements = [];
        foreach ($statement as $record) {
            $dependentElements[] = $record;
        }
        return $dependentElements;
    }

    /**
     * Return array of data map items that are of given type
     *
     * @param string $type
     * @param DataMapItem[] $items
     * @return DataMapItem[]
     */
    protected function filterItemsByType(string $type, array $items)
    {
        return array_filter(
            $items,
            function (DataMapItem $item) use ($type) {
                return $item->getType() === $type;
            }
        );
    }

    /**
     * Return only ids that are integer - so no "NEW..." values
     *
     * @param string[]|int[] $ids
     * @param bool $numeric
     * @return int[]|string[]
     */
    protected function filterNumericIds(array $ids, bool $numeric = true)
    {
        return array_filter(
            $ids,
            function ($id) use ($numeric) {
                return MathUtility::canBeInterpretedAsInteger($id) === $numeric;
            }
        );
    }

    /**
     * Return only ids that don't have an item equivalent in $this->allItems.
     *
     * @param string $tableName
     * @param int[] $ids
     * @return array
     */
    protected function filterNewItemIds(string $tableName, array $ids)
    {
        return array_filter(
            $ids,
            function ($id) use ($tableName) {
                return $this->findItem($tableName, $id) === null;
            }
        );
    }

    /**
     * Flatten array
     *
     * @param array $relationItems
     * @return string[]
     */
    protected function mapRelationItemId(array $relationItems)
    {
        return array_map(
            function (array $relationItem) {
                return (int)$relationItem['id'];
            },
            $relationItems
        );
    }

    /**
     * @param array $fieldNames
     * @param array $element
     * @return int|null
     */
    protected function resolveAncestorId(array $fieldNames, array $element)
    {
        // implicit: having source value different to parent value, use source pointer
        if (
            !empty($fieldNames['source'])
            && $element[$fieldNames['source']] !== $element[$fieldNames['parent']]
        ) {
            return (int)$fieldNames['source'];
        }
        if (!empty($fieldNames['parent'])) {
            // implicit: use parent pointer if defined
            return (int)$element[$fieldNames['parent']];
        }
        return null;
    }

    /**
     * Builds a map from ancestor ids to accordant localization dependents.
     *
     * The result of e.g. [5 => [6, 7]] refers to ids 6 and 7 being dependents
     * (either used in parent or source field) of the ancestor with id 5.
     *
     * @param array $fieldNames
     * @param array $elements
     * @return array
     */
    protected function buildElementAncestorIdMap(array $fieldNames, array $elements)
    {
        $ancestorIdMap = [];
        foreach ($elements as $element) {
            $ancestorId = $this->resolveAncestorId($fieldNames, $element);
            if ($ancestorId !== null) {
                $ancestorIdMap[$ancestorId][] = (int)$element['uid'];
            }
        }
        return $ancestorIdMap;
    }

    /**
     * See if an items is in item list and return it
     *
     * @param string $tableName
     * @param string|int $id
     * @return DataMapItem|null
     */
    protected function findItem(string $tableName, $id)
    {
        return $this->allItems[$tableName . ':' . $id] ?? null;
    }

    /**
     * Duplicates an item from data-map and prefixed language title,
     * if applicable for the accordant field name.
     *
     * @param string $tableName
     * @param string|int $fromId
     * @param int $language
     * @param array $fieldNames
     * @param bool $localize
     * @return array
     */
    protected function duplicateFromDataMap(string $tableName, $fromId, int $language, array $fieldNames, bool $localize)
    {
        $data = $this->allDataMap[$tableName][$fromId];
        // just return duplicated item if localization cannot be applied
        if (empty($language) || !$localize) {
            return $data;
        }

        $data[$fieldNames['language']] = $language;
        if (empty($data[$fieldNames['parent']])) {
            // @todo Only $id used in TCA type 'select' is resolved in DataHandler's remapStack
            $data[$fieldNames['parent']] = $fromId;
        }
        if (!empty($fieldNames['source'])) {
            // @todo Not sure, whether $id is resolved in DataHandler's remapStack
            $data[$fieldNames['source']] = $fromId;
        }
        // unset field names that are expected to be handled in this processor
        foreach ($this->getFieldNamesToBeHandled($tableName) as $fieldName) {
            unset($data[$fieldName]);
        }

        $prefixFieldNames = array_intersect(
            array_keys($data),
            $this->getPrefixLanguageTitleFieldNames($tableName)
        );
        if (empty($prefixFieldNames)) {
            return $data;
        }

        $languageService = $this->getLanguageService();
        $languageRecord = BackendUtility::getRecord('sys_language', $language, 'title');
        list($pageId) = BackendUtility::getTSCpid($tableName, $fromId, $data['pid'] ?? null);

        $tsConfigTranslateToMessage = BackendUtility::getPagesTSconfig($pageId)['TCEMAIN.']['translateToMessage'] ?? '';
        if (!empty($tsConfigTranslateToMessage)) {
            $prefix = $tsConfigTranslateToMessage;
            if ($languageService !== null) {
                $prefix = $languageService->sL($prefix);
            }
            $prefix = sprintf($prefix, $languageRecord['title']);
        }
        if (empty($prefix)) {
            $prefix = 'Translate to ' . $languageRecord['title'] . ':';
        }

        foreach ($prefixFieldNames as $prefixFieldName) {
            // @todo The hook in DataHandler is not applied here
            $data[$prefixFieldName] = '[' . $prefix . '] ' . $data[$prefixFieldName];
        }

        return $data;
    }

    /**
     * Field names we have to deal with
     *
     * @param DataMapItem $item
     * @param string $scope
     * @param bool $modified
     * @return string[]
     */
    protected function getFieldNamesForItemScope(
        DataMapItem $item,
        string $scope,
        bool $modified
    ) {
        if (
            $scope === DataMapItem::SCOPE_PARENT
            || $scope === DataMapItem::SCOPE_SOURCE
        ) {
            if (!State::isApplicable($item->getTableName())) {
                return [];
            }
            return $item->getState()->filterFieldNames($scope, $modified);
        }
        if ($scope === DataMapItem::SCOPE_EXCLUDE) {
            return $this->getLocalizationModeExcludeFieldNames(
                $item->getTableName()
            );
        }
        return [];
    }

    /**
     * Field names of TCA table with columns having l10n_mode=exclude
     *
     * @param string $tableName
     * @return string[]
     */
    protected function getLocalizationModeExcludeFieldNames(string $tableName)
    {
        $localizationExcludeFieldNames = [];
        if (empty($GLOBALS['TCA'][$tableName]['columns'])) {
            return $localizationExcludeFieldNames;
        }

        foreach ($GLOBALS['TCA'][$tableName]['columns'] as $fieldName => $configuration) {
            if (($configuration['l10n_mode'] ?? null) === 'exclude') {
                $localizationExcludeFieldNames[] = $fieldName;
            }
        }

        return $localizationExcludeFieldNames;
    }

    /**
     * Gets a list of field names which have to be handled. Basically this
     * includes fields using allowLanguageSynchronization or l10n_mode=exclude.
     *
     * @param string $tableName
     * @return string[]
     */
    protected function getFieldNamesToBeHandled(string $tableName)
    {
        return array_merge(
            State::getFieldNames($tableName),
            $this->getLocalizationModeExcludeFieldNames($tableName)
        );
    }

    /**
     * Field names of TCA table with columns having l10n_mode=prefixLangTitle
     *
     * @param string $tableName
     * @return array
     */
    protected function getPrefixLanguageTitleFieldNames(string $tableName)
    {
        $prefixLanguageTitleFieldNames = [];
        if (empty($GLOBALS['TCA'][$tableName]['columns'])) {
            return $prefixLanguageTitleFieldNames;
        }

        foreach ($GLOBALS['TCA'][$tableName]['columns'] as $fieldName => $configuration) {
            $type = $configuration['config']['type'] ?? null;
            if (
                ($configuration['l10n_mode'] ?? null) === 'prefixLangTitle'
                && ($type === 'input' || $type === 'text')
            ) {
                $prefixLanguageTitleFieldNames[] = $fieldName;
            }
        }

        return $prefixLanguageTitleFieldNames;
    }

    /**
     * True if we're dealing with a field that has foreign db relations
     *
     * @param string $tableName
     * @param string $fieldName
     * @return bool True if field is type=group with internalType === db or select with foreign_table
     */
    protected function isRelationField(string $tableName, string $fieldName): bool
    {
        if (empty($GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config']['type'])) {
            return false;
        }

        $configuration = $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'];

        return
            $configuration['type'] === 'group'
                && ($configuration['internal_type'] ?? null) === 'db'
                && !empty($configuration['allowed'])
            || $configuration['type'] === 'select'
                && (
                    !empty($configuration['foreign_table'])
                        && !empty($GLOBALS['TCA'][$configuration['foreign_table']])
                    || ($configuration['special'] ?? null) === 'languages'
                )
            || $this->isInlineRelationField($tableName, $fieldName)
        ;
    }

    /**
     * True if we're dealing with an inline field
     *
     * @param string $tableName
     * @param string $fieldName
     * @return bool TRUE if field is of type inline with foreign_table set
     */
    protected function isInlineRelationField(string $tableName, string $fieldName): bool
    {
        if (empty($GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config']['type'])) {
            return false;
        }

        $configuration = $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'];

        return
            $configuration['type'] === 'inline'
            && !empty($configuration['foreign_table'])
            && !empty($GLOBALS['TCA'][$configuration['foreign_table']])
        ;
    }

    /**
     * Determines whether the table can be localized and either has fields
     * with allowLanguageSynchronization enabled or l10n_mode set to exclude.
     *
     * @param string $tableName
     * @return bool
     */
    protected function isApplicable(string $tableName): bool
    {
        return
            State::isApplicable($tableName)
            || BackendUtility::isTableLocalizable($tableName)
                && count($this->getLocalizationModeExcludeFieldNames($tableName)) > 0
        ;
    }

    /**
     * @return RelationHandler
     */
    protected function createRelationHandler()
    {
        $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
        $relationHandler->setWorkspaceId($this->backendUser->workspace);
        return $relationHandler;
    }

    /**
     * @return LanguageService|null
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'] ?? null;
    }
}
