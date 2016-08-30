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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * ExtDirect server
 */
class ExtDirectServer extends AbstractHandler
{
    /**
     * @var \TYPO3\CMS\Workspaces\Service\GridDataService
     */
    protected $gridDataService;

    /**
     * @var \TYPO3\CMS\Workspaces\Service\StagesService
     */
    protected $stagesService;

    /**
     * @var \cogpowered\FineDiff\Diff
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
        $response = [
            'result' => $integrity->getStatusRepresentation()
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
        if (!isset($parameter->language) || !\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($parameter->language)) {
            $parameter->language = null;
        }
        $versions = $this->getWorkspaceService()->selectVersionsInWorkspace($this->getCurrentWorkspace(), 0, -99, $pageId, $parameter->depth, 'tables_select', $parameter->language);
        $data = $this->getGridDataService()->generateGridListFromVersions($versions, $parameter, $this->getCurrentWorkspace());
        return $data;
    }

    /**
     * Gets the editing history of a record.
     *
     * @param stdClass $parameters
     * @return array
     */
    public function getHistory($parameters)
    {
        /** @var $historyService \TYPO3\CMS\Workspaces\Service\HistoryService */
        $historyService = GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Service\HistoryService::class);
        $history = $historyService->getHistory($parameters->table, $parameters->liveId);
        return [
            'data' => $history,
            'total' => count($history)
        ];
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
        $stages = [];
        if ($currentWorkspace != \TYPO3\CMS\Workspaces\Service\WorkspaceService::SELECT_ALL_WORKSPACES) {
            $stages = $this->getStagesService()->getStagesForWSUser();
        }
        $data = [
            'total' => count($stages),
            'data' => $stages
        ];
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
        /** @var $diffUtility \TYPO3\CMS\Core\Utility\DiffUtility */
        $diffUtility = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Utility\DiffUtility::class);
        /** @var $parseObj \TYPO3\CMS\Core\Html\RteHtmlParser */
        $parseObj = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Html\RteHtmlParser::class);
        $liveRecord = BackendUtility::getRecord($parameter->table, $parameter->t3ver_oid);
        $versionRecord = BackendUtility::getRecord($parameter->table, $parameter->uid);
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $icon_Live = $iconFactory->getIconForRecord($parameter->table, $liveRecord, Icon::SIZE_SMALL)->render();
        $icon_Workspace = $iconFactory->getIconForRecord($parameter->table, $versionRecord, Icon::SIZE_SMALL)->render();
        $stagePosition = $this->getStagesService()->getPositionOfCurrentStage($parameter->stage);
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
            $fieldTitle = $GLOBALS['LANG']->sL(BackendUtility::getItemLabel($parameter->table, $fieldName));
            if (empty($fieldTitle)) {
                $fieldTitle = $fieldName;
            }
            // Gets the TCA configuration for the current field
            $configuration = $GLOBALS['TCA'][$parameter->table]['columns'][$fieldName]['config'];
            // check for exclude fields
            if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['TCA'][$parameter->table]['columns'][$fieldName]['exclude'] == 0 || GeneralUtility::inList($GLOBALS['BE_USER']->groupData['non_exclude_fields'], $parameter->table . ':' . $fieldName)) {
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

                    $diffReturnArray[] = [
                        'field' => $fieldName,
                        'label' => $fieldTitle,
                        'content' => $fileReferenceDifferences['differences']
                    ];
                    $liveReturnArray[] = [
                        'field' => $fieldName,
                        'label' => $fieldTitle,
                        'content' => $fileReferenceDifferences['live']
                    ];
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
                        $diffReturnArray[] = [
                            'field' => $fieldName,
                            'label' => $fieldTitle,
                            'content' => $versionThumb
                        ];
                        $liveReturnArray[] = [
                            'field' => $fieldName,
                            'label' => $fieldTitle,
                            'content' => $liveThumb
                        ];
                    } else {
                        $diffReturnArray[] = [
                            'field' => $fieldName,
                            'label' => $fieldTitle,
                            'content' => $diffUtility->makeDiffDisplay($liveRecord[$fieldName], $versionRecord[$fieldName])
                        ];
                        $liveReturnArray[] = [
                            'field' => $fieldName,
                            'label' => $fieldTitle,
                            'content' => $parseObj->TS_images_rte($liveRecord[$fieldName])
                        ];
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
                    // escape/santinize the others
                    'path_Live' => htmlspecialchars($parameter->path_Live),
                    'label_Stage' => htmlspecialchars($parameter->label_Stage),
                    'stage_position' => (int)$stagePosition['position'],
                    'stage_count' => (int)$stagePosition['count']
                ]
            ]
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
        $randomValue = uniqid('file');

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
                    \TYPO3\CMS\Core\Resource\ProcessedFile::CONTEXT_IMAGEPREVIEW,
                    ['width' => 40, 'height' => 40]
                );
                $thumbnailMarkup = '<img src="' . $thumbnailFile->getPublicUrl(true) . '" />';
                $substitutes[$identifierWithRandomValue] = $thumbnailMarkup;
            } else {
                $substitutes[$identifierWithRandomValue] = $fileReference->getPublicUrl();
            }
        }

        $differences = $this->getDifferenceHandler()->render($liveInformation, $versionInformation);
        $liveInformation = str_replace(array_keys($substitutes), array_values($substitutes), trim($liveInformation));
        $differences = str_replace(array_keys($substitutes), array_values($substitutes), trim($differences));

        return [
            'live' => $liveInformation,
            'differences' => $differences
        ];
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
        $sysLogReturnArray = [];
        $sysLogRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
            'log_data,tstamp,userid',
            'sys_log',
            'action=6 and details_nr=30 AND tablename=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($table, 'sys_log')
                . ' AND recuid=' . (int)$uid,
            '',
            'tstamp DESC'
        );
        foreach ($sysLogRows as $sysLogRow) {
            $sysLogEntry = [];
            $data = unserialize($sysLogRow['log_data']);
            $beUserRecord = BackendUtility::getRecord('be_users', $sysLogRow['userid']);
            $sysLogEntry['stage_title'] = htmlspecialchars($this->getStagesService()->getStageTitle($data['stage']));
            $sysLogEntry['user_uid'] = (int)$sysLogRow['userid'];
            $sysLogEntry['user_username'] = is_array($beUserRecord) ? htmlspecialchars($beUserRecord['username']) : '';
            $sysLogEntry['tstamp'] = htmlspecialchars(BackendUtility::datetime($sysLogRow['tstamp']));
            $sysLogEntry['user_comment'] = nl2br(htmlspecialchars($data['comment']));
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
        $systemLanguages = [
            [
                'uid' => 'all',
                'title' => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('language.allLanguages', 'workspaces'),
                'icon' => $iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render()
            ]
        ];
        foreach ($this->getGridDataService()->getSystemLanguages() as $id => $systemLanguage) {
            if ($id < 0) {
                continue;
            }
            $systemLanguages[] = [
                'uid' => $id,
                'title' => htmlspecialchars($systemLanguage['title']),
                'icon' => $iconFactory->getIcon($systemLanguage['flagIcon'], Icon::SIZE_SMALL)->render()
            ];
        }
        $result = [
            'total' => count($systemLanguages),
            'data' => $systemLanguages
        ];
        return $result;
    }

    /**
     * Gets the Grid Data Service.
     *
     * @return \TYPO3\CMS\Workspaces\Service\GridDataService
     */
    protected function getGridDataService()
    {
        if (!isset($this->gridDataService)) {
            $this->gridDataService = GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Service\GridDataService::class);
        }
        return $this->gridDataService;
    }

    /**
     * Gets the Stages Service.
     *
     * @return \TYPO3\CMS\Workspaces\Service\StagesService
     */
    protected function getStagesService()
    {
        if (!isset($this->stagesService)) {
            $this->stagesService = GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Service\StagesService::class);
        }
        return $this->stagesService;
    }

    /**
     * Gets the difference handler, parsing differences based on sentences.
     *
     * @return \cogpowered\FineDiff\Diff
     */
    protected function getDifferenceHandler()
    {
        if (!isset($this->differenceHandler)) {
            $granularity = new \cogpowered\FineDiff\Granularity\Word();
            $this->differenceHandler = new \cogpowered\FineDiff\Diff($granularity);
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
