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

namespace TYPO3\CMS\Workspaces\Service;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Backend\Backend\Avatar\Avatar;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\ValueFormatter\FlexFormValueFormatter;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\SearchableSchemaFieldsCollector;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Schema\VisibleSchemaFieldsCollector;
use TYPO3\CMS\Core\Utility\DiffGranularity;
use TYPO3\CMS\Core\Utility\DiffUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Workspaces\Authorization\WorkspacePublishGate;
use TYPO3\CMS\Workspaces\Domain\Model\CombinedRecord;
use TYPO3\CMS\Workspaces\Domain\Model\WorkspaceStage;
use TYPO3\CMS\Workspaces\Event\AfterCompiledCacheableDataForWorkspaceEvent;
use TYPO3\CMS\Workspaces\Event\AfterDataGeneratedForWorkspaceEvent;
use TYPO3\CMS\Workspaces\Event\GetVersionedDataEvent;
use TYPO3\CMS\Workspaces\Event\ModifyVersionDifferencesEvent;
use TYPO3\CMS\Workspaces\Event\SortVersionedDataEvent;
use TYPO3\CMS\Workspaces\Exception\WorkspaceStageNotFoundException;
use TYPO3\CMS\Workspaces\Preview\PreviewUriBuilder;
use TYPO3\CMS\Workspaces\Service\Dependency\CollectionService;

/**
 * @internal
 */
readonly class GridDataService
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private ModuleProvider $moduleProvider,
        private WorkspacePublishGate $workspacePublishGate,
        private IntegrityService $integrityService,
        private PreviewUriBuilder $previewUriBuilder,
        private TcaSchemaFactory $tcaSchemaFactory,
        private UriBuilder $uriBuilder,
        private IconFactory $iconFactory,
        private StagesService $stagesService,
        private DiffUtility $diffUtility,
        private HistoryService $historyService,
        private ResourceFactory $resourceFactory,
        private LoggerInterface $logger,
        private FlexFormValueFormatter $flexFormValueFormatter,
        private SearchableSchemaFieldsCollector $searchableSchemaFieldsCollector,
        private VisibleSchemaFieldsCollector $visibleSchemaFieldsCollector,
        private Avatar $avatar,
        private TranslationConfigurationProvider $translationConfigurationProvider,
    ) {}

    /**
     * Generates grid list array from given versions.
     *
     * @param WorkspaceStage[] $stages
     * @param array $versions All records uids etc. First key is table name, second key incremental integer.
     *                        Records are associative arrays with uid and t3ver_oid fields. The pid of the online
     *                        record is found as "livepid" the pid of the offline record is found in "wspid"
     * @param \stdClass $parameter Parameters as submitted by JavaScript component
     * @return array Version record information (filtered, sorted and limited)
     */
    public function generateGridListFromVersions(array $stages, array $versions, \stdClass $parameter): array
    {
        // Read the given parameters from grid. If the parameter is not set use default values.
        $filterTxt = $parameter->filterTxt ?? '';
        $start = isset($parameter->start) ? (int)$parameter->start : 0;
        $limit = isset($parameter->limit) ? (int)$parameter->limit : 30;
        $dataArray = $this->generateDataArray($stages, $versions, $filterTxt);
        return [
            // Only count parent records for pagination
            'total' => count(array_filter($dataArray, static function ($element) {
                return (int)($element['Workspaces_CollectionLevel'] ?? 0) === 0;
            })),
            'data' => $this->getDataArray($dataArray, $start, $limit),
        ];
    }

    /**
     * @param WorkspaceStage[] $stages
     */
    public function getRowDetails(array $stages, \stdClass $parameter): array
    {
        $backendUser = $this->getBackendUser();
        $table = $parameter->table;
        $schema = $this->tcaSchemaFactory->get($table);
        $diffReturnArray = [];
        $liveReturnArray = [];
        $plainLiveRecord = $liveRecord = (array)BackendUtility::getRecord($table, $parameter->t3ver_oid);
        $plainVersionRecord = $versionRecord = (array)BackendUtility::getRecord($table, $parameter->uid);
        $versionState = VersionState::tryFrom($versionRecord['t3ver_state'] ?? 0);

        try {
            $currentStage = $this->stagesService->getStage($stages, $parameter->stage);
        } catch (WorkspaceStageNotFoundException) {
            $currentStage = null;
        }
        $nextStageSendToTitle = false;
        $previousStageSendToTitle = false;
        if ($currentStage?->isAllowed) {
            try {
                $nextStage = $this->stagesService->getNextStage($stages, $currentStage->uid);
                if ($nextStage->isExecuteStage) {
                    $nextStageSendToTitle = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:publish_execute_action_option');
                } else {
                    $nextStageSendToTitle = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:actionSendToStage') . ' "' . $nextStage->title . '"';
                }
            } catch (WorkspaceStageNotFoundException) {
                // keep false as title
            }
            try {
                $previousStage = $this->stagesService->getPreviousStage($stages, $currentStage->uid);
                $previousStageSendToTitle = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:actionSendToStage') . ' "' . $previousStage->title . '"';
            } catch (WorkspaceStageNotFoundException) {
                // keep false as title
            }
        }

        $iconWorkspace = $this->iconFactory->getIconForRecord($table, $versionRecord, IconSize::SMALL);
        $fieldsOfRecords = array_keys($liveRecord);
        $isNewOrDeletePlaceholder = $versionState === VersionState::NEW_PLACEHOLDER || $versionState === VersionState::DELETE_PLACEHOLDER;
        $suitableFields = ($isNewOrDeletePlaceholder && ($parameter->filterFields ?? false)) ? array_flip($this->getSuitableFields($table, $liveRecord)) : [];
        foreach ($fieldsOfRecords as $fieldName) {
            if (!$schema->hasField($fieldName)) {
                continue;
            }
            // Disable internal fields
            // l10n_diffsource is not needed, see #91667
            if ($schema->isLanguageAware() && $schema->getCapability(TcaSchemaCapability::Language)->getDiffSourceField()?->getName() === $fieldName) {
                continue;
            }
            if ($schema->hasCapability(TcaSchemaCapability::AncestorReferenceField) && $schema->getCapability(TcaSchemaCapability::AncestorReferenceField)->getFieldName() === $fieldName) {
                continue;
            }
            $fieldTypeInformation = $schema->getField($fieldName);
            // Get the field's label. If not available, use the field name
            $fieldTitle = $this->getLanguageService()->sL($fieldTypeInformation->getLabel());
            if (empty($fieldTitle)) {
                $fieldTitle = $fieldName;
            }
            // Gets the TCA configuration for the current field
            $configuration = $fieldTypeInformation->getConfiguration();
            // check for exclude fields
            $isFieldExcluded = $fieldTypeInformation->supportsAccessControl();
            if ($backendUser->isAdmin() || !$isFieldExcluded || GeneralUtility::inList($backendUser->groupData['non_exclude_fields'], $table . ':' . $fieldName)) {
                $granularity = $fieldTypeInformation->isType(TableColumnType::FLEX) ? DiffGranularity::CHARACTER : DiffGranularity::WORD;
                // call diff class only if there is a difference
                if ($fieldTypeInformation->isType(TableColumnType::FILE)) {
                    $useThumbnails = false;
                    if (!empty($configuration['allowed']) && !empty($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'])) {
                        $fileExtensions = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], true);
                        $allowedExtensions = GeneralUtility::trimExplode(',', $configuration['allowed'], true);
                        $differentExtensions = array_diff($allowedExtensions, $fileExtensions);
                        $useThumbnails = empty($differentExtensions);
                    }
                    if ($fieldTypeInformation->getType() === 'file') {
                        $liveFileReferences = $this->resolveFileReferences($table, $fieldName, $fieldTypeInformation, $liveRecord, 0);
                        $versionFileReferences = $this->resolveFileReferences($table, $fieldName, $fieldTypeInformation, $versionRecord, $backendUser->workspace);
                    } else {
                        $liveFileReferences = [];
                        $versionFileReferences = [];
                    }
                    $fileReferenceDifferences = $this->prepareFileReferenceDifferences($liveFileReferences, $versionFileReferences, $useThumbnails);
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
                    $newOrDeleteRecord[$fieldName] = $this->formatValue($table, $fieldName, (string)$liveRecord[$fieldName], $liveRecord['uid'], $configuration, $plainLiveRecord);
                    if ($newOrDeleteRecord[$fieldName] === '') {
                        // Don't add empty fields
                        continue;
                    }
                    $diffReturnArray[] = [
                        'field' => $fieldName,
                        'label' => $fieldTitle,
                        'content' => $versionState === VersionState::NEW_PLACEHOLDER
                            ? $this->diffUtility->diff('', strip_tags($newOrDeleteRecord[$fieldName]), $granularity)
                            : $this->diffUtility->diff(strip_tags($newOrDeleteRecord[$fieldName]), '', $granularity),
                    ];
                    // Generally not needed by Core, but let's make it available for further processing in hooks
                    $liveReturnArray[] = [
                        'field' => $fieldName,
                        'label' => $fieldTitle,
                        'content' => $newOrDeleteRecord[$fieldName],
                    ];
                } elseif ((string)$liveRecord[$fieldName] !== (string)$versionRecord[$fieldName]) {
                    // Select the human-readable values before diff
                    $liveRecord[$fieldName] = $this->formatValue($table, $fieldName, (string)$liveRecord[$fieldName], $liveRecord['uid'], $configuration, $plainLiveRecord);
                    $versionRecord[$fieldName] = $this->formatValue($table, $fieldName, (string)$versionRecord[$fieldName], $versionRecord['uid'], $configuration, $plainVersionRecord);
                    $fieldDifferences = $this->diffUtility->diff(strip_tags($liveRecord[$fieldName]), strip_tags($versionRecord[$fieldName]), $granularity);
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
        $versionDifferencesEvent = $this->eventDispatcher->dispatch(new ModifyVersionDifferencesEvent($diffReturnArray, $liveReturnArray, $parameter));
        $history = $this->historyService->getHistory($table, $parameter->t3ver_oid);
        $stageChanges = $this->historyService->getStageChanges($table, (int)$parameter->t3ver_oid);
        $commentsForRecord = [];
        foreach ($stageChanges as $entry) {
            $preparedEntry = [];
            $beUserRecord = BackendUtility::getRecord('be_users', $entry['userid']);
            $preparedEntry['stage_title'] = $this->stagesService->getStageTitle((int)$entry['history_data']['next']);
            $preparedEntry['previous_stage_title'] = $this->stagesService->getStageTitle((int)$entry['history_data']['current']);
            $preparedEntry['user_uid'] = (int)$entry['userid'];
            $preparedEntry['user_username'] = is_array($beUserRecord) ? $beUserRecord['username'] : '';
            $preparedEntry['tstamp'] = BackendUtility::datetime($entry['tstamp']);
            $preparedEntry['user_comment'] = $entry['history_data']['comment'];
            $preparedEntry['user_avatar'] = $beUserRecord ? $this->avatar->render($beUserRecord) : '';
            $commentsForRecord[] = $preparedEntry;
        }
        return [
            'total' => 1,
            'data' => [
                [
                    // these parts contain HTML (don't escape)
                    'diff' => $versionDifferencesEvent->getVersionDifferences(),
                    'icon_Workspace' => $iconWorkspace->getIdentifier(),
                    'icon_Workspace_Overlay' => $iconWorkspace->getOverlayIcon()?->getIdentifier() ?? '',
                    'comments' => $commentsForRecord,
                    // escape/sanitize the others
                    'path_Live' => htmlspecialchars(BackendUtility::getRecordPath($liveRecord['pid'], '', 999)),
                    'label_Stage' => htmlspecialchars($currentStage->title),
                    'label_PrevStage' => $previousStageSendToTitle ? ['title' => $previousStageSendToTitle] : false,
                    'label_NextStage' => $nextStageSendToTitle ? ['title' => $nextStageSendToTitle] : false,
                    'stage_position' => $this->stagesService->getPositionOfCurrentStage($stages, $currentStage->uid),
                    'stage_count' => count($stages) - 1, // Do not count 'pseudo' execute stage
                    'parent' => [
                        'table' => htmlspecialchars($table),
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
     * Generates grid list array from given versions.
     *
     * @param WorkspaceStage[] $stages
     * @param array $versions All available version records
     * @param string $filterTxt Text to be used to filter record result
     */
    protected function generateDataArray(array $stages, array $versions, string $filterTxt): array
    {
        $backendUser = $this->getBackendUser();
        $workspaceAccess = $backendUser->checkWorkspace($backendUser->workspace);
        $swapStage = ($workspaceAccess['publish_access'] ?? 0) & WorkspaceService::PUBLISH_ACCESS_ONLY_IN_PUBLISH_STAGE ? StagesService::STAGE_PUBLISH_ID : StagesService::STAGE_EDIT_ID;

        $isAllowedToPublish = $this->workspacePublishGate->isGranted($backendUser, $backendUser->workspace);
        $defaultGridColumns = [
            'Workspaces_Collection' => 0,
            'Workspaces_CollectionLevel' => 0,
            'Workspaces_CollectionParent' => '',
            'Workspaces_CollectionCurrent' => '',
            'Workspaces_CollectionChildren' => 0,
        ];
        $searchDataKeys = [
            'path_Workspace',
            'label_Stage',
            'label_Workspace',
        ];
        $integrityIssues = [];
        $dataArray = [];
        foreach ($versions as $table => $records) {
            $table = (string)$table;
            $schema = $this->tcaSchemaFactory->get($table);
            $hiddenField = $schema->hasCapability(TcaSchemaCapability::RestrictionDisabledField) ? $schema->getCapability(TcaSchemaCapability::RestrictionDisabledField)->getFieldName() : null;
            $isRecordTypeAllowedToModify = $backendUser->check('tables_modify', $table);

            foreach ($records as $record) {
                $origRecord = (array)BackendUtility::getRecord($table, $record['t3ver_oid']);
                $versionRecord = (array)BackendUtility::getRecord($table, $record['uid']);
                $combinedRecord = CombinedRecord::createFromArrays($table, $origRecord, $versionRecord);
                $hasDiff = $this->versionIsModified($stages, $combinedRecord);
                $integrityIssues = $this->integrityService->checkElement($combinedRecord, $integrityIssues);
                $currentStage = null;
                $currentStageTitle = '';
                $previousStage = null;
                $nextStage = null;
                try {
                    $currentStage = $this->stagesService->getStage($stages, (int)$versionRecord['t3ver_stage']);
                    $currentStageTitle = $currentStage->title;
                    $nextStage = $this->stagesService->getNextStage($stages, $currentStage->uid);
                    $previousStage = $this->stagesService->getPreviousStage($stages, $currentStage->uid);
                } catch (WorkspaceStageNotFoundException) {
                    // Shouldn't happen except for 'editing' stage, which has no previous stage.
                }

                if ($hiddenField !== null) {
                    $recordState = $this->workspaceState($versionRecord['t3ver_state'], (bool)$origRecord[$hiddenField], (bool)$versionRecord[$hiddenField], $hasDiff);
                } else {
                    $recordState = $this->workspaceState($versionRecord['t3ver_state'], $hasDiff);
                }

                $isDeletedPage = $table === 'pages' && $recordState === 'deleted';
                $pageId = (int)($record['pid'] ?? null);
                if ($table === 'pages') {
                    // The page ID for a translated page is considered here
                    $pageId = (int)(!empty($record['l10n_parent']) ? $record['l10n_parent'] : ($record['t3ver_oid'] ?: $record['uid']));
                }
                $viewUrl = $this->previewUriBuilder->buildUriForElement($table, (int)$record['uid'], $origRecord, $versionRecord);
                $workspaceRecordLabel = BackendUtility::getRecordTitle($table, $versionRecord);
                $iconWorkspace = $this->iconFactory->getIconForRecord($table, $versionRecord, IconSize::SMALL);
                $calculatedT3verOid = $record['t3ver_oid'];
                if (VersionState::tryFrom($record['t3ver_state'] ?? 0) === VersionState::NEW_PLACEHOLDER) {
                    // If we're dealing with a 'new' record, this one has no t3ver_oid. On publish, there is no
                    // live counterpart, but the publish methods later need a live uid to publish to. We thus
                    // use the uid as t3ver_oid here to be transparent on javascript side.
                    $calculatedT3verOid = $record['uid'];
                }

                $versionArray = $defaultGridColumns;
                $versionArray['table'] = $table;
                $versionArray['id'] = $table . ':' . $record['uid'];
                $versionArray['uid'] = $record['uid'];
                $versionArray['label_Workspace'] = htmlspecialchars($workspaceRecordLabel);
                $versionArray['label_Stage'] = htmlspecialchars($currentStageTitle);
                $versionArray['value_nextStage'] = $nextStage->uid ?? 0;
                $versionArray['value_prevStage'] = $previousStage->uid ?? 0;
                $versionArray['path_Workspace'] = htmlspecialchars(BackendUtility::getRecordPath((int)$record['wspid'], '', 0));
                $versionArray['lastChangedFormatted'] = '';
                if (array_key_exists('tstamp', $versionRecord)) {
                    // @todo: Avoid hard coded access to 'tstamp' and use table TCA 'ctrl' 'tstamp' value instead, if set.
                    $versionArray['lastChangedFormatted'] = BackendUtility::datetime((int)$versionRecord['tstamp']);
                }
                $versionArray['t3ver_wsid'] = $versionRecord['t3ver_wsid'];
                $versionArray['t3ver_oid'] = $calculatedT3verOid;
                $versionArray['livepid'] = $record['livepid'];
                $versionArray['stage'] = $versionRecord['t3ver_stage'];
                $versionArray['icon_Workspace'] = $iconWorkspace->getIdentifier();
                $versionArray['icon_Workspace_Overlay'] = $iconWorkspace->getOverlayIcon()?->getIdentifier() ?? '';
                $languageValue = $this->getLanguageValue($schema, $versionRecord);
                $versionArray['language'] = [
                    'icon' => $this->iconFactory->getIcon($this->getSystemLanguageValue($languageValue, $pageId, 'flagIcon') ?? 'empty-empty', IconSize::SMALL)->getIdentifier(),
                    'title' => $this->getSystemLanguageValue($languageValue, $pageId, 'title'),
                    'title_crop' => htmlspecialchars(GeneralUtility::fixed_lgd_cs($this->getSystemLanguageValue($languageValue, $pageId, 'title'), (int)$backendUser->uc['titleLen'])),
                ];
                if ($isAllowedToPublish && $swapStage === StagesService::STAGE_PUBLISH_ID && (int)$versionRecord['t3ver_stage'] === StagesService::STAGE_PUBLISH_ID) {
                    $versionArray['allowedAction_publish'] = $isRecordTypeAllowedToModify && $this->stagesService->getStage($stages, StagesService::STAGE_PUBLISH_ID)->isAllowed;
                } elseif ($isAllowedToPublish && $swapStage === StagesService::STAGE_EDIT_ID) {
                    $versionArray['allowedAction_publish'] = $isRecordTypeAllowedToModify;
                } else {
                    $versionArray['allowedAction_publish'] = false;
                }
                $versionArray['allowedAction_delete'] = $isRecordTypeAllowedToModify;
                // preview and editing of a deleted page won't work ;)
                $versionArray['allowedAction_view'] = !$isDeletedPage && $viewUrl;
                $versionArray['allowedAction_edit'] = $isRecordTypeAllowedToModify && !$isDeletedPage;
                $versionArray['allowedAction_versionPageOpen'] = $this->isPageModuleAllowed() && !$isDeletedPage;
                $versionArray['state_Workspace'] = $recordState;
                $versionArray['hasChanges'] = $recordState !== 'unchanged';
                $versionArray['urlToPage'] = (string)$this->uriBuilder->buildUriFromRoute('workspaces_admin', [
                    'workspace' => $backendUser->workspace,
                    'id' => $record['pid'] ?? 0,
                ]);
                // Allows to be overridden by PSR-14 event to dynamically modify the expand / collapse state
                $versionArray['expanded'] = false;

                if ($filterTxt === '') {
                    // Add row if there is no additional 'filter text'
                    $dataArray[(string)$versionArray['id']] = $versionArray;
                    continue;
                }
                // If there is a 'filter text', add row only if there is a match in a static list of keys.
                foreach ($searchDataKeys as $dataKey) {
                    if (stripos((string)$versionArray[$dataKey], $filterTxt) !== false) {
                        $dataArray[(string)$versionArray['id']] = $versionArray;
                        break;
                    }
                }
            }

            $event = new AfterCompiledCacheableDataForWorkspaceEvent($this, $dataArray, $versions);
            $this->eventDispatcher->dispatch($event);
            $dataArray = $event->getData();
            $versions = $event->getVersions();
            // Enrich elements after everything has been processed:
            foreach ($dataArray as &$element) {
                $identifier = $element['table'] . ':' . $element['t3ver_oid'];
                $messages = $this->integrityService->getIssueMessages($integrityIssues, $identifier);
                $element['integrity'] = [
                    'status' => $this->integrityService->getStatusRepresentation($integrityIssues, $identifier),
                    'messages' => htmlspecialchars(implode('<br>', $messages)),
                ];
            }
        }

        $event = new AfterDataGeneratedForWorkspaceEvent($this, $dataArray, $versions);
        $this->eventDispatcher->dispatch($event);
        $dataArray = $event->getData();
        $event = new SortVersionedDataEvent($this, $dataArray, 'label_Workspace', 'ASC');
        $this->eventDispatcher->dispatch($event);
        $dataArray = $event->getData();
        return $this->resolveDataArrayDependencies($dataArray);
    }

    /**
     * @param WorkspaceStage[] $stages
     */
    protected function versionIsModified(array $stages, CombinedRecord $combinedRecord): bool
    {
        $params = new \stdClass();
        $params->stage = (int)$combinedRecord->getVersionRecord()->getRow()['t3ver_stage'];
        $params->t3ver_oid = $combinedRecord->getLiveRecord()->getUid();
        $params->table = $combinedRecord->getLiveRecord()->getTable();
        $params->uid = $combinedRecord->getVersionRecord()->getUid();
        // @todo: Refactor. It is odd this calls the huge getRowDetails() method when only the 'diff' array section is needed.
        $result = $this->getRowDetails($stages, $params);
        return !empty($result['data'][0]['diff']);
    }

    /**
     * Resolves dependencies of nested structures
     * and sort data elements considering these dependencies.
     */
    protected function resolveDataArrayDependencies(array $dataArray): array
    {
        $collectionService = $this->getDependencyCollectionService();
        $dependencyResolver = $collectionService->getDependencyResolver();
        foreach ($dataArray as $dataElement) {
            $dependencyResolver->addElement($dataElement['table'], $dataElement['uid']);
        }
        return $collectionService->process($dataArray);
    }

    /**
     * Gets the data array by considering the page to be shown in the grid view.
     */
    protected function getDataArray(array $dataArray, int $start, int $limit): array
    {
        // Ensure that there are numerical indexes
        $dataArray = array_values($dataArray);
        $dataArrayCount = count($dataArray);
        $start = $this->calculateStartWithCollections($dataArray, $start);
        $end = min($start + $limit, $dataArrayCount);
        // Fill the data array part
        $dataArrayPart = $this->fillDataArrayPart($dataArray, $start, $end);
        $event = new GetVersionedDataEvent($this, $dataArray, $start, $limit, $dataArrayPart);
        $this->eventDispatcher->dispatch($event);
        return $event->getDataArrayPart();
    }

    /**
     * Checks whether the configured page module can be accessed by the current user.
     * Note that this does not check whether a custom page module is configured correctly.
     */
    protected function isPageModuleAllowed(): bool
    {
        return $this->moduleProvider->accessGranted('web_layout', $this->getBackendUser());
    }

    /**
     * Gets the state of a given state value.
     *
     * @param int $stateId        stateId of offline record
     * @param bool $hiddenOnline  hidden status of online record
     * @param bool $hiddenOffline hidden status of offline record
     * @param bool $hasDiff    whether the version has any changes
     */
    protected function workspaceState(int $stateId, bool $hiddenOnline = false, bool $hiddenOffline = false, bool $hasDiff = true): string
    {
        $hiddenState = null;
        if (!$hiddenOnline && $hiddenOffline) {
            $hiddenState = 'hidden';
        } elseif ($hiddenOnline && !$hiddenOffline) {
            $hiddenState = 'unhidden';
        }
        switch (VersionState::tryFrom($stateId)) {
            case VersionState::NEW_PLACEHOLDER:
                $state = 'new';
                break;
            case VersionState::DELETE_PLACEHOLDER:
                $state = 'deleted';
                break;
            case VersionState::MOVE_POINTER:
                $state = 'moved';
                break;
            default:
                if (!$hasDiff) {
                    $state =  'unchanged';
                } else {
                    $state = ($hiddenState ?: 'modified');
                }
        }

        return $state;
    }

    /**
     * Gets the language value (system language uid) of a given database record
     *
     * @param array $record Database record
     */
    protected function getLanguageValue(TcaSchema $schema, array $record): int
    {
        $languageValue = 0;
        if ($schema->isLanguageAware()) {
            $languageField = $schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName();
            if (!empty($record[$languageField])) {
                $languageValue = (int)$record[$languageField];
            }
        }
        return $languageValue;
    }

    /**
     * Gets a named value of an available system language
     *
     * @param int $id system language uid
     * @param int $pageId page id of a site
     * @param string $key Name of the value to be fetched (e.g. title)
     */
    protected function getSystemLanguageValue(int $id, int $pageId, string $key): ?string
    {
        $value = null;
        $systemLanguages = $this->translationConfigurationProvider->getSystemLanguages($pageId);
        if (!empty($systemLanguages[$id][$key])) {
            $value = $systemLanguages[$id][$key];
        }
        return $value;
    }

    /**
     * Calculate the "real" start value by also taking collection children into account
     */
    protected function calculateStartWithCollections(array $dataArray, int $start): int
    {
        // The recordsCount is the real record items count, while the
        // parentRecordsCount only takes the parent records into account
        $recordsCount = $parentRecordsCount = 0;
        while ($parentRecordsCount < $start) {
            // As soon as no more item exists in the dataArray, the loop needs to end
            // prematurely to prevent invalid items which would may led to some unexpected
            // behaviour. Note: This usually should never happen since these records must
            // exists => As they were responsible for increasing the start value. However to
            // prevent errors in case multiple different users manipulate the records count
            // somehow simultaneously, we apply this check to be save.
            if (!isset($dataArray[$recordsCount])) {
                break;
            }
            // Loop over the dataArray until we found enough parent records
            $item = $dataArray[$recordsCount];
            if (($item['Workspaces_CollectionLevel'] ?? 0) === 0) {
                // In case the current parent record is the last one ($start is reached),
                // ensure its collection children are counted as well.
                if (($parentRecordsCount + 1) === $start && (int)($item['Workspaces_Collection'] ?? 0) !== 0) {
                    // By not providing the third parameter, we only count the collection children recursively
                    $this->addCollectionChildrenRecursive($dataArray, $item, $recordsCount);
                }
                // Only increase the parent records count in case $item is a parent record
                $parentRecordsCount++;
            }
            // Always increase the record items count
            $recordsCount++;
        }
        return $recordsCount;
    }

    /**
     * Fill the data array part until enough parent records are found ($end is reached).
     * Also adds the related collection children, but without increasing the corresponding
     * parent records count.
     */
    private function fillDataArrayPart(array $dataArray, int $start, int $end): array
    {
        // Initialize empty data array part
        $dataArrayPart = [];
        // The recordsCount is the real record items count, while the
        // parentRecordsCount only takes the parent records into account.
        $itemsCount = $parentRecordsCount = $start;
        while ($parentRecordsCount < $end) {
            // As soon as no more item exists in the dataArray, the loop needs to end
            // prematurely to prevent invalid items which would trigger JavaScript errors.
            if (!isset($dataArray[$itemsCount])) {
                break;
            }
            // Loop over the dataArray until we found enough parent records
            $item = $dataArray[$itemsCount];
            // Add the item to the $dataArrayPart
            $dataArrayPart[] = $item;
            if (($item['Workspaces_CollectionLevel'] ?? 0) === 0) {
                // In case the current parent record is the last one ($end is reached),
                // ensure its collection children are added as well.
                if (($parentRecordsCount + 1) === $end && (int)($item['Workspaces_Collection'] ?? 0) !== 0) {
                    // Add collection children recursively
                    $this->addCollectionChildrenRecursive($dataArray, $item, $itemsCount, $dataArrayPart);
                }
                // Only increase the parent records count in case $item is a parent record
                $parentRecordsCount++;
            }
            // Always increase the record items count
            $itemsCount++;
        }
        return $dataArrayPart;
    }

    /**
     * Add collection children to the data array part recursively
     */
    protected function addCollectionChildrenRecursive(array $dataArray, array $item, int &$recordsCount, array &$dataArrayPart = []): void
    {
        $collectionParent = (string)$item['Workspaces_CollectionCurrent'];
        foreach ($dataArray as $element) {
            if ((string)($element['Workspaces_CollectionParent'] ?? '') === $collectionParent) {
                // Increase the "real" record items count
                $recordsCount++;
                // Fetch the children from the dataArray using the current record items
                // count. This is possible since the dataArray is already sorted.
                $child = $dataArray[$recordsCount];
                // In case $dataArrayPart is not given, just count the item
                if ($dataArrayPart !== []) {
                    // Add the children
                    $dataArrayPart[] = $child;
                }
                // In case the $child is also a collection, add its children as well (recursively)
                if ((int)($child['Workspaces_Collection'] ?? 0) !== 0) {
                    $this->addCollectionChildrenRecursive($dataArray, $child, $recordsCount, $dataArrayPart);
                }
            }
        }
    }

    protected function formatValue(string $table, string $fieldName, string $value, int $uid, array $tcaConfiguration, array $fullRow): string
    {
        if (($tcaConfiguration['type'] ?? '') === 'flex') {
            return $this->flexFormValueFormatter->format($table, $fieldName, $value, $uid, $tcaConfiguration);
        }
        return (string)BackendUtility::getProcessedValue($table, $fieldName, $value, 0, true, false, $uid, true, (int)$fullRow['pid'], $fullRow);
    }

    /**
     * Gets the fields suitable for being displayed in new and delete diff views
     */
    protected function getSuitableFields(string $table, array $row): array
    {
        // @todo Usage of searchableSchemaFieldsCollector seems like a misuse here, or at least it's unexpected
        return $this->searchableSchemaFieldsCollector->getUniqueFieldList(
            $table,
            $this->visibleSchemaFieldsCollector->getFieldNames($table, $row),
            false
        );
    }

    /**
     * @return FileReference[]
     */
    protected function resolveFileReferences(string $tableName, string $fieldName, FieldTypeInterface $fieldTypeInformation, array $element, int $workspaceId): array
    {
        $configuration = $fieldTypeInformation->getConfiguration();
        if (($configuration['type'] ?? '') !== 'file') {
            return [];
        }
        $fileReferences = [];
        $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
        $relationHandler->setWorkspaceId($workspaceId);
        $relationHandler->initializeForField(
            $tableName,
            $configuration,
            $element,
            $element[$fieldName],
        );
        $relationHandler->processDeletePlaceholder();
        $referenceUids = $relationHandler->tableArray[$configuration['foreign_table']] ?? [];
        foreach ($referenceUids as $referenceUid) {
            try {
                $fileReference = $this->resourceFactory->getFileReferenceObject($referenceUid, [], $workspaceId === 0);
                $fileReferences[$fileReference->getUid()] = $fileReference;
            } catch (FileDoesNotExistException) {
                // Just catch the exception here. There is nothing an editor or even admin could do.
            } catch (\InvalidArgumentException $e) {
                // The storage does not exist anymore. Log the exception message for admins as they maybe can restore the storage.
                $this->logger->error($e->getMessage(), [
                    'table' => $tableName,
                    'fieldName' => $fieldName,
                    'referenceUid' => $referenceUid,
                    'exception' => $e,
                ]);
            }
        }
        return $fileReferences;
    }

    /**
     * Prepares difference view for file references.
     *
     * @param FileReference[] $liveFileReferences
     * @param FileReference[] $versionFileReferences
     */
    protected function prepareFileReferenceDifferences(array $liveFileReferences, array $versionFileReferences, bool $useThumbnails): ?array
    {
        $randomValue = StringUtility::getUniqueId('file');
        $liveValues = [];
        $versionValues = [];
        $candidates = [];
        $substitutes = [];
        // Process live references
        foreach ($liveFileReferences as $liveFileReference) {
            $identifierWithRandomValue = $randomValue . '__' . $liveFileReference->getUid() . '__' . $randomValue;
            $candidates[$identifierWithRandomValue] = $liveFileReference;
            $liveValues[] = $identifierWithRandomValue;
        }
        // Process version references
        foreach ($versionFileReferences as $versionFileReference) {
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
        $differences = $this->diffUtility->diff(strip_tags($liveInformation), strip_tags($versionInformation));
        $liveInformation = str_replace(array_keys($substitutes), array_values($substitutes), trim($liveInformation));
        $differences = str_replace(array_keys($substitutes), array_values($substitutes), trim($differences));
        return [
            'live' => $liveInformation,
            'differences' => $differences,
        ];
    }

    protected function getDependencyCollectionService(): CollectionService
    {
        return GeneralUtility::makeInstance(CollectionService::class);
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
