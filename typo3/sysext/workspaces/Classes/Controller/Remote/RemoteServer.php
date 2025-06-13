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
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Backend\Avatar\Avatar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\ValueFormatter\FlexFormValueFormatter;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
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
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Schema\VisibleSchemaFieldsCollector;
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
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
#[Autoconfigure(public: true)]
readonly class RemoteServer
{
    public function __construct(
        protected GridDataService $gridDataService,
        protected StagesService $stagesService,
        protected WorkspaceService $workspaceService,
        protected EventDispatcherInterface $eventDispatcher,
        protected FlexFormValueFormatter $flexFormValueFormatter,
        private DiffUtility $diffUtility,
        protected IconFactory $iconFactory,
        protected Avatar $avatar,
        protected ConnectionPool $connectionPool,
        protected SearchableSchemaFieldsCollector $searchableSchemaFieldsCollector,
        protected VisibleSchemaFieldsCollector $visibleSchemaFieldsCollector,
        private IntegrityService $integrityService,
        private TcaSchemaFactory $tcaSchemaFactory,
        private HistoryService $historyService,
        private LoggerInterface $logger,
    ) {}

    /**
     * Checks integrity of elements before performing actions on them.
     */
    public function checkIntegrity(\stdClass $parameters): array
    {
        $issues = $this->integrityService->check($this->getAffectedElements($parameters));
        return [
            'result' => $this->integrityService->getStatusRepresentation($issues),
        ];
    }

    /**
     * Get List of workspace changes
     */
    public function getWorkspaceInfos(\stdClass $parameter, ServerRequestInterface $request): array
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
            (int)$parameter->stage,
            $pageId,
            (int)$parameter->depth,
            'tables_select',
            $parameter->language !== null ? (int)$parameter->language : null
        );
        $data = $this->gridDataService->generateGridListFromVersions($versions, $parameter, $this->getCurrentWorkspace());
        return $data;
    }

    /**
     * Fetch further information to current selected workspace record.
     */
    public function getRowDetails(\stdClass $parameter): array
    {
        $table = $parameter->table;
        $schema = $this->tcaSchemaFactory->get($table);
        $diffReturnArray = [];
        $liveReturnArray = [];
        $plainLiveRecord = $liveRecord = (array)BackendUtility::getRecord($table, $parameter->t3ver_oid);
        $plainVersionRecord = $versionRecord = (array)BackendUtility::getRecord($table, $parameter->uid);
        $versionState = VersionState::tryFrom($versionRecord['t3ver_state'] ?? 0);
        $iconWorkspace = $this->iconFactory->getIconForRecord($table, $versionRecord, IconSize::SMALL);
        $stagePosition = $this->stagesService->getPositionOfCurrentStage($parameter->stage);
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

            // Get the field's label. If not available, use the field name
            $fieldTitle = $this->getLanguageService()->sL(BackendUtility::getItemLabel($table, $fieldName));
            if (empty($fieldTitle)) {
                $fieldTitle = $fieldName;
            }
            $fieldTypeInformation = $schema->getField($fieldName);
            // Gets the TCA configuration for the current field
            $configuration = $fieldTypeInformation->getConfiguration();
            // check for exclude fields
            $isFieldExcluded = $fieldTypeInformation->supportsAccessControl();
            if ($this->getBackendUser()->isAdmin() || !$isFieldExcluded || GeneralUtility::inList($this->getBackendUser()->groupData['non_exclude_fields'], $table . ':' . $fieldName)) {
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
                        $liveFileReferences = $this->resolveFileReferences(
                            $table,
                            $fieldName,
                            $fieldTypeInformation,
                            $liveRecord,
                            0
                        );
                        $versionFileReferences = $this->resolveFileReferences(
                            $table,
                            $fieldName,
                            $fieldTypeInformation,
                            $versionRecord,
                            $this->getCurrentWorkspace()
                        );
                    } else {
                        $liveFileReferences = [];
                        $versionFileReferences = [];
                    }
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
                    $newOrDeleteRecord[$fieldName] = $this->formatValue($table, $fieldName, (string)$liveRecord[$fieldName], $liveRecord['uid'], $configuration, $plainLiveRecord);

                    // Don't add empty fields
                    if ($newOrDeleteRecord[$fieldName] === '') {
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

        $versionDifferencesEvent = $this->eventDispatcher->dispatch(
            new ModifyVersionDifferencesEvent($diffReturnArray, $liveReturnArray, $parameter)
        );

        $history = $this->historyService->getHistory($table, $parameter->t3ver_oid);
        $stageChanges = $this->historyService->getStageChanges($table, (int)$parameter->t3ver_oid);
        $commentsForRecord = $this->getCommentsForRecord($stageChanges);

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

    protected function formatValue(string $table, string $fieldName, string $value, int $uid, array $tcaConfiguration, array $fullRow): string
    {
        if (($tcaConfiguration['type'] ?? '') === 'flex') {
            return $this->flexFormValueFormatter->format($table, $fieldName, $value, $uid, $tcaConfiguration);
        }
        return (string)BackendUtility::getProcessedValue($table, $fieldName, $value, 0, true, false, $uid, true, (int)$fullRow['pid'], $fullRow);
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

    /**
     * Prepares all comments of the stage change history entries for returning the JSON structure
     */
    protected function getCommentsForRecord(array $historyEntries): array
    {
        $allStageChanges = [];
        foreach ($historyEntries as $entry) {
            $preparedEntry = [];
            $beUserRecord = BackendUtility::getRecord('be_users', $entry['userid']);
            $preparedEntry['stage_title'] = htmlspecialchars($this->stagesService->getStageTitle((int)$entry['history_data']['next']));
            $preparedEntry['previous_stage_title'] = htmlspecialchars($this->stagesService->getStageTitle((int)$entry['history_data']['current']));
            $preparedEntry['user_uid'] = (int)$entry['userid'];
            $preparedEntry['user_username'] = is_array($beUserRecord) ? htmlspecialchars($beUserRecord['username']) : '';
            $preparedEntry['tstamp'] = htmlspecialchars(BackendUtility::datetime($entry['tstamp']));
            $preparedEntry['user_comment'] = nl2br(htmlspecialchars($entry['history_data']['comment']));
            $preparedEntry['user_avatar'] = $beUserRecord ? $this->avatar->render($beUserRecord) : '';
            $allStageChanges[] = $preparedEntry;
        }
        return $allStageChanges;
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
     * Gets affected elements on publishing/swapping actions.
     * Affected elements have a dependency, e.g. translation overlay
     * and the default origin record - thus, the default record would be
     * affected if the translation overlay shall be published.
     *
     * @return CombinedRecord[]
     */
    protected function getAffectedElements(\stdClass $parameters): array
    {
        $affectedElements = [];
        if ($parameters->type === 'selection') {
            foreach ((array)$parameters->selection as $element) {
                $affectedElements[] = CombinedRecord::create($element->table, (int)$element->liveId, (int)$element->versionId);
            }
        }
        return $affectedElements;
    }

    /**
     * Gets the current workspace ID.
     */
    protected function getCurrentWorkspace(): int
    {
        return $this->workspaceService->getCurrentWorkspace();
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
    protected function resolveFileReferences(string $tableName, string $fieldName, FieldTypeInterface $fieldTypeInformation, array $element, ?int $workspaceId = null): array
    {
        $configuration = $fieldTypeInformation->getConfiguration();
        if (($configuration['type'] ?? '') !== 'file') {
            return [];
        }

        $fileReferences = [];
        $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
        if ($workspaceId !== null) {
            $relationHandler->setWorkspaceId($workspaceId);
        }
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
                $fileReference = GeneralUtility::makeInstance(ResourceFactory::class)->getFileReferenceObject(
                    $referenceUid,
                    [],
                    $workspaceId === 0
                );
                $fileReferences[$fileReference->getUid()] = $fileReference;
            } catch (FileDoesNotExistException) {
                /*
                We just catch the exception here
                Reasoning: There is nothing an editor or even admin could do
                */
            } catch (\InvalidArgumentException $e) {
                /*
                The storage does not exist anymore
                Log the exception message for admins as they maybe can restore the storage
                */
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
}
