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

namespace TYPO3\CMS\Backend\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\Exception\DatabaseRecordException;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\OnTheFly;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Resolve and prepare inline data.
 */
class TcaInline extends AbstractDatabaseRecordProvider implements FormDataProviderInterface
{
    public function __construct(
        private readonly FlashMessageService $flashMessageService,
    ) {}

    /**
     * Resolve inline fields
     *
     * @return array
     */
    public function addData(array $result)
    {
        $result = $this->addInlineFirstPid($result);

        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (!$this->isInlineField($fieldConfig)) {
                continue;
            }
            $result['processedTca']['columns'][$fieldName]['children'] = [];
            if (!$this->isUserAllowedToModify($fieldConfig)) {
                continue;
            }
            if ($result['inlineResolveExistingChildren']) {
                $result = $this->resolveRelatedRecords($result, $fieldName);
                $result = $this->addForeignSelectorAndUniquePossibleRecords($result, $fieldName);
            }
        }

        return $result;
    }

    /**
     * Is column of type "inline"
     *
     * @param array $fieldConfig
     * @return bool
     */
    protected function isInlineField($fieldConfig)
    {
        return !empty($fieldConfig['config']['type']) && $fieldConfig['config']['type'] === 'inline';
    }

    /**
     * Is user allowed to modify child elements
     *
     * @param array $fieldConfig
     * @return bool
     */
    protected function isUserAllowedToModify($fieldConfig)
    {
        return $this->getBackendUser()->check('tables_modify', $fieldConfig['config']['foreign_table']);
    }

    /**
     * The "entry" pid for inline records. Nested inline records can potentially hang around on different
     * pid's, but the entry pid is needed for AJAX calls, so that they would know where the action takes place on the page structure.
     *
     * @param array $result Incoming result
     * @return array Modified result
     * @todo: Find out when and if this is different from 'effectivePid'
     */
    protected function addInlineFirstPid(array $result)
    {
        if ($result['inlineFirstPid'] === null) {
            $table = $result['tableName'];
            $row = $result['databaseRow'];
            // If the parent is a page, use the uid(!) of the (new?) page as pid for the child records:
            if ($table === 'pages') {
                $liveVersionId = BackendUtility::getLiveVersionIdOfRecord('pages', $row['uid']);
                $pid = $liveVersionId ?? $row['uid'];
            } elseif (($row['pid'] ?? 0) < 0) {
                $prevRec = BackendUtility::getRecord($table, (int)abs($row['pid']));
                $pid = $prevRec['pid'];
            } else {
                $pid = $row['pid'] ?? 0;
            }
            if (MathUtility::canBeInterpretedAsInteger($pid)) {
                $pageRecord = BackendUtility::getRecord('pages', (int)$pid);
                if (($pageRecord[$GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'] ?? null] ?? 0) > 0) {
                    $pid = (int)$pageRecord[$GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField']];
                }
            } elseif (!str_starts_with($pid, 'NEW')) {
                throw new \RuntimeException(
                    'inlineFirstPid should either be an integer or a "NEW..." string',
                    1521220142
                );
            }
            $result['inlineFirstPid'] = $pid;
        }
        return $result;
    }

    /**
     * Substitute the value in databaseRow of this inline field with an array
     * that contains the databaseRows of currently connected records and some meta information.
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @return array Modified item array
     */
    protected function resolveRelatedRecordsOverlays(array $result, $fieldName)
    {
        $childTableName = $result['processedTca']['columns'][$fieldName]['config']['foreign_table'];

        $connectedUidsOfLocalizedOverlay = [];
        if ($result['command'] === 'edit') {
            $connectedUidsOfLocalizedOverlay = $this->resolveConnectedRecordUids(
                $result['processedTca']['columns'][$fieldName]['config'],
                $result['tableName'],
                $result['databaseRow'],
                $result['databaseRow'][$fieldName]
            );
        }
        $result['databaseRow'][$fieldName] = implode(',', $connectedUidsOfLocalizedOverlay);
        $connectedUidsOfLocalizedOverlay = $this->getSubstitutedWorkspacedUids($connectedUidsOfLocalizedOverlay, $childTableName);
        if ($result['inlineCompileExistingChildren']) {
            $tableNameWithDefaultRecords = $result['tableName'];
            $connectedUidsOfDefaultLanguageRecord = $this->resolveConnectedRecordUids(
                $result['processedTca']['columns'][$fieldName]['config'],
                $tableNameWithDefaultRecords,
                $result['defaultLanguageRow'],
                (string)($result['defaultLanguageRow'][$fieldName] ?? '')
            );
            $connectedUidsOfDefaultLanguageRecord = $this->getSubstitutedWorkspacedUids($connectedUidsOfDefaultLanguageRecord, $childTableName);

            $showPossibleLocalizationRecords = $result['processedTca']['columns'][$fieldName]['config']['appearance']['showPossibleLocalizationRecords'] ?? false;

            // Find which records are localized, which records are not localized and which are
            // localized but miss default language record
            $fieldNameWithDefaultLanguageUid = $GLOBALS['TCA'][$childTableName]['ctrl']['transOrigPointerField'] ?? '';
            foreach ($connectedUidsOfLocalizedOverlay as $localizedUid) {
                try {
                    $localizedRecord = $this->getRecordFromDatabase($childTableName, $localizedUid);
                } catch (DatabaseRecordException $e) {
                    // The child could not be compiled, probably it was deleted and a dangling mm record exists
                    $this->logger->warning(
                        $e->getMessage(),
                        [
                            'table' => $childTableName,
                            'uid' => $localizedUid,
                            'exception' => $e,
                        ]
                    );
                    continue;
                }
                // Compile localized record
                $compiledChild = $this->compileChild($result, $fieldName, $localizedUid);
                $result['processedTca']['columns'][$fieldName]['children'][] = $compiledChild;
                // If that relation is configured to "showPossibleLocalizationRecords", this localized record
                // needs to be removed from the list of records that are pending to be localized.
                if ($fieldNameWithDefaultLanguageUid && $showPossibleLocalizationRecords) {
                    $uidOfDefaultLanguageRecord = (int)$localizedRecord[$fieldNameWithDefaultLanguageUid];
                    if (in_array($uidOfDefaultLanguageRecord, $connectedUidsOfDefaultLanguageRecord, true)) {
                        // This localized child has a default language record. Remove this record from list of default language records
                        $connectedUidsOfDefaultLanguageRecord = array_diff($connectedUidsOfDefaultLanguageRecord, [$uidOfDefaultLanguageRecord]);
                    }
                    $uidOfDefaultLanguageRecordWorkspaceVersionArray = $this->getSubstitutedWorkspacedUids([$uidOfDefaultLanguageRecord], $childTableName);
                    if (!empty($uidOfDefaultLanguageRecordWorkspaceVersionArray)
                        && in_array($uidOfDefaultLanguageRecordWorkspaceVersionArray[0], $connectedUidsOfDefaultLanguageRecord, true)
                    ) {
                        // In some situations 'l10n_parent' of a localized workspace record points to the live version
                        // of the default language record, and not to the workspace version, even though it exists.
                        // Filter those as well, since the interface would otherwise show the item as "can be localized/synchronized".
                        $connectedUidsOfDefaultLanguageRecord = array_diff($connectedUidsOfDefaultLanguageRecord, [$uidOfDefaultLanguageRecordWorkspaceVersionArray[0]]);
                    }
                }
            }
            if ($showPossibleLocalizationRecords) {
                foreach ($connectedUidsOfDefaultLanguageRecord as $defaultLanguageUid) {
                    // If there are still uids in $connectedUidsOfDefaultLanguageRecord, these are records that
                    // exist in default language, but are not localized yet. Compile and mark those
                    try {
                        $compiledChild = $this->compileChild($result, $fieldName, $defaultLanguageUid, true);
                    } catch (DatabaseRecordException $e) {
                        // The child could not be compiled, probably it was deleted and a dangling mm record exists
                        $this->logger->warning(
                            $e->getMessage(),
                            [
                                'table' => $childTableName,
                                'uid' => $defaultLanguageUid,
                                'exception' => $e,
                            ]
                        );
                        continue;
                    }
                    $result['processedTca']['columns'][$fieldName]['children'][] = $compiledChild;
                }
            }
        }

        return $result;
    }

    /**
     * Substitute the value in databaseRow of this inline field with an array
     * that contains the databaseRows of currently connected records and some meta information.
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @return array Modified item array
     */
    protected function resolveRelatedRecords(array $result, $fieldName)
    {
        if ($result['defaultLanguageRow'] !== null) {
            return $this->resolveRelatedRecordsOverlays($result, $fieldName);
        }

        $childTableName = $result['processedTca']['columns'][$fieldName]['config']['foreign_table'];
        $connectedUidsOfDefaultLanguageRecord = $this->resolveConnectedRecordUids(
            $result['processedTca']['columns'][$fieldName]['config'],
            $result['tableName'],
            $result['databaseRow'],
            $result['databaseRow'][$fieldName]
        );
        $result['databaseRow'][$fieldName] = implode(',', $connectedUidsOfDefaultLanguageRecord);

        $connectedUidsOfDefaultLanguageRecord = $this->getSubstitutedWorkspacedUids($connectedUidsOfDefaultLanguageRecord, $childTableName);

        if ($result['inlineCompileExistingChildren']) {
            foreach ($connectedUidsOfDefaultLanguageRecord as $uid) {
                try {
                    $compiledChild = $this->compileChild($result, $fieldName, $uid);
                    $result['processedTca']['columns'][$fieldName]['children'][] = $compiledChild;
                } catch (DatabaseRecordException $e) {
                    // Nothing to do here, missing child is just not being rendered.
                }
            }
        }
        return $result;
    }

    /**
     * If there is a foreign_selector or foreign_unique configuration, fetch
     * the list of possible records that can be connected and attach the to the
     * inline configuration.
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @return array Modified item array
     */
    protected function addForeignSelectorAndUniquePossibleRecords(array $result, $fieldName)
    {
        if (!is_array($result['processedTca']['columns'][$fieldName]['config']['selectorOrUniqueConfiguration'] ?? null)) {
            return $result;
        }

        $selectorOrUniqueConfiguration = $result['processedTca']['columns'][$fieldName]['config']['selectorOrUniqueConfiguration'];
        $foreignFieldName = $selectorOrUniqueConfiguration['fieldName'];
        $selectorOrUniquePossibleRecords = [];

        if ($selectorOrUniqueConfiguration['config']['type'] === 'select') {
            // Compile child table data for this field only
            $selectDataInput = [
                'request' => $result['request'],
                'tableName' => $result['processedTca']['columns'][$fieldName]['config']['foreign_table'],
                'command' => 'new',
                // Since there is no existing record that may have a type, it does not make sense to
                // do extra handling of pageTsConfig merged here. Just provide "parent" pageTS as is
                'pageTsConfig' => $result['pageTsConfig'],
                'userTsConfig' => $result['userTsConfig'],
                'databaseRow' => $result['databaseRow'],
                'processedTca' => [
                    'ctrl' => [],
                    'columns' => [
                        $foreignFieldName => [
                            'config' => $selectorOrUniqueConfiguration['config'],
                        ],
                    ],
                ],
                'inlineExpandCollapseStateArray' => $result['inlineExpandCollapseStateArray'],
                'site' => $result['site'],
            ];
            $formDataGroup = GeneralUtility::makeInstance(OnTheFly::class);
            $formDataGroup->setProviderList([TcaSelectItems::class]);
            $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class);
            $compilerResult = $formDataCompiler->compile($selectDataInput, $formDataGroup);
            $selectorOrUniquePossibleRecords = $compilerResult['processedTca']['columns'][$foreignFieldName]['config']['items'];
        }

        $result['processedTca']['columns'][$fieldName]['config']['selectorOrUniquePossibleRecords'] = $selectorOrUniquePossibleRecords;

        return $result;
    }

    /**
     * Compile a full child record
     *
     * @param array $result Result array of parent
     * @param string $parentFieldName Name of parent field
     * @param int $childUid Uid of child to compile
     * @return array Full result array
     */
    protected function compileChild(array $result, $parentFieldName, $childUid, $isInlineDefaultLanguageRecordInLocalizedParentContext = false)
    {
        $parentConfig = $result['processedTca']['columns'][$parentFieldName]['config'];
        $childTableName = $parentConfig['foreign_table'];

        $inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
        $inlineStackProcessor->initializeByGivenStructure($result['inlineStructure']);
        $inlineTopMostParent = $inlineStackProcessor->getStructureLevel(0) ?: [];

        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class);
        $formDataCompilerInput = [
            'request' => $result['request'],
            'command' => 'edit',
            'tableName' => $childTableName,
            'vanillaUid' => (int)$childUid,
            // Give incoming returnUrl down to children so they generate a returnUrl back to
            // the originally opening record, also see "originalReturnUrl" in inline container
            // and FormInlineAjaxController
            'returnUrl' => $result['returnUrl'],
            'isInlineChild' => true,
            'isInlineDefaultLanguageRecordInLocalizedParentContext' => $isInlineDefaultLanguageRecordInLocalizedParentContext,
            'inlineStructure' => $result['inlineStructure'],
            'inlineExpandCollapseStateArray' => $result['inlineExpandCollapseStateArray'],
            'inlineFirstPid' => $result['inlineFirstPid'],
            'inlineParentConfig' => $parentConfig,

            // values of the current parent element
            // it is always a string either an id or new...
            'inlineParentUid' => $result['databaseRow']['uid'],
            'inlineParentTableName' => $result['tableName'],
            'inlineParentFieldName' => $parentFieldName,

            // values of the top most parent element set on first level and not overridden on following levels
            'inlineTopMostParentUid' => $result['inlineTopMostParentUid'] ?: $inlineTopMostParent['uid'] ?? '',
            'inlineTopMostParentTableName' => $result['inlineTopMostParentTableName'] ?: $inlineTopMostParent['table'] ?? '',
            'inlineTopMostParentFieldName' => $result['inlineTopMostParentFieldName'] ?: $inlineTopMostParent['field'] ?? '',
        ];

        // For foreign_selector with useCombination $mainChild is the mm record
        // and $combinationChild is the child-child. For 1:n "normal" relations,
        // $mainChild is just the normal child record and $combinationChild is empty.
        $mainChild = $formDataCompiler->compile($formDataCompilerInput, GeneralUtility::makeInstance(TcaDatabaseRecord::class));
        if (($parentConfig['foreign_selector'] ?? false) && ($parentConfig['appearance']['useCombination'] ?? false)) {
            try {
                $mainChild['combinationChild'] = $this->compileChildChild($mainChild, $parentConfig);
            } catch (DatabaseRecordException $e) {
                // The child could not be compiled, probably it was deleted and a dangling mm record
                // exists. This is a data inconsistency, we catch this exception and create a flash message
                $message = vsprintf(
                    $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:formEngine.databaseRecordErrorInlineChildChild'),
                    [$e->getTableName(), $e->getUid(), $childTableName, (int)$childUid]
                );
                $flashMessage = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    $message,
                    '',
                    ContextualFeedbackSeverity::ERROR
                );
                $this->flashMessageService->getMessageQueueByIdentifier()->enqueue($flashMessage);
            }
        }
        return $mainChild;
    }

    /**
     * With useCombination set, not only content of the intermediate table, but also
     * the connected child should be rendered in one go. Prepare this here.
     *
     * @param array $child Full data array of "mm" record
     * @param array $parentConfig TCA configuration of "parent"
     * @return array Full data array of child
     */
    protected function compileChildChild(array $child, array $parentConfig)
    {
        // foreign_selector on intermediate is probably type=select, so data provider of this table resolved that to the uid already
        $childChildUid = $child['databaseRow'][$parentConfig['foreign_selector']][0];
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class);

        $formDataCompilerInput = [
            'request' => $child['request'],
            'command' => 'edit',
            'tableName' => $this->getChildChildTableName($parentConfig['foreign_selector'] ?? '', $child),
            'vanillaUid' => (int)$childChildUid,
            'isInlineChild' => true,
            'isInlineChildExpanded' => $child['isInlineChildExpanded'],
            // @todo: this is the wrong inline structure, isn't it? Shouldn't it contain the part from child child, too?
            'inlineStructure' => $child['inlineStructure'],
            'inlineFirstPid' => $child['inlineFirstPid'],
            // values of the top most parent element set on first level and not overridden on following levels
            'inlineTopMostParentUid' => $child['inlineTopMostParentUid'],
            'inlineTopMostParentTableName' => $child['inlineTopMostParentTableName'],
            'inlineTopMostParentFieldName' => $child['inlineTopMostParentFieldName'],
        ];
        $childChild = $formDataCompiler->compile($formDataCompilerInput, GeneralUtility::makeInstance(TcaDatabaseRecord::class));
        return $childChild;
    }

    /**
     * Substitute given list of uids in child table with workspace uid if needed
     *
     * @param array $connectedUids List of connected uids
     * @param string $childTableName Name of child table
     * @return int[] List of substituted uids
     */
    protected function getSubstitutedWorkspacedUids(array $connectedUids, string $childTableName): array
    {
        $backendUser = $this->getBackendUser();
        $newConnectedUids = [];
        foreach ($connectedUids as $uid) {
            // Fetch workspace version of a record (if any):
            if ($backendUser->workspace !== 0 && BackendUtility::isTableWorkspaceEnabled($childTableName)) {
                $workspaceVersion = BackendUtility::getWorkspaceVersionOfRecord($backendUser->workspace, $childTableName, $uid, 'uid,t3ver_state');
                if (!empty($workspaceVersion)) {
                    $versionState = VersionState::tryFrom($workspaceVersion['t3ver_state'] ?? 0);
                    if ($versionState === VersionState::DELETE_PLACEHOLDER) {
                        continue;
                    }
                    $uid = $workspaceVersion['uid'];
                }
            }
            $newConnectedUids[] = (int)$uid;
        }
        return $newConnectedUids;
    }

    /**
     * Use RelationHandler to resolve connected uids.
     *
     * @param array $parentConfig TCA config section of parent
     * @param string $parentTableName Name of parent table
     * @param array $parentRecord Full parent record
     * @param string $parentFieldValue Database value of parent record of this inline field
     * @return array Array with connected uids
     * @todo: Cover with unit tests
     */
    protected function resolveConnectedRecordUids(array $parentConfig, string $parentTableName, array $parentRecord, string $parentFieldValue): array
    {
        $directlyConnectedIds = GeneralUtility::trimExplode(',', $parentFieldValue);
        $parentUid = (int)$parentRecord['uid'];
        // Relations to non-MM tables point to the LIVE version, so we need to ensure
        // we use the live version that is sent to RelationHandler
        if (empty($parentConfig['MM']) && (int)($parentRecord['t3ver_oid'] ?? 0) > 0) {
            $parentUid = (int)$parentRecord['t3ver_oid'];
        }
        $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
        $relationHandler->start($parentFieldValue, $parentConfig['foreign_table'] ?? '', $parentConfig['MM'] ?? '', $parentUid, $parentTableName, $parentConfig);
        $foreignRecordUids = $relationHandler->getValueArray();
        $resolvedForeignRecordUids = [];
        foreach ($foreignRecordUids as $aForeignRecordUid) {
            if ($parentConfig['MM'] ?? $parentConfig['foreign_field'] ?? false) {
                $resolvedForeignRecordUids[] = (int)$aForeignRecordUid;
            } else {
                foreach ($directlyConnectedIds as $id) {
                    if ((int)$aForeignRecordUid === (int)$id) {
                        $resolvedForeignRecordUids[] = (int)$aForeignRecordUid;
                    }
                }
            }
        }
        return $resolvedForeignRecordUids;
    }

    /**
     * The child-child table name is set in the child TCA "the selector field" and is depending on
     * the TCA type (select or group) either the "foreign_table" or the (first) "allowed" table.
     */
    protected function getChildChildTableName(string $foreignSelector, array $childConfiguration): string
    {
        $config = $childConfiguration['processedTca']['columns'][$foreignSelector]['config'] ?? [];
        $type = $config['type'] ?? '';

        return match ($type) {
            'select' => $config['foreign_table'] ?? '',
            'group' => GeneralUtility::trimExplode(',', $config['allowed'] ?? '', true)[0] ?? '',
            default => '',
        };
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
