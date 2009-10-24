<?php
/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
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
 *
 * Output:
 * List of all "Website user" records stored in the configured storage PID.
 * Records will be editable, if the current BE user has got edit rights for the table "fe_users".
 * Only the title column (username) will be shown.
 * Context menu is active.
 *
 * <code title="Full">
 * <f:be.tableList tableName="fe_users" fieldList="{0: 'name', 1: 'email'}" storagePid="1" levels="2" filter='foo' recordsPerPage="10" sortField="name" sortDescending="true" readOnly="true" enableClickMenu="false" clickTitleMode="info" alternateBackgroundColors="true" />
 * </code>
 *
 * Output:
 * List of "Website user" records with a text property of "foo" stored on PID 1 and two levels down.
 * Clicking on a username will open the TYPO3 info popup for the respective record
 *
 * @package     Fluid
 * @subpackage  ViewHelpers\Be
 * @author      Bastian Waidelich <bastian@typo3.org>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id:
 *
 */
require_once (PATH_typo3 . 'class.db_list.inc');
require_once (PATH_typo3 . 'class.db_list_extra.inc');

class Tx_Fluid_ViewHelpers_Be_TableListViewHelper extends Tx_Fluid_ViewHelpers_Be_AbstractBackendViewHelper {


	/**
	 * Renders a record list as known from the TYPO3 list module
	 * Note: This feature is experimental!
	 *
	 * @param string $tableName name of the database table
	 * @param array $fieldList list of fields to be displayed. If empty, only the title column (configured in $TCA[$tableName]['ctrl']['title']) is shown
	 * @param integer $storagePid by default, records are fetched from the storage PID configured in persistence.storagePid. With this argument, the storage PID can be overwritten
	 * @param integer $levels corresponds to the level selector of the TYPO3 list module. By default only records from the current storagePid are fetched
	 * @param string $filter corresponds to the "Search String" textbox of the TYPO3 list module. If not empty, only records matching the string will be fetched
	 * @param integer $recordsPerPage amount of records to be displayed at once. Defaults to $TCA[$tableName]['interface']['maxSingleDBListItems'] or (if that's not set) to 100
	 * @param string $sortField table field to sort the results by
	 * @param boolean $sortDescending if TRUE records will be sorted in descending order
	 * @param boolean $readOnly if TRUE, the edit icons won't be shown. Otherwise edit icons will be shown, if the current BE user has edit rights for the specified table!
	 * @param boolean $enableClickMenu enables context menu
	 * @param string $clickTitleMode one of "edit", "show" (only pages, tt_content), "info"
	 * @param boolean $alternateBackgroundColors if set, rows will have alternate background colors
	 * @return string the rendered record list
	 * @see localRecordList
	 */
	public function render($tableName, array $fieldList = array(), $storagePid = NULL, $levels = 0, $filter = '', $recordsPerPage = 0, $sortField = '', $sortDescending = FALSE, $readOnly = FALSE, $enableClickMenu = TRUE, $clickTitleMode = NULL, $alternateBackgroundColors = FALSE) {
		$pageinfo = t3lib_BEfunc::readPageAccess(t3lib_div::_GP('id'), $GLOBALS['BE_USER']->getPagePermsClause(1));

		$dblist = t3lib_div::makeInstance('localRecordList');
		$dblist->backPath = $GLOBALS['BACK_PATH'];
		$dblist->pageRow = $pageinfo;
		if ($readOnly === FALSE) {
			$dblist->calcPerms = $GLOBALS['BE_USER']->calcPerms($pageinfo);
		}
		$dblist->showClipboard = FALSE;
		$dblist->disableSingleTableView = TRUE;
		$dblist->clickTitleMode = $clickTitleMode;
		$dblist->alternateBgColors = $alternateBackgroundColors;
		$dblist->clickMenuEnabled = $enableClickMenu;

		if ($storagePid === NULL) {
			$frameworkConfiguration = Tx_Extbase_Dispatcher::getExtbaseFrameworkConfiguration();
			$storagePid = $frameworkConfiguration['persistence']['storagePid'];
		}

		$dblist->start($storagePid, $tableName, (integer)t3lib_div::_GP('pointer'), $filter, $levels, $recordsPerPage);
		$dblist->allFields = TRUE;
		$dblist->dontShowClipControlPanels = TRUE;
		$dblist->displayFields = FALSE;
		$dblist->setFields = array($tableName => $fieldList);
		$dblist->noControlPanels = TRUE;
		$dblist->sortField = $sortField;
		$dblist->sortRev = $sortDescending;

		$dblist->script = $_SERVER['REQUEST_URI'];
		$dblist->generateList();

		return $dblist->HTMLcode;
	}
}
?>
