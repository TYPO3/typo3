<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2010 Jochen Rieger (j.rieger@connecta.ag) 
 *  (c) 2010 - 2011 Michael Miousse (michael.miousse@infoglobe.ca)
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

	/**
	 * @var string
	 */
	protected $relativePath;

	/**
	 * Information about the current page record.
	 *
	 * @var array
	 */
	protected $pageRecord = array();

	/**
	 * Information, if the module is accessible for the current user or not.
	 *
	 * @var boolean
	 */
	protected $isAccessibleForCurrentUser = FALSE;

	/**
	 * Depth for the recursivity of the link validation.
	 *
	 * @var integer
	 */
	protected $search_level;

	/**
	 * Link validation class.
	 *
	 * @var tx_linkvalidator_processing
	 */
	protected $processing;

	/**
	 * TSconfig of the current module.
	 *
	 * @var array
	 */
	protected $modTS = array();

	/**
	 * List of available link types to check defined in the TSconfig.
	 *
	 * @var array
	 */
	protected $availableOptions = array();

	/**
	 * List of link types currently chosen in the Statistics table.
	 * Used to show broken links of these types only.
	 *
	 * @var array
	 */
	protected $checkOpt = array();

	/**
	 * Hint message displayed on top of the module.
	 *
	 * @var string
	 */
	protected $firstSteps;

	/**
	 * Html for the button "Check Links".
	 *
	 * @var string
	 */
	protected $updateListHtml;

	/**
	 * Html for the button "Refresh Display".
	 *
	 * @var string
	 */
	protected $refreshListHtml;

	/**
	 * Html for the Statistics table with the checkboxes of the link types and the numbers of broken links.
	 *
	 * @var string
	 */
	protected $checkOptHtml;

	/**
	 * Complete content (html) to be displayed.
	 *
	 * @var string
	 */
	protected $content;

	/**
	 * Main method of modfunc1
	 *
	 * @return string Module content
	 */
	public function main() {
		$GLOBALS['LANG']->includeLLFile('EXT:linkvalidator/modfunc1/locallang.xml');

		$this->search_level = t3lib_div::_GP('search_levels');

		if (isset($this->pObj->id)) {
			$this->modTS = t3lib_BEfunc::getModTSconfig($this->pObj->id, 'mod.linkvalidator');
			$this->modTS = $this->modTS['properties'];
		}

		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] as $linkType => $value) {
					// Compile list of all available types. Used for checking with button "Check Links".
				if (strpos($this->modTS['linktypes'], $linkType) !== FALSE) {
					$this->availableOptions[$linkType] = 1;
				}
					// Compile list of types currently selected by the checkboxes.
				if ($this->pObj->MOD_SETTINGS[$linkType]) {
					$this->checkOpt[$linkType] = 1;
				}
			}
		}

		$this->initialize();

		$this->firstSteps = $GLOBALS['LANG']->getLL('first.steps');

		if ($this->modTS['showUpdateButton'] == 1) {
			$this->firstSteps .= ' ' . $GLOBALS['LANG']->getLL('first.steps.info.update.button');
			$this->updateListHtml = '<input type="submit" name="updateLinkList" value="' . $GLOBALS['LANG']->getLL('label_update') . '"/>';
		}

		if (t3lib_extMgm::isLoaded('scheduler')) {
			if ($GLOBALS['BE_USER']->isAdmin()) {
				$this->firstSteps .= ' ' . 
				sprintf($GLOBALS['LANG']->getLL('first.steps.info.scheduler'),
				 	'<a href="' . t3lib_div::getIndpEnv('TYPO3_SITE_URL') . 'typo3/mod.php?M=tools_txschedulerM1">', '</a>'
				);
			} else {
				$this->firstSteps .= ' ' . $GLOBALS['LANG']->getLL('first.steps.info.scheduler.admin');
			}
		}

		$this->refreshListHtml = '<input type="submit" name="refreshLinkList" value="' . $GLOBALS['LANG']->getLL('label_refresh') . '"/>';

		$this->processing = t3lib_div::makeInstance('tx_linkvalidator_processing');
		$this->updateBrokenLinks();

		$brokenLinkOverView = $this->processing->getLinkCounts($this->pObj->id);
		$this->checkOptHtml = $this->getCheckOptions($brokenLinkOverView);

		$this->render();


		return $this->flush();
	}


	/**
	 * Initializes the menu array internally.
	 *
	 * @return array Module menu
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
	}


	/**
	 * Initializes the Module.
	 *
	 * @return void
	 */
	protected function initialize() {
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
	}


	/**
	 * Updates the table of stored broken links.
	 *
	 * @return void
	 */
	protected function updateBrokenLinks() {
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
		$pageList = $this->processing->extGetTreeList(
			$this->pObj->id,
			$this->search_level,
			0,
			$GLOBALS['BE_USER']->getPagePermsClause(1)
		);
		$pageList .= $this->pObj->id;

		$this->processing->init($searchFields, $pageList);

			// check if button press
		$update = t3lib_div::_GP('updateLinkList');

		if (!empty($update)) {
			$this->processing->getLinkStatistics($this->availableOptions, $this->modTS['checkhidden']);
		}
	}


	/**
	 * Renders the content of the module.
	 *
	 * @return void
	 */
	protected function render() {
		if ($this->isAccessibleForCurrentUser) {
			$this->content = $this->drawBrokenLinksTable();
		} else {
				// If no access or if ID == zero
			$message = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$GLOBALS['LANG']->getLL('no.access'),
				$GLOBALS['LANG']->getLL('no.access.title'),
				t3lib_FlashMessage::ERROR
			);
			$this->content .= $message->render();
		}
	}


	/**
	 * Flushes the rendered content to the browser.
	 *
	 * @return void
	 */
	protected function flush() {
		$content.= $this->doc->moduleBody(
			$this->pageRecord,
			$this->getDocHeaderButtons(),
			$this->getTemplateMarkers()
		);

		return $content;
	}


	/**
	 * Builds the selector for the level of pages to search.
	 *
	 * @return string Html code of that selector
	 */
	private function getLevelSelector() {
			// Make level selector:
		$opt = array();
		$parts = array(
			0 => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_0'),
			1 => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_1'),
			2 => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_2'),
			3 => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_3'),
			999 => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_infi'),
		);

		foreach ($parts as $kv => $label) {
			$opt[] = '<option value="' . $kv . '"' . ($kv == intval($this->search_level) ? ' selected="selected"' : '') . '>' . htmlspecialchars($label) . '</option>';
		}
		$lMenu = '<select name="search_levels">' . implode('', $opt) . '</select>';
		return $lMenu;
	}

	/**
	 * Displays the table of broken links or a note if there were no broken links.
	 *
	 * @return html Content of the table or of the note
	 */
	private function drawBrokenLinksTable() {
		$content = '';
		$items = array();
		$brokenLinkItems = '';
		$keyOpt = array();

		if (is_array($this->checkOpt)) {
			$keyOpt = array_keys($this->checkOpt);
		}

		$pageList = $this->processing->extGetTreeList(
			$this->pObj->id,
			$this->search_level,
			0,
			$GLOBALS['BE_USER']->getPagePermsClause(1)
		);
		$pageList .= $this->pObj->id;

		if (($res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tx_linkvalidator_link',
			'record_pid in (' . $pageList . ') and link_type in (\'' . implode("','", $keyOpt) . '\')',
			'',
			'record_uid ASC, uid ASC')
		)) {
				// Display table with broken links
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
				$brokenLinksTemplate = t3lib_parsehtml::getSubpart($this->doc->moduleTemplate, '###BROKENLINKS_CONTENT###');

				$brokenLinksItemTemplate = t3lib_parsehtml::getSubpart($this->doc->moduleTemplate, '###BROKENLINKS_ITEM###');

					// Table header
				$brokenLinksMarker = $this->startTable();

					// Table rows containing the broken links
				while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
					$items[] = $this->drawTableRow($row['table_name'], $row, $brokenLinksItemTemplate);
				}
				$brokenLinkItems = implode(chr(10), $items);

				// Display note that there are no broken links to display
			} else {
				$brokenLinksTemplate = t3lib_parsehtml::getSubpart($this->doc->moduleTemplate, '###NOBROKENLINKS_CONTENT###');

				$brokenLinksMarker['LIST_HEADER'] = $this->doc->sectionHeader($GLOBALS['LANG']->getLL('list.header'));
				$message = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					$GLOBALS['LANG']->getLL('list.no.broken.links'),
					$GLOBALS['LANG']->getLL('list.no.broken.links.title'),
					t3lib_FlashMessage::OK
				);
				$brokenLinksMarker['NO_BROKEN_LINKS'] = $message->render();
			}
		}
		$brokenLinksTemplate = t3lib_parsehtml::substituteMarkerArray($brokenLinksTemplate, $brokenLinksMarker, '###|###', TRUE);

		$content = t3lib_parsehtml::substituteSubpart($brokenLinksTemplate, '###BROKENLINKS_ITEM', $brokenLinkItems);

		return $content;
	}



	/**
	 * Displays the table header of the table with the broken links.
	 *
	 * @return string Code of content
	 */
	private function startTable() {
		global $TYPO3_CONF_VARS;

			// Listing head
		$makerTableHead = array();

		$makerTableHead['tablehead_path'] = $GLOBALS['LANG']->getLL('list.tableHead.path');
		$makerTableHead['tablehead_element'] = $GLOBALS['LANG']->getLL('list.tableHead.element');
		$makerTableHead['tablehead_headlink'] = $GLOBALS['LANG']->getLL('list.tableHead.headlink');
		$makerTableHead['tablehead_linktarget'] = $GLOBALS['LANG']->getLL('list.tableHead.linktarget');
		$makerTableHead['tablehead_linkmessage'] = $GLOBALS['LANG']->getLL('list.tableHead.linkmessage');
		$makerTableHead['tablehead_lastcheck'] = $GLOBALS['LANG']->getLL('list.tableHead.lastCheck');

			// Add CSH to the header of each column
		foreach($makerTableHead as $column => $label) {
			$label = t3lib_BEfunc::wrapInHelp('linkvalidator', $column, $label);
			$makerTableHead[$column] = $label;
		}

			// Add section header
		$makerTableHead['list_header'] = $this->doc->sectionHeader($GLOBALS['LANG']->getLL('list.header'));

		return $makerTableHead;
	}


	/**
	 * Displays one line of the broken links table.
	 *
	 * @param string $table Name of database table
	 * @param array $row Record row to be processed
	 * @param string $brokenLinksItemTemplate Markup of the template to be used
	 * @return string HTML of the rendered row
	 */
	private function drawTableRow($table, array $row, $brokenLinksItemTemplate) {
		$markerArray = array();
		if (is_array($row) && !empty($row['link_type'])) {
			if (($hookObj = $this->hookObjectsArr[$row['link_type']])) {
				$brokenUrl = $hookObj->getBrokenUrl($row);
			}
		}

		$params = '&edit[' . $table . '][' . $row['record_uid'] . ']=edit';
		$actionLinks = '<a href="#" onclick="' .
				t3lib_BEfunc::editOnClick(
					$params,
					$GLOBALS['BACK_PATH'],
					t3lib_div::getIndpEnv('REQUEST_URI') . '?id=' . $this->pObj->id . '&search_levels=' . $this->search_level
				) . '"' .
				' title="' . $GLOBALS['LANG']->getLL('list.edit') . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-document-open') . '</a>';

		$elementHeadline = $row['headline'];
		if (empty($elementHeadline)) {
			$elementHeadline = '<i>' . $GLOBALS['LANG']->getLL('list.no.headline') . '</i>';
		}

			// Get the language label for the field from TCA
		if ($GLOBALS['TCA'][$table]['columns'][$row['field']]['label']) {
			$fieldName = $GLOBALS['TCA'][$table]['columns'][$row['field']]['label'];
			$fieldName = $GLOBALS['LANG']->sL($fieldName);
				// Crop colon from end if present.
			if (substr($fieldName, '-1', '1') === ':') {
				$fieldName = substr($fieldName, '0', strlen($fieldName)-1);
			}
		}
			// Fallback, if there is no label
		$fieldName = $fieldName ? $fieldName : $row['field'];

			// column "Element"
		$element = t3lib_iconWorks::getSpriteIconForRecord($table, $row, array('title' => $table . ':' . $row['record_uid']));
		$element .= $elementHeadline;
		$element .= ' ' . sprintf($GLOBALS['LANG']->getLL('list.field'), $fieldName);

		$markerArray['actionlink'] = $actionLinks;
		$markerArray['path'] = t3lib_BEfunc::getRecordPath($row['record_pid'], '', 0, 0);
		$markerArray['element'] = $element; 
		$markerArray['headlink'] = $row['link_title'];
		$markerArray['linktarget'] = $brokenUrl;

		$response = unserialize($row['url_response']);
		if ($response['valid']) {
			$linkMessage = '<span style="color: green;">' . $GLOBALS['LANG']->getLL('list.msg.ok') . '</span>';
		} else {
			$linkMessage = '<span style="color: red;">' . $hookObj->getErrorMessage($response['errorParams']) . '</span>';
		}
		$markerArray['linkmessage'] = $linkMessage;

		$lastRunDate = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], $row['last_check']);
		$lastRunTime = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'], $row['last_check']);
		$message = sprintf($GLOBALS['LANG']->getLL('list.msg.lastRun'), $lastRunDate, $lastRunTime);
		$markerArray['lastcheck'] = $message;

			// Return the table html code as string
		return t3lib_parsehtml::substituteMarkerArray($brokenLinksItemTemplate, $markerArray, '###|###', TRUE, TRUE);
	}


	/**
	 * Builds the checkboxes out of the hooks array.
	 *
	 * @param array $brokenLinkOverView array of broken links information
	 * @return string code content
	 */
	private function getCheckOptions(array $brokenLinkOverView) {
		$content = '';
		$checkOptionsTemplate = '';
		$checkOptionsTemplate = t3lib_parsehtml::getSubpart($this->doc->moduleTemplate, '###CHECKOPTIONS_SECTION###');

		$hookSectionContent = '';
		$hookSectionTemplate = t3lib_parsehtml::getSubpart($checkOptionsTemplate, '###HOOK_SECTION###');

		$markerArray['statistics_header'] = $this->doc->sectionHeader($GLOBALS['LANG']->getLL('overviews.statistics.header'));

		$totalCountLabel = $GLOBALS['LANG']->getLL('overviews.nbtotal');
		$totalCountLabel = t3lib_BEfunc::wrapInHelp('linkvalidator', 'checkboxes', $totalCountLabel);
		$markerArray['total_count_label'] = $totalCountLabel;

		if (empty($brokenLinkOverView['brokenlinkCount'])) {
			$markerArray['total_count'] = '0';
		} else {
			$markerArray['total_count'] = $brokenLinkOverView['brokenlinkCount'];
		}

		$linktypes = t3lib_div::trimExplode(',', $this->modTS['linktypes'], 1);
		$hookSectionContent = '';

		if (is_array($linktypes)) {
			if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])
				&& is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])
			) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] as $type => $value) {
					if (in_array($type, $linktypes)) {
						$hookSectionMarker = array();
						if (empty($brokenLinkOverView[$type])) {
							$hookSectionMarker['count'] = '0';
						} else {
							$hookSectionMarker['count'] = $brokenLinkOverView[$type];
						}
						$translation = $GLOBALS['LANG']->getLL('hooks.' . $type);
						$translation = $translation ? $translation : $type;
						$option = t3lib_BEfunc::getFuncCheck(
							array('id' => $this->pObj->id, 'search_levels' => $this->search_level),
							'SET[' . $type . ']',
							$this->pObj->MOD_SETTINGS[$type],
							'',
							'',
							'id="SET[' . $type . ']"'
						) . '<label for="SET[' . $type . ']">' . $translation . '</label>';
						$hookSectionMarker['option'] = $option;
						$hookSectionContent .= t3lib_parsehtml::substituteMarkerArray($hookSectionTemplate, $hookSectionMarker, '###|###', TRUE, TRUE);
					}
				}
			}
		}

		$checkOptionsTemplate = t3lib_parsehtml::substituteSubpart($checkOptionsTemplate, '###HOOK_SECTION###', $hookSectionContent);

		return t3lib_parsehtml::substituteMarkerArray($checkOptionsTemplate, $markerArray, '###|###', TRUE, TRUE);
	}


	/**
	 * Loads data in the HTML head section (e.g. JavaScript or stylesheet information).
	 *
	 * @return void
	 */
	private function loadHeaderData() {
		$this->doc->addStyleSheet('linkvalidator', $this->relativePath . 'res/linkvalidator.css', 'linkvalidator');
	}


	/**
	 * Gets the buttons that shall be rendered in the docHeader.
	 *
	 * @return array Available buttons for the docHeader
	 */
	private function getDocHeaderButtons() {
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
	 * @return string HTML representiation of the shortcut button
	 */
	private function getShortcutButton() {
		$result = '';
		if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
			$result = $this->doc->makeShortcutIcon('', 'function', $this->MCONF['name']);
		}
		return $result;
	}


	/**
	 * Gets the filled markers that are used in the HTML template.
	 *
	 * @return array The filled marker array
	 */
	private function getTemplateMarkers() {

		$markers = array(
			'FIRST_STEPS'           => $this->firstSteps,
			'FUNC_MENU'             => $this->getLevelSelector(),
			'CONTENT'               => $this->content,
			'TITLE'                 => $GLOBALS['LANG']->getLL('title'),
			'CHECKALLLINK'          => $this->checkAllHtml,
			'CHECKOPTIONS'          => $this->checkOptHtml,
			'ID'                    => '<input type="hidden" name="id" value="' . $this->pObj->id . '"/>',
			'REFRESH'               => $this->refreshListHtml,
			'UPDATE'                => $this->updateListHtml
		);

		return $markers;
	}


	/**
	 * Determines whether the current user is an admin.
	 *
	 * @return boolean Whether the current user is admin
	 */
	private function isCurrentUserAdmin() {
		return ((bool) $GLOBALS['BE_USER']->user['admin']);
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/linkvalidator/modfunc1/class.tx_linkvalidator_modfunc1.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/linkvalidator/modfunc1/class.tx_linkvalidator_modfunc1.php']);
}

?>