<?php
namespace TYPO3\CMS\Backend\Controller\ContentElement;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\History\RecordHistory;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\History\RecordHistoryStore;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\DiffUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Controller for showing the history module of TYPO3s backend
 * @see \TYPO3\CMS\Backend\History\RecordHistory
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class ElementHistoryController
{
    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * @var RecordHistory
     */
    protected $historyObject;

    /**
     * Display diff or not (0-no diff, 1-inline)
     *
     * @var int
     */
    protected $showDiff = 1;

    /**
     * @var array
     */
    protected $recordCache = [];

    /**
     * ModuleTemplate object
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * Restrict editing by non-Admins (0-no, 1-yes)
     *
     * @var bool
     */
    protected $editLock = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->view = $this->initializeView();
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation([]);

        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        $lastHistoryEntry = (int)($parsedBody['historyEntry'] ?? $queryParams['historyEntry'] ?? 0);
        $rollbackFields = $parsedBody['rollbackFields'] ?? $queryParams['rollbackFields'] ?? null;
        $element = $parsedBody['element'] ?? $queryParams['element'] ?? null;
        $displaySettings = $this->prepareDisplaySettings($request);
        $this->view->assign('currentSelection', $displaySettings);

        $this->showDiff = (int)$displaySettings['showDiff'];

        // Start history object
        $this->historyObject = GeneralUtility::makeInstance(RecordHistory::class, $element, $rollbackFields);
        $this->historyObject->setShowSubElements((int)$displaySettings['showSubElements']);
        $this->historyObject->setLastHistoryEntry($lastHistoryEntry);
        if ($displaySettings['maxSteps']) {
            $this->historyObject->setMaxSteps((int)$displaySettings['maxSteps']);
        }

        // Do the actual logic now (rollback, show a diff for certain changes,
        // or show the full history of a page or a specific record)
        $this->historyObject->createChangeLog();
        if (!empty($this->historyObject->changeLog)) {
            if ($this->historyObject->shouldPerformRollback()) {
                $this->historyObject->performRollback();
            } elseif ($lastHistoryEntry) {
                $completeDiff = $this->historyObject->createMultipleDiff();
                $this->displayMultipleDiff($completeDiff);
                $this->view->assign('showDifferences', true);
                $this->view->assign('fullViewUrl', $this->buildUrl(['historyEntry' => '']));
            }
            if ($this->historyObject->getElementData()) {
                $this->displayHistory($this->historyObject->changeLog);
            }
        }

        /** @var \TYPO3\CMS\Core\Http\NormalizedParams $normalizedParams */
        $normalizedParams = $request->getAttribute('normalizedParams');
        $elementData = $this->historyObject->getElementData();
        if ($elementData) {
            $this->setPagePath($elementData[0], $elementData[1]);
            $this->editLock = $this->getEditLockFromElement($elementData[0], $elementData[1]);
            // Get link to page history if the element history is shown
            if ($elementData[0] !== 'pages') {
                $this->view->assign('singleElement', true);
                $parentPage = BackendUtility::getRecord($elementData[0], $elementData[1], '*', '', false);
                if ($parentPage['pid'] > 0 && BackendUtility::readPageAccess($parentPage['pid'], $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW))) {
                    $this->view->assign('fullHistoryUrl', $this->buildUrl([
                        'element' => 'pages:' . $parentPage['pid'],
                        'historyEntry' => '',
                        'returnUrl' => $normalizedParams->getRequestUri(),
                    ]));
                }
            }
        }

        $this->view->assign('TYPO3_REQUEST_URI', $normalizedParams->getRequestUrl());
        $this->view->assign('editLock', $this->editLock);

        // Setting up the buttons and markers for docheader
        $this->getButtons($request);
        // Build the <body> for the module
        $this->moduleTemplate->setContent($this->view->render());

        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Creates the correct path to the current record
     *
     * @param string $table
     * @param int $uid
     */
    protected function setPagePath($table, $uid)
    {
        $uid = (int)$uid;

        if ($table === 'pages') {
            $pageId = $uid;
        } else {
            $record = BackendUtility::getRecord($table, $uid, '*', '', false);
            $pageId = $record['pid'];
        }

        $pageAccess = BackendUtility::readPageAccess($pageId, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));
        if (is_array($pageAccess)) {
            $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($pageAccess);
        }
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     *
     * @param ServerRequestInterface $request
     */
    protected function getButtons(ServerRequestInterface $request)
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $helpButton = $buttonBar->makeHelpButton()
            ->setModuleName('xMOD_csh_corebe')
            ->setFieldName('history_log');
        $buttonBar->addButton($helpButton);

        // Get returnUrl parameter
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        $returnUrl = GeneralUtility::sanitizeLocalUrl($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? '');

        if ($returnUrl) {
            $backButton = $buttonBar->makeLinkButton()
                ->setHref($returnUrl)
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.closeDoc'))
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
            $buttonBar->addButton($backButton, ButtonBar::BUTTON_POSITION_LEFT, 10);
        }
    }

    /**
     * Displays settings evaluation
     *
     * @param ServerRequestInterface $request
     * @return array
     */
    protected function prepareDisplaySettings(ServerRequestInterface $request)
    {
        // Get current selection from UC, merge data, write it back to UC
        $currentSelection = is_array($this->getBackendUser()->uc['moduleData']['history'])
            ? $this->getBackendUser()->uc['moduleData']['history']
            : ['maxSteps' => '', 'showDiff' => 1, 'showSubElements' => 1];
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        $currentSelectionOverride = $parsedBody['settings'] ?? $queryParams['settings'] ?? null;

        if (is_array($currentSelectionOverride) && !empty($currentSelectionOverride)) {
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

        $scriptUrl = GeneralUtility::linkThisScript();

        foreach ($selector as $key => $values) {
            foreach ($values as $singleKey => $singleVal) {
                $selector[$key][$singleKey]['scriptUrl'] = htmlspecialchars(GeneralUtility::quoteJSvalue($scriptUrl . '&settings[' . $key . ']=' . $singleKey));
            }
        }
        $this->view->assign('settings', $selector);
        return $currentSelection;
    }

    /**
     * Displays a diff over multiple fields including rollback links
     *
     * @param array $diff Difference array
     */
    protected function displayMultipleDiff(array $diff)
    {
        // Get all array keys needed
        $arrayKeys = array_merge(array_keys($diff['newData']), array_keys($diff['insertsDeletes']), array_keys($diff['oldData']));
        $arrayKeys = array_unique($arrayKeys);
        if (!empty($arrayKeys)) {
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
                    $tmpArr = [
                        'newRecord' => $diff['oldData'][$key],
                        'oldRecord' => $diff['newData'][$key]
                    ];
                    $singleLine['differences'] = $this->renderDiff($tmpArr, $elParts[0], $elParts[1]);
                }
                $elParts = explode(':', $key);
                $singleLine['revertRecordUrl'] = $this->buildUrl(['rollbackFields' => $key]);
                $singleLine['title'] = $this->generateTitle($elParts[0], $elParts[1]);
                $lines[] = $singleLine;
            }
            $this->view->assign('revertAllUrl', $this->buildUrl(['rollbackFields' => 'ALL']));
            $this->view->assign('multipleDiff', $lines);
        }
    }

    /**
     * Shows the full change log
     *
     * @param array $historyEntries
     */
    protected function displayHistory(array $historyEntries)
    {
        if (empty($historyEntries)) {
            return;
        }
        $languageService = $this->getLanguageService();
        $lines = [];
        $beUserArray = BackendUtility::getUserNames();

        // Traverse changeLog array:
        foreach ($historyEntries as $entry) {
            // Build up single line
            $singleLine = [];

            // Get user names
            $singleLine['backendUserUid'] = $entry['userid'];
            $singleLine['backendUserName'] = $entry['userid'] ? $beUserArray[$entry['userid']]['username'] : '';
            // Executed by switch user
            if (!empty($entry['originaluserid'])) {
                $singleLine['originalBackendUserName'] = $beUserArray[$entry['originaluserid']]['username'];
            }

            // Diff link
            $singleLine['diffUrl'] = $this->buildUrl(['historyEntry' => $entry['uid']]);
            // Add time
            $singleLine['time'] = BackendUtility::datetime($entry['tstamp']);
            // Add age
            $singleLine['age'] = BackendUtility::calcAge($GLOBALS['EXEC_TIME'] - $entry['tstamp'], $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears'));

            $singleLine['title'] = $this->generateTitle($entry['tablename'], $entry['recuid']);
            $singleLine['elementUrl'] = $this->buildUrl(['element' => $entry['tablename'] . ':' . $entry['recuid']]);
            $singleLine['actiontype'] = $entry['actiontype'];
            if ((int)$entry['actiontype'] === RecordHistoryStore::ACTION_MODIFY) {
                // show changes
                if (!$this->showDiff) {
                    // Display field names instead of full diff
                    // Re-write field names with labels
                    $tmpFieldList = array_keys($entry['newRecord']);
                    foreach ($tmpFieldList as $key => $value) {
                        $tmp = str_replace(':', '', $languageService->sL(BackendUtility::getItemLabel($entry['tablename'], $value)));
                        if ($tmp) {
                            $tmpFieldList[$key] = $tmp;
                        } else {
                            // remove fields if no label available
                            unset($tmpFieldList[$key]);
                        }
                    }
                    $singleLine['fieldNames'] = implode(',', $tmpFieldList);
                } else {
                    // Display diff
                    $singleLine['differences'] = $this->renderDiff($entry, $entry['tablename']);
                }
            }
            // put line together
            $lines[] = $singleLine;
        }
        $this->view->assign('history', $lines);
    }

    /**
     * Renders HTML table-rows with the comparison information of an sys_history entry record
     *
     * @param array $entry sys_history entry record.
     * @param string $table The table name
     * @param int $rollbackUid If set to UID of record, display rollback links
     * @return array array of records
     */
    protected function renderDiff($entry, $table, $rollbackUid = 0): array
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
                    $rollbackUrl = '';
                    if ($rollbackUid) {
                        $rollbackUrl = $this->buildUrl(['rollbackFields' => $table . ':' . $rollbackUid . ':' . $fN]);
                    }
                    $lines[] = [
                        'title' => $languageService->sL(BackendUtility::getItemLabel($table, $fN)),
                        'rollbackUrl' => $rollbackUrl,
                        'result' => str_replace('\n', PHP_EOL, str_replace('\r\n', '\n', $diffres))
                    ];
                }
            }
        }
        return $lines;
    }

    /**
     * Generates the URL for a link to the current page
     *
     * @param array $overrideParameters
     * @return string
     */
    protected function buildUrl($overrideParameters = []): string
    {
        $params = [];
        // Setting default values based on GET parameters:
        if ($this->historyObject->getElementData()) {
            $params['element'] = $this->historyObject->getElementString();
        }
        $params['historyEntry'] = $this->historyObject->lastHistoryEntry;
        // Merging overriding values:
        $params = array_merge($params, $overrideParameters);
        // Make the link:

        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute('record_history', $params);
    }

    /**
     * Generates the title and puts the record title behind
     *
     * @param string $table
     * @param string $uid
     * @return string
     */
    protected function generateTitle($table, $uid): string
    {
        $title = $table . ':' . $uid;
        if (!empty($GLOBALS['TCA'][$table]['ctrl']['label'])) {
            $record = $this->getRecord($table, $uid);
            $title .= ' (' . BackendUtility::getRecordTitle($table, $record, true) . ')';
        }
        return $title;
    }

    /**
     * Gets a database record (cached).
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
     * Returns a new standalone view, shorthand function
     *
     * @return StandaloneView
     */
    protected function initializeView()
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

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
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
     * Get the editlock value from page of a history element
     *
     * @param string $tableName
     * @param int $elementUid
     *
     * @return bool
     */
    protected function getEditLockFromElement($tableName, $elementUid): bool
    {
        // If the user is admin, then he may always edit the page.
        if ($this->getBackendUser()->isAdmin()) {
            return false;
        }

        $record = BackendUtility::getRecord($tableName, $elementUid, '*', '', false);
        // we need the parent page record for the editlock info if element isn't a page
        if ($tableName !== 'pages') {
            $pageId = $record['pid'];
            $record = BackendUtility::getRecord('pages', $pageId, '*', '', false);
        }

        return (bool)$record['editlock'];
    }
}
