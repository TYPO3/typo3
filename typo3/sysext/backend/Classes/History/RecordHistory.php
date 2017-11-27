<?php
namespace TYPO3\CMS\Backend\History;

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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\DiffUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class for the record history display module show_rechis
 */
class RecordHistory
{
    /**
     * Maximum number of sys_history steps to show.
     *
     * @var int
     */
    public $maxSteps = 20;

    /**
     * Display diff or not (0-no diff, 1-inline)
     *
     * @var int
     */
    public $showDiff = 1;

    /**
     * On a pages table - show sub elements as well.
     *
     * @var int
     */
    public $showSubElements = 1;

    /**
     * Show inserts and deletes as well
     *
     * @var int
     */
    public $showInsertDelete = 1;

    /**
     * Element reference, syntax [tablename]:[uid]
     *
     * @var string
     */
    public $element;

    /**
     * syslog ID which is not shown anymore
     *
     * @var int
     */
    public $lastSyslogId;

    /**
     * @var string
     */
    public $returnUrl;

    /**
     * @var array
     */
    public $changeLog = [];

    /**
     * @var bool
     */
    public $showMarked = false;

    /**
     * @var array
     */
    protected $recordCache = [];

    /**
     * @var array
     */
    protected $pageAccessCache = [];

    /**
     * @var string
     */
    protected $rollbackFields = '';

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * Constructor for the class
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        // GPvars:
        $this->element = $this->getArgument('element');
        $this->returnUrl = $this->getArgument('returnUrl');
        $this->lastSyslogId = $this->getArgument('diff');
        $this->rollbackFields = $this->getArgument('rollbackFields');
        // Resolve sh_uid if set
        $this->resolveShUid();

        $this->view = $this->getFluidTemplateObject();
    }

    /**
     * Main function for the listing of history.
     * It detects incoming variables like element reference, history element uid etc. and renders the correct screen.
     *
     * @return string HTML content for the module
     */
    public function main()
    {
        // Save snapshot
        if ($this->getArgument('highlight') && !$this->getArgument('settings')) {
            $this->toggleHighlight($this->getArgument('highlight'));
        }

        $this->displaySettings();

        if ($this->createChangeLog()) {
            if ($this->rollbackFields) {
                $completeDiff = $this->createMultipleDiff();
                $this->performRollback($completeDiff);
            }
            if ($this->lastSyslogId) {
                $this->view->assign('lastSyslogId', $this->lastSyslogId);
                $completeDiff = $this->createMultipleDiff();
                $this->displayMultipleDiff($completeDiff);
            }
            if ($this->element) {
                $this->displayHistory();
            }
        }

        return $this->view->render();
    }

    /*******************************
     *
     * database actions
     *
     *******************************/
    /**
     * Toggles highlight state of record
     *
     * @param int $uid Uid of sys_history entry
     */
    public function toggleHighlight($uid)
    {
        $uid = (int)$uid;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_history');
        $row = $queryBuilder
            ->select('snapshot')
            ->from('sys_history')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)))
            ->execute()
            ->fetch();

        if (!empty($row)) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_history');
            $queryBuilder
                ->update('sys_history')
                ->set('snapshot', (int)!$row['snapshot'])
                ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)))
                ->execute();
        }
    }

    /**
     * perform rollback
     *
     * @param array $diff Diff array to rollback
     * @return string
     * @access private
     */
    public function performRollback($diff)
    {
        if (!$this->rollbackFields) {
            return '';
        }
        $reloadPageFrame = 0;
        $rollbackData = explode(':', $this->rollbackFields);
        // PROCESS INSERTS AND DELETES
        // rewrite inserts and deletes
        $cmdmapArray = [];
        $data = [];
        if ($diff['insertsDeletes']) {
            switch (count($rollbackData)) {
                case 1:
                    // all tables
                    $data = $diff['insertsDeletes'];
                    break;
                case 2:
                    // one record
                    if ($diff['insertsDeletes'][$this->rollbackFields]) {
                        $data[$this->rollbackFields] = $diff['insertsDeletes'][$this->rollbackFields];
                    }
                    break;
                case 3:
                    // one field in one record -- ignore!
                    break;
            }
            if (!empty($data)) {
                foreach ($data as $key => $action) {
                    $elParts = explode(':', $key);
                    if ((int)$action === 1) {
                        // inserted records should be deleted
                        $cmdmapArray[$elParts[0]][$elParts[1]]['delete'] = 1;
                        // When the record is deleted, the contents of the record do not need to be updated
                        unset($diff['oldData'][$key]);
                        unset($diff['newData'][$key]);
                    } elseif ((int)$action === -1) {
                        // deleted records should be inserted again
                        $cmdmapArray[$elParts[0]][$elParts[1]]['undelete'] = 1;
                    }
                }
            }
        }
        // Writes the data:
        if ($cmdmapArray) {
            $tce = GeneralUtility::makeInstance(DataHandler::class);
            $tce->debug = 0;
            $tce->dontProcessTransformations = 1;
            $tce->start([], $cmdmapArray);
            $tce->process_cmdmap();
            unset($tce);
            if (isset($cmdmapArray['pages'])) {
                $reloadPageFrame = 1;
            }
        }
        if (!$diff['insertsDeletes']) {
            // PROCESS CHANGES
            // create an array for process_datamap
            $diffModified = [];
            foreach ($diff['oldData'] as $key => $value) {
                $splitKey = explode(':', $key);
                $diffModified[$splitKey[0]][$splitKey[1]] = $value;
            }
            switch (count($rollbackData)) {
                case 1:
                    // all tables
                    $data = $diffModified;
                    break;
                case 2:
                    // one record
                    $data[$rollbackData[0]][$rollbackData[1]] = $diffModified[$rollbackData[0]][$rollbackData[1]];
                    break;
                case 3:
                    // one field in one record
                    $data[$rollbackData[0]][$rollbackData[1]][$rollbackData[2]] = $diffModified[$rollbackData[0]][$rollbackData[1]][$rollbackData[2]];
                    break;
            }
            // Removing fields:
            $data = $this->removeFilefields($rollbackData[0], $data);
            // Writes the data:
            $tce = GeneralUtility::makeInstance(DataHandler::class);
            $tce->debug = 0;
            $tce->dontProcessTransformations = 1;
            $tce->start($data, []);
            $tce->process_datamap();
            unset($tce);
            if (isset($data['pages'])) {
                $reloadPageFrame = 1;
            }
        }
        // Return to normal operation
        $this->lastSyslogId = false;
        $this->rollbackFields = '';
        $this->createChangeLog();
        $this->view->assign('reloadPageFrame', $reloadPageFrame);
    }

    /*******************************
     *
     * Display functions
     *
     *******************************/
    /**
     * Displays settings
     */
    public function displaySettings()
    {
        // Get current selection from UC, merge data, write it back to UC
        $currentSelection = is_array($this->getBackendUser()->uc['moduleData']['history'])
            ? $this->getBackendUser()->uc['moduleData']['history']
            : ['maxSteps' => '', 'showDiff' => 1, 'showSubElements' => 1, 'showInsertDelete' => 1];
        $currentSelectionOverride = $this->getArgument('settings');
        if ($currentSelectionOverride) {
            $currentSelection = array_merge($currentSelection, $currentSelectionOverride);
            $this->getBackendUser()->uc['moduleData']['history'] = $currentSelection;
            $this->getBackendUser()->writeUC($this->getBackendUser()->uc);
        }
        // Display selector for number of history entries
        $selector['maxSteps'] = [
            10 => [
                'value' => 10
            ],
            20 => [
                'value' => 20
            ],
            50 => [
                'value' => 50
            ],
            100 => [
                'value' => 100
            ],
            999 => [
                'value' => 'maxSteps_all'
            ],
            'marked' => [
                'value' => 'maxSteps_marked'
            ]
        ];
        $selector['showDiff'] = [
            0 => [
                'value' => 'showDiff_no'
            ],
            1 => [
                'value' => 'showDiff_inline'
            ]
        ];
        $selector['showSubElements'] = [
            0 => [
                'value' => 'no'
            ],
            1 => [
                'value' => 'yes'
            ]
        ];
        $selector['showInsertDelete'] = [
            0 => [
                'value' => 'no'
            ],
            1 => [
                'value' => 'yes'
            ]
        ];

        $scriptUrl = GeneralUtility::linkThisScript();
        $languageService = $this->getLanguageService();

        foreach ($selector as $key => $values) {
            foreach ($values as $singleKey => $singleVal) {
                $selector[$key][$singleKey]['scriptUrl'] = htmlspecialchars(GeneralUtility::quoteJSvalue($scriptUrl . '&settings[' . $key . ']=' . $singleKey));
            }
        }
        $this->view->assign('settings', $selector);
        $this->view->assign('currentSelection', $currentSelection);
        $this->view->assign('TYPO3_REQUEST_URI', htmlspecialchars(GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL')));

        // set values correctly
        if ($currentSelection['maxSteps'] !== 'marked') {
            $this->maxSteps = $currentSelection['maxSteps'] ? (int)$currentSelection['maxSteps'] : $this->maxSteps;
        } else {
            $this->showMarked = true;
            $this->maxSteps = false;
        }
        $this->showDiff = (int)$currentSelection['showDiff'];
        $this->showSubElements = (int)$currentSelection['showSubElements'];
        $this->showInsertDelete = (int)$currentSelection['showInsertDelete'];

        // Get link to page history if the element history is shown
        $elParts = explode(':', $this->element);
        if (!empty($this->element) && $elParts[0] !== 'pages') {
            $this->view->assign('singleElement', 'true');
            $pid = $this->getRecord($elParts[0], $elParts[1]);

            if ($this->hasPageAccess('pages', $pid['pid'])) {
                $this->view->assign('fullHistoryLink', $this->linkPage(htmlspecialchars($languageService->getLL('elementHistory_link')), ['element' => 'pages:' . $pid['pid']]));
            }
        }
    }

    /**
     * Shows the full change log
     *
     * @return string HTML for list, wrapped in a table.
     */
    public function displayHistory()
    {
        if (empty($this->changeLog)) {
            return '';
        }
        $languageService = $this->getLanguageService();
        $lines = [];
        $beUserArray = BackendUtility::getUserNames();

        $i = 0;

        // Traverse changeLog array:
        foreach ($this->changeLog as $sysLogUid => $entry) {
            // stop after maxSteps
            if ($this->maxSteps && $i > $this->maxSteps) {
                break;
            }
            // Show only marked states
            if (!$entry['snapshot'] && $this->showMarked) {
                continue;
            }
            $i++;
            // Build up single line
            $singleLine = [];

            // Get user names
            $userName = $entry['user'] ? $beUserArray[$entry['user']]['username'] : $languageService->getLL('externalChange');
            // Executed by switch-user
            if (!empty($entry['originalUser'])) {
                $userName .= ' (' . $languageService->getLL('viaUser') . ' ' . $beUserArray[$entry['originalUser']]['username'] . ')';
            }
            $singleLine['backendUserName'] = htmlspecialchars($userName);
            $singleLine['backendUserUid'] = $entry['user'];
            // add user name

            // Diff link
            $image = $this->iconFactory->getIcon('actions-document-history-open', Icon::SIZE_SMALL)->render();
            $singleLine['rollbackLink']= $this->linkPage($image, ['diff' => $sysLogUid]);
            // remove first link
            $singleLine['time'] = htmlspecialchars(BackendUtility::datetime($entry['tstamp']));
            // add time
            $singleLine['age'] = htmlspecialchars(BackendUtility::calcAge($GLOBALS['EXEC_TIME'] - $entry['tstamp'], $languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears')));
            // add age

            $singleLine['tableUid'] = $this->linkPage(
                $this->generateTitle($entry['tablename'], $entry['recuid']),
                ['element' => $entry['tablename'] . ':' . $entry['recuid']],
                '',
                htmlspecialchars($languageService->getLL('linkRecordHistory'))
            );
            // add record UID
            // Show insert/delete/diff/changed field names
            if ($entry['action']) {
                // insert or delete of element
                $singleLine['action'] = htmlspecialchars($languageService->getLL($entry['action']));
            } else {
                // Display field names instead of full diff
                if (!$this->showDiff) {
                    // Re-write field names with labels
                    $tmpFieldList = explode(',', $entry['fieldlist']);
                    foreach ($tmpFieldList as $key => $value) {
                        $tmp = str_replace(':', '', htmlspecialchars($languageService->sL(BackendUtility::getItemLabel($entry['tablename'], $value))));
                        if ($tmp) {
                            $tmpFieldList[$key] = $tmp;
                        } else {
                            // remove fields if no label available
                            unset($tmpFieldList[$key]);
                        }
                    }
                    $singleLine['fieldNames'] = htmlspecialchars(implode(',', $tmpFieldList));
                } else {
                    // Display diff
                    $diff = $this->renderDiff($entry, $entry['tablename']);
                    $singleLine['differences'] = $diff;
                }
            }
            // Show link to mark/unmark state
            if (!$entry['action']) {
                if ($entry['snapshot']) {
                    $title = htmlspecialchars($languageService->getLL('unmarkState'));
                    $image = $this->iconFactory->getIcon('actions-unmarkstate', Icon::SIZE_SMALL)->render();
                } else {
                    $title = htmlspecialchars($languageService->getLL('markState'));
                    $image = $this->iconFactory->getIcon('actions-markstate', Icon::SIZE_SMALL)->render();
                }
                $singleLine['markState'] = $this->linkPage($image, ['highlight' => $entry['uid']], '', $title);
            } else {
                $singleLine['markState'] = '';
            }
            // put line together
            $lines[] = $singleLine;
        }
        $this->view->assign('history', $lines);

        if ($this->lastSyslogId) {
            $this->view->assign('fullViewLink', $this->linkPage(htmlspecialchars($languageService->getLL('fullView')), ['diff' => '']));
        }
    }

    /**
     * Displays a diff over multiple fields including rollback links
     *
     * @param array $diff Difference array
     */
    public function displayMultipleDiff($diff)
    {
        // Get all array keys needed
        $arrayKeys = array_merge(array_keys($diff['newData']), array_keys($diff['insertsDeletes']), array_keys($diff['oldData']));
        $arrayKeys = array_unique($arrayKeys);
        $languageService = $this->getLanguageService();
        if ($arrayKeys) {
            $lines = [];
            foreach ($arrayKeys as $key) {
                $singleLine = [];
                $elParts = explode(':', $key);
                // Turn around diff because it should be a "rollback preview"
                if ((int)$diff['insertsDeletes'][$key] === 1) {
                    // insert
                    $singleLine['insertDelete'] = 'delete';
                } elseif ((int)$diff['insertsDeletes'][$key] === -1) {
                    $singleLine['insertDelete'] = 'insert';
                }
                // Build up temporary diff array
                // turn around diff because it should be a "rollback preview"
                if ($diff['newData'][$key]) {
                    $tmpArr['newRecord'] = $diff['oldData'][$key];
                    $tmpArr['oldRecord'] = $diff['newData'][$key];
                    $singleLine['differences'] = $this->renderDiff($tmpArr, $elParts[0], $elParts[1]);
                }
                $elParts = explode(':', $key);
                $singleLine['revertRecordLink'] = $this->createRollbackLink($key, htmlspecialchars($languageService->getLL('revertRecord')), 1);
                $singleLine['title'] = $this->generateTitle($elParts[0], $elParts[1]);
                $lines[] = $singleLine;
            }
            $this->view->assign('revertAllLink', $this->createRollbackLink('ALL', htmlspecialchars($languageService->getLL('revertAll')), 0));
            $this->view->assign('multipleDiff', $lines);
        }
    }

    /**
     * Renders HTML table-rows with the comparison information of an sys_history entry record
     *
     * @param array $entry sys_history entry record.
     * @param string $table The table name
     * @param int $rollbackUid If set to UID of record, display rollback links
     * @return string|null HTML table
     * @access private
     */
    public function renderDiff($entry, $table, $rollbackUid = 0)
    {
        $lines = [];
        if (is_array($entry['newRecord'])) {
            /* @var DiffUtility $diffUtility */
            $diffUtility = GeneralUtility::makeInstance(DiffUtility::class);
            $diffUtility->stripTags = false;
            $fieldsToDisplay = array_keys($entry['newRecord']);
            $languageService = $this->getLanguageService();
            foreach ($fieldsToDisplay as $fN) {
                if (is_array($GLOBALS['TCA'][$table]['columns'][$fN]) && $GLOBALS['TCA'][$table]['columns'][$fN]['config']['type'] !== 'passthrough') {
                    // Create diff-result:
                    $diffres = $diffUtility->makeDiffDisplay(
                        BackendUtility::getProcessedValue($table, $fN, $entry['oldRecord'][$fN], 0, true),
                        BackendUtility::getProcessedValue($table, $fN, $entry['newRecord'][$fN], 0, true)
                    );
                    $lines[] = [
                        'title' => ($rollbackUid ? $this->createRollbackLink(($table . ':' . $rollbackUid . ':' . $fN), htmlspecialchars($languageService->getLL('revertField')), 2) : '') . '
                          ' . htmlspecialchars($languageService->sL(BackendUtility::getItemLabel($table, $fN))),
                        'result' => str_replace('\n', PHP_EOL, str_replace('\r\n', '\n', $diffres))
                    ];
                }
            }
        }
        if ($lines) {
            return $lines;
        }
        // error fallback
        return null;
    }

    /*******************************
     *
     * build up history
     *
     *******************************/
    /**
     * Creates a diff between the current version of the records and the selected version
     *
     * @return array Diff for many elements, 0 if no changelog is found
     */
    public function createMultipleDiff()
    {
        $insertsDeletes = [];
        $newArr = [];
        $differences = [];
        if (!$this->changeLog) {
            return 0;
        }
        // traverse changelog array
        foreach ($this->changeLog as $value) {
            $field = $value['tablename'] . ':' . $value['recuid'];
            // inserts / deletes
            if ($value['action']) {
                if (!$insertsDeletes[$field]) {
                    $insertsDeletes[$field] = 0;
                }
                if ($value['action'] === 'insert') {
                    $insertsDeletes[$field]++;
                } else {
                    $insertsDeletes[$field]--;
                }
                // unset not needed fields
                if ($insertsDeletes[$field] === 0) {
                    unset($insertsDeletes[$field]);
                }
            } else {
                // update fields
                // first row of field
                if (!isset($newArr[$field])) {
                    $newArr[$field] = $value['newRecord'];
                    $differences[$field] = $value['oldRecord'];
                } else {
                    // standard
                    $differences[$field] = array_merge($differences[$field], $value['oldRecord']);
                }
            }
        }
        // remove entries where there were no changes effectively
        foreach ($newArr as $record => $value) {
            foreach ($value as $key => $innerVal) {
                if ($newArr[$record][$key] == $differences[$record][$key]) {
                    unset($newArr[$record][$key]);
                    unset($differences[$record][$key]);
                }
            }
            if (empty($newArr[$record]) && empty($differences[$record])) {
                unset($newArr[$record]);
                unset($differences[$record]);
            }
        }
        return [
            'newData' => $newArr,
            'oldData' => $differences,
            'insertsDeletes' => $insertsDeletes
        ];
    }

    /**
     * Creates change log including sub-elements, filling $this->changeLog
     *
     * @return int
     */
    public function createChangeLog()
    {
        $elParts = explode(':', $this->element);

        if (empty($this->element)) {
            return 0;
        }

        $changeLog = $this->getHistoryData($elParts[0], $elParts[1]);
        // get history of tables of this page and merge it into changelog
        if ($elParts[0] === 'pages' && $this->showSubElements && $this->hasPageAccess('pages', $elParts[1])) {
            foreach ($GLOBALS['TCA'] as $tablename => $value) {
                // check if there are records on the page
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tablename);
                $queryBuilder->getRestrictions()->removeAll();

                $rows = $queryBuilder
                    ->select('uid')
                    ->from($tablename)
                    ->where(
                        $queryBuilder->expr()->eq(
                            'pid',
                            $queryBuilder->createNamedParameter($elParts[1], \PDO::PARAM_INT)
                        )
                    )
                    ->execute();
                if ($rows->rowCount() === 0) {
                    continue;
                }
                foreach ($rows as $row) {
                    // if there is history data available, merge it into changelog
                    $newChangeLog = $this->getHistoryData($tablename, $row['uid']);
                    if (is_array($newChangeLog) && !empty($newChangeLog)) {
                        foreach ($newChangeLog as $key => $newChangeLogEntry) {
                            $changeLog[$key] = $newChangeLogEntry;
                        }
                    }
                }
            }
        }
        if (!$changeLog) {
            return 0;
        }
        krsort($changeLog);
        $this->changeLog = $changeLog;
        return 1;
    }

    /**
     * Gets history and delete/insert data from sys_log and sys_history
     *
     * @param string $table DB table name
     * @param int $uid UID of record
     * @return array|int Array of history data of the record or 0 if no history could be fetched
     */
    public function getHistoryData($table, $uid)
    {
        if (empty($GLOBALS['TCA'][$table]) || !$this->hasTableAccess($table) || !$this->hasPageAccess($table, $uid)) {
            // error fallback
            return 0;
        }
        // If table is found in $GLOBALS['TCA']:
        $uid = $this->resolveElement($table, $uid);
        // Selecting the $this->maxSteps most recent states:
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_history');
        $rows = $queryBuilder
            ->select('sys_history.*', 'sys_log.userid', 'sys_log.log_data')
            ->from('sys_history')
            ->from('sys_log')
            ->where(
                $queryBuilder->expr()->eq(
                    'sys_history.sys_log_uid',
                    $queryBuilder->quoteIdentifier('sys_log.uid')
                ),
                $queryBuilder->expr()->eq(
                    'sys_history.tablename',
                    $queryBuilder->createNamedParameter($table, \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'sys_history.recuid',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            )
            ->orderBy('sys_log.uid', 'DESC')
            ->setMaxResults((int)$this->maxSteps)
            ->execute()
            ->fetchAll();

        $changeLog = [];
        if (!empty($rows)) {
            // Traversing the result, building up changesArray / changeLog:
            foreach ($rows as $row) {
                // Only history until a certain syslog ID needed
                if ($this->lastSyslogId && $row['sys_log_uid'] < $this->lastSyslogId) {
                    continue;
                }
                $hisDat = unserialize($row['history_data']);
                $logData = unserialize($row['log_data']);
                if (is_array($hisDat['newRecord']) && is_array($hisDat['oldRecord'])) {
                    // Add information about the history to the changeLog
                    $hisDat['uid'] = $row['uid'];
                    $hisDat['tstamp'] = $row['tstamp'];
                    $hisDat['user'] = $row['userid'];
                    $hisDat['originalUser'] = (empty($logData['originalUser']) ? null : $logData['originalUser']);
                    $hisDat['snapshot'] = $row['snapshot'];
                    $hisDat['fieldlist'] = $row['fieldlist'];
                    $hisDat['tablename'] = $row['tablename'];
                    $hisDat['recuid'] = $row['recuid'];
                    $changeLog[$row['sys_log_uid']] = $hisDat;
                } else {
                    debug('ERROR: [getHistoryData]');
                    // error fallback
                    return 0;
                }
            }
        }
        // SELECT INSERTS/DELETES
        if ($this->showInsertDelete) {
            // Select most recent inserts and deletes // WITHOUT snapshots
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_log');
            $result = $queryBuilder
                ->select('uid', 'userid', 'action', 'tstamp', 'log_data')
                ->from('sys_log')
                ->where(
                    $queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->eq('action', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)),
                        $queryBuilder->expr()->eq('action', $queryBuilder->createNamedParameter(3, \PDO::PARAM_INT))
                    ),
                    $queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter($table, \PDO::PARAM_STR)),
                    $queryBuilder->expr()->eq('recuid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
                )
                ->orderBy('uid', 'DESC')
                ->setMaxResults((int)$this->maxSteps)
                ->execute();

            // If none are found, nothing more to do
            if ($result->rowCount() === 0) {
                return $changeLog;
            }
            foreach ($result as $row) {
                if ($this->lastSyslogId && $row['uid'] < $this->lastSyslogId) {
                    continue;
                }
                $hisDat = [];
                $logData = unserialize($row['log_data']);
                switch ($row['action']) {
                    case 1:
                        // Insert
                        $hisDat['action'] = 'insert';
                        break;
                    case 3:
                        // Delete
                        $hisDat['action'] = 'delete';
                        break;
                }
                $hisDat['tstamp'] = $row['tstamp'];
                $hisDat['user'] = $row['userid'];
                $hisDat['originalUser'] = (empty($logData['originalUser']) ? null : $logData['originalUser']);
                $hisDat['tablename'] = $table;
                $hisDat['recuid'] = $uid;
                $changeLog[$row['uid']] = $hisDat;
            }
        }
        return $changeLog;
    }

    /*******************************
     *
     * Various helper functions
     *
     *******************************/
    /**
     * Generates the title and puts the record title behind
     *
     * @param string $table
     * @param string $uid
     * @return string
     */
    public function generateTitle($table, $uid)
    {
        $out = $table . ':' . $uid;
        if ($labelField = $GLOBALS['TCA'][$table]['ctrl']['label']) {
            $record = $this->getRecord($table, $uid);
            $out .= ' (' . BackendUtility::getRecordTitle($table, $record, true) . ')';
        }
        return $out;
    }

    /**
     * Creates a link for the rollback
     *
     * @param string $key Parameter which is set to rollbackFields
     * @param string $alt Optional, alternative label and title tag of image
     * @param int $type Optional, type of rollback: 0 - ALL; 1 - element; 2 - field
     * @return string HTML output
     */
    public function createRollbackLink($key, $alt = '', $type = 0)
    {
        return $this->linkPage('<span class="btn btn-default" style="margin-right: 5px;">' . $alt . '</span>', ['rollbackFields' => $key]);
    }

    /**
     * Creates a link to the same page.
     *
     * @param string $str String to wrap in <a> tags (must be htmlspecialchars()'ed prior to calling function)
     * @param array $inparams Array of key/value pairs to override the default values with.
     * @param string $anchor Possible anchor value.
     * @param string $title Possible title.
     * @return string Link.
     * @access private
     */
    public function linkPage($str, $inparams = [], $anchor = '', $title = '')
    {
        // Setting default values based on GET parameters:
        $params['element'] = $this->element;
        $params['returnUrl'] = $this->returnUrl;
        $params['diff'] = $this->lastSyslogId;
        // Merging overriding values:
        $params = array_merge($params, $inparams);
        // Make the link:
        $link = BackendUtility::getModuleUrl('record_history', $params) . ($anchor ? '#' . $anchor : '');
        return '<a href="' . htmlspecialchars($link) . '"' . ($title ? ' title="' . $title . '"' : '') . '>' . $str . '</a>';
    }

    /**
     * Will traverse the field names in $dataArray and look in $GLOBALS['TCA'] if the fields are of types which cannot
     * be handled by the sys_history (that is currently group types with internal_type set to "file")
     *
     * @param string $table Table name
     * @param array $dataArray The data array
     * @return array The modified data array
     * @access private
     */
    public function removeFilefields($table, $dataArray)
    {
        if ($GLOBALS['TCA'][$table]) {
            foreach ($GLOBALS['TCA'][$table]['columns'] as $field => $config) {
                if ($config['config']['type'] === 'group' && $config['config']['internal_type'] === 'file') {
                    unset($dataArray[$field]);
                }
            }
        }
        return $dataArray;
    }

    /**
     * Convert input element reference to workspace version if any.
     *
     * @param string $table Table of input element
     * @param int $uid UID of record
     * @return int converted UID of record
     */
    public function resolveElement($table, $uid)
    {
        if (isset($GLOBALS['TCA'][$table])) {
            if ($workspaceVersion = BackendUtility::getWorkspaceVersionOfRecord($this->getBackendUser()->workspace, $table, $uid, 'uid')) {
                $uid = $workspaceVersion['uid'];
            }
        }
        return $uid;
    }

    /**
     * Resolve sh_uid (used from log)
     */
    public function resolveShUid()
    {
        $shUid = $this->getArgument('sh_uid');
        if (empty($shUid)) {
            return;
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_history');
        $record = $queryBuilder
            ->select('*')
            ->from('sys_history')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($shUid, \PDO::PARAM_INT)))
            ->execute()
            ->fetch();

        if (empty($record)) {
            return;
        }
        $this->element = $record['tablename'] . ':' . $record['recuid'];
        $this->lastSyslogId = $record['sys_log_uid'] - 1;
    }

    /**
     * Determines whether user has access to a page.
     *
     * @param string $table
     * @param int $uid
     * @return bool
     */
    protected function hasPageAccess($table, $uid)
    {
        $uid = (int)$uid;

        if ($table === 'pages') {
            $pageId = $uid;
        } else {
            $record = $this->getRecord($table, $uid);
            $pageId = $record['pid'];
        }

        if (!isset($this->pageAccessCache[$pageId])) {
            $isDeletedPage = false;
            if ($this->showInsertDelete && isset($GLOBALS['TCA']['pages']['ctrl']['delete'])) {
                $deletedField = $GLOBALS['TCA']['pages']['ctrl']['delete'];
                $pageRecord = $this->getRecord('pages', $pageId);
                $isDeletedPage = (bool)$pageRecord[$deletedField];
            }
            if ($isDeletedPage) {
                // The page is deleted, so we fake its uid to be the one of the parent page.
                // By doing so, the following API will use this id to traverse the rootline
                // and check whether it is in the users' web mounts.
                // We check however if the user has (or better had) access to the deleted page itself.
                // Since the only way we got here is by requesting the history of the parent page
                // we can be sure this parent page actually exists.
                $pageRecord['uid'] = $pageRecord['pid'];
                $this->pageAccessCache[$pageId] = $this->getBackendUser()->doesUserHaveAccess($pageRecord, Permission::PAGE_SHOW);
            } else {
                $this->pageAccessCache[$pageId] = BackendUtility::readPageAccess(
                    $pageId,
                    $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW)
                );
            }
        }

        return $this->pageAccessCache[$pageId] !== false;
    }

    /**
     * Determines whether user has access to a table.
     *
     * @param string $table
     * @return bool
     */
    protected function hasTableAccess($table)
    {
        return $this->getBackendUser()->check('tables_select', $table);
    }

    /**
     * Gets a database record.
     *
     * @param string $table
     * @param int $uid
     * @return array|null
     */
    protected function getRecord($table, $uid)
    {
        if (!isset($this->recordCache[$table][$uid])) {
            $this->recordCache[$table][$uid] = BackendUtility::getRecord($table, $uid, '*', '', false);
        }
        return $this->recordCache[$table][$uid];
    }

    /**
     * Gets the current backend user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Fetches GET/POST arguments and sanitizes the values for
     * the expected disposal. Invalid values will be converted
     * to an empty string.
     *
     * @param string $name Name of the argument
     * @return array|string|int
     */
    protected function getArgument($name)
    {
        $value = GeneralUtility::_GP($name);

        switch ($name) {
            case 'element':
                if ($value !== '' && !preg_match('#^[a-z0-9_.]+:[0-9]+$#i', $value)) {
                    $value = '';
                }
                break;
            case 'rollbackFields':
            case 'revert':
                if ($value !== '' && !preg_match('#^[a-z0-9_.]+(:[0-9]+(:[a-z0-9_.]+)?)?$#i', $value)) {
                    $value = '';
                }
                break;
            case 'returnUrl':
                $value = GeneralUtility::sanitizeLocalUrl($value);
                break;
            case 'diff':
            case 'highlight':
            case 'sh_uid':
                $value = (int)$value;
                break;
            case 'settings':
                if (!is_array($value)) {
                    $value = [];
                }
                break;
            default:
                $value = '';
        }

        return $value;
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * returns a new standalone view, shorthand function
     *
     * @return StandaloneView
     */
    protected function getFluidTemplateObject()
    {
        /** @var StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Layouts')]);
        $view->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Partials')]);
        $view->setTemplateRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates')]);

        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates/RecordHistory/Main.html'));

        $view->getRequest()->setControllerExtensionName('Backend');
        return $view;
    }
}
