<?php
namespace TYPO3\CMS\Workspaces\Service;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Grid data service
 */
class GridDataService
{
    const SIGNAL_GenerateDataArray_BeforeCaching = 'generateDataArray.beforeCaching';
    const SIGNAL_GenerateDataArray_PostProcesss = 'generateDataArray.postProcess';
    const SIGNAL_GetDataArray_PostProcesss = 'getDataArray.postProcess';
    const SIGNAL_SortDataArray_PostProcesss = 'sortDataArray.postProcess';

    const GridColumn_Collection = 'Workspaces_Collection';
    const GridColumn_CollectionLevel = 'Workspaces_CollectionLevel';
    const GridColumn_CollectionParent = 'Workspaces_CollectionParent';
    const GridColumn_CollectionCurrent = 'Workspaces_CollectionCurrent';
    const GridColumn_CollectionChildren = 'Workspaces_CollectionChildren';

    /**
     * Id of the current active workspace.
     *
     * @var int
     */
    protected $currentWorkspace = null;

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
     * @var \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface
     */
    protected $workspacesCache = null;

    /**
     * @var array
     */
    protected $systemLanguages;

    /**
     * @var \TYPO3\CMS\Workspaces\Service\IntegrityService
     */
    protected $integrityService;

    /**
     * Generates grid list array from given versions.
     *
     * @param array $versions All records uids etc. First key is table name, second key incremental integer. Records are associative arrays with uid and t3ver_oid fields. The pid of the online record is found as "livepid" the pid of the offline record is found in "wspid
     * @param \stdClass $parameter Parameters as submitted by JavaScript component
     * @param int $currentWorkspace The current workspace
     * @return array Version record information (filtered, sorted and limited)
     * @throws \InvalidArgumentException
     */
    public function generateGridListFromVersions($versions, $parameter, $currentWorkspace)
    {
        // Read the given parameters from grid. If the parameter is not set use default values.
        $filterTxt = isset($parameter->filterTxt) ? $parameter->filterTxt : '';
        $start = isset($parameter->start) ? (int)$parameter->start : 0;
        $limit = isset($parameter->limit) ? (int)$parameter->limit : 30;
        $this->sort = isset($parameter->sort) ? $parameter->sort : 't3ver_oid';
        $this->sortDir = isset($parameter->dir) ? $parameter->dir : 'ASC';
        if (is_int($currentWorkspace)) {
            $this->currentWorkspace = $currentWorkspace;
        } else {
            throw new \InvalidArgumentException('No such workspace defined');
        }
        $data = [];
        $data['data'] = [];
        $this->generateDataArray($versions, $filterTxt);
        $data['total'] = count($this->dataArray);
        $data['data'] = $this->getDataArray($start, $limit);
        return $data;
    }

    /**
     * Generates grid list array from given versions.
     *
     * @param array $versions All available version records
     * @param string $filterTxt Text to be used to filter record result
     * @return void
     */
    protected function generateDataArray(array $versions, $filterTxt)
    {
        $workspaceAccess = $GLOBALS['BE_USER']->checkWorkspace($GLOBALS['BE_USER']->workspace);
        $swapStage = $workspaceAccess['publish_access'] & 1 ? \TYPO3\CMS\Workspaces\Service\StagesService::STAGE_PUBLISH_ID : 0;
        $swapAccess = $GLOBALS['BE_USER']->workspacePublishAccess($GLOBALS['BE_USER']->workspace) && $GLOBALS['BE_USER']->workspaceSwapAccess();
        $this->initializeWorkspacesCachingFramework();
        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        // check for dataArray in cache
        if ($this->getDataArrayFromCache($versions, $filterTxt) === false) {
            /** @var $stagesObj \TYPO3\CMS\Workspaces\Service\StagesService */
            $stagesObj = GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Service\StagesService::class);
            $defaultGridColumns = [
                self::GridColumn_Collection => 0,
                self::GridColumn_CollectionLevel => 0,
                self::GridColumn_CollectionParent => '',
                self::GridColumn_CollectionCurrent => '',
                self::GridColumn_CollectionChildren => 0,
            ];
            foreach ($versions as $table => $records) {
                $hiddenField = $this->getTcaEnableColumnsFieldName($table, 'disabled');
                $isRecordTypeAllowedToModify = $GLOBALS['BE_USER']->check('tables_modify', $table);

                foreach ($records as $record) {
                    $origRecord = BackendUtility::getRecord($table, $record['t3ver_oid']);
                    $versionRecord = BackendUtility::getRecord($table, $record['uid']);
                    $combinedRecord = \TYPO3\CMS\Workspaces\Domain\Model\CombinedRecord::createFromArrays($table, $origRecord, $versionRecord);
                    $this->getIntegrityService()->checkElement($combinedRecord);

                    if ($hiddenField !== null) {
                        $recordState = $this->workspaceState($versionRecord['t3ver_state'], $origRecord[$hiddenField], $versionRecord[$hiddenField]);
                    } else {
                        $recordState = $this->workspaceState($versionRecord['t3ver_state']);
                    }

                    $isDeletedPage = $table == 'pages' && $recordState == 'deleted';
                    $viewUrl = \TYPO3\CMS\Workspaces\Service\WorkspaceService::viewSingleRecord($table, $record['uid'], $origRecord, $versionRecord);
                    $versionArray = [];
                    $versionArray['table'] = $table;
                    $versionArray['id'] = $table . ':' . $record['uid'];
                    $versionArray['uid'] = $record['uid'];
                    $versionArray['workspace'] = $versionRecord['t3ver_id'];
                    $versionArray = array_merge($versionArray, $defaultGridColumns);
                    $versionArray['label_Workspace'] = htmlspecialchars(BackendUtility::getRecordTitle($table, $versionRecord));
                    $versionArray['label_Live'] = htmlspecialchars(BackendUtility::getRecordTitle($table, $origRecord));
                    $versionArray['label_Stage'] = htmlspecialchars($stagesObj->getStageTitle($versionRecord['t3ver_stage']));
                    $tempStage = $stagesObj->getNextStage($versionRecord['t3ver_stage']);
                    $versionArray['label_nextStage'] = htmlspecialchars($stagesObj->getStageTitle($tempStage['uid']));
                    $tempStage = $stagesObj->getPrevStage($versionRecord['t3ver_stage']);
                    $versionArray['label_prevStage'] = htmlspecialchars($stagesObj->getStageTitle($tempStage['uid']));
                    $versionArray['path_Live'] = htmlspecialchars(BackendUtility::getRecordPath($record['livepid'], '', 999));
                    $versionArray['path_Workspace'] = htmlspecialchars(BackendUtility::getRecordPath($record['wspid'], '', 999));
                    $versionArray['workspace_Title'] = htmlspecialchars(\TYPO3\CMS\Workspaces\Service\WorkspaceService::getWorkspaceTitle($versionRecord['t3ver_wsid']));
                    $versionArray['workspace_Tstamp'] = $versionRecord['tstamp'];
                    $versionArray['workspace_Formated_Tstamp'] = BackendUtility::datetime($versionRecord['tstamp']);
                    $versionArray['t3ver_wsid'] = $versionRecord['t3ver_wsid'];
                    $versionArray['t3ver_oid'] = $record['t3ver_oid'];
                    $versionArray['livepid'] = $record['livepid'];
                    $versionArray['stage'] = $versionRecord['t3ver_stage'];
                    $versionArray['icon_Live'] = $iconFactory->getIconForRecord($table, $origRecord, Icon::SIZE_SMALL)->render();
                    $versionArray['icon_Workspace'] = $iconFactory->getIconForRecord($table, $versionRecord, Icon::SIZE_SMALL)->render();
                    $languageValue = $this->getLanguageValue($table, $versionRecord);
                    $versionArray['languageValue'] = $languageValue;
                    $versionArray['language'] = [
                        'icon' => $iconFactory->getIcon($this->getSystemLanguageValue($languageValue, 'flagIcon'), Icon::SIZE_SMALL)->render()
                    ];
                    $versionArray['allowedAction_nextStage'] = $isRecordTypeAllowedToModify && $stagesObj->isNextStageAllowedForUser($versionRecord['t3ver_stage']);
                    $versionArray['allowedAction_prevStage'] = $isRecordTypeAllowedToModify && $stagesObj->isPrevStageAllowedForUser($versionRecord['t3ver_stage']);
                    if ($swapAccess && $swapStage != 0 && $versionRecord['t3ver_stage'] == $swapStage) {
                        $versionArray['allowedAction_swap'] = $isRecordTypeAllowedToModify && $stagesObj->isNextStageAllowedForUser($swapStage);
                    } elseif ($swapAccess && $swapStage == 0) {
                        $versionArray['allowedAction_swap'] = $isRecordTypeAllowedToModify;
                    } else {
                        $versionArray['allowedAction_swap'] = false;
                    }
                    $versionArray['allowedAction_delete'] = $isRecordTypeAllowedToModify;
                    // preview and editing of a deleted page won't work ;)
                    $versionArray['allowedAction_view'] = !$isDeletedPage && $viewUrl;
                    $versionArray['allowedAction_edit'] = $isRecordTypeAllowedToModify && !$isDeletedPage;
                    $versionArray['allowedAction_editVersionedPage'] = $isRecordTypeAllowedToModify && !$isDeletedPage;
                    $versionArray['state_Workspace'] = $recordState;

                    $versionArray = array_merge(
                        $versionArray,
                        $this->getAdditionalColumnService()->getData($combinedRecord)
                    );

                    if ($filterTxt == '' || $this->isFilterTextInVisibleColumns($filterTxt, $versionArray)) {
                        $versionIdentifier = $versionArray['id'];
                        $this->dataArray[$versionIdentifier] = $versionArray;
                    }
                }
            }
            // Suggested slot method:
            // methodName(\TYPO3\CMS\Workspaces\Service\GridDataService $gridData, array $dataArray, array $versions)
            list($this->dataArray, $versions) = $this->emitSignal(self::SIGNAL_GenerateDataArray_BeforeCaching, $this->dataArray, $versions);
            // Enrich elements after everything has been processed:
            foreach ($this->dataArray as &$element) {
                $identifier = $element['table'] . ':' . $element['t3ver_oid'];
                $element['integrity'] = [
                    'status' => $this->getIntegrityService()->getStatusRepresentation($identifier),
                    'messages' => htmlspecialchars($this->getIntegrityService()->getIssueMessages($identifier, true))
                ];
            }
            $this->setDataArrayIntoCache($versions, $filterTxt);
        }
        // Suggested slot method:
        // methodName(\TYPO3\CMS\Workspaces\Service\GridDataService $gridData, array $dataArray, array $versions)
        list($this->dataArray, $versions) = $this->emitSignal(self::SIGNAL_GenerateDataArray_PostProcesss, $this->dataArray, $versions);
        $this->sortDataArray();
        $this->resolveDataArrayDependencies();
    }

    /**
     * Resolves dependencies of nested structures
     * and sort data elements considering these dependencies.
     *
     * @return void
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
        $dataArrayPart = [];
        $dataArrayCount = count($this->dataArray);
        $end = ($start + $limit < $dataArrayCount ? $start + $limit : $dataArrayCount);

        // Ensure that there are numerical indexes
        $this->dataArray = array_values(($this->dataArray));
        for ($i = $start; $i < $end; $i++) {
            $dataArrayPart[] = $this->dataArray[$i];
        }

        // Ensure that collections are not cut for the pagination
        if (!empty($this->dataArray[$i][self::GridColumn_Collection])) {
            $collectionIdentifier = $this->dataArray[$i][self::GridColumn_Collection];
            for ($i = $i + 1; $i < $dataArrayCount && $collectionIdentifier === $this->dataArray[$i][self::GridColumn_Collection]; $i++) {
                $dataArrayPart[] = $this->dataArray[$i];
            }
        }

        // Suggested slot method:
        // methodName(\TYPO3\CMS\Workspaces\Service\GridDataService $gridData, array $dataArray, $start, $limit, array $dataArrayPart)
        list($this->dataArray, $start, $limit, $dataArrayPart) = $this->emitSignal(self::SIGNAL_GetDataArray_PostProcesss, $this->dataArray, $start, $limit, $dataArrayPart);
        return $dataArrayPart;
    }

    /**
     * Initializes the workspace cache
     *
     * @return void
     */
    protected function initializeWorkspacesCachingFramework()
    {
        $this->workspacesCache = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->getCache('workspaces_cache');
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
        $this->workspacesCache->set($hash, $this->dataArray, [$this->currentWorkspace, 'user_' . $GLOBALS['BE_USER']->user['uid']]);
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
        if ($content !== false) {
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
        $hashArray = [
            $GLOBALS['BE_USER']->workspace,
            $GLOBALS['BE_USER']->user['uid'],
            $versions,
            $filterTxt,
            $this->sort,
            $this->sortDir,
            $this->currentWorkspace
        ];
        $hash = md5(serialize($hashArray));
        return $hash;
    }

    /**
     * Performs sorting on the data array accordant to the
     * selected column in the grid view to be used for sorting.
     *
     * @return void
     */
    protected function sortDataArray()
    {
        if (is_array($this->dataArray)) {
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
        } else {
            GeneralUtility::sysLog(
                'Try to sort "' . $this->sort . '" in "TYPO3\\CMS\\Workspaces\\Service\\GridDataService::sortDataArray" but $this->dataArray is empty! This might be the Bug #26422 which could not reproduced yet.',
                'workspaces',
                GeneralUtility::SYSLOG_SEVERITY_ERROR
            );
        }
        // Suggested slot method:
        // methodName(\TYPO3\CMS\Workspaces\Service\GridDataService $gridData, array $dataArray, $sortColumn, $sortDirection)
        list($this->dataArray, $this->sort, $this->sortDir) = $this->emitSignal(self::SIGNAL_SortDataArray_PostProcesss, $this->dataArray, $this->sort, $this->sortDir);
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
        $path_cmp = strcasecmp($a['path_Workspace'], $b['path_Workspace']);
        if ($path_cmp < 0) {
            return $path_cmp;
        } elseif ($path_cmp == 0) {
            if ($a[$this->sort] == $b[$this->sort]) {
                return 0;
            }
            if ($this->sortDir == 'ASC') {
                return $a[$this->sort] < $b[$this->sort] ? -1 : 1;
            } elseif ($this->sortDir == 'DESC') {
                return $a[$this->sort] > $b[$this->sort] ? -1 : 1;
            }
        } elseif ($path_cmp > 0) {
            return $path_cmp;
        }
        return 0;
    }

    /**
     * Implements individual sorting for columns based on string comparison.
     *
     * @param string $a First value
     * @param string $b Second value
     * @return int
     */
    protected function stringSort($a, $b)
    {
        if (!$this->isSortable($a, $b)) {
            return 0;
        }
        $path_cmp = strcasecmp($a['path_Workspace'], $b['path_Workspace']);
        if ($path_cmp < 0) {
            return $path_cmp;
        } elseif ($path_cmp == 0) {
            if ($a[$this->sort] == $b[$this->sort]) {
                return 0;
            }
            if ($this->sortDir == 'ASC') {
                return strcasecmp($a[$this->sort], $b[$this->sort]);
            } elseif ($this->sortDir == 'DESC') {
                return strcasecmp($a[$this->sort], $b[$this->sort]) * -1;
            }
        } elseif ($path_cmp > 0) {
            return $path_cmp;
        }
        return 0;
    }

    /**
     * Determines whether dataArray elements are sortable.
     * Only elements on the first level (0) or below the same
     * parent element are directly sortable.
     *
     * @param array $a
     * @param array $b
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
     * Determines whether the text used to filter the results is part of
     * a column that is visible in the grid view.
     *
     * @param string $filterText
     * @param array $versionArray
     * @return bool
     */
    protected function isFilterTextInVisibleColumns($filterText, array $versionArray)
    {
        if (is_array($GLOBALS['BE_USER']->uc['moduleData']['Workspaces'][$GLOBALS['BE_USER']->workspace]['columns'])) {
            foreach ($GLOBALS['BE_USER']->uc['moduleData']['Workspaces'][$GLOBALS['BE_USER']->workspace]['columns'] as $column => $value) {
                if (isset($value['hidden']) && isset($column) && isset($versionArray[$column])) {
                    if ($value['hidden'] == 0) {
                        switch ($column) {
                            case 'workspace_Tstamp':
                                if (stripos($versionArray['workspace_Formated_Tstamp'], $filterText) !== false) {
                                    return true;
                                }
                                break;
                            case 'change':
                                if (stripos(strval($versionArray[$column]), str_replace('%', '', $filterText)) !== false) {
                                    return true;
                                }
                                break;
                            default:
                                if (stripos(strval($versionArray[$column]), $filterText) !== false) {
                                    return true;
                                }
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
     * @param int $stateId stateId of offline record
     * @param bool $hiddenOnline hidden status of online record
     * @param bool $hiddenOffline hidden status of offline record
     * @return string
     */
    protected function workspaceState($stateId, $hiddenOnline = false, $hiddenOffline = false)
    {
        $hiddenState = null;
        if ($hiddenOnline == 0 && $hiddenOffline == 1) {
            $hiddenState = 'hidden';
        } elseif ($hiddenOnline == 1 && $hiddenOffline == 0) {
            $hiddenState = 'unhidden';
        }
        switch ($stateId) {
            case -1:
                $state = 'new';
                break;
            case 2:
                $state = 'deleted';
                break;
            case 4:
                $state = 'moved';
                break;
            default:
                $state = ($hiddenState ?: 'modified');
        }
        return $state;
    }

    /**
     * Gets the field name of the enable-columns as defined in $TCA.
     *
     * @param string $table Name of the table
     * @param string $type Type to be fetches (e.g. 'disabled', 'starttime', 'endtime', 'fe_group)
     * @return string|NULL The accordant field name or NULL if not defined
     */
    protected function getTcaEnableColumnsFieldName($table, $type)
    {
        $fieldName = null;

        if (!(empty($GLOBALS['TCA'][$table]['ctrl']['enablecolumns'][$type]))) {
            $fieldName = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns'][$type];
        }

        return $fieldName;
    }

    /**
     * Gets the used language value (sys_language.uid) of
     * a given database record.
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
     * Gets a named value of the available sys_language elements.
     *
     * @param int $id sys_language uid
     * @param string $key Name of the value to be fetched (e.g. title)
     * @return string|NULL
     * @see getSystemLanguages
     */
    protected function getSystemLanguageValue($id, $key)
    {
        $value = null;
        $systemLanguages = $this->getSystemLanguages();
        if (!empty($systemLanguages[$id][$key])) {
            $value = $systemLanguages[$id][$key];
        }
        return $value;
    }

    /**
     * Gets all available system languages.
     *
     * @return array
     */
    public function getSystemLanguages()
    {
        if (!isset($this->systemLanguages)) {
            /** @var $translateTools \TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider */
            $translateTools = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider::class);
            $this->systemLanguages = $translateTools->getSystemLanguages();
        }
        return $this->systemLanguages;
    }

    /**
     * Gets an instance of the integrity service.
     *
     * @return \TYPO3\CMS\Workspaces\Service\IntegrityService
     */
    protected function getIntegrityService()
    {
        if (!isset($this->integrityService)) {
            $this->integrityService = GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Service\IntegrityService::class);
        }
        return $this->integrityService;
    }

    /**
     * Emits a signal to be handled by any registered slots.
     *
     * @param string $signalName Name of the signal
     * @return array
     */
    protected function emitSignal($signalName)
    {
        // Arguments are always ($this, [method argument], [method argument], ...)
        $signalArguments = array_merge([$this], array_slice(func_get_args(), 1));
        $slotReturn = $this->getSignalSlotDispatcher()->dispatch(\TYPO3\CMS\Workspaces\Service\GridDataService::class, $signalName, $signalArguments);
        return array_slice($slotReturn, 1);
    }

    /**
     * @return \TYPO3\CMS\Workspaces\Service\Dependency\CollectionService
     */
    protected function getDependencyCollectionService()
    {
        return GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Service\Dependency\CollectionService::class);
    }

    /**
     * @return \TYPO3\CMS\Workspaces\Service\AdditionalColumnService
     */
    protected function getAdditionalColumnService()
    {
        return $this->getObjectManager()->get(\TYPO3\CMS\Workspaces\Service\AdditionalColumnService::class);
    }

    /**
     * @return \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected function getSignalSlotDispatcher()
    {
        return $this->getObjectManager()->get(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
    }

    /**
     * @return \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected function getObjectManager()
    {
        return GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
    }
}
