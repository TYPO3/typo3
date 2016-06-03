<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Be;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * View helper which renders a record list as known from the TYPO3 list module
 * Note: This feature is experimental!
 *
 * = Examples =
 *
 * <code title="Minimal">
 * <f:be.tableList tableName="fe_users" />
 * </code>
 * <output>
 * List of all "Website user" records stored in the configured storage PID.
 * Records will be editable, if the current BE user has got edit rights for the table "fe_users".
 * Only the title column (username) will be shown.
 * Context menu is active.
 * </output>
 *
 * <code title="Full">
 * <f:be.tableList tableName="fe_users" fieldList="{0: 'name', 1: 'email'}" storagePid="1" levels="2" filter='foo' recordsPerPage="10" sortField="name" sortDescending="true" readOnly="true" enableClickMenu="false" clickTitleMode="info" />
 * </code>
 * <output>
 * List of "Website user" records with a text property of "foo" stored on PID 1 and two levels down.
 * Clicking on a username will open the TYPO3 info popup for the respective record
 * </output>
 */
class TableListViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper
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
    public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * Renders a record list as known from the TYPO3 list module
     * Note: This feature is experimental!
     *
     * @param string $tableName name of the database table
     * @param array $fieldList list of fields to be displayed. If empty, only the title column (configured in $TCA[$tableName]['ctrl']['title']) is shown
     * @param int $storagePid by default, records are fetched from the storage PID configured in persistence.storagePid. With this argument, the storage PID can be overwritten
     * @param int $levels corresponds to the level selector of the TYPO3 list module. By default only records from the current storagePid are fetched
     * @param string $filter corresponds to the "Search String" textbox of the TYPO3 list module. If not empty, only records matching the string will be fetched
     * @param int $recordsPerPage amount of records to be displayed at once. Defaults to $TCA[$tableName]['interface']['maxSingleDBListItems'] or (if that's not set) to 100
     * @param string $sortField table field to sort the results by
     * @param bool $sortDescending if TRUE records will be sorted in descending order
     * @param bool $readOnly if TRUE, the edit icons won't be shown. Otherwise edit icons will be shown, if the current BE user has edit rights for the specified table!
     * @param bool $enableClickMenu enables context menu
     * @param string $clickTitleMode one of "edit", "show" (only pages, tt_content), "info
     * @return string the rendered record list
     * @see \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList
     */
    public function render($tableName, array $fieldList = array(), $storagePid = null, $levels = 0, $filter = '', $recordsPerPage = 0, $sortField = '', $sortDescending = false, $readOnly = false, $enableClickMenu = true, $clickTitleMode = null)
    {
        $pageinfo = BackendUtility::readPageAccess(GeneralUtility::_GP('id'), $GLOBALS['BE_USER']->getPagePermsClause(1));
        /** @var $dblist \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList */
        $dblist = GeneralUtility::makeInstance(\TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList::class);
        $dblist->pageRow = $pageinfo;
        if ($readOnly === false) {
            $dblist->calcPerms = $GLOBALS['BE_USER']->calcPerms($pageinfo);
        }
        $dblist->showClipboard = false;
        $dblist->disableSingleTableView = true;
        $dblist->clickTitleMode = $clickTitleMode;
        $dblist->clickMenuEnabled = $enableClickMenu;
        if ($storagePid === null) {
            $frameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
            $storagePid = $frameworkConfiguration['persistence']['storagePid'];
        }
        $dblist->start($storagePid, $tableName, (int)GeneralUtility::_GP('pointer'), $filter, $levels, $recordsPerPage);
        $dblist->allFields = true;
        $dblist->dontShowClipControlPanels = true;
        $dblist->displayFields = false;
        $dblist->setFields = array($tableName => $fieldList);
        $dblist->noControlPanels = true;
        $dblist->sortField = $sortField;
        $dblist->sortRev = $sortDescending;
        $dblist->script = $_SERVER['REQUEST_URI'];
        $dblist->generateList();
        return $dblist->HTMLcode;
    }
}
