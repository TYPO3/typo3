<?php
namespace TYPO3\CMS\Compatibility7\Hooks;

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

use TYPO3\CMS\Backend\Controller\PageLayoutController;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\FormResultCompiler;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Tree\View\ContentLayoutPagePositionMap;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Adds the action `QuickEdit to the module `Web > Layout`
 */
class PageLayoutActionHook
{

    /**
     * @var bool
     */
    protected $undoButton;

    /**
     * @var array
     */
    protected $undoButtonR;

    /**
     * @var bool
     */
    protected $deleteButton;

    /**
     * @var string
     */
    protected $closeUrl;

    /**
     * @var array
     */
    protected $eRParts = [];

    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'web_layout';

    /**
     * @var string
     */
    protected $R_URI;

    /**
     * @var PageLayoutController
     */
    protected $controller;

    /**
     * Initializes the action
     *
     * @param array $parameters the hook parameters
     * @param PageLayoutController $controller the page layout controller
     * @return void
     */
    public function initAction(array $parameters, PageLayoutController $controller)
    {
        $this->controller = $controller;
        // Add function to MOD_MENU
        $this->controller->MOD_MENU['function'] = array_slice($this->controller->MOD_MENU['function'], 0, 1, true)
            + ['0' => $this->getLanguageService()->getLL('m_function_0')] + $this->controller->MOD_MENU['function'];
        // Remove QuickEdit as option if page type is not...
        if (GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['FE']['content_doktypes'] . ',6', $this->controller->pageinfo['doktype'])) {
            array_unshift($parameters['actions'], $this->getLanguageService()->getLL('m_function_0'));
        }
        // TSconfig settings and blinding of menu-items
        if ($this->controller->modTSconfig['properties']['QEisDefault']) {
            ksort($parameters['actions']);
        }
    }

    /**
     * Renders the content of the action
     *
     * @param array $parameters the hook parameters
     * @param PageLayoutController $controller the page layout controller
     * @return string the module content
     */
    public function renderAction(array $parameters, PageLayoutController $controller)
    {
        $this->controller = $controller;

        $content = $this->renderContent();
        $this->makeButtons();

        return $content;
    }

    /**
     * Makes the action buttons
     *
     * @return void
     */
    protected function makeButtons()
    {
        $buttonBar = $this->controller->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        $iconFactory = $this->controller->getModuleTemplate()->getIconFactory();
        $lang = $this->getLanguageService();
        // Add CSH (Context Sensitive Help) icon to tool bar
        $contextSensitiveHelpButton = $buttonBar->makeHelpButton()
            ->setModuleName($this->controller->descrTable)
            ->setFieldName('quickEdit');
        $buttonBar->addButton($contextSensitiveHelpButton);

        if (!$this->controller->modTSconfig['properties']['disableIconToolbar']) {
            // Move record
            if (MathUtility::canBeInterpretedAsInteger($this->eRParts[1])) {
                $urlParameters = [
                    'table' => $this->eRParts[0],
                    'uid' => $this->eRParts[1],
                    'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                ];
                $moveButton = $buttonBar->makeLinkButton()
                    ->setHref(BackendUtility::getModuleUrl('move_element', $urlParameters))
                    ->setTitle($lang->getLL('move_' . ($this->eRParts[0] === 'tt_content' ? 'record' : 'page')))
                    ->setIcon($iconFactory->getIcon('actions-' . ($this->eRParts[0] === 'tt_content' ? 'document' : 'page') . '-move', Icon::SIZE_SMALL));
                $buttonBar->addButton($moveButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
            }
        }

        // Close Record
        $closeButton = $buttonBar->makeLinkButton()
            ->setHref('#')
            ->setOnClick('jumpToUrl(' . GeneralUtility::quoteJSvalue($this->closeUrl) . '); return false;')
            ->setTitle($lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:rm.closeDoc'))
            ->setIcon($iconFactory->getIcon('actions-document-close', Icon::SIZE_SMALL));
        $buttonBar->addButton($closeButton, ButtonBar::BUTTON_POSITION_LEFT, 0);

        // Save Record
        $saveButtonDropdown = $buttonBar->makeSplitButton();
        $saveButton = $buttonBar->makeInputButton()
            ->setName('_savedok')
            ->setValue('1')
            ->setForm('PageLayoutController')
            ->setTitle($lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:rm.saveDoc'))
            ->setIcon($iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL));
        $saveButtonDropdown->addItem($saveButton);
        $saveAndCloseButton = $buttonBar->makeInputButton()
            ->setName('_saveandclosedok')
            ->setValue('1')
            ->setForm('PageLayoutController')
            ->setOnClick('document.editform.redirect.value=\'' . $this->closeUrl . '\';')
            ->setTitle($lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:rm.saveCloseDoc'))
            ->setIcon($iconFactory->getIcon('actions-document-save-close', Icon::SIZE_SMALL));
        $saveButtonDropdown->addItem($saveAndCloseButton);
        $saveAndShowPageButton = $buttonBar->makeInputButton()
            ->setName('_savedokview')
            ->setValue('1')
            ->setForm('PageLayoutController')
            ->setOnClick('document.editform.redirect.value+=\'&popView=1\';')
            ->setTitle($lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:rm.saveDocShow'))
            ->setIcon($iconFactory->getIcon('actions-document-save-view', Icon::SIZE_SMALL));
        $saveButtonDropdown->addItem($saveAndShowPageButton);
        $buttonBar->addButton($saveButtonDropdown, ButtonBar::BUTTON_POSITION_LEFT, 1);

        // Delete record
        if ($this->deleteButton) {
            $dataAttributes = [];
            $dataAttributes['table'] = $this->eRParts[0];
            $dataAttributes['uid'] = $this->eRParts[1];
            $dataAttributes['return-url'] = BackendUtility::getModuleUrl($this->moduleName) . '&id=' . $this->controller->id;
            $deleteButton = $buttonBar->makeLinkButton()
                ->setHref('#')
                ->setClasses('t3js-editform-delete-record')
                ->setDataAttributes($dataAttributes)
                ->setTitle($lang->getLL('deleteItem'))
                ->setIcon($iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL));
            $buttonBar->addButton($deleteButton, ButtonBar::BUTTON_POSITION_LEFT, 4);
        }

        // History
        if ($this->undoButton) {
            $undoButton = $buttonBar->makeLinkButton()
                ->setHref('#')
                ->setOnClick('window.location.href=' .
                    GeneralUtility::quoteJSvalue(
                        BackendUtility::getModuleUrl(
                            'record_history',
                            [
                                'element' => $this->eRParts[0] . ':' . $this->eRParts[1],
                                'revert' => 'ALL_FIELDS',
                                'returnUrl' => $this->R_URI,
                            ]
                        )
                    ) . '; return false;')
                ->setTitle(sprintf($lang->getLL('undoLastChange'), BackendUtility::calcAge($GLOBALS['EXEC_TIME'] - $this->undoButtonR['tstamp'], $lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears'))))
                ->setIcon($iconFactory->getIcon('actions-edit-undo', Icon::SIZE_SMALL));
            $buttonBar->addButton($undoButton, ButtonBar::BUTTON_POSITION_LEFT, 5);
            $historyButton = $buttonBar->makeLinkButton()
                ->setHref('#')
                ->setOnClick('jumpToUrl(' .
                    GeneralUtility::quoteJSvalue(
                        BackendUtility::getModuleUrl(
                            'record_history',
                            [
                                'element' => $this->eRParts[0] . ':' . $this->eRParts[1],
                                'returnUrl' => $this->R_URI,
                            ]
                            ) . '#latest'
                        ) . ');return false;')
                ->setTitle($lang->getLL('recordHistory'))
                ->setIcon($iconFactory->getIcon('actions-document-history-open', Icon::SIZE_SMALL));
            $buttonBar->addButton($historyButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
        }
    }

    /**
     * Makes the action menu
     *
     * @param array $edit_record the record to make the menu for
     *
     * @return array
     */
    protected function makeMenu($edit_record)
    {
        $lang = $this->getLanguageService();
        $beUser = $this->getBackendUser();

        $quickEditMenu = $this->controller->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $quickEditMenu->setIdentifier('quickEditMenu');
        $quickEditMenu->setLabel('');

        // Setting close url/return url for exiting this script:
        // Goes to 'Columns' view if close is pressed (default)
        $this->closeUrl = $this->controller->local_linkThisScript(['SET' => ['function' => 1]]);
        if ($this->returnUrl) {
            $this->closeUrl = $this->returnUrl;
        }
        $retUrlStr = $this->returnUrl ? '&returnUrl=' . rawurlencode($this->returnUrl) : '';

        // Creating the selector box, allowing the user to select which element to edit:
        $isSelected = 0;
        $languageOverlayRecord = '';
        if ($this->current_sys_language) {
            list($languageOverlayRecord) = BackendUtility::getRecordsByField(
                'pages_language_overlay',
                'pid',
                $this->id,
                'AND sys_language_uid=' . (int)$this->current_sys_language
            );
        }
        if (is_array($languageOverlayRecord)) {
            $inValue = 'pages_language_overlay:' . $languageOverlayRecord['uid'];
            $isSelected += (int)$edit_record == $inValue;
            $menuItem = $quickEditMenu->makeMenuItem()
                ->setTitle('[ ' . $lang->getLL('editLanguageHeader') . ' ]')
                ->setHref(BackendUtility::getModuleUrl($this->moduleName) . '&id=' . $this->id . '&edit_record=' . $inValue . $retUrlStr)
                ->setActive($edit_record == $inValue);
            $quickEditMenu->addMenuItem($menuItem);
        } else {
            $inValue = 'pages:' . $this->id;
            $isSelected += (int)$edit_record == $inValue;
            $menuItem = $quickEditMenu->makeMenuItem()
                ->setTitle('[ ' . $lang->getLL('editPageProperties') . ' ]')
                ->setHref(BackendUtility::getModuleUrl($this->moduleName) . '&id=' . $this->id . '&edit_record=' . $inValue . $retUrlStr)
                ->setActive($edit_record == $inValue);
            $quickEditMenu->addMenuItem($menuItem);
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        if ($this->controller->MOD_SETTINGS['tt_content_showHidden']) {
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        }
        $queryBuilder->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($this->controller->id, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($this->controller->current_sys_language, \PDO::PARAM_INT)
                    ),
                $queryBuilder->expr()->in(
                    'colPos',
                    $queryBuilder->createNamedParameter(
                        GeneralUtility::intExplode(',', $this->controller->colPosList, true),
                        Connection::PARAM_INT_ARRAY
                        )
                    ),
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->gte(
                        't3ver_state',
                        $queryBuilder->createNamedParameter(
                            (string)new VersionState(VersionState::DEFAULT_STATE),
                            \PDO::PARAM_INT
                            )
                        ),
                    $queryBuilder->expr()->eq(
                        't3ver_wsid',
                        $queryBuilder->createNamedParameter(
                            (string)new VersionState(VersionState::DEFAULT_STATE),
                            \PDO::PARAM_INT
                            )
                        )
                    )
                )
                ->orderBy('colPos')
                ->addOrderBy('sorting');
        if (!$beUser->user['admin']) {
            $queryBuilder->andWhere(
                    $queryBuilder->expr()->eq(
                        'editlock',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    )
                );
        }
        $statement = $queryBuilder->execute();
        $colPos = null;
        $first = 1;
            // Page is the pid if no record to put this after.
            $prev = $this->controller->id;
        while ($cRow = $statement->fetch()) {
            BackendUtility::workspaceOL('tt_content', $cRow);
            if (is_array($cRow)) {
                if ($first) {
                    if (!$edit_record) {
                        $edit_record = 'tt_content:' . $cRow['uid'];
                    }
                    $first = 0;
                }
                if (!isset($colPos) || $cRow['colPos'] !== $colPos) {
                    $colPos = $cRow['colPos'];
                    $menuItem = $quickEditMenu->makeMenuItem()
                            ->setTitle(' ')
                            ->setHref('#');
                    $quickEditMenu->addMenuItem($menuItem);
                    $menuItem = $quickEditMenu->makeMenuItem()
                            ->setTitle('__' . $lang->sL(BackendUtility::getLabelFromItemlist('tt_content', 'colPos', $colPos)) . ':__')
                            ->setHref(BackendUtility::getModuleUrl($this->moduleName) . '&id=' . $this->controller->id . '&edit_record=_EDIT_COL:' . $colPos . $retUrlStr);
                    $quickEditMenu->addMenuItem($menuItem);
                }
                $inValue = 'tt_content:' . $cRow['uid'];
                $isSelected += (int)$edit_record == $inValue;
                $menuItem = $quickEditMenu->makeMenuItem()
                        ->setTitle(GeneralUtility::fixed_lgd_cs(($cRow['header'] ? $cRow['header'] : '[' . $lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.no_title') . '] ' . strip_tags($cRow['bodytext'])), $beUser->uc['titleLen']))
                        ->setHref(BackendUtility::getModuleUrl($this->moduleName) . '&id=' . $this->controller->id . '&edit_record=' . $inValue . $retUrlStr)
                        ->setActive($edit_record == $inValue);
                $quickEditMenu->addMenuItem($menuItem);
                $prev = -$cRow['uid'];
            }
        }
            // If edit_record is not set (meaning, no content elements was found for this language) we simply set it to create a new element:
            if (!$edit_record) {
                $edit_record = 'tt_content:new/' . $prev . '/' . $colPos;
                $inValue = 'tt_content:new/' . $prev . '/' . $colPos;
                $isSelected += (int)$edit_record == $inValue;
                $menuItem = $quickEditMenu->makeMenuItem()
                    ->setTitle('[ ' . $lang->getLL('newLabel') . ' ]')
                    ->setHref(BackendUtility::getModuleUrl($this->moduleName) . '&id=' . $this->controller->id . '&edit_record=' . $inValue . $retUrlStr)
                    ->setActive($edit_record == $inValue);
                $quickEditMenu->addMenuItem($menuItem);
            }
            // If none is yet selected...
            if (!$isSelected) {
                $menuItem = $quickEditMenu->makeMenuItem()
                    ->setTitle('__________')
                    ->setHref('#');
                $quickEditMenu->addMenuItem($menuItem);
                $menuItem = $quickEditMenu->makeMenuItem()
                    ->setTitle('[ ' . $lang->getLL('newLabel') . ' ]')
                    ->setHref(BackendUtility::getModuleUrl($this->moduleName) . '&id=' . $this->controller->id . '&edit_record=' . $edit_record . $retUrlStr)
                    ->setActive($edit_record == $inValue);
                $quickEditMenu->addMenuItem($menuItem);
            }
        $this->controller->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($quickEditMenu);
        return $edit_record;
    }

    /**
     * Renders the action content
     *
     * @return string the content
     */
    protected function renderContent()
    {
        $beUser = $this->getBackendUser();
        $lang = $this->getLanguageService();
        // Set the edit_record value for internal use in this function:
        $edit_record = $this->controller->edit_record;
        // If a command to edit all records in a column is issue, then select all those elements, and redirect to FormEngine
        if (substr($edit_record, 0, 9) === '_EDIT_COL') {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
            if ($this->controller->MOD_SETTINGS['tt_content_showHidden']) {
                $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            }
            $statement = $queryBuilder->select('*')
                ->from('tt_content')
                ->orderBy('sorting')
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($this->controller->id, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq(
                        'colPos',
                        $queryBuilder->createNamedParameter(substr($edit_record, 10), \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter($this->controller->current_sys_language, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->gte(
                            't3ver_state',
                            $queryBuilder->createNamedParameter(
                                (string)new VersionState(VersionState::DEFAULT_STATE),
                                \PDO::PARAM_INT
                            )
                        ),
                        $queryBuilder->expr()->eq(
                            't3ver_wsid',
                            $queryBuilder->createNamedParameter($beUser->workspace, \PDO::PARAM_INT)
                        )
                    )
                )
                ->execute();
            $idListA = [];
            while ($cRow = $statement->fetch()) {
                $idListA[] = $cRow['uid'];
            }
            $url = BackendUtility::getModuleUrl('record_edit', [
                'edit[tt_content][' . implode(',', $idListA) . ']' => 'edit',
                'returnUrl' => $this->controller->local_linkThisScript(['edit_record' => ''])
            ]);
            HttpUtility::redirect($url);
        }
        // If the former record edited was the creation of a NEW record, this will look up the created records uid:
        if ($this->controller->new_unique_uid) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_log');
            $queryBuilder->getRestrictions()->removeAll();
            $sys_log_row = $queryBuilder->select('tablename', 'recuid')
                ->from('sys_log')
                ->where(
                    $queryBuilder->expr()->eq(
                        'userid',
                        $queryBuilder->createNamedParameter($beUser->user['uid'], \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'NEWid',
                        $queryBuilder->createNamedParameter($this->controller->new_unique_uid, \PDO::PARAM_INT)
                    )
                )
                ->execute()
                ->fetch();
            if (is_array($sys_log_row)) {
                $edit_record = $sys_log_row['tablename'] . ':' . $sys_log_row['recuid'];
            }
        }
        $edit_record = $this->makeMenu($edit_record);
        // Splitting the edit-record cmd value into table/uid:
        $this->eRParts = explode(':', $edit_record);
        $tableName = $this->eRParts[0];
        // Delete-button flag?
        $this->deleteButton = MathUtility::canBeInterpretedAsInteger($this->eRParts[1]) && $edit_record && ($tableName !== 'pages' && $this->controller->EDIT_CONTENT || $tableName === 'pages' && $this->controller->CALC_PERMS & Permission::PAGE_DELETE);
        // If undo-button should be rendered (depends on available items in sys_history)
        $this->undoButton = false;

        // if there is no content on a page
        // the parameter $this->eRParts[1] will be set to e.g. /new/1
        // which is not an integer value and it will throw an exception here on certain dbms
        // thus let's check that before as there cannot be a history for a new record
        $this->undoButtonR = false;
        if (MathUtility::canBeInterpretedAsInteger($this->eRParts[1])) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_history');
            $queryBuilder->getRestrictions()->removeAll();
            $this->undoButtonR = $queryBuilder->select('tstamp')
                ->from('sys_history')
                ->where(
                    $queryBuilder->expr()->eq(
                        'tablename',
                        $queryBuilder->createNamedParameter($tableName, \PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->eq(
                        'recuid',
                        $queryBuilder->createNamedParameter($this->eRParts[1], \PDO::PARAM_INT)
                    )
                )
                ->orderBy('tstamp', 'DESC')
                ->setMaxResults(1)
                ->execute()
                ->fetch();
        }
        if ($this->undoButtonR) {
            $this->undoButton = true;
        }
        // Setting up the Return URL for coming back to THIS script (if links take the user to another script)
        $R_URL_parts = parse_url(GeneralUtility::getIndpEnv('REQUEST_URI'));
        $R_URL_getvars = GeneralUtility::_GET();
        unset($R_URL_getvars['popView']);
        unset($R_URL_getvars['new_unique_uid']);
        $R_URL_getvars['edit_record'] = $edit_record;
        $this->R_URI = $R_URL_parts['path'] . '?' . GeneralUtility::implodeArrayForUrl('', $R_URL_getvars);

        // Creating editing form:
        if ($edit_record) {
            // Splitting uid parts for special features, if new:
            list($uidVal, $neighborRecordUid, $ex_colPos) = explode('/', $this->eRParts[1]);

            if ($uidVal === 'new') {
                $command = 'new';
                // Page id of this new record
                $theUid = $this->controller->id;
                if ($neighborRecordUid) {
                    $theUid = $neighborRecordUid;
                }
            } else {
                $command = 'edit';
                $theUid = $uidVal;
                // Convert $uidVal to workspace version if any:
                $draftRecord = BackendUtility::getWorkspaceVersionOfRecord($beUser->workspace, $tableName, $theUid, 'uid');
                if ($draftRecord) {
                    $theUid = $draftRecord['uid'];
                }
            }

            // @todo: Hack because DatabaseInitializeNewRow reads from _GP directly
            $GLOBALS['_GET']['defVals'][$tableName] = [
                'colPos' => (int)$ex_colPos,
                'sys_language_uid' => (int)$this->controller->current_sys_language
            ];

            /** @var TcaDatabaseRecord $formDataGroup */
            $formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
            /** @var FormDataCompiler $formDataCompiler */
            $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
            /** @var NodeFactory $nodeFactory */
            $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);

            try {
                $formDataCompilerInput = [
                    'tableName' => $tableName,
                    'vanillaUid' => (int)$theUid,
                    'command' => $command,
                ];
                $formData = $formDataCompiler->compile($formDataCompilerInput);

                if ($command !== 'new') {
                    BackendUtility::lockRecords($tableName, $formData['databaseRow']['uid'], $tableName === 'tt_content' ? $formData['databaseRow']['pid'] : 0);
                }

                $formData['renderType'] = 'outerWrapContainer';
                $formResult = $nodeFactory->create($formData)->render();

                $panel = $formResult['html'];
                $formResult['html'] = '';

                /** @var FormResultCompiler $formResultCompiler */
                $formResultCompiler = GeneralUtility::makeInstance(FormResultCompiler::class);
                $formResultCompiler->mergeResult($formResult);

                $row = $formData['databaseRow'];
                $new_unique_uid = '';
                if ($command === 'new') {
                    $new_unique_uid = $row['uid'];
                }

                // Add hidden fields:
                if ($uidVal === 'new') {
                    $panel .= '<input type="hidden" name="data[' . $tableName . '][' . $row['uid'] . '][pid]" value="' . $row['pid'] . '" />';
                }
                $redirect = ($uidVal === 'new' ? BackendUtility::getModuleUrl(
                    $this->moduleName,
                    ['id' => $this->controller->id, 'new_unique_uid' => $new_unique_uid, 'returnUrl' => $this->returnUrl]
                ) : $this->R_URI);
                $panel .= '
                    <input type="hidden" name="_serialNumber" value="' . md5(microtime()) . '" />
                    <input type="hidden" name="edit_record" value="' . $edit_record . '" />
                    <input type="hidden" name="redirect" value="' . htmlspecialchars($redirect) . '" />
                    ';
                // Add JavaScript as needed around the form:
                $content = $formResultCompiler->addCssFiles() . $panel . $formResultCompiler->printNeededJSFunctions();

                // Display "is-locked" message:
                if ($command === 'edit') {
                    $lockInfo = BackendUtility::isRecordLocked($tableName, $formData['databaseRow']['uid']);
                    if ($lockInfo) {
                        /** @var \TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage */
                        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $lockInfo['msg'], '', FlashMessage::WARNING);
                        /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
                        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                        /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
                        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
                        $defaultFlashMessageQueue->enqueue($flashMessage);
                    }
                }
            } catch (AccessDeniedException $e) {
                // If no edit access, print error message:
                $content = '<h2>' . htmlspecialchars($lang->getLL('noAccess')) . '</h2>';
                $content .= '<div>' . $lang->getLL('noAccess_msg') . '<br /><br />' . ($beUser->errorMsg ? 'Reason: ' . $beUser->errorMsg . '<br /><br />' : '') . '</div>';
            }
        } else {
            // If no edit access, print error message:
            $content = '<h2>' . $lang->getLL('noAccess') . '</h2>';
            $content .= '<div>' . $lang->getLL('noAccess_msg') . '</div>';
        }

        // Element selection matrix:
        if ($tableName === 'tt_content' && MathUtility::canBeInterpretedAsInteger($this->eRParts[1])) {
            $content .= '<h2>' . $lang->getLL('CEonThisPage') . '</h2>';
            // PositionMap
            $posMap = GeneralUtility::makeInstance(ContentLayoutPagePositionMap::class);
            $posMap->cur_sys_language = $this->controller->current_sys_language;
            $content .= $posMap->printContentElementColumns(
                $this->controller->id,
                $this->eRParts[1],
                $this->controller->colPosList,
                $this->controller->MOD_SETTINGS['tt_content_showHidden'],
                $this->R_URI
                );
            // Toggle hidden ContentElements
            $numberOfHiddenElements = $this->controller->getNumberOfHiddenElements();
            if ($numberOfHiddenElements) {
                $content .= '<div class="checkbox">';
                $content .= '<label for="checkTt_content_showHidden">';
                $content .= BackendUtility::getFuncCheck($this->controller->id, 'SET[tt_content_showHidden]', $this->controller->MOD_SETTINGS['tt_content_showHidden'], '', '', 'id="checkTt_content_showHidden"');
                $content .= (!$numberOfHiddenElements ? ('<span class="text-muted">' . htmlspecialchars($lang->getLL('hiddenCE')) . '</span>') : htmlspecialchars($lang->getLL('hiddenCE')) . ' (' . $numberOfHiddenElements . ')');
                $content .= '</label>';
                $content .= '</div>';
            }
            // CSH
            $content .= BackendUtility::cshItem($this->descrTable, 'quickEdit_selElement', null, '<span class="btn btn-default btn-sm">|</span>');
        }

        $content = '<form action="' .
            htmlspecialchars(BackendUtility::getModuleUrl('tce_db', ['prErr' => 1, 'uPT' => 1])) .
            '" method="post" enctype="multipart/form-data" name="editform" id="PageLayoutController" onsubmit="return TBE_EDITOR.checkSubmit(1);">' .
            $content .
            '</form>';

        return $content;
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
