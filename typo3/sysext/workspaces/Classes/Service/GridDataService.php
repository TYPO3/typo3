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

namespace TYPO3\CMS\Workspaces\Service;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Workspaces\Controller\Remote\RemoteServer;
use TYPO3\CMS\Workspaces\Domain\Model\CombinedRecord;
use TYPO3\CMS\Workspaces\Event\AfterCompiledCacheableDataForWorkspaceEvent;
use TYPO3\CMS\Workspaces\Event\AfterDataGeneratedForWorkspaceEvent;
use TYPO3\CMS\Workspaces\Event\GetVersionedDataEvent;
use TYPO3\CMS\Workspaces\Event\SortVersionedDataEvent;
use TYPO3\CMS\Workspaces\Preview\PreviewUriBuilder;
use TYPO3\CMS\Workspaces\Service\Dependency\CollectionService;

/**
 * Grid data service
 */
class GridDataService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const GridColumn_Collection = 'Workspaces_Collection';
    public const GridColumn_CollectionLevel = 'Workspaces_CollectionLevel';
    public const GridColumn_CollectionParent = 'Workspaces_CollectionParent';
    public const GridColumn_CollectionCurrent = 'Workspaces_CollectionCurrent';
    public const GridColumn_CollectionChildren = 'Workspaces_CollectionChildren';

    /**
     * Id of the current active workspace.
     *
     * @var int
     */
    protected $currentWorkspace;

    /**
     * Version record information (filtered, sorted and limited)
     *
     * @var array
     */
    protected $dataArray = [];

    /**
     * Name of the field used for sorting.
     *
     * @var string
     */
    protected $sort = '';

    /**
     * Direction used for sorting (ASC, DESC).
     *
     * @var string
     */
    protected $sortDir = '';

    /**
     * @var FrontendInterface
     */
    protected $workspacesCache;

    /**
     * @var IntegrityService|null
     */
    protected $integrityService;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly WorkspaceService $workspaceService,
        private readonly ModuleProvider $moduleProvider,
    ) {}

    /**
     * Generates grid list array from given versions.
     *
     * @param array $versions All records uids etc. First key is table name, second key incremental integer. Records are associative arrays with uid and t3ver_oid fields. The pid of the online record is found as "livepid" the pid of the offline record is found in "wspid
     * @param \stdClass $parameter Parameters as submitted by JavaScript component
     * @param int $currentWorkspace The current workspace
     * @return array Version record information (filtered, sorted and limited)
     * @throws \InvalidArgumentException
     */
    public function generateGridListFromVersions($versions, $parameter, $currentWorkspace, ServerRequestInterface $request)
    {
        // Read the given parameters from grid. If the parameter is not set use default values.
        $filterTxt = $parameter->filterTxt ?? '';
        $start = isset($parameter->start) ? (int)$parameter->start : 0;
        $limit = isset($parameter->limit) ? (int)$parameter->limit : 30;
        $this->sort = $parameter->sort ?? 't3ver_oid';
        $this->sortDir = $parameter->dir ?? 'ASC';
        if (is_int($currentWorkspace)) {
            $this->currentWorkspace = $currentWorkspace;
        } else {
            throw new \InvalidArgumentException('No such workspace defined', 1476048304);
        }
        $this->generateDataArray($versions, $filterTxt, $request);
        return [
            // Only count parent records for pagination
            'total' => count(array_filter($this->dataArray, static function ($element) {
                return (int)($element[self::GridColumn_CollectionLevel] ?? 0) === 0;
            })),
            'data' =>  $this->getDataArray($start, $limit),
        ];
    }

    /**
     * Generates grid list array from given versions.
     *
     * @param array $versions All available version records
     * @param string $filterTxt Text to be used to filter record result
     */
    protected function generateDataArray(array $versions, $filterTxt, ServerRequestInterface $request)
    {
        $backendUser = $this->getBackendUser();
        $workspaceAccess = $backendUser->checkWorkspace($backendUser->workspace);
        $swapStage = ($workspaceAccess['publish_access'] ?? 0) & 1 ? StagesService::STAGE_PUBLISH_ID : 0;
        $swapAccess = $backendUser->workspacePublishAccess($backendUser->workspace);
        $this->initializeWorkspacesCachingFramework();
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        // check for dataArray in cache
        if ($this->getDataArrayFromCache($versions, $filterTxt) === false) {
            $stagesObj = GeneralUtility::makeInstance(StagesService::class);
            $defaultGridColumns = [
                self::GridColumn_Collection => 0,
                self::GridColumn_CollectionLevel => 0,
                self::GridColumn_CollectionParent => '',
                self::GridColumn_CollectionCurrent => '',
                self::GridColumn_CollectionChildren => 0,
            ];
            foreach ($versions as $table => $records) {
                $table = (string)$table;
                $hiddenField = $this->getTcaEnableColumnsFieldName($table, 'disabled');
                $isRecordTypeAllowedToModify = $backendUser->check('tables_modify', $table);

                foreach ($records as $record) {
                    $origRecord = (array)BackendUtility::getRecord($table, $record['t3ver_oid']);
                    $versionRecord = (array)BackendUtility::getRecord($table, $record['uid']);
                    $combinedRecord = CombinedRecord::createFromArrays($table, $origRecord, $versionRecord);
                    $hasDiff = $this->versionIsModified($combinedRecord, $request);
                    $this->getIntegrityService()->checkElement($combinedRecord);

                    if ($hiddenField !== null) {
                        $recordState = $this->workspaceState($versionRecord['t3ver_state'], $origRecord[$hiddenField], $versionRecord[$hiddenField], $hasDiff);
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
                    $liveRecordLabel = BackendUtility::getRecordTitle($table, $origRecord);
                    $iconLive = $iconFactory->getIconForRecord($table, $origRecord, Icon::SIZE_SMALL);
                    $iconWorkspace = $iconFactory->getIconForRecord($table, $versionRecord, Icon::SIZE_SMALL);
                    [$pathWorkspaceCropped, $pathWorkspace] = BackendUtility::getRecordPath((int)$record['wspid'], '', 15, 1000);
                    $calculatedT3verOid = $record['t3ver_oid'];
                    if ((int)($record['t3ver_state'] ?? 0) === VersionState::NEW_PLACEHOLDER) {
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
                    $versionArray['label_Live'] = htmlspecialchars($liveRecordLabel);
                    $versionArray['label_Live_crop'] = htmlspecialchars(GeneralUtility::fixed_lgd_cs($liveRecordLabel, (int)$backendUser->uc['titleLen']));
                    $versionArray['label_Stage'] = htmlspecialchars($stagesObj->getStageTitle($versionRecord['t3ver_stage']));
                    $tempStage = $stagesObj->getNextStage($versionRecord['t3ver_stage']);
                    $versionArray['label_nextStage'] = htmlspecialchars($stagesObj->getStageTitle($tempStage['uid']));
                    $versionArray['value_nextStage'] = (int)$tempStage['uid'];
                    $tempStage = $stagesObj->getPrevStage($versionRecord['t3ver_stage']);
                    $versionArray['label_prevStage'] = htmlspecialchars($stagesObj->getStageTitle($tempStage['uid'] ?? 0));
                    $versionArray['value_prevStage'] = (int)($tempStage['uid'] ?? 0);
                    $versionArray['path_Live'] = htmlspecialchars(BackendUtility::getRecordPath($record['livepid'], '', 999));
                    $versionArray['path_Workspace'] = htmlspecialchars($pathWorkspace);
                    $versionArray['path_Workspace_crop'] = htmlspecialchars($pathWorkspaceCropped);
                    $versionArray['workspace_Title'] = htmlspecialchars($this->workspaceService->getWorkspaceTitle((int)$versionRecord['t3ver_wsid']));
                    $versionArray['workspace_Tstamp'] = $versionRecord['tstamp'];
                    $versionArray['lastChangedFormatted'] = BackendUtility::datetime($versionRecord['tstamp']);
                    $versionArray['t3ver_wsid'] = $versionRecord['t3ver_wsid'];
                    $versionArray['t3ver_oid'] = $calculatedT3verOid;
                    $versionArray['livepid'] = $record['livepid'];
                    $versionArray['stage'] = $versionRecord['t3ver_stage'];
                    $versionArray['icon_Live'] = $iconLive->getIdentifier();
                    $versionArray['icon_Live_Overlay'] = $iconLive->getOverlayIcon()?->getIdentifier() ?? '';
                    $versionArray['icon_Workspace'] = $iconWorkspace->getIdentifier();
                    $versionArray['icon_Workspace_Overlay'] = $iconWorkspace->getOverlayIcon()?->getIdentifier() ?? '';
                    $languageValue = $this->getLanguageValue($table, $versionRecord);
                    $versionArray['languageValue'] = $languageValue;
                    $versionArray['language'] = [
                        'icon' => $iconFactory->getIcon($this->getSystemLanguageValue($languageValue, $pageId, 'flagIcon'), Icon::SIZE_SMALL)->getIdentifier(),
                    ];
                    $versionArray['allowedAction_nextStage'] = $isRecordTypeAllowedToModify && $stagesObj->isNextStageAllowedForUser($versionRecord['t3ver_stage']);
                    $versionArray['allowedAction_prevStage'] = $isRecordTypeAllowedToModify && $stagesObj->isPrevStageAllowedForUser($versionRecord['t3ver_stage']);
                    if ($swapAccess && $swapStage != 0 && $versionRecord['t3ver_stage'] == $swapStage) {
                        $versionArray['allowedAction_publish'] = $isRecordTypeAllowedToModify && $stagesObj->isNextStageAllowedForUser($swapStage);
                    } elseif ($swapAccess && $swapStage == 0) {
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
                    $versionArray['hasChanges'] = ($recordState === 'unchanged') ? false : true;
                    // Allows to be overridden by PSR-14 event to dynamically modify the expand / collapse state
                    $versionArray['expanded'] = false;

                    if ($filterTxt == '' || $this->isFilterTextInVisibleColumns($filterTxt, $versionArray)) {
                        $versionIdentifier = $versionArray['id'];
                        $this->dataArray[$versionIdentifier] = $versionArray;
                    }
                }
            }

            // Trigger a PSR-14 event
            $event = new AfterCompiledCacheableDataForWorkspaceEvent($this, $this->dataArray, $versions);
            $this->eventDispatcher->dispatch($event);
            $this->dataArray = $event->getData();
            $versions = $event->getVersions();
            // Enrich elements after everything has been processed:
            foreach ($this->dataArray as &$element) {
                $identifier = $element['table'] . ':' . $element['t3ver_oid'];
                $element['integrity'] = [
                    'status' => $this->getIntegrityService()->getStatusRepresentation($identifier),
                    'messages' => htmlspecialchars((string)$this->getIntegrityService()->getIssueMessages($identifier, true)),
                ];
            }
            $this->setDataArrayIntoCache($versions, $filterTxt);
        }

        // Trigger a PSR-14 event
        $event = new AfterDataGeneratedForWorkspaceEvent($this, $this->dataArray, $versions);
        $this->eventDispatcher->dispatch($event);
        $this->dataArray = $event->getData();
        $this->sortDataArray();
        $this->resolveDataArrayDependencies();
    }

    protected function versionIsModified(CombinedRecord $combinedRecord, ServerRequestInterface $request): bool
    {
        $remoteServer = GeneralUtility::makeInstance(RemoteServer::class);

        $params = new \StdClass();
        $params->stage = $combinedRecord->getVersionRecord()->getRow()['t3ver_stage'];
        $params->t3ver_oid = $combinedRecord->getLiveRecord()->getUid();
        $params->table = $combinedRecord->getLiveRecord()->getTable();
        $params->uid = $combinedRecord->getVersionRecord()->getUid();

        $result = $remoteServer->getRowDetails($params, $request);
        return !empty($result['data'][0]['diff']);
    }

    /**
     * Resolves dependencies of nested structures
     * and sort data elements considering these dependencies.
     */
    protected function resolveDataArrayDependencies()
    {
        $collectionService = $this->getDependencyCollectionService();
        $dependencyResolver = $collectionService->getDependencyResolver();

        foreach ($this->dataArray as $dataElement) {
            $dependencyResolver->addElement($dataElement['table'], $dataElement['uid']);
        }

        $this->dataArray = $collectionService->process($this->dataArray);
    }

    /**
     * Gets the data array by considering the page to be shown in the grid view.
     *
     * @param int $start
     * @param int $limit
     * @return array
     */
    protected function getDataArray($start, $limit)
    {
        $dataArrayCount = count($this->dataArray);
        $start = $this->calculateStartWithCollections($start);
        $end = ($start + $limit < $dataArrayCount ? $start + $limit : $dataArrayCount);

        // Ensure that there are numerical indexes
        $this->dataArray = array_values($this->dataArray);
        // Fill the data array part
        $dataArrayPart = $this->fillDataArrayPart($start, $end);

        // Trigger a PSR-14 event
        $event = new GetVersionedDataEvent($this, $this->dataArray, $start, $limit, $dataArrayPart);
        $this->eventDispatcher->dispatch($event);
        $this->dataArray = $event->getData();
        return $event->getDataArrayPart();
    }

    /**
     * Initializes the workspace cache
     */
    protected function initializeWorkspacesCachingFramework()
    {
        $this->workspacesCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('workspaces_cache');
    }

    /**
     * Puts the generated dataArray into the workspace cache.
     *
     * @param array $versions All records uids etc. First key is table name, second key incremental integer. Records are associative arrays with uid and t3ver_oid fields. The pid of the online record is found as "livepid" the pid of the offline record is found in "wspid
     * @param string $filterTxt The given filter text from the grid.
     */
    protected function setDataArrayIntoCache(array $versions, $filterTxt)
    {
        $hash = $this->calculateHash($versions, $filterTxt);
        $this->workspacesCache->set(
            $hash,
            $this->dataArray,
            [
                (string)$this->currentWorkspace,
                'user_' . $this->getBackendUser()->user['uid'],
            ]
        );
    }

    /**
     * Checks if a cache entry is given for given versions and filter text and tries to load the data array from cache.
     *
     * @param array $versions All records uids etc. First key is table name, second key incremental integer. Records are associative arrays with uid and t3ver_oid fields. The pid of the online record is found as "livepid" the pid of the offline record is found in "wspid
     * @param string $filterTxt The given filter text from the grid.
     * @return bool TRUE if cache entry was successfully fetched from cache and content put to $this->dataArray
     */
    protected function getDataArrayFromCache(array $versions, $filterTxt)
    {
        $cacheEntry = false;
        $hash = $this->calculateHash($versions, $filterTxt);
        $content = $this->workspacesCache->get($hash);
        if (is_array($content)) {
            $this->dataArray = $content;
            $cacheEntry = true;
        }
        return $cacheEntry;
    }

    /**
     * Calculates the hash value of the used workspace, the user id, the versions array, the filter text, the sorting attribute, the workspace selected in grid and the sorting direction.
     *
     * @param array $versions All records uids etc. First key is table name, second key incremental integer. Records are associative arrays with uid and t3ver_oid fields. The pid of the online record is found as "livepid" the pid of the offline record is found in "wspid
     * @param string $filterTxt The given filter text from the grid.
     * @return string
     */
    protected function calculateHash(array $versions, $filterTxt)
    {
        $backendUser = $this->getBackendUser();
        $hashArray = [
            $backendUser->workspace,
            $backendUser->user['uid'],
            $versions,
            $filterTxt,
            $this->sort,
            $this->sortDir,
            $this->currentWorkspace,
        ];
        $hash = md5(serialize($hashArray));
        return $hash;
    }

    /**
     * Performs sorting on the data array accordant to the
     * selected column in the grid view to be used for sorting.
     */
    protected function sortDataArray()
    {
        switch ($this->sort) {
            case 'uid':
            case 'change':
            case 'workspace_Tstamp':
            case 't3ver_oid':
            case 'liveid':
            case 'livepid':
            case 'languageValue':
                uasort($this->dataArray, [$this, 'intSort']);
                break;
            case 'label_Workspace':
            case 'label_Live':
            case 'label_Stage':
            case 'workspace_Title':
            case 'path_Live':
                // case 'path_Workspace': This is the first sorting attribute
                uasort($this->dataArray, [$this, 'stringSort']);
                break;
            default:
                // Do nothing
        }

        // Trigger an event for extensibility
        $event = new SortVersionedDataEvent($this, $this->dataArray, $this->sort, $this->sortDir);
        $this->eventDispatcher->dispatch($event);
        $this->dataArray = $event->getData();
        $this->sort = $event->getSortColumn();
        $this->sortDir = $event->getSortDirection();
    }

    /**
     * Implements individual sorting for columns based on integer comparison.
     *
     * @param array $a First value
     * @param array $b Second value
     * @return int
     */
    protected function intSort(array $a, array $b)
    {
        if (!$this->isSortable($a, $b)) {
            return 0;
        }

        // First sort by using the page-path in current workspace
        $pathSortingResult = strcasecmp($a['path_Workspace'], $b['path_Workspace']);
        if ($pathSortingResult !== 0) {
            return $pathSortingResult;
        }

        if ($a[$this->sort] == $b[$this->sort]) {
            $sortingResult = 0;
        } elseif ($this->sortDir === 'ASC') {
            $sortingResult = $a[$this->sort] < $b[$this->sort] ? -1 : 1;
        } elseif ($this->sortDir === 'DESC') {
            $sortingResult = $a[$this->sort] > $b[$this->sort] ? -1 : 1;
        } else {
            $sortingResult = 0;
        }

        return $sortingResult;
    }

    /**
     * Implements individual sorting for columns based on string comparison.
     *
     * @param array $a First value
     * @param array $b Second value
     * @return int
     */
    protected function stringSort($a, $b)
    {
        if (!$this->isSortable($a, $b)) {
            return 0;
        }

        // First sort by using the page-path in current workspace
        $pathSortingResult = strcasecmp($a['path_Workspace'], $b['path_Workspace']);
        if ($pathSortingResult !== 0) {
            return $pathSortingResult;
        }

        if ($a[$this->sort] == $b[$this->sort]) {
            $sortingResult = 0;
        } elseif ($this->sortDir === 'ASC') {
            $sortingResult = strcasecmp($a[$this->sort], $b[$this->sort]);
        } elseif ($this->sortDir === 'DESC') {
            $sortingResult = strcasecmp($a[$this->sort], $b[$this->sort]) * -1;
        } else {
            $sortingResult = 0;
        }

        return $sortingResult;
    }

    /**
     * Determines whether dataArray elements are sortable.
     * Only elements on the first level (0) or below the same
     * parent element are directly sortable.
     *
     * @return bool
     */
    protected function isSortable(array $a, array $b)
    {
        return
            $a[self::GridColumn_CollectionLevel] === 0 && $b[self::GridColumn_CollectionLevel] === 0
            || $a[self::GridColumn_CollectionParent] === $b[self::GridColumn_CollectionParent]
        ;
    }

    /**
     * Checks whether the configured page module can be accessed by the current user.
     * Note that this does not check whether a custom page module is configured correctly.
     */
    protected function isPageModuleAllowed(): bool
    {
        return $this->moduleProvider->accessGranted(
            trim($this->getBackendUser()->getTSConfig()['options.']['overridePageModule'] ?? 'web_layout'),
            $this->getBackendUser()
        );
    }

    /**
     * Determines whether the text used to filter the results is part of
     * a column that is visible in the grid view.
     *
     * @param string $filterText
     * @return bool
     */
    protected function isFilterTextInVisibleColumns($filterText, array $versionArray)
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
                'label_Live' => ['hidden' => 0],
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
     *
     * @return string
     */
    protected function workspaceState($stateId, $hiddenOnline = false, $hiddenOffline = false, $hasDiff = true)
    {
        $hiddenState = null;
        if ($hiddenOnline == 0 && $hiddenOffline == 1) {
            $hiddenState = 'hidden';
        } elseif ($hiddenOnline == 1 && $hiddenOffline == 0) {
            $hiddenState = 'unhidden';
        }
        switch ($stateId) {
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
    protected function getTcaEnableColumnsFieldName($table, $type)
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
    protected function getLanguageValue($table, array $record)
    {
        $languageValue = 0;
        if (BackendUtility::isTableLocalizable($table)) {
            $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
            if (!empty($record[$languageField])) {
                $languageValue = $record[$languageField];
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
    protected function getSystemLanguageValue($id, $pageId, $key)
    {
        $value = null;
        $systemLanguages = $this->getSystemLanguages((int)$pageId);
        if (!empty($systemLanguages[$id][$key])) {
            $value = $systemLanguages[$id][$key];
        }
        return $value;
    }

    /**
     * Calculate the "real" start value by also taking collection children into account
     */
    protected function calculateStartWithCollections(int $start): int
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
            if (!isset($this->dataArray[$recordsCount])) {
                break;
            }
            // Loop over the dataArray until we found enough parent records
            $item = $this->dataArray[$recordsCount];
            if (($item[self::GridColumn_CollectionLevel] ?? 0) === 0) {
                // In case the current parent record is the last one ($start is reached),
                // ensure its collection children are counted as well.
                if (($parentRecordsCount + 1) === $start && (int)($item[self::GridColumn_Collection] ?? 0) !== 0) {
                    // By not providing the third parameter, we only count the collection children recursively
                    $this->addCollectionChildrenRecursive($item, $recordsCount);
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
    private function fillDataArrayPart(int $start, int $end): array
    {
        // Initialize empty data array part
        $dataArrayPart = [];
        // The recordsCount is the real record items count, while the
        // parentRecordsCount only takes the parent records into account.
        $itemsCount = $parentRecordsCount = $start;
        while ($parentRecordsCount < $end) {
            // As soon as no more item exists in the dataArray, the loop needs to end
            // prematurely to prevent invalid items which would trigger JavaScript errors.
            if (!isset($this->dataArray[$itemsCount])) {
                break;
            }
            // Loop over the dataArray until we found enough parent records
            $item = $this->dataArray[$itemsCount];
            // Add the item to the $dataArrayPart
            $dataArrayPart[] = $item;
            if (($item[self::GridColumn_CollectionLevel] ?? 0) === 0) {
                // In case the current parent record is the last one ($end is reached),
                // ensure its collection children are added as well.
                if (($parentRecordsCount + 1) === $end && (int)($item[self::GridColumn_Collection] ?? 0) !== 0) {
                    // Add collection children recursively
                    $this->addCollectionChildrenRecursive($item, $itemsCount, $dataArrayPart);
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
    protected function addCollectionChildrenRecursive(array $item, int &$recordsCount, array &$dataArrayPart = []): void
    {
        $collectionParent = (string)$item[self::GridColumn_CollectionCurrent];
        foreach ($this->dataArray as $element) {
            if ((string)($element[self::GridColumn_CollectionParent] ?? '') === $collectionParent) {
                // Increase the "real" record items count
                $recordsCount++;
                // Fetch the children from the dataArray using the current record items
                // count. This is possible since the dataArray is already sorted.
                $child = $this->dataArray[$recordsCount];
                // In case $dataArrayPart is not given, just count the item
                if ($dataArrayPart !== []) {
                    // Add the children
                    $dataArrayPart[] = $child;
                }
                // In case the $child is also a collection, add it's children as well (recursively)
                if ((int)($child[self::GridColumn_Collection] ?? 0) !== 0) {
                    $this->addCollectionChildrenRecursive($child, $recordsCount, $dataArrayPart);
                }
            }
        }
    }

    /**
     * Gets all available system languages.
     *
     * @return array
     */
    protected function getSystemLanguages(int $pageId)
    {
        return GeneralUtility::makeInstance(TranslationConfigurationProvider::class)->getSystemLanguages($pageId);
    }

    /**
     * Gets an instance of the integrity service.
     *
     * @return IntegrityService
     */
    protected function getIntegrityService()
    {
        if (!isset($this->integrityService)) {
            $this->integrityService = GeneralUtility::makeInstance(IntegrityService::class);
        }
        return $this->integrityService;
    }

    /**
     * @return Dependency\CollectionService
     */
    protected function getDependencyCollectionService()
    {
        return GeneralUtility::makeInstance(CollectionService::class);
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
