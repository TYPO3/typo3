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

namespace TYPO3\CMS\Core\DataHandling\Localization;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Platform\PlatformInformation;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\ReferenceIndexUpdater;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * This processor analyzes and enriches the provided data-map before it is
 * actually processed by the calling DataHandler instance. Fields configured with
 * "allowLanguageSynchronization" or "l10n_mode=exclude" are synchronized either
 * from their parent/source record into the translation, or pushed downwards from
 * a default-language record to all its dependent translations.
 *
 * Except for inline relational record editing (IRRE), all modifications are
 * applied to the data-map directly, which ensures proper history entries as a
 * side-effect. For IRRE fields, this processor triggers copy or localize actions
 * by instantiating a local DataHandler instance.
 *
 * Processing flow in process():
 * The while loop implements a dirty queue that processes records one IRRE nesting
 * level at a time. On each iteration it processes the records in $modifiedDataMap:
 *
 * 1. collectItems() — turns each data-map entry into a typed DataMapItem
 *    (DataMapItem::TYPE_PARENT for default-language records,
 *    DataMapItem::TYPE_DIRECT_CHILD for first-level translations,
 *    DataMapItem::TYPE_GRAND_CHILD for translations of translations) and resolves
 *    their dependency graph (which parent/source record each translation points
 *    to). Items are accumulated in $allItems across iterations; $nextItems holds
 *    only the batch of the current iteration.
 *
 * 2. sanitize() — run once on the first iteration only. Strips fields from
 *    translation data-map entries that are subject to parent/source synchronization
 *    and not explicitly marked as "custom" in l10n_state. The stripped values are
 *    stashed in $sanitizationMap so that synchronizeReferences() can later detect
 *    child relations already created by DataHandler::copyRecord_procBasedOnFieldType()
 *    during copy operations, and avoid duplicating them.
 *
 * 3. enrich() — applies synchronized field values from the parent/source record
 *    into the translation via synchronizeTranslationItem(), and pushes values
 *    downwards from a parent item to its dependent translation items via
 *    populateTranslationItem(). For IRRE fields, synchronizeReferences() diffs
 *    the parent's child list against the translation's child list, then issues
 *    localize/copy/delete commands to a sub-DataHandler.
 *
 * The dirty queue: modifyDataMap() writes every field-value change to both
 * $allDataMap (the canonical, growing data-map) and $modifiedDataMap (the queue
 * for the next iteration). When enrich() localizes IRRE children via a
 * sub-DataHandler, those newly created records are fed back into $modifiedDataMap.
 * The while loop then picks them up in the next iteration, so that their own
 * inline children can be synchronized in turn. This is necessary because the UIDs
 * of newly localized IRRE records are unknown before the sub-DataHandler runs -
 * the tree cannot be pre-traversed. The loop terminates when no new records
 * were added to $modifiedDataMap during an enrich() pass.
 *
 * The mutable properties carry temporary state across internal method calls
 * during a single process() run and are reset at the end of process(). The class
 * must be non-shared (shared: false) in the DI container because IRRE
 * synchronization spawns nested DataHandler instances that each trigger their own
 * process() call, resulting in DH->DMP->DH->DMP nesting chains. A shared
 * instance would have its temporary state corrupted by the inner call.
 *
 * @internal should only be used by the TYPO3 Core
 */
#[Autoconfigure(public: true, shared: false)]
class DataMapProcessor
{
    protected array $allDataMap = [];

    /**
     * @var array<string, array>
     */
    protected array $modifiedDataMap = [];

    /**
     * @var array<string, array<int, array>>
     */
    protected array $sanitizationMap = [];

    /**
     * @var DataMapItem[]
     */
    protected array $allItems = [];

    /**
     * @var DataMapItem[]
     */
    protected array $nextItems = [];

    public function __construct(
        private readonly TcaSchemaFactory $tcaSchemaFactory,
        private readonly ConnectionPool $connectionPool,
        private readonly SiteFinder $siteFinder,
    ) {}

    /**
     * Processes the submitted data-map and returns the sanitized and enriched
     * version depending on accordant localization states and dependencies.
     */
    public function process(
        array $dataMap,
        BackendUserAuthentication $backendUser,
        ReferenceIndexUpdater $referenceIndexUpdater,
    ): array {
        $this->allDataMap = $dataMap;
        $this->modifiedDataMap = $dataMap;
        $this->allItems = [];
        $this->sanitizationMap = [];
        $this->nextItems = [];

        $iterations = 0;
        while (!empty($this->modifiedDataMap)) {
            $this->nextItems = [];
            foreach ($this->modifiedDataMap as $tableName => $idValues) {
                $this->collectItems($tableName, $idValues, $backendUser);
            }
            $this->modifiedDataMap = [];
            if (empty($this->nextItems)) {
                break;
            }
            if ($iterations++ === 0) {
                $this->sanitize($this->allItems);
            }
            $this->enrich($this->nextItems, $backendUser, $referenceIndexUpdater);
        }
        $dataMap = $this->purgeDataMap($this->allDataMap);

        // Reset class state to make clear this is temporary state, only.
        $this->allDataMap = [];
        $this->modifiedDataMap = [];
        $this->allItems = [];
        $this->sanitizationMap = [];
        $this->nextItems = [];

        return $dataMap;
    }

    /**
     * Purges superfluous empty data-map sections.
     */
    protected function purgeDataMap(array $dataMap): array
    {
        foreach ($dataMap as $tableName => $idValues) {
            foreach ($idValues as $id => $values) {
                // `l10n_state` should be serialized JSON at this point,
                // in case it's not, it most probably was ignored in `collectItems()`
                if (is_array($values['l10n_state'] ?? null)) {
                    unset($dataMap[$tableName][$id]['l10n_state'], $values['l10n_state']);
                }
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
     */
    protected function collectItems(string $tableName, array $idValues, BackendUserAuthentication $backendUser): void
    {
        if (!$this->isApplicable($tableName)) {
            return;
        }

        $schema = $this->getSchema($tableName);
        if ($schema === null) {
            throw new \RuntimeException('TCA schema for table "' . $tableName . '" not found, but table was considered applicable for language synchronization', 1744454610);
        }
        $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);
        $fieldNames = [
            'uid' => 'uid',
            'l10n_state' => 'l10n_state',
            'language' => $languageCapability->getLanguageField()->getName(),
            'parent' => $languageCapability->getTranslationOriginPointerField()->getName(),
        ];
        if ($languageCapability->hasTranslationSourceField()) {
            $fieldNames['source'] = $languageCapability->getTranslationSourceField()->getName();
        }

        $translationValues = $this->fetchTranslationValues(
            $tableName,
            $fieldNames,
            $this->filterNewItemIds($tableName, $this->filterNumericIds(array_keys($idValues)), $this->allItems),
            $backendUser,
        );

        $dependencies = $this->fetchDependencies(
            $tableName,
            $this->filterNewItemIds($tableName, array_keys($idValues), $this->allItems),
            $backendUser,
            $this->allDataMap,
        );

        foreach ($idValues as $id => $values) {
            $item = $this->allItems[$tableName . ':' . $id] ?? null;
            // build item if it has not been created in a previous iteration
            if ($item === null) {
                $recordValues = $translationValues[$id] ?? [];
                $item = DataMapItem::build($tableName, $id, $values, $recordValues, $fieldNames);

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
    protected function sanitize(array $items): void
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
    protected function enrich(array $items, BackendUserAuthentication $backendUser, ReferenceIndexUpdater $referenceIndexUpdater): void
    {
        foreach (['directChild', 'grandChild'] as $type) {
            foreach ($this->filterItemsByType($type, $items) as $item) {
                foreach ($item->getApplicableScopes() as $scope) {
                    $fromId = $item->getIdForScope($scope);
                    $fieldNames = $this->getFieldNamesForItemScope($item, $scope, !$item->isNew());
                    $this->synchronizeTranslationItem($item, $fieldNames, $fromId, $backendUser, $referenceIndexUpdater);
                }
                $this->populateTranslationItem($item, $backendUser, $referenceIndexUpdater);
                $this->allDataMap = $this->finishTranslationItem($item, $this->allDataMap);
            }
        }
        foreach ($this->filterItemsByType('parent', $items) as $item) {
            $this->populateTranslationItem($item, $backendUser, $referenceIndexUpdater);
        }
    }

    /**
     * Sanitizes the submitted data-map for a particular item and removes
     * fields which are not defined as custom and thus rely on either parent
     * or source values.
     */
    protected function sanitizeTranslationItem(DataMapItem $item): void
    {
        $fieldNames = [];
        foreach ($item->getApplicableScopes() as $scope) {
            $fieldNames = array_merge($fieldNames, $this->getFieldNamesForItemScope($item, $scope, false));
        }
        $fieldNameMap = array_combine($fieldNames, $fieldNames) ?: [];
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
     */
    protected function synchronizeTranslationItem(DataMapItem $item, array $fieldNames, string|int $fromId, BackendUserAuthentication $backendUser, ReferenceIndexUpdater $referenceIndexUpdater): void
    {
        if (empty($fieldNames)) {
            return;
        }
        $fieldNameList = 'uid,' . implode(',', $fieldNames);
        $fromRecord = ['uid' => $fromId];
        if (MathUtility::canBeInterpretedAsInteger($fromId)) {
            $fromRecord = BackendUtility::getRecordWSOL($item->getTableName(), (int)$fromId, $fieldNameList);
        }
        $forRecord = [];
        if (!$item->isNew()) {
            $forRecord = BackendUtility::getRecordWSOL($item->getTableName(), $item->getId(), $fieldNameList);
        }
        if (is_array($fromRecord) && is_array($forRecord)) {
            foreach ($fieldNames as $fieldName) {
                $this->synchronizeFieldValues(
                    $item,
                    $fieldName,
                    $fromRecord,
                    $forRecord,
                    $backendUser,
                    $referenceIndexUpdater,
                );
            }
        }
    }

    /**
     * Populates values downwards, either from a parent language item or
     * a source language item to an accordant dependent translation item.
     */
    protected function populateTranslationItem(DataMapItem $item, BackendUserAuthentication $backendUser, ReferenceIndexUpdater $referenceIndexUpdater): void
    {
        foreach ([DataMapItem::SCOPE_PARENT, DataMapItem::SCOPE_SOURCE] as $scope) {
            foreach ($item->findDependencies($scope) as $dependentItem) {
                // use suggested item, if it was submitted in data-map
                $suggestedDependentItem = $this->allItems[$dependentItem->getTableName() . ':' . $dependentItem->getId()] ?? null;
                if ($suggestedDependentItem !== null) {
                    $dependentItem = $suggestedDependentItem;
                }
                foreach ([$scope, DataMapItem::SCOPE_EXCLUDE] as $dependentScope) {
                    $fieldNames = $this->getFieldNamesForItemScope($dependentItem, $dependentScope, false);
                    $this->synchronizeTranslationItem(
                        $dependentItem,
                        $fieldNames,
                        $item->getId(),
                        $backendUser,
                        $referenceIndexUpdater,
                    );
                }
            }
        }
    }

    /**
     * Finishes a translation item by updating states to be persisted.
     */
    protected function finishTranslationItem(DataMapItem $item, array $allDataMap): array
    {
        if ($item->isParentType() || !State::isApplicable($item->getTableName())) {
            return $allDataMap;
        }
        $allDataMap[$item->getTableName()][$item->getId()]['l10n_state'] = $item->getState()->export();
        return $allDataMap;
    }

    /**
     * Synchronize simple values like text and similar
     */
    protected function synchronizeFieldValues(DataMapItem $item, string $fieldName, array $fromRecord, array $forRecord, BackendUserAuthentication $backendUser, ReferenceIndexUpdater $referenceIndexUpdater): void
    {
        // skip if this field has been processed already, assumed that proper sanitation happened
        if ($this->isSetInDataMap($item->getTableName(), $item->getId(), $fieldName, $this->allDataMap)) {
            return;
        }

        $fromId = $fromRecord['uid'];
        // retrieve value from in-memory data-map
        if ($this->isSetInDataMap($item->getTableName(), $fromId, $fieldName, $this->allDataMap)) {
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
            $this->modifyDataMap($item->getTableName(), $item->getId(), [$fieldName => $fromValue]);
        } elseif (!$this->isReferenceField($item->getTableName(), $fieldName)) {
            // direct relational values
            $this->synchronizeDirectRelations($item, $fieldName, $fromRecord, $backendUser);
        } else {
            // reference values
            $this->synchronizeReferences($item, $fieldName, $fromRecord, $forRecord, $backendUser, $referenceIndexUpdater);
        }
    }

    /**
     * Synchronize select and group field localizations
     */
    protected function synchronizeDirectRelations(DataMapItem $item, string $fieldName, array $fromRecord, BackendUserAuthentication $backendUser): void
    {
        $configuration = $this->getSchema($item->getTableName())?->getField($fieldName)->getConfiguration();
        $fromId = $fromRecord['uid'];
        if ($this->isSetInDataMap($item->getTableName(), $fromId, $fieldName, $this->allDataMap)) {
            $fromValue = $this->allDataMap[$item->getTableName()][$fromId][$fieldName];
        } else {
            $fromValue = $fromRecord[$fieldName];
        }

        // non-MM relations are stored as comma separated values, just use them
        // if values are available in data-map already, just use them as well
        if (empty($configuration['MM']) || $this->isSetInDataMap($item->getTableName(), $fromId, $fieldName, $this->allDataMap)) {
            $this->modifyDataMap($item->getTableName(), $item->getId(), [$fieldName => $fromValue]);
            return;
        }
        // fetch MM relations from storage
        $type = $configuration['type'];
        $manyToManyTable = $configuration['MM'];
        if ($type === 'group' && !empty(trim($configuration['allowed'] ?? ''))) {
            $tableNames = trim($configuration['allowed']);
        } elseif ($type === 'select' || $type === 'category') {
            $tableNames = $configuration['foreign_table'] ?? '';
        } else {
            return;
        }

        $relationHandler = $this->createRelationHandler($backendUser);
        $relationHandler->start(
            '',
            $tableNames,
            $manyToManyTable,
            $fromId,
            $item->getTableName(),
            $configuration
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
     * Handle synchronization of references (inline or file).
     * References are always modelled as 1:n composite relation - which
     * means that direct(!) children cannot exist without their parent.
     * Removing a relative parent results in cascaded removal of all direct(!)
     * children as well.
     *
     * @throws \RuntimeException
     */
    protected function synchronizeReferences(DataMapItem $item, string $fieldName, array $fromRecord, array $forRecord, BackendUserAuthentication $backendUser, ReferenceIndexUpdater $referenceIndexUpdater): void
    {
        $configuration = $this->getSchema($item->getTableName())?->getField($fieldName)->getConfiguration() ?? [];
        $isLocalizationModeExclude = ($configuration['l10n_mode'] ?? null) === 'exclude';
        $foreignTableName = $configuration['foreign_table'];

        $fieldNames = [
            'language' => null,
            'parent' => null,
            'source' => null,
        ];
        $foreignTableSchema = $this->getSchema($foreignTableName);
        $isTranslatable = $foreignTableSchema?->isLanguageAware() ?? false;
        $isLocalized = !empty($item->getLanguage());
        if ($isTranslatable) {
            $languageCapability = $foreignTableSchema->getCapability(TcaSchemaCapability::Language);
            $fieldNames = [
                'language' => $languageCapability->getLanguageField()->getName(),
                'parent' => $languageCapability->getTranslationOriginPointerField()->getName(),
                'source' =>  $languageCapability->getTranslationSourceField()?->getName(),
            ];
        }

        $suggestedAncestorIds = $this->resolveSuggestedInlineRelations($item, $fieldName, $fromRecord, $backendUser, $this->allDataMap);
        $persistedIds = $this->resolvePersistedInlineRelations($item, $fieldName, $forRecord, $backendUser);

        // The dependent ID map points from language parent/source record to
        // localization, thus keys: parents/sources & values: localizations
        $dependentIdMap = $this->fetchDependentIdMap($foreignTableName, $suggestedAncestorIds, (int)$item->getLanguage(), $backendUser);
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
        $createAncestorIds = $this->filterNumericIds($missingAncestorIds);
        // non-persisted elements that should be duplicated in data-map directly
        $populateAncestorIds = array_diff($missingAncestorIds, $createAncestorIds);
        // this desired state map defines the final result of child elements in their parent translation
        $desiredIdMap = array_combine($suggestedAncestorIds, $suggestedAncestorIds) ?: [];
        // update existing translations in the desired state map
        foreach ($dependentIdMap as $ancestorId => $translationId) {
            if (isset($desiredIdMap[$ancestorId])) {
                $desiredIdMap[$ancestorId] = $translationId;
            }
        }
        // no children to be synchronized, but element order could have been changed
        if (empty($removeAncestorIds) && empty($missingAncestorIds)) {
            $this->modifyDataMap($item->getTableName(), $item->getId(), [$fieldName => implode(',', array_values($desiredIdMap))]);
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
            $this->modifyDataMap($item->getTableName(), $item->getId(), [$fieldName => $sanitizedValue]);
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
                $localCommandMap[$foreignTableName][$createAncestorId]['copy'] = [
                    'target' => -$createAncestorId,
                    'ignoreLocalization' => true,
                ];
            } else {
                // otherwise, trigger the localization process
                $localCommandMap[$foreignTableName][$createAncestorId]['localize'] = $item->getLanguage();
            }
        }
        // execute copy, localize and delete actions on persisted child records
        if (!empty($localCommandMap)) {
            $localDataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $localDataHandler->start([], $localCommandMap, $backendUser, $referenceIndexUpdater);
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
                // apply localization references to l10n_mode=exclude children,
                // without keeping their reference to their origin, synchronization is not possible.
                if ($isLocalizationModeExclude && $isTranslatable && $isLocalized) {
                    $adjustCopiedValues = $this->applyLocalizationReferences(
                        $foreignTableName,
                        $createAncestorId,
                        (int)$item->getLanguage(),
                        $fieldNames,
                        []
                    );
                    $this->modifyDataMap($foreignTableName, $newLocalizationId, $adjustCopiedValues);
                }
            }
        }
        // populate new child records in data-map
        foreach ($populateAncestorIds as $populateAncestorId) {
            $newLocalizationId = StringUtility::getUniqueId('NEW');
            $desiredIdMap[$populateAncestorId] = $newLocalizationId;
            $duplicatedValues = $this->allDataMap[$foreignTableName][$populateAncestorId] ?? [];
            // applies localization references to given raw data-map item
            if ($isTranslatable && $isLocalized) {
                $duplicatedValues = $this->applyLocalizationReferences(
                    $foreignTableName,
                    $populateAncestorId,
                    (int)$item->getLanguage(),
                    $fieldNames,
                    $duplicatedValues
                );
            }
            // prefixes language title if applicable for the accordant field name in raw data-map item
            if ($isTranslatable && $isLocalized && !$isLocalizationModeExclude) {
                $duplicatedValues = $this->prefixLanguageTitle(
                    $foreignTableName,
                    $populateAncestorId,
                    (int)$item->getLanguage(),
                    $duplicatedValues
                );
            }
            $this->modifyDataMap($foreignTableName, $newLocalizationId, $duplicatedValues);
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
     * @return int[]|string[]
     */
    protected function resolveSuggestedInlineRelations(DataMapItem $item, string $fieldName, array $fromRecord, BackendUserAuthentication $backendUser, array $allDataMap): array
    {
        $suggestedAncestorIds = [];
        $fromId = $fromRecord['uid'];
        $configuration = $this->getSchema($item->getTableName())?->getField($fieldName)->getConfiguration();
        $foreignTableName = $configuration['foreign_table'] ?? '';
        $manyToManyTable = $configuration['MM'] ?? '';

        // determine suggested elements of either translation parent or source record
        // from data-map, in case the accordant language parent/source record was modified
        if ($this->isSetInDataMap($item->getTableName(), $fromId, $fieldName, $allDataMap)) {
            $suggestedAncestorIds = GeneralUtility::trimExplode(',', $allDataMap[$item->getTableName()][$fromId][$fieldName], true);
        } elseif (MathUtility::canBeInterpretedAsInteger($fromId)) {
            // determine suggested elements of either translation parent or source record from storage
            $relationHandler = $this->createRelationHandler($backendUser);
            $relationHandler->start(
                $fromRecord[$fieldName],
                $foreignTableName,
                $manyToManyTable,
                $fromId,
                $item->getTableName(),
                $configuration
            );
            $suggestedAncestorIds = $this->mapRelationItemId($relationHandler->itemArray);
        }

        return array_filter($suggestedAncestorIds);
    }

    /**
     * Determine persisted inline relations for current data-map-item.
     *
     * @return int[]
     */
    private function resolvePersistedInlineRelations(DataMapItem $item, string $fieldName, array $forRecord, BackendUserAuthentication $backendUser): array
    {
        $persistedIds = [];
        $configuration = $this->getSchema($item->getTableName())?->getField($fieldName)->getConfiguration();
        $foreignTableName = $configuration['foreign_table'] ?? '';
        $manyToManyTable = $configuration['MM'] ?? '';

        // determine persisted elements for the current data-map item
        if (!$item->isNew()) {
            $relationHandler = $this->createRelationHandler($backendUser);
            $relationHandler->start(
                $forRecord[$fieldName] ?? '',
                $foreignTableName,
                $manyToManyTable,
                $item->getId(),
                $item->getTableName(),
                $configuration
            );
            $persistedIds = $this->mapRelationItemId($relationHandler->itemArray);
        }

        return array_filter($persistedIds);
    }

    /**
     * Determines whether a combination of table name, id and field name is
     * set in data-map. This method considers null values as well, that would
     * not be considered by a plain isset() invocation.
     */
    protected function isSetInDataMap(string $tableName, string|int $id, string $fieldName, array $allDataMap): bool
    {
        return
            // directly look-up field name
            isset($allDataMap[$tableName][$id][$fieldName])
            // check existence of field name as key for null values
            || isset($allDataMap[$tableName][$id])
            && is_array($allDataMap[$tableName][$id])
            && array_key_exists($fieldName, $allDataMap[$tableName][$id]);
    }

    /**
     * Applies modifications to the data-map, calling this method is essential
     * to determine new data-map items to be process for synchronizing chained
     * record localizations.
     *
     * @throws \RuntimeException
     */
    protected function modifyDataMap(string $tableName, string|int $id, array $values): void
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

    protected function addNextItem(DataMapItem $item): void
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
     */
    protected function fetchTranslationValues(string $tableName, array $fieldNames, array $ids, BackendUserAuthentication $backendUser): array
    {
        if ($ids === []) {
            return [];
        }

        $connection = $this->connectionPool->getConnectionForTable($tableName);
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll()
            // NOT using WorkspaceRestriction here since it's wrong in this case. See ws OR restriction below.
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $expressions = [];
        $isWorkspaceAware = $this->getSchema($tableName)?->isWorkspaceAware() ?? false;
        if ($isWorkspaceAware) {
            $expressions[] = $queryBuilder->expr()->eq('t3ver_wsid', 0);
            if ($backendUser->workspace > 0) {
                // If this is a workspace record (t3ver_wsid = be-user-workspace), then fetch this one
                // if it is NOT a deleted placeholder (t3ver_state=2), but ok with casual overlay (t3ver_state=0),
                // new ws-record (t3ver_state=1), or moved record (t3ver_state=4).
                // It *might* be possible to simplify this since it may be the case that ws-deleted records are
                // impossible to be incoming here at all? But this query is a safe thing, so we go with it for now.
                $expressions[] = $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('t3ver_wsid', $queryBuilder->createNamedParameter($backendUser->workspace, Connection::PARAM_INT)),
                    $queryBuilder->expr()->in(
                        't3ver_state',
                        $queryBuilder->createNamedParameter(
                            [VersionState::DEFAULT_STATE->value, VersionState::NEW_PLACEHOLDER->value, VersionState::MOVE_POINTER->value],
                            Connection::PARAM_INT_ARRAY
                        )
                    ),
                );
            }
        }

        $translationValues = [];
        $maxBindParameters = PlatformInformation::getMaxBindParameters($connection->getDatabasePlatform());
        // We are using the max bind parameter value as way to retrieve the data in chunks. However, we are not
        // using up the placeholders by providing the id list directly, we keep this calculation to avoid hitting
        // max query size limitation in most cases. If that is hit, it can be increased by adjusting the dbms setting.
        foreach (array_chunk($ids, $maxBindParameters, true) as $chunk) {
            $result = $queryBuilder
                ->select(...array_values($fieldNames))
                ->from($tableName)
                ->where(
                    $queryBuilder->expr()->in(
                        'uid',
                        $queryBuilder->quoteArrayBasedValueListToIntegerList($chunk)
                    ),
                    $queryBuilder->expr()->or(...$expressions)
                )
                ->executeQuery();
            while ($record = $result->fetchAssociative()) {
                $translationValues[$record['uid']] = $record;
            }
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
     * @param int[]|string[] $ids
     * @return array<int|string, array<string, list<DataMapItem>>>
     */
    protected function fetchDependencies(string $tableName, array $ids, BackendUserAuthentication $backendUser, array $allDataMap): array
    {
        if ($ids === []) {
            return [];
        }

        $schema = $this->getSchema($tableName);
        if (!$schema?->isLanguageAware()) {
            return [];
        }
        $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);
        $fieldNames = [
            'uid' => 'uid',
            'l10n_state' => 'l10n_state',
            'language' => $languageCapability->getLanguageField()->getName(),
            'parent' => $languageCapability->getTranslationOriginPointerField()->getName(),
        ];
        if ($languageCapability->hasTranslationSourceField()) {
            $fieldNames['source'] = $languageCapability->getTranslationSourceField()->getName();
        }

        $fieldNamesMap = array_combine($fieldNames, $fieldNames);

        $persistedIds = $this->filterNumericIds($ids);
        $createdIds = array_diff($ids, $persistedIds);
        $dependentElements = $this->fetchDependentElements($tableName, $persistedIds, $fieldNames, $backendUser);

        foreach ($createdIds as $createdId) {
            $data = $allDataMap[$tableName][$createdId] ?? null;
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
     */
    protected function fetchDependentIdMap(string $tableName, array $ids, int $desiredLanguage, BackendUserAuthentication $backendUser): array
    {
        if ($ids === []) {
            return [];
        }

        $ids = $this->filterNumericIds($ids);
        $schema = $this->getSchema($tableName);
        $isTranslatable = $schema?->isLanguageAware() ?? false;
        $originFieldName = $schema?->hasCapability(TcaSchemaCapability::AncestorReferenceField) ? $schema->getCapability(TcaSchemaCapability::AncestorReferenceField)->getFieldName() : null;

        if (!$isTranslatable && $originFieldName === null) {
            // @todo Possibly throw an error, since pointing to original entity is not possible (via origin/parent)
            return [];
        }

        if ($isTranslatable) {
            $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);
            $fieldNames = [
                'uid' => 'uid',
                'l10n_state' => 'l10n_state',
                'language' => $languageCapability->getLanguageField()->getName(),
                'parent' => $languageCapability->getTranslationOriginPointerField()->getName(),
            ];
            if ($languageCapability->hasTranslationSourceField()) {
                $fieldNames['source'] = $languageCapability->getTranslationSourceField()->getName();
            }
        } else {
            $fieldNames = [
                'uid' => 'uid',
                'origin' => $originFieldName,
            ];
        }
        $ancestorIdMap = [];

        $fetchIds = $ids;
        if ($isTranslatable) {
            // expand search criteria via parent and source elements
            $translationValues = $this->fetchTranslationValues($tableName, $fieldNames, $ids, $backendUser);
            $ancestorIdMap = $this->buildElementAncestorIdMap($fieldNames, $translationValues);
            $fetchIds = array_unique(array_merge($ids, array_keys($ancestorIdMap)));
        }

        $dependentElements = $this->fetchDependentElements($tableName, $fetchIds, $fieldNames, $backendUser);

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
            if (in_array($ancestorId, $ids, true)) {
                $dependentIdMap[$ancestorId] = $dependentId;
            } elseif (!empty($ancestorIdMap[$ancestorId])) {
                // resolve from previously expanded search criteria
                $possibleChainedIds = array_intersect(
                    $ids,
                    $ancestorIdMap[$ancestorId]
                );
                if (!empty($possibleChainedIds)) {
                    // use the first found id from `$possibleChainedIds`
                    $ancestorId = reset($possibleChainedIds);
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
     * @throws \InvalidArgumentException
     */
    protected function fetchDependentElements(string $tableName, array $ids, array $fieldNames, BackendUserAuthentication $backendUser): array
    {
        if ($ids === []) {
            return [];
        }
        $connection = $this->connectionPool->getConnectionForTable($tableName);
        $ids = $this->filterNumericIds($ids);
        $maxBindParameters = PlatformInformation::getMaxBindParameters($connection->getDatabasePlatform());
        $dependentElements = [];
        foreach (array_chunk($ids, $maxBindParameters, true) as $idsChunked) {
            $queryBuilder = $connection->createQueryBuilder();
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $backendUser->workspace));

            $zeroParameter = $queryBuilder->createNamedParameter(0, Connection::PARAM_INT);
            $idsParameter = $queryBuilder->quoteArrayBasedValueListToIntegerList($idsChunked);
            // fetch by language dependency
            if (!empty($fieldNames['language']) && !empty($fieldNames['parent'])) {
                $ancestorPredicates = [
                    $queryBuilder->expr()->in(
                        $fieldNames['parent'],
                        $idsParameter
                    ),
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
                    $queryBuilder->expr()->or(...$ancestorPredicates),
                ];
            } elseif (!empty($fieldNames['origin'])) {
                // fetch by origin dependency ("copied from")
                $predicates = [
                    $queryBuilder->expr()->in(
                        $fieldNames['origin'],
                        $idsParameter
                    ),
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
                ->executeQuery();
            while ($record = $statement->fetchAssociative()) {
                $dependentElements[] = $record;
            }
        }
        return $dependentElements;
    }

    /**
     * Return array of data map items that are of given type
     *
     * @param DataMapItem[] $items
     * @return DataMapItem[]
     */
    protected function filterItemsByType(string $type, array $items): array
    {
        return array_filter(
            $items,
            static function (DataMapItem $item) use ($type): bool {
                return $item->getType() === $type;
            }
        );
    }

    /**
     * Return only ids that are integer - so no "NEW..." values
     *
     * @param string[]|int[] $ids
     * @return int[]
     */
    protected function filterNumericIds(array $ids): array
    {
        $ids = array_filter($ids, MathUtility::canBeInterpretedAsInteger(...));
        return array_map(intval(...), $ids);
    }

    /**
     * Return only ids that don't have an item equivalent in $this->allItems.
     *
     * @param int[] $ids
     */
    protected function filterNewItemIds(string $tableName, array $ids, array $allItems): array
    {
        return array_filter(
            $ids,
            static function (string|int $id) use ($tableName, $allItems): bool {
                return !isset($allItems[$tableName . ':' . $id]);
            }
        );
    }

    /**
     * Flatten array
     *
     * @return int[]
     */
    protected function mapRelationItemId(array $relationItems): array
    {
        return array_map(
            static function (array $relationItem): int {
                return (int)$relationItem['id'];
            },
            $relationItems
        );
    }

    /**
     * @param array<string, string> $fieldNames
     * @param array<string, mixed> $element
     * @return int|null either a (non-empty) ancestor uid, or `null` if unresolved
     */
    protected function resolveAncestorId(array $fieldNames, array $element): ?int
    {
        $sourceName = $fieldNames['source'] ?? null;
        if ($sourceName !== null && !empty($element[$sourceName])) {
            // implicit: use source pointer if given (not empty)
            return (int)$element[$sourceName];
        }
        $parentName = $fieldNames['parent'] ?? null;
        if ($parentName !== null && !empty($element[$parentName])) {
            // implicit: use parent pointer if given (not empty)
            return (int)$element[$parentName];
        }
        return null;
    }

    /**
     * Builds a map from ancestor ids to accordant localization dependents.
     *
     * The result of e.g. [5 => [6, 7]] refers to ids 6 and 7 being dependents
     * (either used in parent or source field) of the ancestor with id 5.
     */
    protected function buildElementAncestorIdMap(array $fieldNames, array $elements): array
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
     * Applies localization references to given raw data-map item.
     */
    protected function applyLocalizationReferences(string $tableName, string|int $fromId, int $language, array $fieldNames, array $data): array
    {
        // just return if localization cannot be applied
        if (empty($language)) {
            return $data;
        }
        // apply `languageField`, e.g. `sys_language_uid`
        $data[$fieldNames['language']] = $language;
        // apply `transOrigPointerField`, e.g. `l10n_parent`
        if (empty($data[$fieldNames['parent']])) {
            // @todo Only $id used in TCA type 'select' is resolved in DataHandler's remapStack
            $data[$fieldNames['parent']] = $fromId;
        }
        // apply `translationSource`, e.g. `l10n_source`
        if (!empty($fieldNames['source'])) {
            // @todo Not sure, whether $id is resolved in DataHandler's remapStack
            $data[$fieldNames['source']] = $fromId;
        }
        // unset field names that are expected to be handled in this processor
        foreach ($this->getFieldNamesToBeHandled($tableName) as $fieldName) {
            unset($data[$fieldName]);
        }
        return $data;
    }

    /**
     * Prefixes language title if applicable for the accordant field name in raw data-map item.
     */
    protected function prefixLanguageTitle(string $tableName, string|int $fromId, int $language, array $data): array
    {
        $prefixFieldNames = array_intersect(array_keys($data), $this->getPrefixLanguageTitleFieldNames($tableName));
        if (empty($prefixFieldNames)) {
            return $data;
        }

        $pageId = (int)BackendUtility::getRealPageId($tableName, (int)$fromId, $data['pid'] ?? null);
        $tsConfig = BackendUtility::getPagesTSconfig($pageId)['TCEMAIN.'] ?? [];
        if (($translateToMessage = (string)($tsConfig['translateToMessage'] ?? '')) === '') {
            // Return in case translateToMessage had been unset
            return $data;
        }

        $tableRelatedConfig = $tsConfig['default.'] ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($tableRelatedConfig, $tsConfig['table.'][$tableName . '.'] ?? []);
        if ($tableRelatedConfig['disablePrependAtCopy'] ?? false) {
            // Return in case "disablePrependAtCopy" is set for this table
            return $data;
        }

        try {
            $site = $this->siteFinder->getSiteByPageId($pageId);
            $siteLanguage = $site->getLanguageById($language);
            $languageTitle = $siteLanguage->getTitle();
        } catch (SiteNotFoundException|\InvalidArgumentException $e) {
            $languageTitle = '';
        }

        $languageService = $this->getLanguageService();
        if ($languageService !== null) {
            $translateToMessage = $languageService->sL($translateToMessage);
        }
        $translateToMessage = sprintf($translateToMessage, $languageTitle);

        if ($translateToMessage === '') {
            // Return for edge cases when the translateToMessage got empty, e.g. because the referenced LLL
            // label is empty or only contained a placeholder which is replaced by an empty language title.
            return $data;
        }

        $translateToMessage = '[' . $translateToMessage . '] ';
        $schema = $this->getSchema($tableName);
        // @todo The hook in DataHandler is not applied here
        foreach ($prefixFieldNames as $prefixFieldName) {
            if (!isset($data[$prefixFieldName])) {
                continue;
            }
            $fieldContent = $data[$prefixFieldName];
            if ($schema->getField($prefixFieldName)->isType(TableColumnType::TEXT) && str_starts_with($fieldContent, '<')) {
                // If the field is a text field, we need to prepend the translation message to the content
                // that means, it should be after the first opening HTML tag, if one exists.
                // @todo: Ideally we can use TcaSchema and Subschema in the future, to resolve this issue properly
                $data[$prefixFieldName] = preg_replace('/(<[^>]+>)/', '$1' . $translateToMessage, $fieldContent, 1);
            } else {
                $data[$prefixFieldName] = $translateToMessage . $fieldContent;
            }
        }

        return $data;
    }

    /**
     * Field names we have to deal with
     *
     * @return string[]
     */
    protected function getFieldNamesForItemScope(DataMapItem $item, string $scope, bool $modified): array
    {
        if ($scope === DataMapItem::SCOPE_PARENT || $scope === DataMapItem::SCOPE_SOURCE) {
            if (!State::isApplicable($item->getTableName())) {
                return [];
            }
            return $item->getState()->filterFieldNames($scope, $modified);
        }
        if ($scope === DataMapItem::SCOPE_EXCLUDE) {
            return $this->getLocalizationModeExcludeFieldNames($item->getTableName());
        }
        return [];
    }

    /**
     * Field names of TCA table with columns having l10n_mode=exclude
     *
     * @return string[]
     */
    protected function getLocalizationModeExcludeFieldNames(string $tableName): array
    {
        $localizationExcludeFieldNames = [];
        $schema = $this->getSchema($tableName);
        foreach ($schema?->getFields() ?? [] as $fieldName => $configuration) {
            if (($configuration->getConfiguration()['l10n_mode'] ?? null) === 'exclude' && $configuration->getType() !== 'none') {
                $localizationExcludeFieldNames[] = $fieldName;
            }
        }
        return $localizationExcludeFieldNames;
    }

    /**
     * Gets a list of field names which have to be handled. Basically this
     * includes fields using allowLanguageSynchronization or l10n_mode=exclude.
     *
     * @return string[]
     */
    protected function getFieldNamesToBeHandled(string $tableName): array
    {
        return array_merge(State::getFieldNames($tableName), $this->getLocalizationModeExcludeFieldNames($tableName));
    }

    /**
     * Field names of TCA table with columns having l10n_mode=prefixLangTitle
     */
    protected function getPrefixLanguageTitleFieldNames(string $tableName): array
    {
        $prefixLanguageTitleFieldNames = [];
        $schema = $this->getSchema($tableName);
        foreach ($schema?->getFields() ?? [] as $fieldName => $configuration) {
            $type = $configuration->getType();
            if (
                ($configuration->getConfiguration()['l10n_mode'] ?? null) === 'prefixLangTitle'
                && ($type === 'input' || $type === 'text' || $type === 'email')
            ) {
                $prefixLanguageTitleFieldNames[] = $fieldName;
            }
        }
        return $prefixLanguageTitleFieldNames;
    }

    /**
     * True if we're dealing with a field that has foreign db relations (type=group or select with foreign_table).
     */
    protected function isRelationField(string $tableName, string $fieldName): bool
    {
        if (!$this->getSchema($tableName)?->hasField($fieldName)) {
            return false;
        }
        $field = $this->getSchema($tableName)->getField($fieldName);
        $fieldType = $field->getType();
        $configuration = $field->getConfiguration();
        return ($fieldType === 'group' && !empty($configuration['allowed']))
            || (
                ($fieldType === 'select' || $fieldType === 'category')
                && $this->getSchema($configuration['foreign_table'] ?? '') !== null
            )
            || $this->isReferenceField($tableName, $fieldName)
        ;
    }

    /**
     * True if we're dealing with a reference field (either "inline" or "file") with foreign_table set.
     */
    protected function isReferenceField(string $tableName, string $fieldName): bool
    {
        if (!$this->getSchema($tableName)?->hasField($fieldName)) {
            return false;
        }
        $field = $this->getSchema($tableName)->getField($fieldName);
        $fieldType = $field->getType();
        $configuration = $field->getConfiguration();
        return
            ($fieldType === 'inline' || $fieldType === 'file')
            && $this->getSchema($configuration['foreign_table'] ?? '') !== null
        ;
    }

    /**
     * Determines whether the table can be localized and either has fields
     * with allowLanguageSynchronization enabled or l10n_mode set to exclude.
     */
    protected function isApplicable(string $tableName): bool
    {
        return
            State::isApplicable($tableName)
            || $this->getSchema($tableName)?->isLanguageAware()
                && count($this->getLocalizationModeExcludeFieldNames($tableName)) > 0
        ;
    }

    protected function createRelationHandler(BackendUserAuthentication $backendUser): RelationHandler
    {
        $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
        $relationHandler->setWorkspaceId($backendUser->workspace);
        return $relationHandler;
    }

    protected function getSchema(string $table): ?TcaSchema
    {
        if ($this->tcaSchemaFactory->has($table)) {
            return $this->tcaSchemaFactory->get($table);
        }
        return null;
    }

    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }
}
