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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * This processor analyses the provided data-map before actually being process
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
    protected $dataMap = [];

    /**
     * @var BackendUserAuthentication
     */
    protected $backendUser;

    /**
     * @var DataMapItem[]
     */
    protected $items = [];

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
        $this->dataMap = $dataMap;
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
        foreach ($this->dataMap as $tableName => $idValues) {
            $this->collectItems($tableName, $idValues);
        }
        if (!empty($this->items)) {
            $this->sanitize();
            $this->enrich();
        }
        return $this->dataMap;
    }

    /**
     * Create data map items of all affected rows
     *
     * @param string $tableName
     * @param array $idValues
     */
    protected function collectItems(string $tableName, array $idValues)
    {
        $forTableName = $tableName;
        if ($forTableName === 'pages') {
            $forTableName = 'pages_language_overlay';
        }

        if (!$this->isApplicable($forTableName)) {
            return;
        }

        $fieldNames = [
            'uid' => 'uid',
            'l10n_state' => 'l10n_state',
            'language' => $GLOBALS['TCA'][$forTableName]['ctrl']['languageField'],
            'parent' => $GLOBALS['TCA'][$forTableName]['ctrl']['transOrigPointerField'],
        ];
        if (!empty($GLOBALS['TCA'][$forTableName]['ctrl']['translationSource'])) {
            $fieldNames['source'] = $GLOBALS['TCA'][$forTableName]['ctrl']['translationSource'];
        }

        $translationValues = [];
        // Fetching parent/source pointer values does not make sense for pages
        if ($tableName !== 'pages') {
            $translationValues = $this->fetchTranslationValues(
                $tableName,
                $fieldNames,
                $this->filterNumericIds(array_keys($idValues))
            );
        }

        $dependencies = $this->fetchDependencies(
            $forTableName,
            $this->filterNumericIds(array_keys($idValues))
        );

        foreach ($idValues as $id => $values) {
            $recordValues = $translationValues[$id] ?? [];
            $item = DataMapItem::build(
                $tableName,
                $id,
                $values,
                $recordValues,
                $fieldNames
            );

            // must be any kind of localization and in connected mode
            if ($item->getLanguage() > 0 && empty($item->getParent())) {
                unset($item);
                continue;
            }
            // add dependencies
            if (!empty($dependencies[$id])) {
                $item->setDependencies($dependencies[$id]);
            }
            $this->items[$tableName . ':' . $id] = $item;
        }
    }

    /**
     * Sanitizes the submitted data-map and removes fields which are not
     * defined as custom and thus rely on either parent or source values.
     */
    protected function sanitize()
    {
        foreach (['grandChild', 'directChild'] as $type) {
            foreach ($this->filterItemsByType($type) as $item) {
                $this->sanitizeTranslationItem($item);
            }
        }
    }

    /**
     * Handle synchronization of an item list
     */
    protected function enrich()
    {
        foreach (['grandChild', 'directChild'] as $type) {
            foreach ($this->filterItemsByType($type) as $item) {
                foreach ($item->getApplicableScopes() as $scope) {
                    $fromId = $item->getIdForScope($scope);
                    $fieldNames = $this->getFieldNamesForItemScope($item, $scope, !$item->isNew());
                    $this->synchronizeTranslationItem($item, $fieldNames, $fromId);
                }
                $this->populateTranslationItem($item);
                $this->finishTranslationItem($item);
            }
        }
        foreach ($this->filterItemsByType('parent') as $item) {
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
        $fieldNames = array_merge(
            $this->getFieldNamesForItemScope($item, DataMapItem::SCOPE_PARENT, !$item->isNew()),
            $this->getFieldNamesForItemScope($item, DataMapItem::SCOPE_SOURCE, !$item->isNew())
        );
        // remove fields, that are submitted in data-map, but not defined as custom
        $this->dataMap[$item->getTableName()][$item->getId()] = array_diff_key(
            $this->dataMap[$item->getTableName()][$item->getId()],
            array_combine($fieldNames, $fieldNames)
        );
    }

    /**
     * Synchronize a single item
     *
     * @param DataMapItem $item
     * @param array $fieldNames
     * @param int $fromId
     */
    protected function synchronizeTranslationItem(DataMapItem $item, array $fieldNames, int $fromId)
    {
        if (empty($fieldNames)) {
            return;
        }
        $fieldNameList = 'uid,' . implode(',', $fieldNames);
        $fromRecord = BackendUtility::getRecordWSOL(
            $item->getFromTableName(),
            $fromId,
            $fieldNameList
        );
        $forRecord = [];
        if (!$item->isNew()) {
            $forRecord = BackendUtility::getRecordWSOL(
                $item->getTableName(),
                $item->getId(),
                $fieldNameList
            );
        }
        foreach ($fieldNames as $fieldName) {
            $this->synchronizeFieldValues(
                $item,
                $fieldName,
                $fromRecord,
                $forRecord
            );
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
        if ($item->isNew()) {
            return;
        }

        foreach ([State::STATE_PARENT, State::STATE_SOURCE] as $scope) {
            foreach ($item->findDependencies($scope) as $dependentItem) {
                // use suggested item, if it was submitted in data-map
                $suggestedDependentItem = $this->findItem(
                    $dependentItem->getTableName(),
                    $dependentItem->getId()
                );
                if ($suggestedDependentItem !== null) {
                    $dependentItem = $suggestedDependentItem;
                }
                $fieldNames = $this->getFieldNamesForItemScope(
                    $dependentItem,
                    $scope,
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

        $this->dataMap[$item->getTableName()][$item->getId()]['l10n_state'] = $item->getState()->export();
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
        if (!empty($this->dataMap[$item->getTableName()][$item->getId()][$fieldName])) {
            return;
        }

        $fromId = $fromRecord['uid'];
        $fromValue = $this->dataMap[$item->getFromTableName()][$fromId][$fieldName] ?? $fromRecord[$fieldName];

        // plain values
        if (!$this->isRelationField($item->getFromTableName(), $fieldName)) {
            $this->dataMap[$item->getTableName()][$item->getId()][$fieldName] = $fromValue;
        // direct relational values
        } elseif (!$this->isInlineRelationField($item->getFromTableName(), $fieldName)) {
            $this->synchronizeDirectRelations($item, $fieldName, $fromRecord);
        // inline relational values
        } else {
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
        $fromId = $fromRecord['uid'];
        $fromValue = $this->dataMap[$item->getFromTableName()][$fromId][$fieldName] ?? $fromRecord[$fieldName];
        $configuration = $GLOBALS['TCA'][$item->getFromTableName()]['columns'][$fieldName];

        // non-MM relations are stored as comma separated values, just use them
        // if values are available in data-map already, just use them as well
        if (
            empty($configuration['config']['MM'])
            || isset($this->dataMap[$item->getFromTableName()][$fromId][$fieldName])
            || ($configuration['config']['special'] ?? null) === 'languages'
        ) {
            $this->dataMap[$item->getTableName()][$item->getId()][$fieldName] = $fromValue;
            return;
        }

        // fetch MM relations from storage
        $type = $configuration['config']['type'];
        $manyToManyTable = $configuration['config']['MM'];
        if ($type === 'group' && $configuration['config']['internal_type'] === 'db') {
            $tableNames = trim($configuration['config']['allowed'] ?? '');
        } elseif ($configuration['config']['type'] === 'select') {
            $tableNames = ($configuration['foreign_table'] ?? '');
        } else {
            return;
        }

        $relationHandler = $this->createRelationHandler();
        $relationHandler->start(
            '',
            $tableNames,
            $manyToManyTable,
            $fromId,
            $item->getFromTableName(),
            $configuration['config']
        );

        // provide list of relations, optionally prepended with table name
        // e.g. "13,19,23" or "tt_content_27,tx_extension_items_28"
        $this->dataMap[$item->getTableName()][$item->getId()][$fieldName] = implode(
            ',',
            $relationHandler->getValueArray()
        );
    }

    /**
     * Handle synchonization of inline relations
     *
     * @param DataMapItem $item
     * @param string $fieldName
     * @param array $fromRecord
     * @param array $forRecord
     */
    protected function synchronizeInlineRelations(DataMapItem $item, string $fieldName, array $fromRecord, array $forRecord)
    {
        $fromId = $fromRecord['uid'];
        $configuration = $GLOBALS['TCA'][$item->getFromTableName()]['columns'][$fieldName];
        $foreignTableName = $configuration['config']['foreign_table'];
        $manyToManyTable = ($configuration['config']['MM'] ?? '');

        $languageFieldName = ($GLOBALS['TCA'][$foreignTableName]['ctrl']['languageField'] ?? null);
        $parentFieldName = ($GLOBALS['TCA'][$foreignTableName]['ctrl']['transOrigPointerField'] ?? null);
        $sourceFieldName = ($GLOBALS['TCA'][$foreignTableName]['ctrl']['translationSource'] ?? null);

        // determine suggested elements of either translation parent or source record
        // from data-map, in case the accordant language parent/source record was modified
        if (isset($this->dataMap[$item->getFromTableName()][$fromId][$fieldName])) {
            $suggestedAncestorIds = GeneralUtility::trimExplode(
                ',',
                $this->dataMap[$item->getFromTableName()][$fromId][$fieldName],
                true
            );
        // determine suggested elements of either translation parent or source record from storage
        } else {
            $relationHandler = $this->createRelationHandler();
            $relationHandler->start(
                $fromRecord[$fieldName],
                $foreignTableName,
                $manyToManyTable,
                $fromId,
                $item->getFromTableName(),
                $configuration['config']
            );
            $suggestedAncestorIds = $this->mapRelationItemId($relationHandler->itemArray);
        }
        // determine persisted elements for the current data-map item
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
        // The dependent ID map points from language parent/source record to
        // localization, thus keys: parents/sources & values: localizations
        $dependentIdMap = $this->fetchDependentIdMap($foreignTableName, $suggestedAncestorIds);
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
        // this desired state map defines the final result of child elements of the translation
        $desiredLocalizationIdMap = array_combine($suggestedAncestorIds, $suggestedAncestorIds);
        // update existing translations in the desired state map
        foreach ($dependentIdMap as $ancestorId => $translationId) {
            if (isset($desiredLocalizationIdMap[$ancestorId])) {
                $desiredLocalizationIdMap[$ancestorId] = $translationId;
            }
        }
        // nothing to synchronize, but element order could have been changed
        if (empty($removeAncestorIds) && empty($missingAncestorIds)) {
            $this->dataMap[$item->getTableName()][$item->getId()][$fieldName] = implode(
                ',',
                array_values($desiredLocalizationIdMap)
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
            if (empty($languageFieldName) || empty($parentFieldName)) {
                $localCommandMap[$foreignTableName][$createAncestorId]['copy'] = true;
            // otherwise, trigger the localization process
            } else {
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
                    throw new \RuntimeException('Child record was not processed', 1486233164);
                }
                $newLocalizationId = $localDataHandler->copyMappingArray_merged[$foreignTableName][$createAncestorId];
                $newLocalizationId = $localDataHandler->getAutoVersionId($foreignTableName, $newLocalizationId) ?? $newLocalizationId;
                $desiredLocalizationIdMap[$createAncestorId] = $newLocalizationId;
            }
        }
        // populate new child records in data-map
        if (!empty($populateAncestorIds)) {
            foreach ($populateAncestorIds as $populateId) {
                $newLocalizationId = StringUtility::getUniqueId('NEW');
                $desiredLocalizationIdMap[$populateId] = $newLocalizationId;
                // @todo l10n_mode=prefixLangTitle is not applied to this "in-memory translation"
                $this->dataMap[$foreignTableName][$newLocalizationId] = $this->dataMap[$foreignTableName][$populateId];
                $this->dataMap[$foreignTableName][$newLocalizationId][$languageFieldName] = $item->getLanguage();
                // @todo Only $populatedIs used in TCA type 'select' is resolved in DataHandler's remapStack
                $this->dataMap[$foreignTableName][$newLocalizationId][$parentFieldName] = $populateId;
                if ($sourceFieldName !== null) {
                    // @todo Not sure, whether $populateId is resolved in DataHandler's remapStack
                    $this->dataMap[$foreignTableName][$newLocalizationId][$sourceFieldName] = $populateId;
                }
            }
        }
        // update inline parent field references - required to update pointer fields
        $this->dataMap[$item->getTableName()][$item->getId()][$fieldName] = implode(
            ',',
            array_values($desiredLocalizationIdMap)
        );
    }

    /**
     * Fetches translation related field values for the items submitted in
     * the data-map. That's why further adjustment for the tables pages vs.
     * pages_language_overlay is not required.
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
     * @param string $tableName
     * @param array $ids
     * @return DataMapItem[][]
     */
    protected function fetchDependencies(string $tableName, array $ids)
    {
        if ($tableName === 'pages') {
            $tableName = 'pages_language_overlay';
        }

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

        $dependentElements = $this->fetchDependentElements($tableName, $ids, $fieldNames);

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
     * Fetch dependent records that depend on given record id's in their parent or source field and
     * create an id map as further lookup array
     *
     * @param string $tableName
     * @param array $ids
     * @return array
     */
    protected function fetchDependentIdMap(string $tableName, array $ids)
    {
        if ($tableName === 'pages') {
            $tableName = 'pages_language_overlay';
        }

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

        $dependentElements = $this->fetchDependentElements($tableName, $ids, $fieldNames);

        $dependentIdMap = [];
        foreach ($dependentElements as $dependentElement) {
            // implicit: having source value different to parent value, use source pointer
            if (
                !empty($fieldNames['source'])
                && $dependentElement[$fieldNames['source']] !== $dependentElement[$fieldNames['parent']]
            ) {
                $dependentIdMap[$dependentElement[$fieldNames['source']]] = $dependentElement['uid'];
            // implicit: otherwise, use parent pointer
            } else {
                $dependentIdMap[$dependentElement[$fieldNames['parent']]] = $dependentElement['uid'];
            }
        }
        return $dependentIdMap;
    }

    /**
     * Fetch all elements that depend on given record id's in their parent or source field
     *
     * @param string $tableName
     * @param array $ids
     * @param array $fieldNames
     * @return array
     */
    protected function fetchDependentElements(string $tableName, array $ids, array $fieldNames)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class, $this->backendUser->workspace, false));

        $zeroParameter = $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT);
        $idsParameter = $queryBuilder->createNamedParameter($ids, Connection::PARAM_INT_ARRAY);

        $predicates = [
            $queryBuilder->expr()->in(
                $fieldNames['parent'],
                $idsParameter
            )
        ];

        if (!empty($fieldNames['source'])) {
            $predicates[] = $queryBuilder->expr()->in(
                $fieldNames['source'],
                $idsParameter
            );
        }

        $statement = $queryBuilder
            ->select(...array_values($fieldNames))
            ->from($tableName)
            ->andWhere(
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
                $queryBuilder->expr()->orX(...$predicates)
            )
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
     * @return DataMapItem[]
     */
    protected function filterItemsByType(string $type)
    {
        return array_filter(
            $this->items,
            function (DataMapItem $item) use ($type) {
                return $item->getType() === $type;
            }
        );
    }

    /**
     * Return only id's that are integer - so no NEW...
     *
     * @param array $ids
     * @param bool $numeric
     * @return array
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
     * Flatten array
     *
     * @param array $relationItems
     * @return string[]
     */
    protected function mapRelationItemId(array $relationItems)
    {
        return array_map(
            function (array $relationItem) {
                return (string)$relationItem['id'];
            },
            $relationItems
        );
    }

    /**
     * See if an items is in item list and return it
     *
     * @param string $tableName
     * @param string|int $id
     * @return null|DataMapItem
     */
    protected function findItem(string $tableName, $id)
    {
        return $this->items[$tableName . ':' . $id] ?? null;
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
}
