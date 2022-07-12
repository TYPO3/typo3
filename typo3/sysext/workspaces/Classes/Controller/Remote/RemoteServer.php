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

namespace TYPO3\CMS\Workspaces\Controller\Remote;

use TYPO3\CMS\Backend\Backend\Avatar\Avatar;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Log\LogDataTrait;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\SysLog\Action\Database as DatabaseAction;
use TYPO3\CMS\Core\Utility\DiffUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Workspaces\Domain\Model\CombinedRecord;
use TYPO3\CMS\Workspaces\Service\GridDataService;
use TYPO3\CMS\Workspaces\Service\HistoryService;
use TYPO3\CMS\Workspaces\Service\IntegrityService;
use TYPO3\CMS\Workspaces\Service\StagesService;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;

/**
 * Class RemoteServer
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class RemoteServer
{
    use LogDataTrait;

    /**
     * @var GridDataService
     */
    protected $gridDataService;

    /**
     * @var StagesService
     */
    protected $stagesService;

    /**
     * @var WorkspaceService
     */
    protected $workspaceService;

    /**
     * @var DiffUtility|null
     */
    protected $differenceHandler;

    public function __construct()
    {
        $this->workspaceService = GeneralUtility::makeInstance(WorkspaceService::class);
        $this->gridDataService = GeneralUtility::makeInstance(GridDataService::class);
        $this->stagesService = GeneralUtility::makeInstance(StagesService::class);
    }

    /**
     * Checks integrity of elements before performing actions on them.
     *
     * @param \stdClass $parameters
     * @return array
     */
    public function checkIntegrity(\stdClass $parameters)
    {
        $integrity = $this->createIntegrityService($this->getAffectedElements($parameters));
        $integrity->check();
        $response = [
            'result' => $integrity->getStatusRepresentation(),
        ];
        return $response;
    }

    /**
     * Get List of workspace changes
     *
     * @param \stdClass $parameter
     * @return array $data
     */
    public function getWorkspaceInfos($parameter)
    {
        // To avoid too much work we use -1 to indicate that every page is relevant
        $pageId = $parameter->id > 0 ? $parameter->id : -1;
        if (!isset($parameter->language) || !MathUtility::canBeInterpretedAsInteger($parameter->language)) {
            $parameter->language = null;
        }
        if (!isset($parameter->stage) || !MathUtility::canBeInterpretedAsInteger($parameter->stage)) {
            // -99 disables stage filtering
            $parameter->stage = -99;
        }
        $versions = $this->workspaceService->selectVersionsInWorkspace(
            $this->getCurrentWorkspace(),
            $parameter->stage,
            $pageId,
            $parameter->depth,
            'tables_select',
            $parameter->language
        );
        $data = $this->gridDataService->generateGridListFromVersions($versions, $parameter, $this->getCurrentWorkspace());
        return $data;
    }

    /**
     * Fetch further information to current selected workspace record.
     *
     * @param \stdClass $parameter
     * @return array $data
     */
    public function getRowDetails($parameter)
    {
        $diffReturnArray = [];
        $liveReturnArray = [];
        $diffUtility = $this->getDifferenceHandler();
        $liveRecord = (array)BackendUtility::getRecord($parameter->table, $parameter->t3ver_oid);
        $versionRecord = (array)BackendUtility::getRecord($parameter->table, $parameter->uid);
        $versionState = VersionState::cast((int)($versionRecord['t3ver_state'] ?? 0));
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $icon_Live = $iconFactory->getIconForRecord($parameter->table, $liveRecord, Icon::SIZE_SMALL)->getIdentifier();
        $icon_Workspace = $iconFactory->getIconForRecord($parameter->table, $versionRecord, Icon::SIZE_SMALL)->getIdentifier();
        $stagePosition = $this->stagesService->getPositionOfCurrentStage($parameter->stage);
        $fieldsOfRecords = array_keys($liveRecord);
        $isNewOrDeletePlaceholder = $versionState->equals(VersionState::NEW_PLACEHOLDER) || $versionState->equals(VersionState::DELETE_PLACEHOLDER);
        $suitableFields = ($isNewOrDeletePlaceholder && ($parameter->filterFields ?? false)) ? array_flip($this->getSuitableFields($parameter->table, $parameter->t3ver_oid)) : [];
        foreach ($fieldsOfRecords as $fieldName) {
            if (
                empty($GLOBALS['TCA'][$parameter->table]['columns'][$fieldName]['config'])
            ) {
                continue;
            }
            // Disable internal fields
            if (($GLOBALS['TCA'][$parameter->table]['ctrl']['transOrigDiffSourceField'] ?? '') === $fieldName) {
                continue;
            }
            if (($GLOBALS['TCA'][$parameter->table]['ctrl']['origUid'] ?? '') === $fieldName) {
                continue;
            }
            // Get the field's label. If not available, use the field name
            $fieldTitle = $this->getLanguageService()->sL(BackendUtility::getItemLabel($parameter->table, $fieldName));
            if (empty($fieldTitle)) {
                $fieldTitle = $fieldName;
            }
            // Gets the TCA configuration for the current field
            $configuration = $GLOBALS['TCA'][$parameter->table]['columns'][$fieldName]['config'];
            // check for exclude fields
            if ($this->getBackendUser()->isAdmin() || $GLOBALS['TCA'][$parameter->table]['columns'][$fieldName]['exclude'] == 0 || GeneralUtility::inList($this->getBackendUser()->groupData['non_exclude_fields'], $parameter->table . ':' . $fieldName)) {
                // call diff class only if there is a difference
                if ($configuration['type'] === 'inline' && $configuration['foreign_table'] === 'sys_file_reference') {
                    $useThumbnails = false;
                    if (!empty($configuration['overrideChildTca']['columns']['uid_local']['config']['appearance']['elementBrowserAllowed']) && !empty($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'])) {
                        $fileExtensions = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], true);
                        $allowedExtensions = GeneralUtility::trimExplode(',', $configuration['overrideChildTca']['columns']['uid_local']['config']['appearance']['elementBrowserAllowed'], true);
                        $differentExtensions = array_diff($allowedExtensions, $fileExtensions);
                        $useThumbnails = empty($differentExtensions);
                    }

                    $liveFileReferences = (array)BackendUtility::resolveFileReferences(
                        $parameter->table,
                        $fieldName,
                        $liveRecord,
                        0
                    );
                    $versionFileReferences = (array)BackendUtility::resolveFileReferences(
                        $parameter->table,
                        $fieldName,
                        $versionRecord,
                        $this->getCurrentWorkspace()
                    );
                    $fileReferenceDifferences = $this->prepareFileReferenceDifferences(
                        $liveFileReferences,
                        $versionFileReferences,
                        $useThumbnails
                    );

                    if ($fileReferenceDifferences === null) {
                        continue;
                    }

                    $diffReturnArray[] = [
                        'field' => $fieldName,
                        'label' => $fieldTitle,
                        'content' => $fileReferenceDifferences['differences'],
                    ];
                    $liveReturnArray[] = [
                        'field' => $fieldName,
                        'label' => $fieldTitle,
                        'content' => $fileReferenceDifferences['live'],
                    ];
                } elseif ($isNewOrDeletePlaceholder && isset($suitableFields[$fieldName])) {
                    // If this is a new or delete placeholder, add diff view for all appropriate fields
                    $newOrDeleteRecord[$fieldName] = BackendUtility::getProcessedValue(
                        $parameter->table,
                        $fieldName,
                        $liveRecord[$fieldName], // Both (live and version) values are the same
                        0,
                        true,
                        false,
                        $liveRecord['uid'] // Relations of new/delete placeholder do always contain the live uid
                    ) ?? '';

                    // Don't add empty fields
                    if ($newOrDeleteRecord[$fieldName] === '') {
                        continue;
                    }

                    $diffReturnArray[] = [
                        'field' => $fieldName,
                        'label' => $fieldTitle,
                        'content' => $versionState->equals(VersionState::NEW_PLACEHOLDER)
                            ? $diffUtility->makeDiffDisplay('', $newOrDeleteRecord[$fieldName])
                            : $diffUtility->makeDiffDisplay($newOrDeleteRecord[$fieldName], ''),
                    ];

                    // Generally not needed by Core, but let's make it available for further processing in hooks
                    $liveReturnArray[] = [
                        'field' => $fieldName,
                        'label' => $fieldTitle,
                        'content' => $newOrDeleteRecord[$fieldName],
                    ];
                } elseif ((string)$liveRecord[$fieldName] !== (string)$versionRecord[$fieldName]) {
                    // Select the human readable values before diff
                    $liveRecord[$fieldName] = BackendUtility::getProcessedValue(
                        $parameter->table,
                        $fieldName,
                        $liveRecord[$fieldName],
                        0,
                        true,
                        false,
                        $liveRecord['uid']
                    );
                    $versionRecord[$fieldName] = BackendUtility::getProcessedValue(
                        $parameter->table,
                        $fieldName,
                        $versionRecord[$fieldName],
                        0,
                        true,
                        false,
                        $versionRecord['uid']
                    );

                    $diffReturnArray[] = [
                        'field' => $fieldName,
                        'label' => $fieldTitle,
                        'content' => $diffUtility->makeDiffDisplay($liveRecord[$fieldName], $versionRecord[$fieldName]),
                    ];
                    $liveReturnArray[] = [
                        'field' => $fieldName,
                        'label' => $fieldTitle,
                        'content' => $liveRecord[$fieldName],
                    ];
                }
            }
        }
        // Hook for modifying the difference and live arrays
        // (this may be used by custom or dynamically-defined fields)
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['workspaces']['modifyDifferenceArray'] ?? [] as $className) {
            $hookObject = GeneralUtility::makeInstance($className);
            if (method_exists($hookObject, 'modifyDifferenceArray')) {
                $hookObject->modifyDifferenceArray($parameter, $diffReturnArray, $liveReturnArray, $diffUtility);
            }
        }

        $historyService = GeneralUtility::makeInstance(HistoryService::class);
        $history = $historyService->getHistory($parameter->table, $parameter->t3ver_oid);
        $stageChanges = $historyService->getStageChanges($parameter->table, (int)$parameter->t3ver_oid);
        $stageChangesFromSysLog = $this->getStageChangesFromSysLog($parameter->table, (int)$parameter->t3ver_oid);
        $commentsForRecord = $this->getCommentsForRecord($stageChanges, $stageChangesFromSysLog);

        if ($this->stagesService->isPrevStageAllowedForUser($parameter->stage)) {
            $prevStage = $this->stagesService->getPrevStage($parameter->stage);
            if (isset($prevStage[0])) {
                $prevStage = current($prevStage);
            }
        }
        if ($this->stagesService->isNextStageAllowedForUser($parameter->stage)) {
            $nextStage = $this->stagesService->getNextStage($parameter->stage);
            if (isset($nextStage[0])) {
                $nextStage = current($nextStage);
            }
        }

        return [
            'total' => 1,
            'data' => [
                [
                    // these parts contain HTML (don't escape)
                    'diff' => $diffReturnArray,
                    'live_record' => $liveReturnArray,
                    'icon_Live' => $icon_Live,
                    'icon_Workspace' => $icon_Workspace,
                    // this part is already escaped in getCommentsForRecord()
                    'comments' => $commentsForRecord,
                    // escape/sanitize the others
                    'path_Live' => htmlspecialchars(BackendUtility::getRecordPath($liveRecord['pid'], '', 999)),
                    'label_Stage' => htmlspecialchars($this->stagesService->getStageTitle($parameter->stage)),
                    'label_PrevStage' => $prevStage ?? false,
                    'label_NextStage' => $nextStage ?? false,
                    'stage_position' => (int)$stagePosition['position'],
                    'stage_count' => (int)$stagePosition['count'],
                    'parent' => [
                        'table' => htmlspecialchars($parameter->table),
                        'uid' => (int)$parameter->uid,
                    ],
                    'history' => [
                        'data' => $history,
                        'total' => count($history),
                    ],
                ],
            ],
        ];
    }

    /**
     * Prepares difference view for file references.
     *
     * @param FileReference[] $liveFileReferences
     * @param FileReference[] $versionFileReferences
     * @param bool|false $useThumbnails
     * @return array|null
     */
    protected function prepareFileReferenceDifferences(array $liveFileReferences, array $versionFileReferences, $useThumbnails = false)
    {
        $randomValue = StringUtility::getUniqueId('file');

        $liveValues = [];
        $versionValues = [];
        $candidates = [];
        $substitutes = [];

        // Process live references
        foreach ($liveFileReferences as $identifier => $liveFileReference) {
            $identifierWithRandomValue = $randomValue . '__' . $liveFileReference->getUid() . '__' . $randomValue;
            $candidates[$identifierWithRandomValue] = $liveFileReference;
            $liveValues[] = $identifierWithRandomValue;
        }

        // Process version references
        foreach ($versionFileReferences as $identifier => $versionFileReference) {
            $identifierWithRandomValue = $randomValue . '__' . $versionFileReference->getUid() . '__' . $randomValue;
            $candidates[$identifierWithRandomValue] = $versionFileReference;
            $versionValues[] = $identifierWithRandomValue;
        }

        // Combine values and surround by spaces
        // (to reduce the chunks Diff will find)
        $liveInformation = ' ' . implode(' ', $liveValues) . ' ';
        $versionInformation = ' ' . implode(' ', $versionValues) . ' ';

        // Return if information has not changed
        if ($liveInformation === $versionInformation) {
            return null;
        }

        /**
         * @var string $identifierWithRandomValue
         * @var FileReference $fileReference
         */
        foreach ($candidates as $identifierWithRandomValue => $fileReference) {
            if ($useThumbnails) {
                $thumbnailFile = $fileReference->getOriginalFile()->process(
                    ProcessedFile::CONTEXT_IMAGEPREVIEW,
                    ['width' => 40, 'height' => 40]
                );
                $thumbnailMarkup = '<img src="' . htmlspecialchars($thumbnailFile->getPublicUrl() ?? '') . '" />';
                $substitutes[$identifierWithRandomValue] = $thumbnailMarkup;
            } else {
                $substitutes[$identifierWithRandomValue] = $fileReference->getPublicUrl();
            }
        }

        $differences = $this->getDifferenceHandler()->makeDiffDisplay($liveInformation, $versionInformation);
        $liveInformation = str_replace(array_keys($substitutes), array_values($substitutes), trim($liveInformation));
        $differences = str_replace(array_keys($substitutes), array_values($substitutes), trim($differences));

        return [
            'live' => $liveInformation,
            'differences' => $differences,
        ];
    }

    /**
     * Prepares all comments of the stage change history entries for returning the JSON structure
     *
     * @param array $historyEntries
     * @param array $additionalChangesFromLog this is not in use since 2022 anymore, and can be removed in TYPO3 v13.0 the latest.
     * @return array
     */
    protected function getCommentsForRecord(array $historyEntries, array $additionalChangesFromLog): array
    {
        $allStageChanges = [];
        $avatar = GeneralUtility::makeInstance(Avatar::class);

        foreach ($historyEntries as $entry) {
            $preparedEntry = [];
            $beUserRecord = BackendUtility::getRecord('be_users', $entry['userid']);
            $preparedEntry['stage_title'] = htmlspecialchars($this->stagesService->getStageTitle($entry['history_data']['next']));
            $preparedEntry['previous_stage_title'] = htmlspecialchars($this->stagesService->getStageTitle($entry['history_data']['current']));
            $preparedEntry['user_uid'] = (int)$entry['userid'];
            $preparedEntry['user_username'] = is_array($beUserRecord) ? htmlspecialchars($beUserRecord['username']) : '';
            $preparedEntry['tstamp'] = htmlspecialchars(BackendUtility::datetime($entry['tstamp']));
            $preparedEntry['user_comment'] = nl2br(htmlspecialchars($entry['history_data']['comment']));
            $preparedEntry['user_avatar'] = $beUserRecord ? $avatar->render($beUserRecord) : '';
            $allStageChanges[] = $preparedEntry;
        }

        // see if there are more
        foreach ($additionalChangesFromLog as $sysLogRow) {
            $sysLogEntry = [];
            $data = $this->unserializeLogData($sysLogRow['log_data'] ?? '');
            $beUserRecord = BackendUtility::getRecord('be_users', $sysLogRow['userid']);
            $sysLogEntry['stage_title'] = htmlspecialchars($this->stagesService->getStageTitle($data['stage']));
            $sysLogEntry['previous_stage_title'] = '';
            $sysLogEntry['user_uid'] = (int)$sysLogRow['userid'];
            $sysLogEntry['user_username'] = is_array($beUserRecord) ? htmlspecialchars($beUserRecord['username']) : '';
            $sysLogEntry['tstamp'] = htmlspecialchars(BackendUtility::datetime($sysLogRow['tstamp']));
            $sysLogEntry['user_comment'] = nl2br(htmlspecialchars($data['comment']));
            $sysLogEntry['user_avatar'] = $avatar->render($beUserRecord);
            $allStageChanges[] = $sysLogEntry;
        }

        // There might be "old" sys_log entries, so they need to be checked as well
        return $allStageChanges;
    }

    /**
     * Find all stage changes from sys_log that do not have a historyId. Can safely be removed in future TYPO3
     * versions as this fallback layer only makes sense in TYPO3 v11 when old records want to have a history.
     */
    protected function getStageChangesFromSysLog(string $table, int $uid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_log');

        return $queryBuilder
            ->select('log_data', 'tstamp', 'userid')
            ->from('sys_log')
            ->where(
                $queryBuilder->expr()->eq(
                    'action',
                    $queryBuilder->createNamedParameter(DatabaseAction::UPDATE, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'details_nr',
                    $queryBuilder->createNamedParameter(30, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'tablename',
                    $queryBuilder->createNamedParameter($table, \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'recuid',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            )
            ->orderBy('tstamp', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Gets the difference handler, parsing differences based on sentences.
     *
     * @return DiffUtility
     */
    protected function getDifferenceHandler()
    {
        if (!isset($this->differenceHandler)) {
            $this->differenceHandler = GeneralUtility::makeInstance(DiffUtility::class);
            $this->differenceHandler->stripTags = false;
        }
        return $this->differenceHandler;
    }

    /**
     * Creates a new instance of the integrity service for the
     * given set of affected elements.
     *
     * @param CombinedRecord[] $affectedElements
     * @return IntegrityService
     * @see getAffectedElements
     */
    protected function createIntegrityService(array $affectedElements)
    {
        $integrityService = GeneralUtility::makeInstance(IntegrityService::class);
        $integrityService->setAffectedElements($affectedElements);
        return $integrityService;
    }

    /**
     * Gets affected elements on publishing/swapping actions.
     * Affected elements have a dependency, e.g. translation overlay
     * and the default origin record - thus, the default record would be
     * affected if the translation overlay shall be published.
     *
     * @param \stdClass $parameters
     * @return array
     */
    protected function getAffectedElements(\stdClass $parameters)
    {
        $affectedElements = [];
        if ($parameters->type === 'selection') {
            foreach ((array)$parameters->selection as $element) {
                $affectedElements[] = CombinedRecord::create($element->table, $element->liveId, $element->versionId);
            }
        } elseif ($parameters->type === 'all') {
            $versions = $this->workspaceService->selectVersionsInWorkspace(
                $this->getCurrentWorkspace(),
                -99,
                -1,
                0,
                'tables_select',
                $this->validateLanguageParameter($parameters)
            );
            foreach ($versions as $table => $tableElements) {
                foreach ($tableElements as $element) {
                    $affectedElement = CombinedRecord::create($table, $element['t3ver_oid'], $element['uid']);
                    $affectedElement->getVersionRecord()->setRow($element);
                    $affectedElements[] = $affectedElement;
                }
            }
        }
        return $affectedElements;
    }

    /**
     * Validates whether the submitted language parameter can be
     * interpreted as integer value.
     *
     * @param \stdClass $parameters
     * @return int|null
     */
    protected function validateLanguageParameter(\stdClass $parameters)
    {
        $language = null;
        if (isset($parameters->language) && MathUtility::canBeInterpretedAsInteger($parameters->language)) {
            $language = $parameters->language;
        }
        return $language;
    }

    /**
     * Gets the current workspace ID.
     *
     * @return int The current workspace ID
     */
    protected function getCurrentWorkspace()
    {
        return $this->workspaceService->getCurrentWorkspace();
    }

    /**
     * Gets the fields suitable for being displayed in new and delete diff views
     *
     * @param string $table
     * @param int $uid
     * @return array
     */
    protected function getSuitableFields(string $table, int $uid): array
    {
        $formDataCompiler = GeneralUtility::makeInstance(
            FormDataCompiler::class,
            GeneralUtility::makeInstance(TcaDatabaseRecord::class)
        );

        try {
            $result = $formDataCompiler->compile(['command' => 'edit', 'tableName' => $table, 'vanillaUid' => $uid]);
            $fieldList = array_unique(array_values($result['columnsToProcess']));
        } catch (\Exception $exception) {
            $fieldList = [];
        }

        return array_unique(array_merge(
            $fieldList,
            GeneralUtility::trimExplode(',', (string)($GLOBALS['TCA'][$table]['ctrl']['searchFields'] ?? ''))
        ));
    }
}
