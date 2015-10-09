<?php
namespace TYPO3\CMS\Backend\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Resolve and prepare inline data.
 */
class TcaInline extends AbstractDatabaseRecordProvider implements FormDataProviderInterface
{
    /**
     * Resolve inline fields
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        $result = $this->addInlineFirstPid($result);

        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (empty($fieldConfig['config']['type']) || $fieldConfig['config']['type'] !== 'inline') {
                continue;
            }
            $result['processedTca']['columns'][$fieldName]['children'] = [];
            if ($result['inlineResolveExistingChildren']) {
                $result = $this->resolveRelatedRecords($result, $fieldName);
            }
        }

        return $result;
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
        if (is_null($result['inlineFirstPid'])) {
            $table = $result['tableName'];
            $row = $result['databaseRow'];
            // If the parent is a page, use the uid(!) of the (new?) page as pid for the child records:
            if ($table == 'pages') {
                $liveVersionId = BackendUtility::getLiveVersionIdOfRecord('pages', $row['uid']);
                $pid = is_null($liveVersionId) ? $row['uid'] : $liveVersionId;
            } elseif ($row['pid'] < 0) {
                $prevRec = BackendUtility::getRecord($table, abs($row['pid']));
                $pid = $prevRec['pid'];
            } else {
                $pid = $row['pid'];
            }
            $result['inlineFirstPid'] = (int)$pid;
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
        $childTableName = $result['processedTca']['columns'][$fieldName]['config']['foreign_table'];

        // localizationMode is either "none", "keep" or "select":
        // * none:   Handled parent row is not a localized record, or if it is a localized row, this is ignored.
        //           Default language records and overlays have distinct children that are not connected to each other.
        // * keep:   Handled parent row is a localized record, but child table is either not localizable, or
        //           "keep" is explicitly set. A localized parent and its default language row share the same
        //           children records. Editing a child from a localized record will change this record for the
        //           default language row, too.
        // * select: Handled parent row is a localized record, child table is localizable. Children records are
        //           localized overlays of a default language record. Three scenarios can happen:
        //           ** Localized child overlay and its default language row exist - show localized overlay record
        //           ** Default child language row exists but child overlay doesn't - show a "synchronize this record" button
        //           ** Localized child overlay exists but default language row does not - this dangling child is a data inconsistency

        // Mode was prepared by TcaInlineConfiguration provider
        $mode = $result['processedTca']['columns'][$fieldName]['config']['behaviour']['localizationMode'];
        if ($mode === 'none') {
            $connectedUids = [];
            // A new record that has distinct children can not have children yet, fetch connected uids for existing only
            if ($result['command'] === 'edit') {
                $connectedUids = $this->resolveConnectedRecordUids(
                    $result['processedTca']['columns'][$fieldName]['config'],
                    $result['tableName'],
                    $result['databaseRow']['uid'],
                    $result['databaseRow'][$fieldName]
                );
            }
            $result['databaseRow'][$fieldName] = implode(',', $connectedUids);
            $connectedUids = $this->getWorkspacedUids($connectedUids, $childTableName);
            // @todo: If inlineCompileExistingChildren must be kept, it might be better to change the data
            // @todo: format of databaseRow for this field and separate the child compilation to an own provider?
            if ($result['inlineCompileExistingChildren']) {
                foreach ($connectedUids as $childUid) {
                    $result['processedTca']['columns'][$fieldName]['children'][] = $this->compileChild($result, $fieldName, $childUid);
                }
            }
        } elseif ($mode === 'keep') {
            // Fetch connected uids of default language record
            $connectedUids = $this->resolveConnectedRecordUids(
                $result['processedTca']['columns'][$fieldName]['config'],
                $result['tableName'],
                $result['defaultLanguageRow']['uid'],
                $result['defaultLanguageRow'][$fieldName]
            );
            $result['databaseRow'][$fieldName] = implode(',', $connectedUids);
            $connectedUids = $this->getWorkspacedUids($connectedUids, $childTableName);
            if ($result['inlineCompileExistingChildren']) {
                foreach ($connectedUids as $childUid) {
                    $result['processedTca']['columns'][$fieldName]['children'][] = $this->compileChild($result, $fieldName, $childUid);
                }
            }
        } else {
            $connectedUidsOfLocalizedOverlay = [];
            if ($result['command'] === 'edit') {
                $connectedUidsOfLocalizedOverlay = $this->resolveConnectedRecordUids(
                    $result['processedTca']['columns'][$fieldName]['config'],
                    $result['tableName'],
                    $result['databaseRow']['uid'],
                    $result['databaseRow'][$fieldName]
                );
            }
            $result['databaseRow'][$fieldName] = implode(',', $connectedUidsOfLocalizedOverlay);
            if ($result['inlineCompileExistingChildren']) {
                $connectedUidsOfDefaultLanguageRecord = $this->resolveConnectedRecordUids(
                    $result['processedTca']['columns'][$fieldName]['config'],
                    $result['tableName'],
                    $result['defaultLanguageRow']['uid'],
                    $result['defaultLanguageRow'][$fieldName]
                );
                $showPossible = $result['processedTca']['columns'][$fieldName]['config']['appearance']['showPossibleLocalizationRecords'];
                $showRemoved = $result['processedTca']['columns'][$fieldName]['config']['appearance']['showRemovedLocalizationRecords'];
                if ($showPossible || $showRemoved) {
                    // Find which records are localized, which records are not localized and which are
                    // localized but miss default language record
                    $fieldNameWithDefaultLanguageUid = $GLOBALS['TCA'][$childTableName]['ctrl']['transOrigPointerField'];
                    foreach ($connectedUidsOfLocalizedOverlay as $localizedUid) {
                        $localizedRecord = $this->getRecordFromDatabase($childTableName, $localizedUid);
                        $uidOfDefaultLanguageRecord = $localizedRecord[$fieldNameWithDefaultLanguageUid];
                        if (in_array($uidOfDefaultLanguageRecord, $connectedUidsOfDefaultLanguageRecord, true)) {
                            // This localized child has a default language record. Remove this record from list of default language records
                            $connectedUidsOfDefaultLanguageRecord = array_diff($connectedUidsOfDefaultLanguageRecord, array($uidOfDefaultLanguageRecord));
                            // Compile localized record
                            $result['processedTca']['columns'][$fieldName]['children'][] = $this->compileChild($result, $fieldName, $localizedUid);
                        } elseif ($showRemoved) {
                            // This localized child has no default language record. Compile child and mark it as such
                            $compiledChild = $this->compileChild($result, $fieldName, $localizedUid);
                            $compiledChild['inlineIsDanglingLocalization'] = true;
                            $result['processedTca']['columns'][$fieldName]['children'][] = $compiledChild;
                        } // Discard child if default language is missing and no showRemoved is set
                    }
                    if ($showPossible) {
                        foreach ($connectedUidsOfDefaultLanguageRecord as $defaultLanguageUid) {
                            // If there are still uids in $connectedUidsOfDefaultLanguageRecord, these are records that
                            // exist in default language, but are not localized yet. Compile and mark those
                            $compiledChild = $this->compileChild($result, $fieldName, $defaultLanguageUid);
                            $compiledChild['inlineIsDefaultLanguage'] = true;
                            $result['processedTca']['columns'][$fieldName]['children'][] = $compiledChild;
                        }
                    }
                }
            }
        }

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
    protected function compileChild(array $result, $parentFieldName, $childUid)
    {
        $parentConfig = $result['processedTca']['columns'][$parentFieldName]['config'];
        $childTableName = $parentConfig['foreign_table'];
        $inlineOverruleTypesArray = [];
        if (isset($parentConfig['foreign_types'])) {
            $inlineOverruleTypesArray = $parentConfig['foreign_types'];
        }
        /** @var TcaDatabaseRecord $formDataGroup */
        $formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
        /** @var FormDataCompiler $formDataCompiler */
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
        $formDataCompilerInput = [
            'command' => 'edit',
            'tableName' => $childTableName,
            'vanillaUid' => (int)$childUid,
            'inlineFirstPid' => $result['inlineFirstPid'],
            'inlineOverruleTypesArray' => $inlineOverruleTypesArray,
        ];
        // For foreign_selector with useCombination $mainChild is the mm record
        // and $combinationChild is the child-child. For "normal" relations, $mainChild
        // is just the normal child record and $combinationChild is empty.
        $mainChild = $formDataCompiler->compile($formDataCompilerInput);
        if ($parentConfig['foreign_selector'] && $parentConfig['appearance']['useCombination']) {
            $mainChild['combinationChild'] = $this->compileCombinationChild($mainChild, $parentConfig);
        }
        return $mainChild;
    }

    /**
     * With useCombination set, not only content of the intermediate table, but also
     * the connected child should be rendered in one go. Prepare this here.
     *
     * @param array $intermediate Full data array of "mm" record
     * @param array $parentConfig TCA configuration of "parent"
     * @return array Full data array of child
     * @todo: probably foreign_selector_fieldTcaOverride should be merged over here before
     */
    protected function compileCombinationChild(array $intermediate, array $parentConfig)
    {
        // foreign_selector on intermediate is probably type=select, so data provider of this table resolved that to the uid already
        $intermediateUid = $intermediate['databaseRow'][$parentConfig['foreign_selector']][0];
        $combinationChild = $this->compileChild($intermediate, $parentConfig['foreign_selector'], $intermediateUid);
        return $combinationChild;
    }

    /**
     * Substitute given list of uids in child table with workspace uid if needed
     *
     * @param array $connectedUids List of connected uids
     * @param string $childTableName Name of child table
     * @return array List of uids in workspace
     */
    protected function getWorkspacedUids(array $connectedUids, $childTableName)
    {
        $backendUser = $this->getBackendUser();
        $newConnectedUids = [];
        foreach ($connectedUids as $uid) {
            // Fetch workspace version of a record (if any):
            // @todo: Needs handling
            if ($backendUser->workspace !== 0 && BackendUtility::isTableWorkspaceEnabled($childTableName)) {
                $workspaceVersion = BackendUtility::getWorkspaceVersionOfRecord($backendUser->workspace, $childTableName, $uid, 'uid,t3ver_state');
                if ($workspaceVersion !== false) {
                    $versionState = VersionState::cast($workspaceVersion['t3ver_state']);
                    if ($versionState->equals(VersionState::DELETE_PLACEHOLDER)) {
                        return [];
                    }
                    $uid = $workspaceVersion['uid'];
                }
            }
            $newConnectedUids[] = $uid;
        }
        return $newConnectedUids;
    }

    /**
     * Use RelationHandler to resolve connected uids.
     *
     * @param array $parentConfig TCA config section of parent
     * @param string $parentTableName Name of parent table
     * @param string $parentUid Uid of parent record
     * @param string $parentFieldValue Database value of parent record of this inline field
     * @return array Array with connected uids
     * @todo: Cover with unit tests
     */
    protected function resolveConnectedRecordUids(array $parentConfig, $parentTableName, $parentUid, $parentFieldValue)
    {
        $directlyConnectedIds = GeneralUtility::trimExplode(',', $parentFieldValue);
        if (empty($parentConfig['MM'])) {
            $parentUid = $this->getLiveDefaultId($parentTableName, $parentUid);
        }
        /** @var RelationHandler $relationHandler */
        $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
        $relationHandler->registerNonTableValues = (bool)$parentConfig['allowedIdValues'];
        $relationHandler->start($parentFieldValue, $parentConfig['foreign_table'], $parentConfig['MM'], $parentUid, $parentTableName, $parentConfig);
        $foreignRecordUids = $relationHandler->getValueArray();
        $resolvedForeignRecordUids = [];
        foreach ($foreignRecordUids as $aForeignRecordUid) {
            if ($parentConfig['MM'] || $parentConfig['foreign_field']) {
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
     * Gets the record uid of the live default record. If already
     * pointing to the live record, the submitted record uid is returned.
     *
     * @param string $tableName
     * @param int $uid
     * @return int
     * @todo: the workspace mess still must be resolved somehow
     */
    protected function getLiveDefaultId($tableName, $uid)
    {
        $liveDefaultId = BackendUtility::getLiveVersionIdOfRecord($tableName, $uid);
        if ($liveDefaultId === null) {
            $liveDefaultId = $uid;
        }
        return $liveDefaultId;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
