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

namespace TYPO3\CMS\Workspaces\Controller\Remote;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Backend\Avatar\Avatar;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\ValueFormatter\FlexFormValueFormatter;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Log\LogDataTrait;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\SysLog\Action\Database as DatabaseAction;
use TYPO3\CMS\Core\Utility\DiffGranularity;
use TYPO3\CMS\Core\Utility\DiffUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Workspaces\Domain\Model\CombinedRecord;
use TYPO3\CMS\Workspaces\Event\ModifyVersionDifferencesEvent;
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

    public function __construct(
        protected readonly GridDataService $gridDataService,
        protected readonly StagesService $stagesService,
        protected readonly WorkspaceService $workspaceService,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly FlexFormValueFormatter $flexFormValueFormatter,
    ) {}

    /**
     * Checks integrity of elements before performing actions on them.
     *
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
    public function getWorkspaceInfos($parameter, ServerRequestInterface $request)
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
        $data = $this->gridDataService->generateGridListFromVersions($versions, $parameter, $this->getCurrentWorkspace(), $request);
        return $data;
    }

    /**
     * Fetch further information to current selected workspace record.
     *
     * @param \stdClass $parameter
     * @return array $data
     */
    public function getRowDetails($parameter, ServerRequestInterface $request)
    {
        $diffUtility = GeneralUtility::makeInstance(DiffUtility::class);
        $diffReturnArray = [];
        $liveReturnArray = [];
        $liveRecord = (array)BackendUtility::getRecord($parameter->table, $parameter->t3ver_oid);
        $versionRecord = (array)BackendUtility::getRecord($parameter->table, $parameter->uid);
        $versionState = VersionState::cast((int)($versionRecord['t3ver_state'] ?? 0));
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $iconLive = $iconFactory->getIconForRecord($parameter->table, $liveRecord, Icon::SIZE_SMALL);
        $iconWorkspace = $iconFactory->getIconForRecord($parameter->table, $versionRecord, Icon::SIZE_SMALL);
        $stagePosition = $this->stagesService->getPositionOfCurrentStage($parameter->stage);
        $fieldsOfRecords = array_keys($liveRecord);
        $isNewOrDeletePlaceholder = $versionState->equals(VersionState::NEW_PLACEHOLDER) || $versionState->equals(VersionState::DELETE_PLACEHOLDER);
        $suitableFields = ($isNewOrDeletePlaceholder && ($parameter->filterFields ?? false)) ? array_flip($this->getSuitableFields($parameter->table, $parameter->t3ver_oid, $request)) : [];
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
            $isFieldExcluded = (bool)($GLOBALS['TCA'][$parameter->table]['columns'][$fieldName]['exclude'] ?? false);
            if ($this->getBackendUser()->isAdmin() || !$isFieldExcluded || GeneralUtility::inList($this->getBackendUser()->groupData['non_exclude_fields'], $parameter->table . ':' . $fieldName)) {
                // call diff class only if there is a difference
                if ($configuration['type'] === 'file') {
                    $useThumbnails = false;
                    if (!empty($configuration['allowed']) && !empty($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'])) {
                        $fileExtensions = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], true);
                        $allowedExtensions = GeneralUtility::trimExplode(',', $configuration['allowed'], true);
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
                    $newOrDeleteRecord[$fieldName] = $this->formatValue($parameter->table, $fieldName, (string)$liveRecord[$fieldName], $liveRecord['uid'], $configuration);

                    // Don't add empty fields
                    if ($newOrDeleteRecord[$fieldName] === '') {
                        continue;
                    }

                    $granularity = ($configuration['type'] ?? '') === 'flex' ? DiffGranularity::CHARACTER : DiffGranularity::WORD;
                    $diffReturnArray[] = [
                        'field' => $fieldName,
                        'label' => $fieldTitle,
                        'content' => $versionState->equals(VersionState::NEW_PLACEHOLDER)
                            ? $diffUtility->makeDiffDisplay('', $newOrDeleteRecord[$fieldName], $granularity)
                            : $diffUtility->makeDiffDisplay($newOrDeleteRecord[$fieldName], '', $granularity),
                    ];

                    // Generally not needed by Core, but let's make it available for further processing in hooks
                    $liveReturnArray[] = [
                        'field' => $fieldName,
                        'label' => $fieldTitle,
                        'content' => $newOrDeleteRecord[$fieldName],
                    ];
                } elseif ((string)$liveRecord[$fieldName] !== (string)$versionRecord[$fieldName]) {
                    // Select the human-readable values before diff
                    $liveRecord[$fieldName] = $this->formatValue($parameter->table, $fieldName, (string)$liveRecord[$fieldName], $liveRecord['uid'], $configuration);
                    $versionRecord[$fieldName] = $this->formatValue($parameter->table, $fieldName, (string)$versionRecord[$fieldName], $versionRecord['uid'], $configuration);
                    $granularity = ($configuration['type'] ?? '') === 'flex' ? DiffGranularity::CHARACTER : DiffGranularity::WORD;
                    $fieldDifferences = $diffUtility->makeDiffDisplay(
                        $liveRecord[$fieldName],
                        $versionRecord[$fieldName],
                        $granularity
                    );

                    $diffReturnArray[] = [
                        'field' => $fieldName,
                        'label' => $fieldTitle,
                        'content' => $fieldDifferences,
                    ];
                    $liveReturnArray[] = [
                        'field' => $fieldName,
                        'label' => $fieldTitle,
                        'content' => $liveRecord[$fieldName],
                    ];
                }
            }
        }

        $versionDifferencesEvent = $this->eventDispatcher->dispatch(
            new ModifyVersionDifferencesEvent($diffReturnArray, $liveReturnArray, $parameter)
        );

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
                    'diff' => $versionDifferencesEvent->getVersionDifferences(),
                    'icon_Live' => $iconLive->getIdentifier(),
                    'icon_Live_Overlay' => $iconLive->getOverlayIcon()?->getIdentifier() ?? '',
                    'icon_Workspace' => $iconWorkspace->getIdentifier(),
                    'icon_Workspace_Overlay' => $iconWorkspace->getOverlayIcon()?->getIdentifier() ?? '',
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

    protected function formatValue(string $table, string $fieldName, string $value, int $uid, array $tcaConfiguration): string
    {
        if (($tcaConfiguration['type'] ?? '') === 'flex') {
            return $this->flexFormValueFormatter->format($table, $fieldName, $value, $uid, $tcaConfiguration);
        }
        return (string)BackendUtility::getProcessedValue($table, $fieldName, $value, defaultPassthrough: true, uid: $uid);
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

        $diffUtility = GeneralUtility::makeInstance(DiffUtility::class);
        $differences = $diffUtility->makeDiffDisplay($liveInformation, $versionInformation);
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
     * @param array $additionalChangesFromLog this is not in use since 2022 anymore, and can be removed in TYPO3 v13.0 the latest.
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
                    $queryBuilder->createNamedParameter(DatabaseAction::UPDATE, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'details_nr',
                    $queryBuilder->createNamedParameter(30, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'tablename',
                    $queryBuilder->createNamedParameter($table)
                ),
                $queryBuilder->expr()->eq(
                    'recuid',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
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
     */
    protected function getSuitableFields(string $table, int $uid, ServerRequestInterface $request): array
    {
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class);

        try {
            $result = $formDataCompiler->compile(
                [
                    'request' => $request,
                    'command' => 'edit',
                    'tableName' => $table,
                    'vanillaUid' => $uid,
                ],
                GeneralUtility::makeInstance(TcaDatabaseRecord::class)
            );
            $fieldList = array_unique(array_values($result['columnsToProcess']));
        } catch (\Exception $exception) {
            // @todo: Avoid this general exception and catch something specific to not hide-away errors.
            $fieldList = [];
        }

        return array_unique(array_merge(
            $fieldList,
            GeneralUtility::trimExplode(',', (string)($GLOBALS['TCA'][$table]['ctrl']['searchFields'] ?? ''))
        ));
    }
}
