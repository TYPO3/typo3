<?php
namespace TYPO3\CMS\Workspaces\ExtDirect;

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

use TYPO3\CMS\Backend\Backend\Avatar\Avatar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Html\RteHtmlParser;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\DiffUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Workspaces\Service\GridDataService;
use TYPO3\CMS\Workspaces\Service\HistoryService;
use TYPO3\CMS\Workspaces\Service\StagesService;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;

/**
 * ExtDirect server
 */
class ExtDirectServer extends AbstractHandler
{
    /**
     * @var GridDataService
     */
    protected $gridDataService;

    /**
     * @var StagesService
     */
    protected $stagesService;

    /**
     * @var DiffUtility
     */
    protected $differenceHandler;

    /**
     * Checks integrity of elements before peforming actions on them.
     *
     * @param \stdClass $parameters
     * @return array
     */
    public function checkIntegrity(\stdClass $parameters)
    {
        $integrity = $this->createIntegrityService($this->getAffectedElements($parameters));
        $integrity->check();
        $response = array(
            'result' => $integrity->getStatusRepresentation()
        );
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
        $versions = $this->getWorkspaceService()->selectVersionsInWorkspace($this->getCurrentWorkspace(), 0, -99, $pageId, $parameter->depth, 'tables_select', $parameter->language);
        $data = $this->getGridDataService()->generateGridListFromVersions($versions, $parameter, $this->getCurrentWorkspace());
        return $data;
    }

    /**
     * Get List of available workspace actions
     *
     * @param \stdClass $parameter
     * @return array $data
     */
    public function getStageActions(\stdClass $parameter)
    {
        $currentWorkspace = $this->getCurrentWorkspace();
        $stages = array();
        if ($currentWorkspace != WorkspaceService::SELECT_ALL_WORKSPACES) {
            $stages = $this->getStagesService()->getStagesForWSUser();
        }
        $data = array(
            'total' => count($stages),
            'data' => $stages
        );
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
        $diffReturnArray = array();
        $liveReturnArray = array();
        $diffUtility = $this->getDifferenceHandler();
        /** @var $parseObj RteHtmlParser */
        $parseObj = GeneralUtility::makeInstance(RteHtmlParser::class);
        $liveRecord = BackendUtility::getRecord($parameter->table, $parameter->t3ver_oid);
        $versionRecord = BackendUtility::getRecord($parameter->table, $parameter->uid);
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $icon_Live = $iconFactory->getIconForRecord($parameter->table, $liveRecord, Icon::SIZE_SMALL)->render();
        $icon_Workspace = $iconFactory->getIconForRecord($parameter->table, $versionRecord, Icon::SIZE_SMALL)->render();
        $stagesService = $this->getStagesService();
        $stagePosition = $stagesService->getPositionOfCurrentStage($parameter->stage);
        $fieldsOfRecords = array_keys($liveRecord);
        if ($GLOBALS['TCA'][$parameter->table]) {
            if ($GLOBALS['TCA'][$parameter->table]['interface']['showRecordFieldList']) {
                $fieldsOfRecords = $GLOBALS['TCA'][$parameter->table]['interface']['showRecordFieldList'];
                $fieldsOfRecords = GeneralUtility::trimExplode(',', $fieldsOfRecords, true);
            }
        }
        foreach ($fieldsOfRecords as $fieldName) {
            if (empty($GLOBALS['TCA'][$parameter->table]['columns'][$fieldName]['config'])) {
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
                    if (!empty($configuration['foreign_selector_fieldTcaOverride']['config']['appearance']['elementBrowserAllowed']) && !empty($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'])) {
                        $fileExtensions = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], true);
                        $allowedExtensions = GeneralUtility::trimExplode(',', $configuration['foreign_selector_fieldTcaOverride']['config']['appearance']['elementBrowserAllowed'], true);
                        $differentExtensions = array_diff($allowedExtensions, $fileExtensions);
                        $useThumbnails = empty($differentExtensions);
                    }

                    $liveFileReferences = BackendUtility::resolveFileReferences(
                        $parameter->table,
                        $fieldName,
                        $liveRecord,
                        0
                    );
                    $versionFileReferences = BackendUtility::resolveFileReferences(
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

                    $diffReturnArray[] = array(
                        'field' => $fieldName,
                        'label' => $fieldTitle,
                        'content' => $fileReferenceDifferences['differences']
                    );
                    $liveReturnArray[] = array(
                        'field' => $fieldName,
                        'label' => $fieldTitle,
                        'content' => $fileReferenceDifferences['live']
                    );
                } elseif ((string)$liveRecord[$fieldName] !== (string)$versionRecord[$fieldName]) {
                    // Select the human readable values before diff
                    $liveRecord[$fieldName] = BackendUtility::getProcessedValue(
                        $parameter->table,
                        $fieldName,
                        $liveRecord[$fieldName],
                        0,
                        1,
                        false,
                        $liveRecord['uid']
                    );
                    $versionRecord[$fieldName] = BackendUtility::getProcessedValue(
                        $parameter->table,
                        $fieldName,
                        $versionRecord[$fieldName],
                        0,
                        1,
                        false,
                        $versionRecord['uid']
                    );

                    if ($configuration['type'] == 'group' && $configuration['internal_type'] == 'file') {
                        $versionThumb = BackendUtility::thumbCode($versionRecord, $parameter->table, $fieldName, '');
                        $liveThumb = BackendUtility::thumbCode($liveRecord, $parameter->table, $fieldName, '');
                        $diffReturnArray[] = array(
                            'field' => $fieldName,
                            'label' => $fieldTitle,
                            'content' => $versionThumb
                        );
                        $liveReturnArray[] = array(
                            'field' => $fieldName,
                            'label' => $fieldTitle,
                            'content' => $liveThumb
                        );
                    } else {
                        $diffReturnArray[] = array(
                            'field' => $fieldName,
                            'label' => $fieldTitle,
                            'content' => $diffUtility->makeDiffDisplay($liveRecord[$fieldName], $versionRecord[$fieldName])
                        );
                        $liveReturnArray[] = array(
                            'field' => $fieldName,
                            'label' => $fieldTitle,
                            'content' => $parseObj->TS_images_rte($liveRecord[$fieldName])
                        );
                    }
                }
            }
        }
        // Hook for modifying the difference and live arrays
        // (this may be used by custom or dynamically-defined fields)
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['workspaces']['modifyDifferenceArray'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['workspaces']['modifyDifferenceArray'] as $className) {
                $hookObject = GeneralUtility::getUserObj($className);
                if (method_exists($hookObject, 'modifyDifferenceArray')) {
                    $hookObject->modifyDifferenceArray($parameter, $diffReturnArray, $liveReturnArray, $diffUtility);
                }
            }
        }
        $commentsForRecord = $this->getCommentsForRecord($parameter->uid, $parameter->table);

        /** @var $historyService HistoryService */
        $historyService = GeneralUtility::makeInstance(HistoryService::class);
        $history = $historyService->getHistory($parameter->table, $parameter->t3ver_oid);

        $prevStage = $stagesService->getPrevStage($parameter->stage);
        $nextStage = $stagesService->getNextStage($parameter->stage);

        if (isset($prevStage[0])) {
            $prevStage = current($prevStage);
        }

        if (isset($nextStage[0])) {
            $nextStage = current($nextStage);
        }

        return array(
            'total' => 1,
            'data' => array(
                array(
                    // these parts contain HTML (don't escape)
                    'diff' => $diffReturnArray,
                    'live_record' => $liveReturnArray,
                    'icon_Live' => $icon_Live,
                    'icon_Workspace' => $icon_Workspace,
                    // this part is already escaped in getCommentsForRecord()
                    'comments' => $commentsForRecord,
                    // escape/sanitize the others
                    'path_Live' => htmlspecialchars(BackendUtility::getRecordPath($liveRecord['pid'], '', 999)),
                    'label_Stage' => htmlspecialchars($stagesService->getStageTitle($parameter->stage)),
                    'label_PrevStage' => $prevStage,
                    'label_NextStage' => $nextStage,
                    'stage_position' => (int)$stagePosition['position'],
                    'stage_count' => (int)$stagePosition['count'],
                    'parent' => [
                        'table' => htmlspecialchars($parameter->table),
                        'uid' => (int)$parameter->uid
                    ],
                    'history' => [
                        'data' => $history,
                        'total' => count($history)
                    ]
                )
            )
        );
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
        $randomValue = uniqid('file');

        $liveValues = array();
        $versionValues = array();
        $candidates = array();
        $substitutes = array();

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
                    array('width' => 40, 'height' => 40)
                );
                $thumbnailMarkup = '<img src="' . $thumbnailFile->getPublicUrl(true) . '" />';
                $substitutes[$identifierWithRandomValue] = $thumbnailMarkup;
            } else {
                $substitutes[$identifierWithRandomValue] = $fileReference->getPublicUrl();
            }
        }

        $differences = $this->getDifferenceHandler()->makeDiffDisplay($liveInformation, $versionInformation);
        $liveInformation = str_replace(array_keys($substitutes), array_values($substitutes), trim($liveInformation));
        $differences = str_replace(array_keys($substitutes), array_values($substitutes), trim($differences));

        return array(
            'live' => $liveInformation,
            'differences' => $differences
        );
    }

    /**
     * Gets an array with all sys_log entries and their comments for the given record uid and table
     *
     * @param int $uid uid of changed element to search for in log
     * @param string $table Name of the record's table
     * @return array
     */
    public function getCommentsForRecord($uid, $table)
    {
        $sysLogReturnArray = array();
        $sysLogRows = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'log_data,tstamp,userid',
            'sys_log',
            'action=6 and details_nr=30 AND tablename=' . $this->getDatabaseConnection()->fullQuoteStr($table, 'sys_log')
                . ' AND recuid=' . (int)$uid,
            '',
            'tstamp DESC'
        );

        /** @var Avatar $avatar */
        $avatar = GeneralUtility::makeInstance(Avatar::class);

        foreach ($sysLogRows as $sysLogRow) {
            $sysLogEntry = array();
            $data = unserialize($sysLogRow['log_data']);
            $beUserRecord = BackendUtility::getRecord('be_users', $sysLogRow['userid']);
            $sysLogEntry['stage_title'] = htmlspecialchars($this->getStagesService()->getStageTitle($data['stage']));
            $sysLogEntry['user_uid'] = (int)$sysLogRow['userid'];
            $sysLogEntry['user_username'] = is_array($beUserRecord) ? htmlspecialchars($beUserRecord['username']) : '';
            $sysLogEntry['tstamp'] = htmlspecialchars(BackendUtility::datetime($sysLogRow['tstamp']));
            $sysLogEntry['user_comment'] = nl2br(htmlspecialchars($data['comment']));
            $sysLogEntry['user_avatar'] = $avatar->render($beUserRecord);
            $sysLogReturnArray[] = $sysLogEntry;
        }
        return $sysLogReturnArray;
    }

    /**
     * Gets all available system languages.
     *
     * @return array
     */
    public function getSystemLanguages()
    {
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $systemLanguages = array(
            array(
                'uid' => 'all',
                'title' => LocalizationUtility::translate('language.allLanguages', 'workspaces'),
                'icon' => $iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render()
            )
        );
        foreach ($this->getGridDataService()->getSystemLanguages() as $id => $systemLanguage) {
            if ($id < 0) {
                continue;
            }
            $systemLanguages[] = array(
                'uid' => $id,
                'title' => htmlspecialchars($systemLanguage['title']),
                'icon' => $iconFactory->getIcon($systemLanguage['flagIcon'], Icon::SIZE_SMALL)->render()
            );
        }
        $result = array(
            'total' => count($systemLanguages),
            'data' => $systemLanguages
        );
        return $result;
    }

    /**
     * @return BackendUserAuthentication;
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return LanguageService;
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return DatabaseConnection;
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * Gets the Grid Data Service.
     *
     * @return GridDataService
     */
    protected function getGridDataService()
    {
        if (!isset($this->gridDataService)) {
            $this->gridDataService = GeneralUtility::makeInstance(GridDataService::class);
        }
        return $this->gridDataService;
    }

    /**
     * Gets the Stages Service.
     *
     * @return StagesService
     */
    protected function getStagesService()
    {
        if (!isset($this->stagesService)) {
            $this->stagesService = GeneralUtility::makeInstance(StagesService::class);
        }
        return $this->stagesService;
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
        }
        return $this->differenceHandler;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected function getObjectManager()
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }
}
