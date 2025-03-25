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

namespace TYPO3\CMS\Backend\Controller\ContentElement;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\History\RecordHistory;
use TYPO3\CMS\Backend\History\RecordHistoryRollback;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\ValueFormatter\FlexFormValueFormatter;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\History\RecordHistoryStore;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\DiffGranularity;
use TYPO3\CMS\Core\Utility\DiffUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Controller for showing the history module of TYPO3s backend.
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
class ElementHistoryController
{
    protected RecordHistory $historyObject;

    /**
     * Display inline differences or not
     */
    protected bool $showDiff = true;
    protected array $recordCache = [];

    protected ModuleTemplate $view;

    protected string $returnUrl = '';

    public function __construct(
        protected readonly IconFactory $iconFactory,
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly DiffUtility $diffUtility,
        private readonly FlexFormValueFormatter $flexFormValueFormatter,
        private readonly TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * Injects the request object for the current request or sub request
     * As this controller goes only through the main() method, it is rather simple for now
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->view = $this->moduleTemplateFactory->create($request);
        $backendUser = $this->getBackendUser();
        $this->view->getDocHeaderComponent()->setMetaInformation([]);
        $buttonBar = $this->view->getDocHeaderComponent()->getButtonBar();

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
                    ->setIcon($this->iconFactory->getIcon('actions-view-go-back', IconSize::SMALL))
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
            $elementUid = (int)$elementUid;
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
                        ->setIcon($this->iconFactory->getIcon('apps-pagetree-page-default', IconSize::SMALL))
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

        return $this->view->renderResponse('RecordHistory/Main');
    }

    /**
     * Creates the correct path to the current record
     */
    protected function setPagePath(string $table, int $uid): void
    {
        $record = BackendUtility::getRecord($table, $uid, '*', '', false);
        if ($table === 'pages') {
            $pageId = $uid;
        } else {
            $pageId = $record['pid'];
        }

        $pageAccess = BackendUtility::readPageAccess($pageId, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));
        if (is_array($pageAccess)) {
            $this->view->getDocHeaderComponent()->setMetaInformation($pageAccess);
        }

        $schema = $this->tcaSchemaFactory->get($table);
        $this->view->assignMultiple([
            'recordTable' => $table,
            'recordTableReadable' => $this->getLanguageService()->sL($schema->getRawConfiguration()['title'] ?? ''),
            'recordUid' => $uid,
            'recordTitle' => $this->generateTitle($table, (string)$uid),
        ]);
    }

    protected function getButtons(): void
    {
        $buttonBar = $this->view->getDocHeaderComponent()->getButtonBar();

        if ($this->returnUrl) {
            $backButton = $buttonBar->makeLinkButton()
                ->setHref($this->returnUrl)
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.closeDoc'))
                ->setShowLabelText(true)
                ->setIcon($this->iconFactory->getIcon('actions-close', IconSize::SMALL));
            $buttonBar->addButton($backButton);
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
    protected function displayMultipleDiff(array $diff): void
    {
        $languageService = $this->getLanguageService();

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

                    // show changes
                    if (!$this->showDiff) {
                        // Display field names instead of full diff
                        // Re-write field names with labels
                        /** @var string[] $tmpFieldList */
                        $tmpFieldList = array_keys($tmpArr['newRecord']);
                        foreach ($tmpFieldList as $fieldKey => $value) {
                            $tmp = str_replace(':', '', $languageService->sL(BackendUtility::getItemLabel($elParts[0], $value)));
                            if ($tmp) {
                                $tmpFieldList[$fieldKey] = $tmp;
                            } else {
                                // remove fields if no label available
                                unset($tmpFieldList[$fieldKey]);
                            }
                        }
                        $singleLine['fieldNames'] = implode(',', $tmpFieldList);
                    } else {
                        // Display diff
                        $singleLine['differences'] = $this->renderDiff($tmpArr, $elParts[0], (int)$elParts[1], true);
                    }
                }
                $elParts = explode(':', $key);
                $singleLine['revertRecordUrl'] = $this->buildUrl(['rollbackFields' => $key]);
                $singleLine['title'] = $this->generateTitle($elParts[0], $elParts[1]);
                $singleLine['recordTable'] = $elParts[0];
                $singleLine['recordUid'] = $elParts[1];
                $lines[] = $singleLine;
            }
            $this->view->assign('revertAllUrl', $this->buildUrl(['rollbackFields' => 'ALL']));
            $this->view->assign('multipleDiff', $lines);
        }
        $this->view->assign('showDifferences', true);
    }

    /**
     * Shows the full change log
     */
    protected function displayHistory(array $historyEntries): void
    {
        if ($historyEntries === []) {
            return;
        }
        $languageService = $this->getLanguageService();
        $lines = [];
        $beUserArray = BackendUtility::getUserNames('username,realName,usergroup,uid');

        // Traverse changeLog array:
        foreach ($historyEntries as $entry) {
            // Build up single line
            $singleLine = [];

            // Get user names
            $singleLine['backendUserUid'] = $entry['userid'];
            $singleLine['backendUserName'] = $beUserArray[$entry['userid']]['username'] ?? '';
            $singleLine['backendUserRealName'] = $beUserArray[$entry['userid']]['realName'] ?? '';
            // Executed by switch user
            if (!empty($entry['originaluserid'])) {
                $singleLine['originalBackendUserUid'] = $entry['originaluserid'];
                $singleLine['originalBackendUserName'] = $beUserArray[$entry['originaluserid']]['username'] ?? '';
                $singleLine['originalBackendRealName'] = $beUserArray[$entry['originaluserid']]['realName'] ?? '';
            }

            // Is a change in a workspace?
            $singleLine['isChangedInWorkspace'] = (int)$entry['workspace'] > 0;

            // Diff link
            $singleLine['diffUrl'] = $this->buildUrl(['historyEntry' => $entry['uid']]);
            // Add time
            $singleLine['day'] = BackendUtility::date($entry['tstamp']);
            $singleLine['time'] = BackendUtility::time($entry['tstamp']);

            $singleLine['title'] = $this->generateTitle($entry['tablename'], (string)$entry['recuid']);
            $singleLine['recordTable'] = $entry['tablename'];
            $singleLine['recordUid'] = $entry['recuid'];

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
                    $singleLine['differences'] = $this->renderDiff($entry, $entry['tablename'], (int)$entry['recuid']);
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
    protected function renderDiff(array $entry, string $table, int $rollbackUid, bool $showRollbackLink = false): array
    {
        if (!$this->tcaSchemaFactory->has($table)) {
            return [];
        }
        $lines = [];
        if (is_array($entry['newRecord'] ?? null)) {
            $fieldsToDisplay = array_keys($entry['newRecord']);
            $languageService = $this->getLanguageService();
            $schema = $this->tcaSchemaFactory->get($table);
            foreach ($fieldsToDisplay as $fN) {
                if (!$schema->hasField($fN)) {
                    continue;
                }
                $fieldInformation = $schema->getField($fN);
                if (!$fieldInformation->isType(TableColumnType::PASSTHROUGH)) {
                    if ($fieldInformation->isType(TableColumnType::FLEX)) {
                        $colConfig = $fieldInformation->getConfiguration();
                        $old = $this->flexFormValueFormatter->format($table, $fN, ($entry['oldRecord'][$fN] ?? ''), $rollbackUid, $colConfig);
                        $new = $this->flexFormValueFormatter->format($table, $fN, ($entry['newRecord'][$fN] ?? ''), $rollbackUid, $colConfig);
                        $diffResult = $this->diffUtility->diff(strip_tags($old), strip_tags($new), DiffGranularity::CHARACTER);
                    } else {
                        $old = (string)BackendUtility::getProcessedValue($table, $fN, ($entry['oldRecord'][$fN] ?? ''), 0, true, false, $rollbackUid);
                        $new = (string)BackendUtility::getProcessedValue($table, $fN, ($entry['newRecord'][$fN] ?? ''), 0, true, false, $rollbackUid);
                        $diffResult = $this->diffUtility->diff(strip_tags($old), strip_tags($new));
                    }
                    $rollbackUrl = '';
                    if ($rollbackUid && $showRollbackLink) {
                        $rollbackUrl = $this->buildUrl(['rollbackFields' => $table . ':' . $rollbackUid . ':' . $fN]);
                    }
                    $lines[] = [
                        'title' => $languageService->sL($fieldInformation->getLabel()),
                        'rollbackUrl' => $rollbackUrl,
                        'result' => str_replace('\n', PHP_EOL, str_replace('\r\n', '\n', $diffResult)),
                    ];
                }
            }
        }
        return $lines;
    }

    /**
     * Generates the URL for a link to the current page
     */
    protected function buildUrl(array $overrideParameters = []): string
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
     */
    protected function generateTitle(string $table, string $uid): string
    {
        $title = '';
        if ($this->tcaSchemaFactory->get($table)->hasCapability(TcaSchemaCapability::Label)) {
            $record = $this->getRecord($table, (int)$uid) ?? [];
            $title .= BackendUtility::getRecordTitle($table, $record);
        }
        return $title;
    }

    /**
     * Gets a database record (cached).
     */
    protected function getRecord(string $table, int $uid): ?array
    {
        if (!isset($this->recordCache[$table][$uid])) {
            $this->recordCache[$table][$uid] = BackendUtility::getRecord($table, $uid, '*', '', false);
        }
        return $this->recordCache[$table][$uid];
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
     */
    protected function getEditLockFromElement(string $tableName, int $elementUid): bool
    {
        // If the user is admin, then he may always edit the page.
        if ($this->getBackendUser()->isAdmin()) {
            return false;
        }

        // Early return if $elementUid is zero
        if ($elementUid === 0) {
            return !$this->tcaSchemaFactory->get($tableName)->getCapability(TcaSchemaCapability::RestrictionRootLevel)->shallIgnoreRootLevelRestriction();
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
