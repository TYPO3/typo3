<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2010 Michael Miousse (michael.miousse@infoglobe.ca)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Module 'Link Validator' for the 'linkvalidator' extension.
 *
 * @author Michael Miousse <michael.miousse@infoglobe.ca>
 * @author Jochen Rieger <j.rieger@connecta.ag>
 * @package TYPO3
 * @subpackage linkvalidator
 */
class tx_linkvalidator_modfunc1 extends t3lib_extobjbase {

	/**
	 * @var template
	 */
	public $doc;
	protected $relativePath;
	protected $pageRecord = array();
	protected $isAccessibleForCurrentUser = FALSE;

	/**
	 * Main method of modfunc1
	 *
	 * @return	html	Module content
	 */
	public function main() {
		$GLOBALS['LANG']->includeLLFile('EXT:linkvalidator/modfunc1/locallang.xml');

		$this->search_level = t3lib_div::_GP('search_levels');

		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] as $linkType => $value) {
				if ($this->pObj->MOD_SETTINGS[$linkType]) {
					$this->checkOpt[$linkType] = 1;
				}
			}
		}

		$this->initialize();

		$this->modTS = t3lib_BEfunc::getModTSconfig($this->pObj->id, 'mod.linkvalidator');
		$this->modTS = $this->modTS['properties'];
		if ($this->modTS['showUpdateButton'] == 1) {
			$this->updateListHtml = '<input type="submit" name="updateLinkList" value="' . $GLOBALS['LANG']->getLL('label_update') . '"/>';
		}
		$this->refreshListHtml = '<input type="submit" name="refreshLinkList" value="' . $GLOBALS['LANG']->getLL('label_refresh') . '"/>';
		$processing = t3lib_div::makeInstance('tx_linkvalidator_processing');
		$this->updateBrokenLinks($processing);

		$brokenLinkOverView = $processing->getLinkCounts($this->pObj->id);
		$this->checkOptHtml = $this->getCheckOptions($brokenLinkOverView);

		$this->render();

		return $this->flush();
	} // end function main()

	/**
	 * Initialize menu array internally
	 *
	 * @return	Module		menu
	 */
	public function modMenu() {
		$modMenu = array (
			'checkAllLink' => 0,
		);

		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] as $linkType => $value) {
				$modMenu[$linkType] = 1;
			}
		}

		return $modMenu;
	} // end function modMenu()


	/**
	 * Initializes the Module
	 *
	 * @return	void
	 */
	public function initialize() {
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] as $linkType => $classRef) {
				$this->hookObjectsArr[$linkType] = &t3lib_div::getUserObj($classRef);
			}
		}

		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->setModuleTemplate(t3lib_extMgm::extPath('linkvalidator') . 'modfunc1/mod_template.html');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];

		$this->relativePath = t3lib_extMgm::extRelPath('linkvalidator');
		$this->pageRecord = t3lib_BEfunc::readPageAccess($this->pObj->id, $this->perms_clause);

		$this->isAccessibleForCurrentUser = FALSE;
		if ($this->pObj->id && is_array($this->pageRecord) || !$this->pObj->id && $this->isCurrentUserAdmin()) {
			$this->isAccessibleForCurrentUser = TRUE;
		}

		$this->loadHeaderData();

			// Don't access in workspace
		if ($GLOBALS['BE_USER']->workspace !== 0) {
			$this->isAccessibleForCurrentUser = FALSE;
		}
	} // end function initialize()


	/**
	 * Update the table of stored broken links
	 *
	 * @param	array		Processing object
	 * @return	void
	 */
	public function updateBrokenLinks($processing) {
		$searchFields = array();

			// get the searchFields from TypoScript
		foreach ($this->modTS['searchFields.'] as $table => $fieldList) {
			$fields = t3lib_div::trimExplode(',', $fieldList);
			foreach ($fields as $field) {
				if (!$searchFields || !is_array($searchFields[$table]) || array_search($field, $searchFields[$table]) == FALSE) {
					$searchFields[$table][] = $field;
				}
			}
		}
			// get children pages
		$pageList = t3lib_tsfeBeUserAuth::extGetTreeList(
			$this->pObj->id,
			$this->search_level,
			0,
			$GLOBALS['BE_USER']->getPagePermsClause(1)
		);

		$pageList .= $this->pObj->id;

		$processing->init($searchFields, $pageList);

		// check if button press
		$update = t3lib_div::_GP('updateLinkList');

		if (!empty($update)) {
			$processing->getLinkStatistics($this->checkOpt, $this->modTS['checkhidden']);
		}
	} // end function updateBrokenLinks()


	/**
	 * Renders the content of the module.
	 *
	 * @return	void
	 */
	public function render() {
		if ($this->isAccessibleForCurrentUser) {
			$this->content = $this->drawBrokenLinksTable();
		} else {
				// If no access or if ID == zero
			$this->content .= $this->doc->spacer(10);
		}
	} // end function render()


	/**
	 * Flushes the rendered content to browser.
	 *
	 * @return	void
	 */
	public function flush() {
		$content.= $this->doc->moduleBody(
			$this->pageRecord,
			$this->getDocHeaderButtons(),
			$this->getTemplateMarkers()
		);

		return $content;
	} // end function flush()

	/**
	 * @return string
	 */
	protected function getLevelSelector() {
			// Make level selector:
		$opt = array();
		$parts = array(
			0 => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.depth_0'),
			1 => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.depth_1'),
			2 => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.depth_2'),
			3 => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.depth_3'),
			999 => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.depth_infi'),
		);

		foreach ($parts as $kv => $label) {
			$opt[] = '<option value="' . $kv . '"' . ($kv == intval($this->search_level) ? ' selected="selected"' : '') . '>' . htmlspecialchars($label) . '</option>';
		}
		$lMenu = '<select name="search_levels">' . implode('', $opt) . '</select>';
		return $lMenu;
	}

	/**
	 * Display the table of broken links
	 *
	 * @return	html	Content of the table
	 */
	protected function drawBrokenLinksTable() {
		$content = '';
		$items = array();
		$brokenLinkItems = '';
		$keyOpt = array();

		$brokenLinksTemplate = t3lib_parsehtml::getSubpart($this->doc->moduleTemplate, '###BROKENLINKS_CONTENT###');

			// table header
		$brokenLinksMarker = $this->startTable();
		$brokenLinksTemplate = t3lib_parsehtml::substituteMarkerArray($brokenLinksTemplate, $brokenLinksMarker, '###|###', TRUE);

		$brokenLinksItemTemplate = t3lib_parsehtml::getSubpart($this->doc->moduleTemplate, '###BROKENLINKS_ITEM###');
		if (is_array($this->checkOpt)) {
			$keyOpt = array_keys($this->checkOpt);
		}

		$pageList = t3lib_tsfeBeUserAuth::extGetTreeList(
			$this->pObj->id,
			$this->search_level,
			0,
			$GLOBALS['BE_USER']->getPagePermsClause(1)
		);
		$pageList .= $this->pObj->id;
		if (($res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tx_linkvalidator_links',
			'recpid in (' . $pageList . ') and typelinks in (\'' . implode("','", $keyOpt) . '\')',
			'',
			'recuid ASC, uid ASC')
		)) {
				// table rows containing the broken links
			while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
				$alter = $alter % 2 ? FALSE : TRUE;
				$items[] = $this->drawTableRow($row['tablename'], $row, $alter, $brokenLinksItemTemplate);
			}
		}

		if (is_array($items)) {
			$brokenLinkItems = implode(chr(10), $items);
		}

		$content = t3lib_parsehtml::substituteSubpart($brokenLinksTemplate, '###BROKENLINKS_ITEM', $brokenLinkItems);

		return $content;
	} // end function drawBrokenLinksTable()


	/**
	 * Calls t3lib_tsfeBeUserAuth::extGetTreeList.
	 * Although this duplicates the function t3lib_tsfeBeUserAuth::extGetTreeList
	 * this is necessary to create the object that is used recursively by the original function.
	 *
	 * Generates a list of Page-uid's from $id. List does not include $id itself
	 * The only pages excluded from the list are deleted pages.
	 *
	 *							  level in the tree to start collecting uid's. Zero means
	 *							  'start right away', 1 = 'next level and out'
	 *
	 * @param	integer		Start page id
	 * @param	integer		Depth to traverse down the page tree.
	 * @param	integer		$begin is an optional integer that determines at which
	 * @param	string		Perms clause
	 * @return	string		Returns the list with a comma in the end (if any pages selected!)
	 */
	public function extGetTreeList($id, $depth, $begin = 0, $perms_clause) {
		return t3lib_tsfeBeUserAuth::extGetTreeList($id, $depth, $begin, $perms_clause);
	}


	/**
	 * Display table begin of the broken links
	 *
	 * @return	html		Code of content
	 */
	protected function startTable() {
		global $LANG;

			// Listing head
		$makerTableHead = array();
		$makerTableHead['list_header'] = $this->doc->sectionHeader($LANG->getLL('list.header'), $h_func);
		$makerTableHead['bgColor2'] = $this->doc->bgColor2;

		$makerTableHead['tablehead_path'] = $LANG->getLL('list.tableHead.path');
		$makerTableHead['tablehead_type'] = $LANG->getLL('list.tableHead.type');
		$makerTableHead['tablehead_headline'] = $LANG->getLL('list.tableHead.headline');
		$makerTableHead['tablehead_field'] = $LANG->getLL('list.tableHead.field');
		$makerTableHead['tablehead_headlink'] = $LANG->getLL('list.tableHead.headlink');
		$makerTableHead['tablehead_linktarget'] = $LANG->getLL('list.tableHead.linktarget');
		$makerTableHead['tablehead_linkmessage'] = $LANG->getLL('list.tableHead.linkmessage');
		$makerTableHead['tablehead_lastcheck'] = $LANG->getLL('list.tableHead.lastCheck');

		return $makerTableHead;
	} // end function startTable()


	/**
	 * Display line of the broken links table
	 *
	 * @param	string		table
	 * @param	string		row record
	 * @param	bool		alternate color between rows
	 * @return	html		code of content
	 */
	protected function drawTableRow($table, $row, $switch, $brokenLinksItemTemplate) {
		$markerArray = array();
		if (is_array($row) && !empty($row['typelinks'])) {
			if (($hookObj = $this->hookObjectsArr[$row['typelinks']])) {
				$brokenUrl = $hookObj->getBrokenUrl($row);
			}
		}

		$params = '&edit[' . $table . '][' . $row['recuid'] . ']=edit';
		$actionLinks = '<a href="#" onclick="' . t3lib_BEfunc::editOnClick($params, $GLOBALS['BACK_PATH'], '') . '">'.
				t3lib_iconWorks::getSpriteIcon('actions-document-open') . '</a>';

		$elementType = $row['headline'];
		if (empty($elementType)) {
			$elementType = $table . ':' . $row['recuid'];
		}

			//Alternating row colors
		if ($switch == TRUE) {
			$switch = FALSE;
			$markerArray['bgcolor_alternating'] = $this->doc->bgColor3;
		} elseif ($switch == FALSE) {
			$switch = TRUE;
			$markerArray['bgcolor_alternating'] = $this->doc->bgColor5;
		}

		$markerArray['actionlink'] = $actionLinks;
		$markerArray['path'] = t3lib_BEfunc::getRecordPath($row['recpid'], '', 0, 0);
		$markerArray['type'] = t3lib_iconWorks::getSpriteIconForRecord($table, $row, array('title' => $row['recuid']));
		$markerArray['headline'] = $elementType;
		$markerArray['field'] = $row['field'];
		$markerArray['headlink'] = $row['linktitle'];
		$markerArray['linktarget'] = $brokenUrl;
		$markerArray['linkmessage'] = $row['urlresponse'];
		$lastRunDate = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], $row['lastcheck']);
		$lastRunTime = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'], $row['lastcheck']);
		$message = sprintf($GLOBALS['LANG']->getLL('list.msg.lastRun'), $lastRunDate, $lastRunTime);
		$markerArray['lastcheck'] = $message;

			// Return the table html code as string
		return t3lib_parsehtml::substituteMarkerArray($brokenLinksItemTemplate, $markerArray, '###|###', TRUE, TRUE);
	} // end function drawTableRow()


	/**
	 * Builds the checkboxes out of the hooks array
	 *
	 * @param	array		array of broken links informations
	 * @return	html		code content
	 */
	protected function getCheckOptions($brokenLinkOverView) {
		global $LANG;
		$content = '';
		$checkOptionsTemplate = '';
		$checkOptionsTemplate = t3lib_parsehtml::getSubpart($this->doc->moduleTemplate, '###CHECKOPTIONS_SECTION###');

		$hookSectionContent = '';
		$hookSectionTemplate = t3lib_parsehtml::getSubpart($checkOptionsTemplate, '###HOOK_SECTION###');

		$markerArray['total_count_label'] = $LANG->getLL('overviews.nbtotal');
		$markerArray['total_count'] = $brokenLinkOverView['brokenlinkCount'];

		$linktypes = t3lib_div::trimExplode(',', $this->modTS['linktypes'], 1);
		$hookSectionContent = '';

		if (is_array($linktypes)) {
			if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])
				&& is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])
			) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] as $key => $value) {
					if (in_array($key, $linktypes)) {
						$hookSectionMarker = array();
						$hookSectionMarker['count'] = $brokenLinkOverView[$key];
						$trans = $GLOBALS['LANG']->getLL('hooks.' . $key);
						$trans = $trans ? $trans : $key;
						$option = t3lib_BEfunc::getFuncCheck(
							$this->pObj->id, 'SET[' . $key . ']',
							$this->pObj->MOD_SETTINGS[$key]
						) . '<label for="' . $key . '">' . $trans . '</label>';
						$hookSectionMarker['option'] = $option;
						$hookSectionContent .= t3lib_parsehtml::substituteMarkerArray($hookSectionTemplate, $hookSectionMarker, '###|###', TRUE, TRUE);
					}
				}
			}
		}

		$checkOptionsTemplate = t3lib_parsehtml::substituteSubpart($checkOptionsTemplate, '###HOOK_SECTION###', $hookSectionContent);

		return t3lib_parsehtml::substituteMarkerArray($checkOptionsTemplate, $markerArray, '###|###', TRUE, TRUE);
	} // end function getCheckOptions()


	/**
	 * Loads data in the HTML head section (e.g. JavaScript or stylesheet information).
	 *
	 * @return	void
	 */
	protected function loadHeaderData() {
		$this->doc->addStyleSheet('linkvalidator', $this->relativePath . 'res/linkvalidator.css', 'linkvalidator');
	}


	/**
	 * Gets the buttons that shall be rendered in the docHeader.
	 *
	 * @return	array		Available buttons for the docHeader
	 */
	protected function getDocHeaderButtons() {
		$buttons = array(
			'csh' => t3lib_BEfunc::cshItem('_MOD_web_func', '', $GLOBALS['BACK_PATH']),
			'shortcut' => $this->getShortcutButton(),
			'save' => ''
		);
		return $buttons;
	}


	/**
	 * Gets the button to set a new shortcut in the backend (if current user is allowed to).
	 *
	 * @return	string		HTML representiation of the shortcut button
	 */
	protected function getShortcutButton() {
		$result = '';
		if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
			$result = $this->doc->makeShortcutIcon('', 'function', $this->MCONF['name']);
		}
		return $result;
	}


	/**
	 * Gets the filled markers that are used in the HTML template.
	 *
	 * @return	array		The filled marker array
	 */
	protected function getTemplateMarkers() {
		$markers = array(
			'FUNC_MENU'				=> $this->getLevelSelector(),
			'CONTENT'				=> $this->content,
			'TITLE'					=> $GLOBALS['LANG']->getLL('title'),
			'CHECKALLLINK'			=> $this->checkAllHtml,
			'CHECKOPTIONS'			=> $this->checkOptHtml,
			'ID'					=> '<input type="hidden" name="id" value="' . $this->pObj->id . '"/>',
			'REFRESH'				=> $this->refreshListHtml,
		    'UPDATE'                =>$this->updateListHtml
		);

		return $markers;
	} // end function getTemplateMarkers()


	/**
	 * Determines whether the current user is admin.
	 *
	 * @return	boolean		Whether the current user is admin
	 */
	protected function isCurrentUserAdmin() {
		return ((bool) $GLOBALS['BE_USER']->user['admin']);
	}
} // end class

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/linkvalidator/modfunc1/class.tx_linkvalidator_modfunc1.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/linkvalidator/modfunc1/class.tx_linkvalidator_modfunc1.php']);
}

?>
