<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Be;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
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
     * @param bool $alternateBackgroundColors Deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     * @return string the rendered record list
     * @see \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList
     */
    public function render($tableName, array $fieldList = [], $storagePid = null, $levels = 0, $filter = '', $recordsPerPage = 0, $sortField = '', $sortDescending = false, $readOnly = false, $enableClickMenu = true, $clickTitleMode = null, $alternateBackgroundColors = false)
    {
        if ($alternateBackgroundColors) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(
                'The option alternateBackgroundColors has no effect anymore and can be removed without problems. The parameter will be removed in TYPO3 CMS 8.'
            );
        }

        $pageinfo = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id'), $GLOBALS['BE_USER']->getPagePermsClause(1));
        /** @var $dblist \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList */
        $dblist = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList::class);
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
        $dblist->start($storagePid, $tableName, (int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('pointer'), $filter, $levels, $recordsPerPage);
        $dblist->allFields = true;
        $dblist->dontShowClipControlPanels = true;
        $dblist->displayFields = false;
        $dblist->setFields = [$tableName => $fieldList];
        $dblist->noControlPanels = true;
        $dblist->sortField = $sortField;
        $dblist->sortRev = $sortDescending;
        $dblist->script = $_SERVER['REQUEST_URI'];
        $dblist->generateList();
        return $dblist->HTMLcode;
    }
}
