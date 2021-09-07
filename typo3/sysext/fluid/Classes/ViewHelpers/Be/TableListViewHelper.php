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

namespace TYPO3\CMS\Fluid\ViewHelpers\Be;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;

/**
 * ViewHelper which renders a record list as known from the TYPO3 list module.
 *
 * .. note::
 *    This feature is experimental!
 *
 * Examples
 * ========
 *
 * Minimal::
 *
 *    <f:be.tableList tableName="fe_users" />
 *
 * List of all "Website user" records stored in the configured storage PID.
 * Records will be editable, if the current backend user has got edit rights for the table ``fe_users``.
 *
 * Only the title column (username) will be shown.
 *
 * Context menu is active.
 *
 * Full::
 *
 *    <f:be.tableList tableName="fe_users" fieldList="{0: 'name', 1: 'email'}"
 *        storagePid="1"
 *        levels="2"
 *        filter="foo"
 *        recordsPerPage="10"
 *        sortField="name"
 *        sortDescending="true"
 *        readOnly="true"
 *        enableClickMenu="false"
 *        enableControlPanels="true"
 *        clickTitleMode="info"
 *        />
 *
 * List of "Website user" records with a text property of ``foo`` stored on PID ``1`` and two levels down.
 * Clicking on a username will open the TYPO3 info popup for the respective record
 */
class TableListViewHelper extends AbstractBackendViewHelper
{
    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
     */
    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * Initialize arguments.
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('tableName', 'string', 'name of the database table', true);
        $this->registerArgument('fieldList', 'array', 'list of fields to be displayed. If empty, only the title column (configured in $TCA[$tableName][\'ctrl\'][\'title\']) is shown', false, []);
        $this->registerArgument('storagePid', 'int', 'by default, records are fetched from the storage PID configured in persistence.storagePid. With this argument, the storage PID can be overwritten');
        $this->registerArgument('levels', 'int', 'corresponds to the level selector of the TYPO3 list module. By default only records from the current storagePid are fetched', false, 0);
        $this->registerArgument('filter', 'string', 'corresponds to the "Search String" textbox of the TYPO3 list module. If not empty, only records matching the string will be fetched', false, '');
        $this->registerArgument('recordsPerPage', 'int', 'amount of records to be displayed at once. Defaults to $TCA[$tableName][\'interface\'][\'maxSingleDBListItems\'] or (if that\'s not set) to 100', false, 0);
        $this->registerArgument('sortField', 'string', 'table field to sort the results by', false, '');
        $this->registerArgument('sortDescending', 'bool', 'if TRUE records will be sorted in descending order', false, false);
        $this->registerArgument('readOnly', 'bool', 'if TRUE, the edit icons won\'t be shown. Otherwise edit icons will be shown, if the current BE user has edit rights for the specified table!', false, false);
        $this->registerArgument('enableClickMenu', 'bool', 'enables context menu', false, true);
        $this->registerArgument('enableControlPanels', 'bool', 'enables control panels', false, false);
        $this->registerArgument('clickTitleMode', 'string', 'one of "edit", "show" (only pages, tt_content), "info');
    }

    /**
     * Renders a record list as known from the TYPO3 list module
     * Note: This feature is experimental!
     *
     * @return string the rendered record list
     * @see \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList
     */
    public function render()
    {
        $tableName = $this->arguments['tableName'];
        $fieldList = $this->arguments['fieldList'];
        $storagePid = $this->arguments['storagePid'];
        $levels = $this->arguments['levels'];
        $filter = $this->arguments['filter'];
        $recordsPerPage = $this->arguments['recordsPerPage'];
        $sortField = $this->arguments['sortField'];
        $sortDescending = $this->arguments['sortDescending'];
        $readOnly = $this->arguments['readOnly'];
        $enableClickMenu = $this->arguments['enableClickMenu'];
        $clickTitleMode = $this->arguments['clickTitleMode'];
        $enableControlPanels = $this->arguments['enableControlPanels'];

        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Recordlist/Recordlist');
        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Recordlist/RecordDownloadButton');
        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ActionDispatcher');
        if ($enableControlPanels === true) {
            $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/MultiRecordSelection');
            $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
        }
        // We need to include the language file, since DatabaseRecordList is heavily using ->getLL
        $this->getLanguageService()->includeLLFile('EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf');

        $pageinfo = BackendUtility::readPageAccess(GeneralUtility::_GP('id'), $GLOBALS['BE_USER']->getPagePermsClause(Permission::PAGE_SHOW)) ?: [];
        $dblist = GeneralUtility::makeInstance(DatabaseRecordList::class);
        $dblist->pageRow = $pageinfo;
        if ($readOnly) {
            $dblist->setIsEditable(false);
        } else {
            $dblist->calcPerms = new Permission($GLOBALS['BE_USER']->calcPerms($pageinfo));
        }
        $dblist->disableSingleTableView = true;
        $dblist->clickTitleMode = $clickTitleMode;
        $dblist->clickMenuEnabled = $enableClickMenu;
        if ($storagePid === null) {
            $frameworkConfiguration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
            $storagePid = $frameworkConfiguration['persistence']['storagePid'];
        }
        $dblist->start($storagePid, $tableName, (int)GeneralUtility::_GP('pointer'), $filter, $levels, $recordsPerPage);
        // Column selector is disabled since fields are defined by the "fieldList" argument
        $dblist->displayColumnSelector = false;
        $dblist->setFields = [$tableName => $fieldList];
        $dblist->noControlPanels = !$enableControlPanels;
        $dblist->sortField = $sortField;
        $dblist->sortRev = $sortDescending;
        return $dblist->generateList();
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
