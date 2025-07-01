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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Workspaces\Authorization\WorkspacePublishGate;
use TYPO3\CMS\Workspaces\Controller\Remote\RemoteServer;
use TYPO3\CMS\Workspaces\Domain\Model\CombinedRecord;
use TYPO3\CMS\Workspaces\Event\AfterCompiledCacheableDataForWorkspaceEvent;
use TYPO3\CMS\Workspaces\Event\AfterDataGeneratedForWorkspaceEvent;
use TYPO3\CMS\Workspaces\Event\GetVersionedDataEvent;
use TYPO3\CMS\Workspaces\Event\SortVersionedDataEvent;
use TYPO3\CMS\Workspaces\Preview\PreviewUriBuilder;
use TYPO3\CMS\Workspaces\Service\Dependency\CollectionService;

/**
 * @internal
 */
#[Autoconfigure(public: true)]
class GridDataService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected ?IntegrityService $integrityService;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ModuleProvider $moduleProvider,
        private readonly WorkspacePublishGate $workspacePublishGate,
        private readonly UriBuilder $uriBuilder,
    ) {}

    /**
     * Generates grid list array from given versions.
     *
     * @param array $versions All records uids etc. First key is table name, second key incremental integer.
     *                        Records are associative arrays with uid and t3ver_oid fields. The pid of the online
     *                        record is found as "livepid" the pid of the offline record is found in "wspid"
     * @param \stdClass $parameter Parameters as submitted by JavaScript component
     * @return array Version record information (filtered, sorted and limited)
     */
    public function generateGridListFromVersions(array $versions, \stdClass $parameter): array
    {
        // Read the given parameters from grid. If the parameter is not set use default values.
        $filterTxt = $parameter->filterTxt ?? '';
        $start = isset($parameter->start) ? (int)$parameter->start : 0;
        $limit = isset($parameter->limit) ? (int)$parameter->limit : 30;
        $dataArray = $this->generateDataArray($versions, $filterTxt);
        return [
            // Only count parent records for pagination
            'total' => count(array_filter($dataArray, static function ($element) {
                return (int)($element['Workspaces_CollectionLevel'] ?? 0) === 0;
            })),
            'data' => $this->getDataArray($dataArray, $start, $limit),
        ];
    }

    /**
     * Generates grid list array from given versions.
     *
     * @param array $versions All available version records
     * @param string $filterTxt Text to be used to filter record result
     */
    protected function generateDataArray(array $versions, string $filterTxt): array
    {
        $backendUser = $this->getBackendUser();
        $workspaceAccess = $backendUser->checkWorkspace($backendUser->workspace);
        $swapStage = ($workspaceAccess['publish_access'] ?? 0) & WorkspaceService::PUBLISH_ACCESS_ONLY_IN_PUBLISH_STAGE ? StagesService::STAGE_PUBLISH_ID : StagesService::STAGE_EDIT_ID;

        $isAllowedToPublish = $this->workspacePublishGate->isGranted($backendUser, $backendUser->workspace);
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $stagesObj = GeneralUtility::makeInstance(StagesService::class);
        $defaultGridColumns = [
            'Workspaces_Collection' => 0,
            'Workspaces_CollectionLevel' => 0,
            'Workspaces_CollectionParent' => '',
            'Workspaces_CollectionCurrent' => '',
            'Workspaces_CollectionChildren' => 0,
        ];
        $dataArray = [];
        foreach ($versions as $table => $records) {
            $table = (string)$table;
            $hiddenField = $this->getTcaEnableColumnsFieldName($table, 'disabled');
            $isRecordTypeAllowedToModify = $backendUser->check('tables_modify', $table);

            foreach ($records as $record) {
                $origRecord = (array)BackendUtility::getRecord($table, $record['t3ver_oid']);
                $versionRecord = (array)BackendUtility::getRecord($table, $record['uid']);
                $combinedRecord = CombinedRecord::createFromArrays($table, $origRecord, $versionRecord);
                $hasDiff = $this->versionIsModified($combinedRecord);
                $this->getIntegrityService()->checkElement($combinedRecord);

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
                $viewUrl = GeneralUtility::makeInstance(PreviewUriBuilder::class)->buildUriForElement($table, (int)$record['uid'], $origRecord, $versionRecord);
                $workspaceRecordLabel = BackendUtility::getRecordTitle($table, $versionRecord);
                $iconWorkspace = $iconFactory->getIconForRecord($table, $versionRecord, IconSize::SMALL);
                [$pathWorkspaceCropped, $pathWorkspace] = BackendUtility::getRecordPath((int)$record['wspid'], '', 15, 1000);
                $calculatedT3verOid = $record['t3ver_oid'];
                if (VersionState::tryFrom($record['t3ver_state'] ?? 0) === VersionState::NEW_PLACEHOLDER) {
                    // If we're dealing with a 'new' record, this one has no t3ver_oid. On publish, there is no
                    // live counterpart, but the publish methods later need a live uid to publish to. We thus
                    // use the uid as t3ver_oid here to be transparent on javascript side.
                    $calculatedT3verOid = $record['uid'];
                }

                $versionArray = [];
                $versionArray['table'] = $table;
                $versionArray['id'] = $table . ':' . $record['uid'];
                $versionArray['uid'] = $record['uid'];
                $versionArray = array_merge($versionArray, $defaultGridColumns);
                $versionArray['label_Workspace'] = htmlspecialchars($workspaceRecordLabel);
                $versionArray['label_Workspace_crop'] = htmlspecialchars(GeneralUtility::fixed_lgd_cs($workspaceRecordLabel, (int)$backendUser->uc['titleLen']));
                $versionArray['label_Stage'] = htmlspecialchars($stagesObj->getStageTitle((int)$versionRecord['t3ver_stage']));
                $tempStage = $stagesObj->getNextStage($versionRecord['t3ver_stage']);
                $versionArray['label_nextStage'] = htmlspecialchars($stagesObj->getStageTitle((int)$tempStage['uid']));
                $versionArray['value_nextStage'] = (int)$tempStage['uid'];
                $tempStage = $stagesObj->getPrevStage($versionRecord['t3ver_stage']);
                $versionArray['label_prevStage'] = htmlspecialchars($stagesObj->getStageTitle((int)($tempStage['uid'] ?? 0)));
                $versionArray['value_prevStage'] = (int)($tempStage['uid'] ?? 0);
                $versionArray['path_Live'] = htmlspecialchars(BackendUtility::getRecordPath($record['livepid'], '', 999));
                $versionArray['path_Workspace'] = htmlspecialchars($pathWorkspace);
                $versionArray['path_Workspace_crop'] = htmlspecialchars($pathWorkspaceCropped);
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
                $languageValue = $this->getLanguageValue($table, $versionRecord);
                $versionArray['languageValue'] = $languageValue;
                $versionArray['language'] = [
                    'icon' => $iconFactory->getIcon($this->getSystemLanguageValue($languageValue, $pageId, 'flagIcon') ?? 'empty-empty', IconSize::SMALL)->getIdentifier(),
                    'title' => $this->getSystemLanguageValue($languageValue, $pageId, 'title'),
                    'title_crop' => htmlspecialchars(GeneralUtility::fixed_lgd_cs($this->getSystemLanguageValue($languageValue, $pageId, 'title'), (int)$backendUser->uc['titleLen'])),
                ];
                if ($isAllowedToPublish && $swapStage !== StagesService::STAGE_EDIT_ID && (int)$versionRecord['t3ver_stage'] === $swapStage) {
                    $versionArray['allowedAction_publish'] = $isRecordTypeAllowedToModify && $stagesObj->isNextStageAllowedForUser($swapStage);
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

                if ($filterTxt == '' || $this->isFilterTextInVisibleColumns($filterTxt, $versionArray)) {
                    $dataArray[(string)$versionArray['id']] = $versionArray;
                }
            }

            $event = new AfterCompiledCacheableDataForWorkspaceEvent($this, $dataArray, $versions);
            $this->eventDispatcher->dispatch($event);
            $dataArray = $event->getData();
            $versions = $event->getVersions();
            // Enrich elements after everything has been processed:
            foreach ($dataArray as &$element) {
                $identifier = $element['table'] . ':' . $element['t3ver_oid'];
                $messages = $this->getIntegrityService()->getIssueMessages($identifier);
                $element['integrity'] = [
                    'status' => $this->getIntegrityService()->getStatusRepresentation($identifier),
                    'messages' => htmlspecialchars(implode('<br>', $messages)),
                ];
            }
        }

        $event = new AfterDataGeneratedForWorkspaceEvent($this, $dataArray, $versions);
        $this->eventDispatcher->dispatch($event);
        $dataArray = $event->getData();
        $dataArray = $this->sortDataArray($dataArray);
        return $this->resolveDataArrayDependencies($dataArray);
    }

    protected function versionIsModified(CombinedRecord $combinedRecord): bool
    {
        $remoteServer = GeneralUtility::makeInstance(RemoteServer::class);

        $params = new \stdClass();
        $params->stage = (int)$combinedRecord->getVersionRecord()->getRow()['t3ver_stage'];
        $params->t3ver_oid = $combinedRecord->getLiveRecord()->getUid();
        $params->table = $combinedRecord->getLiveRecord()->getTable();
        $params->uid = $combinedRecord->getVersionRecord()->getUid();

        $result = $remoteServer->getRowDetails($params);
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

    protected function sortDataArray(array $dataArray): array
    {
        uasort($dataArray, function ($a, $b) {
            if ($a['Workspaces_CollectionLevel'] === 0 && $b['Workspaces_CollectionLevel'] === 0
                || $a['Workspaces_CollectionParent'] === $b['Workspaces_CollectionParent']
            ) {
                // Early returns when elements are not sortable: Only elements on the first level (0) or
                // below the same parent element are directly sortable.
                return 0;
            }
            // First sort by using the page-path in current workspace
            $pathSortingResult = strcasecmp($a['path_Workspace'], $b['path_Workspace']);
            if ($pathSortingResult !== 0) {
                return $pathSortingResult;
            }
            if ($a['label_Workspace'] === $b['label_Workspace']) {
                return 0;
            }
            return strcasecmp($a['label_Workspace'], $b['label_Workspace']);
        });
        // Trigger an event for extensibility
        $event = new SortVersionedDataEvent($this, $dataArray, 'label_Workspace', 'ASC');
        $this->eventDispatcher->dispatch($event);
        return $event->getData();
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
     * Determines whether the text used to filter the results is part of
     * a column that is visible in the grid view.
     */
    protected function isFilterTextInVisibleColumns(string $filterText, array $versionArray): bool
    {
        $backendUser = $this->getBackendUser();
        if (is_array($backendUser->uc['moduleData']['Workspaces'][$backendUser->workspace]['columns'] ?? false)) {
            $visibleColumns = $backendUser->uc['moduleData']['Workspaces'][$backendUser->workspace]['columns'];
        } else {
            $visibleColumns = [
                'workspace_Formated_Tstamp' => ['hidden' => 0],
                'change' => ['hidden' => 0],
                'path_Workspace' => ['hidden' => 0],
                'path_Live' => ['hidden' => 0],
                'label_Stage' => ['hidden' => 0],
                'label_Workspace' => ['hidden' => 0],
            ];
        }
        foreach ($visibleColumns as $column => $value) {
            if (isset($value['hidden']) && isset($versionArray[$column])) {
                if ($value['hidden'] == 0) {
                    switch ($column) {
                        case 'workspace_Tstamp':
                            if (stripos($versionArray['workspace_Formated_Tstamp'], $filterText) !== false) {
                                return true;
                            }
                            break;
                        case 'change':
                            if (stripos((string)$versionArray[$column], str_replace('%', '', $filterText)) !== false) {
                                return true;
                            }
                            break;
                        default:
                            if (stripos((string)$versionArray[$column], $filterText) !== false) {
                                return true;
                            }
                    }
                }
            }
        }
        return false;
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
     * Gets the field name of the enable-columns as defined in $TCA.
     *
     * @param string $table Name of the table
     * @param string $type Type to be fetches (e.g. 'disabled', 'starttime', 'endtime', 'fe_group)
     * @return string|null The accordant field name or NULL if not defined
     */
    protected function getTcaEnableColumnsFieldName(string $table, string $type): ?string
    {
        $fieldName = null;

        if (!empty($GLOBALS['TCA'][$table]['ctrl']['enablecolumns'][$type])) {
            $fieldName = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns'][$type];
        }

        return $fieldName;
    }

    /**
     * Gets the language value (system language uid) of a given database record
     *
     * @param string $table Name of the table
     * @param array $record Database record
     * @return int
     */
    protected function getLanguageValue(string $table, array $record): int
    {
        $languageValue = 0;
        if (BackendUtility::isTableLocalizable($table)) {
            $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
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
     * @return string|null
     * @see getSystemLanguages
     */
    protected function getSystemLanguageValue(int $id, int $pageId, string $key): ?string
    {
        $value = null;
        $systemLanguages = $this->getSystemLanguages($pageId);
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

    /**
     * Gets all available system languages.
     */
    protected function getSystemLanguages(int $pageId): array
    {
        return GeneralUtility::makeInstance(TranslationConfigurationProvider::class)->getSystemLanguages($pageId);
    }

    /**
     * Gets an instance of the integrity service.
     */
    protected function getIntegrityService(): IntegrityService
    {
        if (!isset($this->integrityService)) {
            $this->integrityService = GeneralUtility::makeInstance(IntegrityService::class);
        }
        return $this->integrityService;
    }

    protected function getDependencyCollectionService(): CollectionService
    {
        return GeneralUtility::makeInstance(CollectionService::class);
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
