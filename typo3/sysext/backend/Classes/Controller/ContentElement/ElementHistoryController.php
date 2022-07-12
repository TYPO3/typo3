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

namespace TYPO3\CMS\Backend\Controller\ContentElement;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\History\RecordHistory;
use TYPO3\CMS\Backend\History\RecordHistoryRollback;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\History\RecordHistoryStore;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
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
     * Display inline differences or not
     *
     * @var bool
     */
    protected $showDiff = true;

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
     * @var string
     */
    protected string $returnUrl = '';

    protected IconFactory $iconFactory;
    protected UriBuilder $uriBuilder;
    protected ModuleTemplateFactory $moduleTemplateFactory;

    public function __construct(
        IconFactory $iconFactory,
        UriBuilder $uriBuilder,
        ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->iconFactory = $iconFactory;
        $this->uriBuilder = $uriBuilder;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->view = $this->initializeView();
    }

    /**
     * Injects the request object for the current request or sub request
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $backendUser = $this->getBackendUser();
        $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation([]);
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $this->returnUrl = GeneralUtility::sanitizeLocalUrl($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? '');

        $lastHistoryEntry = (int)($parsedBody['historyEntry'] ?? $queryParams['historyEntry'] ?? 0);
        $rollbackFields = $parsedBody['rollbackFields'] ?? $queryParams['rollbackFields'] ?? null;
        $element = $parsedBody['element'] ?? $queryParams['element'] ?? null;
        $moduleSettings = $this->processSettings($request);
        $this->view->assign('isUserInWorkspace', $backendUser->workspace > 0);

        $this->showDiff = (bool)$moduleSettings['showDiff'];

        // Start history object
        $this->historyObject = GeneralUtility::makeInstance(RecordHistory::class, $element, $rollbackFields);
        $this->historyObject->setShowSubElements((bool)$moduleSettings['showSubElements']);
        $this->historyObject->setLastHistoryEntryNumber($lastHistoryEntry);
        if ($moduleSettings['maxSteps']) {
            $this->historyObject->setMaxSteps((int)$moduleSettings['maxSteps']);
        }

        // Do the actual logic now (rollback, show a diff for certain changes,
        // or show the full history of a page or a specific record)
        $changeLog = $this->historyObject->getChangeLog();
        if (!empty($changeLog)) {
            if ($rollbackFields !== null) {
                $diff = $this->historyObject->getDiff($changeLog);
                GeneralUtility::makeInstance(RecordHistoryRollback::class)->performRollback($rollbackFields, $diff);
            } elseif ($lastHistoryEntry) {
                $completeDiff = $this->historyObject->getDiff($changeLog);
                $this->displayMultipleDiff($completeDiff);
                $button = $buttonBar->makeLinkButton()
                    ->setHref($this->buildUrl(['historyEntry' => '']))
                    ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL))
                    ->setTitle($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_show_rechis.xlf:fullView'))
                    ->setShowLabelText(true);
                $buttonBar->addButton($button);
            }
            if ($this->historyObject->getElementString() !== '') {
                $this->displayHistory($changeLog);
            }
        }

        $elementData = $this->historyObject->getElementInformation();
        $editLock = false;
        if (!empty($elementData)) {
            [$elementTable, $elementUid] = $elementData;
            $this->setPagePath($elementTable, $elementUid);
            $editLock = $this->getEditLockFromElement($elementTable, $elementUid);
            // Get link to page history if the element history is shown
            if ($elementTable !== 'pages') {
                $parentPage = BackendUtility::getRecord($elementTable, $elementUid, '*', '', false);
                if ($parentPage['pid'] > 0 && BackendUtility::readPageAccess($parentPage['pid'], $backendUser->getPagePermsClause(Permission::PAGE_SHOW))) {
                    $button = $buttonBar->makeLinkButton()
                        ->setHref($this->buildUrl([
                            'element' => 'pages:' . $parentPage['pid'],
                            'historyEntry' => '',
                        ]))
                        ->setIcon($this->iconFactory->getIcon('apps-pagetree-page-default', Icon::SIZE_SMALL))
                        ->setTitle($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_show_rechis.xlf:elementHistory_link'))
                        ->setShowLabelText(true);
                    $buttonBar->addButton($button, ButtonBar::BUTTON_POSITION_LEFT, 2);
                }
            }
        }

        $this->view->assign('editLock', $editLock);
        $this->view->assign('moduleSettings', $moduleSettings);
        $this->view->assign('settingsFormUrl', $this->buildUrl());

        // Setting up the buttons and markers for docheader
        $this->getButtons();
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

        $record = BackendUtility::getRecord($table, $uid, '*', '', false);
        if ($table === 'pages') {
            $pageId = $uid;
        } else {
            $pageId = $record['pid'];
        }

        $pageAccess = BackendUtility::readPageAccess($pageId, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));
        if (is_array($pageAccess)) {
            $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($pageAccess);
        }
        $this->view->assign('recordTable', $this->getLanguageService()->sL($GLOBALS['TCA'][$table]['ctrl']['title']));
        $this->view->assign('recordUid', $uid);
    }

    protected function getButtons(): void
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $helpButton = $buttonBar->makeHelpButton()
            ->setModuleName('xMOD_csh_corebe')
            ->setFieldName('history_log');
        $buttonBar->addButton($helpButton);

        if ($this->returnUrl) {
            $backButton = $buttonBar->makeLinkButton()
                ->setHref($this->returnUrl)
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.closeDoc'))
                ->setShowLabelText(true)
                ->setIcon($this->iconFactory->getIcon('actions-close', Icon::SIZE_SMALL));
            $buttonBar->addButton($backButton, ButtonBar::BUTTON_POSITION_LEFT, 10);
        }
    }

    protected function processSettings(ServerRequestInterface $request): array
    {
        // Get current selection from UC, merge data, write it back to UC
        $currentSelection = $this->getBackendUser()->getModuleData('history');
        if (!is_array($currentSelection)) {
            $currentSelection = ['maxSteps' => '', 'showDiff' => 1, 'showSubElements' => 1];
        }
        $currentSelectionOverride = $request->getParsedBody()['settings'] ?? null;
        if (is_array($currentSelectionOverride) && !empty($currentSelectionOverride)) {
            $currentSelection = array_merge($currentSelection, $currentSelectionOverride);
            $this->getBackendUser()->pushModuleData('history', $currentSelection);
        }
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
        /** @var string[] $arrayKeys */
        $arrayKeys = array_merge(array_keys($diff['newData']), array_keys($diff['insertsDeletes']), array_keys($diff['oldData']));
        $arrayKeys = array_unique($arrayKeys);
        if (!empty($arrayKeys)) {
            $lines = [];
            foreach ($arrayKeys as $key) {
                $singleLine = [];
                $elParts = explode(':', $key);
                // Turn around diff because it should be a "rollback preview"
                if ((int)($diff['insertsDeletes'][$key] ?? 0) === 1) {
                    // insert
                    $singleLine['insertDelete'] = 'delete';
                } elseif ((int)($diff['insertsDeletes'][$key] ?? 0) === -1) {
                    $singleLine['insertDelete'] = 'insert';
                }
                // Build up temporary diff array
                // turn around diff because it should be a "rollback preview"
                if ($diff['newData'][$key] ?? false) {
                    $tmpArr = [
                        'newRecord' => $diff['oldData'][$key],
                        'oldRecord' => $diff['newData'][$key],
                    ];
                    $singleLine['differences'] = $this->renderDiff($tmpArr, $elParts[0], (int)$elParts[1], true);
                }
                $elParts = explode(':', $key);
                $singleLine['revertRecordUrl'] = $this->buildUrl(['rollbackFields' => $key]);
                $singleLine['title'] = $this->generateTitle($elParts[0], $elParts[1]);
                $lines[] = $singleLine;
            }
            $this->view->assign('revertAllUrl', $this->buildUrl(['rollbackFields' => 'ALL']));
            $this->view->assign('multipleDiff', $lines);
        }
        $this->view->assign('showDifferences', true);
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
            $singleLine['backendUserName'] = $beUserArray[$entry['userid']]['username'] ?? '';
            // Executed by switch user
            if (!empty($entry['originaluserid'])) {
                $singleLine['originalBackendUserUid'] = $entry['originaluserid'];
                $singleLine['originalBackendUserName'] = $beUserArray[$entry['originaluserid']]['username'] ?? '';
            }

            // Is a change in a workspace?
            $singleLine['isChangedInWorkspace'] = (int)$entry['workspace'] > 0;

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
                    /** @var string[] $tmpFieldList */
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
     * Renders HTML table-rows with the comparison information of a sys_history entry record
     *
     * @param array $entry sys_history entry record.
     * @param string $table The table name
     * @param int $rollbackUid The UID of the record
     * @param bool $showRollbackLink Whether a rollback link should be shown for each changed field
     * @return array array of records
     */
    protected function renderDiff($entry, $table, $rollbackUid = 0, bool $showRollbackLink = false): array
    {
        $lines = [];
        if (is_array($entry['newRecord'])) {
            /* @var DiffUtility $diffUtility */
            $diffUtility = GeneralUtility::makeInstance(DiffUtility::class);
            $diffUtility->stripTags = false;
            $fieldsToDisplay = array_keys($entry['newRecord']);
            $languageService = $this->getLanguageService();
            foreach ($fieldsToDisplay as $fN) {
                if (is_array($GLOBALS['TCA'][$table]['columns'][$fN] ?? null) && ($GLOBALS['TCA'][$table]['columns'][$fN]['config']['type'] ?? '') !== 'passthrough') {
                    // Create diff-result:
                    $diffres = $diffUtility->makeDiffDisplay(
                        BackendUtility::getProcessedValue($table, $fN, ($entry['oldRecord'][$fN] ?? ''), 0, true),
                        BackendUtility::getProcessedValue($table, $fN, ($entry['newRecord'][$fN] ?? ''), 0, true)
                    );
                    $rollbackUrl = '';
                    if ($rollbackUid && $showRollbackLink) {
                        $rollbackUrl = $this->buildUrl(['rollbackFields' => $table . ':' . $rollbackUid . ':' . $fN]);
                    }
                    $lines[] = [
                        'title' => $languageService->sL(BackendUtility::getItemLabel($table, $fN)),
                        'rollbackUrl' => $rollbackUrl,
                        'result' => str_replace('\n', PHP_EOL, str_replace('\r\n', '\n', $diffres)),
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
        $elementString = $this->historyObject->getElementString();
        if ($elementString !== '') {
            $params['element'] = $elementString;
        }
        $params['historyEntry'] = $this->historyObject->getLastHistoryEntryNumber();

        if (!empty($this->returnUrl)) {
            $params['returnUrl'] = $this->returnUrl;
        }

        // Merging overriding values:
        $params = array_merge($params, $overrideParameters);

        // Make the link:
        return (string)$this->uriBuilder->buildUriFromRoute('record_history', $params);
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
            $record = $this->getRecord($table, (int)$uid) ?? [];
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
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Layouts')]);
        $view->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Partials')]);
        $view->setTemplateRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates')]);

        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates/RecordHistory/Main.html'));

        $view->getRequest()->setControllerExtensionName('Backend');
        return $view;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
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
