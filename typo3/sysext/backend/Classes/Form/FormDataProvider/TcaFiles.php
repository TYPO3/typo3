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

namespace TYPO3\CMS\Backend\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\Exception\DatabaseRecordException;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Resolve and prepare files data.
 */
class TcaFiles extends AbstractDatabaseRecordProvider implements FormDataProviderInterface
{
    private const FILE_REFERENCE_TABLE = 'sys_file_reference';
    private const FOREIGN_SELECTOR = 'uid_local';

    public function addData(array $result): array
    {
        // inlineFirstPid is currently resolved by TcaInline
        // @todo check if duplicating the functionality makes sense to resolve dependencies

        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (($fieldConfig['config']['type'] ?? '') !== 'file') {
                continue;
            }

            if (!$this->getBackendUser()->check('tables_modify', self::FILE_REFERENCE_TABLE)) {
                // Early return if user is not allowed to modify the file reference table
                continue;
            }

            if (!($GLOBALS['TCA'][self::FILE_REFERENCE_TABLE] ?? false)) {
                throw new \RuntimeException('Table ' . self::FILE_REFERENCE_TABLE . ' does not exists', 1664364262);
            }

            $childConfiguration = $GLOBALS['TCA'][self::FILE_REFERENCE_TABLE]['columns'][self::FOREIGN_SELECTOR]['config'] ?? [];
            if (($childConfiguration['type'] ?? '') !== 'group' || !($childConfiguration['allowed'] ?? false)) {
                throw new \UnexpectedValueException(
                    'Table ' . $result['tableName'] . ' field ' . $fieldName . ' points to field '
                    . self::FOREIGN_SELECTOR . ' of table ' . self::FILE_REFERENCE_TABLE . ', but this field '
                    . 'is either not defined, is not of type "group" or does not define the "allowed" option.',
                    1664364263
                );
            }

            $result['processedTca']['columns'][$fieldName]['children'] = [];

            $result = $this->initializeMinMaxItems($result, $fieldName);
            $result = $this->initializeParentSysLanguageUid($result, $fieldName);
            $result = $this->initializeAppearance($result, $fieldName);

            // If field is set to readOnly, set all fields of the relation to readOnly as well
            if ($result['inlineParentConfig']['readOnly'] ?? false) {
                foreach ($result['processedTca']['columns'] as $columnName => $columnConfiguration) {
                    $result['processedTca']['columns'][$columnName]['config']['readOnly'] = true;
                }
            }

            // Resolve existing file references - this is usually always done except on ajax calls
            if ($result['inlineResolveExistingChildren']) {
                $result = $this->resolveFileReferences($result, $fieldName);
                if (!empty($fieldConfig['config']['selectorOrUniqueConfiguration'])) {
                    throw new \RuntimeException('selectorOrUniqueConfiguration not implemented for TCA type "file"', 1664380909);
                }
            }
        }

        return $result;
    }

    protected function initializeMinMaxItems(array $result, string $fieldName): array
    {
        $config = $result['processedTca']['columns'][$fieldName]['config'];
        $config['minitems'] = isset($config['minitems']) ? MathUtility::forceIntegerInRange($config['minitems'], 0) : 0;
        $config['maxitems'] = isset($config['maxitems']) ? MathUtility::forceIntegerInRange($config['maxitems'], 1) : 99999;
        $result['processedTca']['columns'][$fieldName]['config'] = $config;

        return $result;
    }

    protected function initializeParentSysLanguageUid(array $result, string $fieldName): array
    {
        if (($parentLanguageFieldName = (string)($result['processedTca']['ctrl']['languageField'] ?? '')) === ''
            || !($GLOBALS['TCA'][self::FILE_REFERENCE_TABLE]['ctrl']['languageField'] ?? false)
            || isset($result['processedTca']['columns'][$fieldName]['config']['inline']['parentSysLanguageUid'])
            || !isset($result['databaseRow'][$parentLanguageFieldName])
        ) {
            return $result;
        }

        $result['processedTca']['columns'][$fieldName]['config']['inline']['parentSysLanguageUid'] =
            is_array($result['databaseRow'][$parentLanguageFieldName])
                ? (int)($result['databaseRow'][$parentLanguageFieldName][0] ?? 0)
                : (int)$result['databaseRow'][$parentLanguageFieldName];

        return $result;
    }

    protected function initializeAppearance(array $result, string $fieldName): array
    {
        $result['processedTca']['columns'][$fieldName]['config']['appearance'] = array_replace_recursive(
            [
                'useSortable' => true,
                'headerThumbnail' => [
                    'height' => '45m',
                ],
                'enabledControls' => [
                    'edit' => true,
                    'info' => true,
                    'dragdrop' => true,
                    'sort' => false,
                    'hide' => true,
                    'delete' => true,
                    'localize' => true,
                ],
            ],
            $result['processedTca']['columns'][$fieldName]['config']['appearance'] ?? []
        );

        return $result;
    }

    /**
     * Substitute the value in databaseRow of this inline field with an array
     * that contains the databaseRows of currently connected records and some meta information.
     */
    protected function resolveFileReferences(array $result, string $fieldName): array
    {
        if ($result['defaultLanguageRow'] !== null) {
            return $this->resolveFileReferenceOverlays($result, $fieldName);
        }

        $fileReferenceUidsOfDefaultLanguageRecord = $this->resolveFileReferenceUids(
            $result['processedTca']['columns'][$fieldName]['config'],
            $result['tableName'],
            $result['databaseRow']['uid'],
            $result['databaseRow'][$fieldName]
        );
        $result['databaseRow'][$fieldName] = implode(',', $fileReferenceUidsOfDefaultLanguageRecord);

        if ($result['inlineCompileExistingChildren']) {
            foreach ($this->getSubstitutedWorkspacedUids($fileReferenceUidsOfDefaultLanguageRecord) as $uid) {
                try {
                    $compiledFileReference = $this->compileFileReference($result, $fieldName, $uid);
                    $result['processedTca']['columns'][$fieldName]['children'][] = $compiledFileReference;
                } catch (DatabaseRecordException $e) {
                    // Nothing to do here, missing file reference is just not being rendered.
                }
            }
        }
        return $result;
    }

    /**
     * Substitute the value in databaseRow of this file field with an array
     * that contains the databaseRows of currently connected file references
     * and some meta information.
     */
    protected function resolveFileReferenceOverlays(array $result, string $fieldName): array
    {
        $fileReferenceUidsOfLocalizedOverlay = [];
        $fieldConfig = $result['processedTca']['columns'][$fieldName]['config'];
        if ($result['command'] === 'edit') {
            $fileReferenceUidsOfLocalizedOverlay = $this->resolveFileReferenceUids(
                $fieldConfig,
                $result['tableName'],
                $result['databaseRow']['uid'],
                $result['databaseRow'][$fieldName]
            );
        }
        $result['databaseRow'][$fieldName] = implode(',', $fileReferenceUidsOfLocalizedOverlay);
        $fileReferenceUidsOfLocalizedOverlay = $this->getSubstitutedWorkspacedUids($fileReferenceUidsOfLocalizedOverlay);
        if ($result['inlineCompileExistingChildren']) {
            $tableNameWithDefaultRecords = $result['tableName'];
            $fileReferenceUidsOfDefaultLanguageRecord = $this->getSubstitutedWorkspacedUids(
                $this->resolveFileReferenceUids(
                    $fieldConfig,
                    $tableNameWithDefaultRecords,
                    $result['defaultLanguageRow']['uid'],
                    $result['defaultLanguageRow'][$fieldName]
                )
            );

            // Find which records are localized, which records are not localized and which are
            // localized but miss default language record
            $fieldNameWithDefaultLanguageUid = (string)($GLOBALS['TCA'][self::FILE_REFERENCE_TABLE]['ctrl']['transOrigPointerField'] ?? '');
            foreach ($fileReferenceUidsOfLocalizedOverlay as $localizedUid) {
                try {
                    $localizedRecord = $this->getRecordFromDatabase(self::FILE_REFERENCE_TABLE, $localizedUid);
                } catch (DatabaseRecordException $e) {
                    // The child could not be compiled, probably it was deleted and a dangling mm record exists
                    $this->logger->warning(
                        $e->getMessage(),
                        [
                            'table' => self::FILE_REFERENCE_TABLE,
                            'uid' => $localizedUid,
                            'exception' => $e,
                        ]
                    );
                    continue;
                }
                $uidOfDefaultLanguageRecord = (int)$localizedRecord[$fieldNameWithDefaultLanguageUid];
                if (in_array($uidOfDefaultLanguageRecord, $fileReferenceUidsOfDefaultLanguageRecord, true)) {
                    // This localized child has a default language record. Remove this record from list of default language records
                    $fileReferenceUidsOfDefaultLanguageRecord = array_diff($fileReferenceUidsOfDefaultLanguageRecord, [$uidOfDefaultLanguageRecord]);
                }
                // Compile localized record
                $compiledFileReference = $this->compileFileReference($result, $fieldName, $localizedUid);
                $result['processedTca']['columns'][$fieldName]['children'][] = $compiledFileReference;
            }
            if ($fieldConfig['appearance']['showPossibleLocalizationRecords'] ?? false) {
                foreach ($fileReferenceUidsOfDefaultLanguageRecord as $defaultLanguageUid) {
                    // If there are still uids in $connectedUidsOfDefaultLanguageRecord, these are records that
                    // exist in default language, but are not localized yet. Compile and mark those
                    try {
                        $compiledFileReference = $this->compileFileReference($result, $fieldName, $defaultLanguageUid);
                    } catch (DatabaseRecordException $e) {
                        // The child could not be compiled, probably it was deleted and a dangling mm record exists
                        $this->logger->warning(
                            $e->getMessage(),
                            [
                                'table' => self::FILE_REFERENCE_TABLE,
                                'uid' => $defaultLanguageUid,
                                'exception' => $e,
                            ]
                        );
                        continue;
                    }
                    $compiledFileReference['isInlineDefaultLanguageRecordInLocalizedParentContext'] = true;
                    $result['processedTca']['columns'][$fieldName]['children'][] = $compiledFileReference;
                }
            }
        }

        return $result;
    }

    protected function compileFileReference(array $result, string $parentFieldName, int $childUid): array
    {
        $inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
        $inlineStackProcessor->initializeByGivenStructure($result['inlineStructure']);
        $inlineTopMostParent = $inlineStackProcessor->getStructureLevel(0) ?: [];

        return GeneralUtility::makeInstance(FormDataCompiler::class)
            ->compile(
                [
                    'request' => $result['request'],
                    'command' => 'edit',
                    'tableName' => self::FILE_REFERENCE_TABLE,
                    'vanillaUid' => $childUid,
                    'returnUrl' => $result['returnUrl'],
                    'isInlineChild' => true,
                    'inlineStructure' => $result['inlineStructure'],
                    'inlineExpandCollapseStateArray' => $result['inlineExpandCollapseStateArray'],
                    'inlineFirstPid' => $result['inlineFirstPid'],
                    'inlineParentConfig' => $result['processedTca']['columns'][$parentFieldName]['config'],
                    'inlineParentUid' => $result['databaseRow']['uid'],
                    'inlineParentTableName' => $result['tableName'],
                    'inlineParentFieldName' => $parentFieldName,
                    'inlineTopMostParentUid' => $result['inlineTopMostParentUid'] ?: $inlineTopMostParent['uid'] ?? '',
                    'inlineTopMostParentTableName' => $result['inlineTopMostParentTableName'] ?: $inlineTopMostParent['table'] ?? '',
                    'inlineTopMostParentFieldName' => $result['inlineTopMostParentFieldName'] ?: $inlineTopMostParent['field'] ?? '',
                ],
                GeneralUtility::makeInstance(TcaDatabaseRecord::class)
            );
    }

    /**
     * Substitute given list of uids with corresponding workspace uids - if needed
     *
     * @param int[] $connectedUids List of file reference uids
     * @return int[] List of substituted uids
     */
    protected function getSubstitutedWorkspacedUids(array $connectedUids): array
    {
        $workspace = $this->getBackendUser()->workspace;
        if ($workspace === 0 || !BackendUtility::isTableWorkspaceEnabled(self::FILE_REFERENCE_TABLE)) {
            return $connectedUids;
        }

        $substitutedUids = [];
        foreach ($connectedUids as $uid) {
            $workspaceVersion = BackendUtility::getWorkspaceVersionOfRecord(
                $workspace,
                self::FILE_REFERENCE_TABLE,
                $uid,
                'uid,t3ver_state'
            );
            if (!empty($workspaceVersion)) {
                $versionState = VersionState::cast($workspaceVersion['t3ver_state']);
                if ($versionState->equals(VersionState::DELETE_PLACEHOLDER)) {
                    continue;
                }
                $uid = $workspaceVersion['uid'];
            }
            $substitutedUids[] = (int)$uid;
        }
        return $substitutedUids;
    }

    /**
     * Resolve file reference uids using the RelationHandler
     *
     * @return int[]
     */
    protected function resolveFileReferenceUids(
        array $parentConfig,
        $parentTableName,
        $parentUid,
        $parentFieldValue
    ): array {
        $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
        $relationHandler->start(
            $parentFieldValue,
            self::FILE_REFERENCE_TABLE,
            '',
            BackendUtility::getLiveVersionIdOfRecord($parentTableName, $parentUid) ?? $parentUid,
            $parentTableName,
            $parentConfig
        );
        return array_map('intval', $relationHandler->getValueArray());
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
