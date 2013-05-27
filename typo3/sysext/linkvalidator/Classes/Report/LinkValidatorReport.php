<?php
namespace TYPO3\CMS\Linkvalidator\Report;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2013 Jochen Rieger (j.rieger@connecta.ag)
 *  (c) 2010 - 2013 Michael Miousse (michael.miousse@infoglobe.ca)
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
 * Module 'Linkvalidator' for the 'linkvalidator' extension
 *
 * @author Michael Miousse <michael.miousse@infoglobe.ca>
 * @author Jochen Rieger <j.rieger@connecta.ag>
 */
class LinkValidatorReport extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule {

	/**
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 */
	public $doc;

	/**
	 * @var string
	 */
	protected $relativePath;

	/**
	 * Information about the current page record
	 *
	 * @var array
	 */
	protected $pageRecord = array();

	/**
	 * Information, if the module is accessible for the current user or not
	 *
	 * @var boolean
	 */
	protected $isAccessibleForCurrentUser = FALSE;

	/**
	 * Depth for the recursive traversal of pages for the link validation
	 *
	 * @var integer
	 */
	protected $searchLevel;

	/**
	 * Link validation class
	 *
	 * @var \TYPO3\CMS\Linkvalidator\LinkAnalyzer
	 */
	protected $processor;

	/**
	 * TSconfig of the current module
	 *
	 * @var array
	 */
	protected $modTS = array();

	/**
	 * List of available link types to check defined in the TSconfig
	 *
	 * @var array
	 */
	protected $availableOptions = array();

	/**
	 * List of link types currently chosen in the statistics table
	 * Used to show broken links of these types only
	 *
	 * @var array
	 */
	protected $checkOpt = array();

	/**
	 * Html for the button "Check Links"
	 *
	 * @var string
	 */
	protected $updateListHtml;

	/**
	 * Html for the button "Refresh Display"
	 *
	 * @var string
	 */
	protected $refreshListHtml;

	/**
	 * Html for the statistics table with the checkboxes of the link types
	 * and the numbers of broken links for report tab
	 *
	 * @var string
	 */
	protected $checkOptHtml;

	/**
	 * Html for the statistics table with the checkboxes of the link types
	 * and the numbers of broken links for check links tab
	 *
	 * @var string
	 */
	protected $checkOptHtmlCheck;

	/**
	 * Complete content (html) to be displayed
	 *
	 * @var string
	 */
	protected $content;

	/**
	 * @var \TYPO3\CMS\Core\Page\PageRenderer
	 */
	protected $pageRenderer;

	/**
	 * @var \TYPO3\CMS\Linkvalidator\Linktype\LinktypeInterface[]
	 */
	protected $hookObjectsArr = array();

	/**
	 * @var string $checkAllHtml
	 */
	protected $checkAllHtml = '';

	/**
	 * Main method of modfuncreport
	 *
	 * @return string Module content
	 */
	public function main() {
		$GLOBALS['LANG']->includeLLFile('EXT:linkvalidator/Resources/Private/Language/Module/locallang.xlf');
		$this->searchLevel = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('search_levels');
		if (isset($this->pObj->id)) {
			$this->modTS = \TYPO3\CMS\Backend\Utility\BackendUtility::getModTSconfig($this->pObj->id, 'mod.linkvalidator');
			$this->modTS = $this->modTS['properties'];
		}
		$update = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('updateLinkList');
		$prefix = '';
		if (!empty($update)) {
			$prefix = 'check';
		}
		$set = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP($prefix . 'SET');
		$this->pObj->handleExternalFunctionValue();
		if (isset($this->searchLevel)) {
			$this->pObj->MOD_SETTINGS['searchlevel'] = $this->searchLevel;
		} else {
			$this->searchLevel = $this->pObj->MOD_SETTINGS['searchlevel'];
		}
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] as $linkType => $value) {
				// Compile list of all available types. Used for checking with button "Check Links".
				if (strpos($this->modTS['linktypes'], $linkType) !== FALSE) {
					$this->availableOptions[$linkType] = 1;
				}
				// Compile list of types currently selected by the checkboxes
				if ($this->pObj->MOD_SETTINGS[$linkType] && empty($set) || $set[$linkType]) {
					$this->checkOpt[$linkType] = 1;
					$this->pObj->MOD_SETTINGS[$linkType] = 1;
				} else {
					$this->pObj->MOD_SETTINGS[$linkType] = 0;
					unset($this->checkOpt[$linkType]);
				}
			}
		}
		$GLOBALS['BE_USER']->pushModuleData('web_info', $this->pObj->MOD_SETTINGS);
		$this->initialize();

		$this->pageRenderer = $this->doc->getPageRenderer();
		// Localization
		$this->pageRenderer->addInlineLanguageLabelFile(
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('linkvalidator', 'Resources/Private/Language/Module/locallang.xlf')
		);
		$this->pageRenderer->addJsInlineCode('linkvalidator', 'function toggleActionButton(prefix) {
			var buttonDisable = true;
			Ext.select(\'.\' + prefix ,false).each(function(checkBox,i){
			checkDom = checkBox.dom;
			if (checkDom.checked){
				buttonDisable = false;
			}

			});
			if (prefix == \'check\'){
				checkSub = document.getElementById(\'updateLinkList\');
			} else {
				checkSub = document.getElementById(\'refreshLinkList\');
			}
			checkSub.disabled = buttonDisable;
		}');
		// Add JS
		$this->pageRenderer->addJsFile($this->doc->backPath . '../t3lib/js/extjs/ux/Ext.ux.FitToParent.js');
		$this->pageRenderer->addJsFile($this->doc->backPath . '../t3lib/js/extjs/ux/flashmessages.js');
		$this->pageRenderer->addJsFile($this->doc->backPath . 'js/extjs/iframepanel.js');
		if ($this->modTS['showCheckLinkTab'] == 1) {
			$this->updateListHtml = '<input type="submit" name="updateLinkList" id="updateLinkList" value="' . $GLOBALS['LANG']->getLL('label_update') . '"/>';
		}
		$this->refreshListHtml = '<input type="submit" name="refreshLinkList" id="refreshLinkList" value="' . $GLOBALS['LANG']->getLL('label_refresh') . '"/>';
		$this->processor = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Linkvalidator\\LinkAnalyzer');
		$this->updateBrokenLinks();
		$brokenLinkOverView = $this->processor->getLinkCounts($this->pObj->id);
		$this->checkOptHtml = $this->getCheckOptions($brokenLinkOverView);
		$this->checkOptHtmlCheck = $this->getCheckOptions($brokenLinkOverView, 'check');
		$this->createTabs();
		return '<div id="linkvalidator-modfuncreport"></div>';
	}

	/**
	 * Create TabPanel to split the report and the checkLink functions
	 *
	 * @return void
	 */
	protected function createTabs() {
		$panelCheck = '';
		if ($this->modTS['showCheckLinkTab'] == 1) {
			$panelCheck = ',
			{
				title: TYPO3.l10n.localize(\'CheckLink\'),
				html: ' . json_encode($this->flush()) . ',
			}';
		}
		$this->render();
		$js = 'var panel = new Ext.TabPanel( {
			renderTo: \'linkvalidator-modfuncreport\',
			id: \'linkvalidator-main\',
			plain: true,
			activeTab: 0,
			bodyStyle: \'padding:10px;\',
			items : [
			{
				autoHeight: true,
				title: TYPO3.l10n.localize(\'Report\'),
				html: ' . json_encode($this->flush(TRUE)) . '
			}' . $panelCheck . '
			]

		});
		';
		$this->pageRenderer->addExtOnReadyCode($js);
	}

	/**
	 * Initializes the menu array internally
	 *
	 * @return array Module menu
	 */
	public function modMenu() {
		$modMenu = array(
			'checkAllLink' => 0
		);
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] as $linkType => $value) {
				$modMenu[$linkType] = 1;
			}
		}
		return $modMenu;
	}

	/**
	 * Initializes the Module
	 *
	 * @return void
	 */
	protected function initialize() {
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] as $linkType => $classRef) {
				$this->hookObjectsArr[$linkType] = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
			}
		}
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('linkvalidator') . 'Resources/Private/Templates/mod_template.html');
		$this->relativePath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('linkvalidator');
		$this->pageRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($this->pObj->id, $GLOBALS['BE_USER']->getPagePermsClause(1));
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
	 * Updates the table of stored broken links
	 *
	 * @return void
	 */
	protected function updateBrokenLinks() {
		$searchFields = array();
		// Get the searchFields from TypoScript
		foreach ($this->modTS['searchFields.'] as $table => $fieldList) {
			$fields = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $fieldList);
			foreach ($fields as $field) {
				if (!$searchFields || !is_array($searchFields[$table]) || array_search($field, $searchFields[$table]) == FALSE) {
					$searchFields[$table][] = $field;
				}
			}
		}
		$rootLineHidden = $this->processor->getRootLineIsHidden($this->pObj->pageinfo);
		if (!$rootLineHidden || $this->modTS['checkhidden'] == 1) {
			// Get children pages
			$pageList = $this->processor->extGetTreeList($this->pObj->id, $this->searchLevel, 0, $GLOBALS['BE_USER']->getPagePermsClause(1), $this->modTS['checkhidden']);
			if ($this->pObj->pageinfo['hidden'] == 0 || $this->modTS['checkhidden'] == 1) {
				$pageList .= $this->pObj->id;
			}
			$this->processor->init($searchFields, $pageList);
			// Check if button press
			$update = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('updateLinkList');
			if (!empty($update)) {
				$this->processor->getLinkStatistics($this->checkOpt, $this->modTS['checkhidden']);
			}
		}
	}

	/**
	 * Renders the content of the module
	 *
	 * @return void
	 */
	protected function render() {
		if ($this->isAccessibleForCurrentUser) {
			$this->content = $this->renderBrokenLinksTable();
		} else {
			// If no access or if ID == zero
			/** @var \TYPO3\CMS\Core\Messaging\FlashMessage $message */
			$message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
				'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
				$GLOBALS['LANG']->getLL('no.access'),
				$GLOBALS['LANG']->getLL('no.access.title'),
				\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
			);
			$this->content .= $message->render();
		}
	}

	/**
	 * Flushes the rendered content to the browser
	 *
	 * @param boolean $form
	 * @return string $content
	 */
	protected function flush($form = FALSE) {
		$content = $this->doc->moduleBody(
			$this->pageRecord,
			$this->getDocHeaderButtons(),
			$form ? $this->getTemplateMarkers() : $this->getTemplateMarkersCheck()
		);
		return $content;
	}

	/**
	 * Builds the selector for the level of pages to search
	 *
	 * @return string Html code of that selector
	 */
	protected function getLevelSelector() {
		// Build level selector
		$opt = array();
		$parts = array(
			0 => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_0'),
			1 => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_1'),
			2 => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_2'),
			3 => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_3'),
			999 => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_infi')
		);
		foreach ($parts as $kv => $label) {
			$opt[] = '<option value="' . $kv . '"' . ($kv == intval($this->searchLevel) ? ' selected="selected"' : '') . '>' . htmlspecialchars($label) . '</option>';
		}
		$lMenu = '<select name="search_levels">' . implode('', $opt) . '</select>';
		return $lMenu;
	}

	/**
	 * Displays the table of broken links or a note if there were no broken links
	 *
	 * @return string Content of the table or of the note
	 */
	protected function renderBrokenLinksTable() {
		$items = ($brokenLinksMarker = array());
		$brokenLinkItems = '';
		$brokenLinksTemplate = \TYPO3\CMS\Core\Html\HtmlParser::getSubpart($this->doc->moduleTemplate, '###NOBROKENLINKS_CONTENT###');
		$keyOpt = array();
		if (is_array($this->checkOpt)) {
			$keyOpt = array_keys($this->checkOpt);
		}
		$rootLineHidden = $this->processor->getRootLineIsHidden($this->pObj->pageinfo);
		if (!$rootLineHidden || $this->modTS['checkhidden'] == 1) {
			$pageList = $this->processor->extGetTreeList(
				$this->pObj->id,
				$this->searchLevel,
				0,
				$GLOBALS['BE_USER']->getPagePermsClause(1),
				$this->modTS['checkhidden']
			);
			// Always add the current page, because we are just displaying the results
			$pageList .= $this->pObj->id;
			if (($res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				'tx_linkvalidator_link',
				'record_pid in (' . $pageList . ') and link_type in (\'' . implode('\',\'', $keyOpt) . '\')',
				'',
				'record_uid ASC, uid ASC')
			)) {
				// Display table with broken links
				if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
					$brokenLinksTemplate = \TYPO3\CMS\Core\Html\HtmlParser::getSubpart($this->doc->moduleTemplate, '###BROKENLINKS_CONTENT###');
					$brokenLinksItemTemplate = \TYPO3\CMS\Core\Html\HtmlParser::getSubpart($this->doc->moduleTemplate, '###BROKENLINKS_ITEM###');
					// Table header
					$brokenLinksMarker = $this->startTable();
					// Table rows containing the broken links
					while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) !== FALSE) {
						$items[] = $this->renderTableRow($row['table_name'], $row, $brokenLinksItemTemplate);
					}
					$brokenLinkItems = implode(chr(10), $items);
				} else {
					$brokenLinksMarker = $this->getNoBrokenLinkMessage($brokenLinksMarker);
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}
		} else {
			$brokenLinksMarker = $this->getNoBrokenLinkMessage($brokenLinksMarker);
		}
		$brokenLinksTemplate = \TYPO3\CMS\Core\Html\HtmlParser::substituteMarkerArray(
			$brokenLinksTemplate,
			$brokenLinksMarker, '###|###',
			TRUE
		);
		$content = \TYPO3\CMS\Core\Html\HtmlParser::substituteSubpart($brokenLinksTemplate, '###BROKENLINKS_ITEM', $brokenLinkItems);
		return $content;
	}

	/**
	 * Replace $brokenLinksMarker['NO_BROKEN_LINKS] with localized flashmessage
	 *
	 * @param array $brokenLinksMarker
	 * @return array $brokenLinksMarker['NO_BROKEN_LINKS] replaced with flashmessage
	 */
	protected function getNoBrokenLinkMessage(array $brokenLinksMarker) {
		$brokenLinksMarker['LIST_HEADER'] = $this->doc->sectionHeader($GLOBALS['LANG']->getLL('list.header'));
		/** @var $message \TYPO3\CMS\Core\Messaging\FlashMessage */
		$message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
			$GLOBALS['LANG']->getLL('list.no.broken.links'),
			$GLOBALS['LANG']->getLL('list.no.broken.links.title'),
			\TYPO3\CMS\Core\Messaging\FlashMessage::OK
		);
		$brokenLinksMarker['NO_BROKEN_LINKS'] = $message->render();
		return $brokenLinksMarker;
	}

	/**
	 * Displays the table header of the table with the broken links
	 *
	 * @return string Code of content
	 */
	protected function startTable() {
		// Listing head
		$makerTableHead = array();
		$makerTableHead['tablehead_path'] = $GLOBALS['LANG']->getLL('list.tableHead.path');
		$makerTableHead['tablehead_element'] = $GLOBALS['LANG']->getLL('list.tableHead.element');
		$makerTableHead['tablehead_headlink'] = $GLOBALS['LANG']->getLL('list.tableHead.headlink');
		$makerTableHead['tablehead_linktarget'] = $GLOBALS['LANG']->getLL('list.tableHead.linktarget');
		$makerTableHead['tablehead_linkmessage'] = $GLOBALS['LANG']->getLL('list.tableHead.linkmessage');
		$makerTableHead['tablehead_lastcheck'] = $GLOBALS['LANG']->getLL('list.tableHead.lastCheck');
		// Add CSH to the header of each column
		foreach ($makerTableHead as $column => $label) {
			$label = \TYPO3\CMS\Backend\Utility\BackendUtility::wrapInHelp('linkvalidator', $column, $label);
			$makerTableHead[$column] = $label;
		}
		// Add section header
		$makerTableHead['list_header'] = $this->doc->sectionHeader($GLOBALS['LANG']->getLL('list.header'));
		return $makerTableHead;
	}

	/**
	 * Displays one line of the broken links table
	 *
	 * @param string $table Name of database table
	 * @param array $row Record row to be processed
	 * @param array $brokenLinksItemTemplate Markup of the template to be used
	 * @return string HTML of the rendered row
	 */
	protected function renderTableRow($table, array $row, $brokenLinksItemTemplate) {
		$markerArray = array();
		$fieldName = '';
		// Restore the linktype object
		$hookObj = $this->hookObjectsArr[$row['link_type']];
		$brokenUrl = $hookObj->getBrokenUrl($row);
		// Construct link to edit the content element
		$params = '&edit[' . $table . '][' . $row['record_uid'] . ']=edit';
		$requestUri = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI') .
			'?id=' . $this->pObj->id .
			'&search_levels=' . $this->searchLevel;
		$actionLink = '<a href="#" onclick="';
		$actionLink .= \TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick(
			$params,
			$GLOBALS['BACK_PATH'],
			$requestUri
		);
		$actionLink .= '" title="' . $GLOBALS['LANG']->getLL('list.edit') . '">';
		$actionLink .= \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-open');
		$actionLink .= '</a>';
		$elementHeadline = $row['headline'];
		if (empty($elementHeadline)) {
			$elementHeadline = '<i>' . $GLOBALS['LANG']->getLL('list.no.headline') . '</i>';
		}
		// Get the language label for the field from TCA
		if ($GLOBALS['TCA'][$table]['columns'][$row['field']]['label']) {
			$fieldName = $GLOBALS['TCA'][$table]['columns'][$row['field']]['label'];
			$fieldName = $GLOBALS['LANG']->sL($fieldName);
			// Crop colon from end if present
			if (substr($fieldName, '-1', '1') === ':') {
				$fieldName = substr($fieldName, '0', strlen($fieldName) - 1);
			}
		}
		// Fallback, if there is no label
		$fieldName = !empty($fieldName) ? $fieldName : $row['field'];
		// column "Element"
		$element = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord(
			$table,
			$row,
			array('title' => $table . ':' . $row['record_uid'])
		);
		$element .= $elementHeadline;
		$element .= ' ' . sprintf($GLOBALS['LANG']->getLL('list.field'), $fieldName);
		$markerArray['actionlink'] = $actionLink;
		$markerArray['path'] = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordPath($row['record_pid'], '', 0, 0);
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
		return \TYPO3\CMS\Core\Html\HtmlParser::substituteMarkerArray($brokenLinksItemTemplate, $markerArray, '###|###', TRUE, TRUE);
	}

	/**
	 * Builds the checkboxes out of the hooks array
	 *
	 * @param array $brokenLinkOverView Array of broken links information
	 * @param string $prefix
	 * @return string code content
	 */
	protected function getCheckOptions(array $brokenLinkOverView, $prefix = '') {
		$markerArray = array();
		$additionalAttr = '';
		if (!empty($prefix)) {
			$additionalAttr = ' onclick="toggleActionButton(\'' . $prefix . '\');" class="' . $prefix . '" ';
		} else {
			$additionalAttr = ' onclick="toggleActionButton(\'refresh\');" class="refresh" ';
		}
		$checkOptionsTemplate = \TYPO3\CMS\Core\Html\HtmlParser::getSubpart($this->doc->moduleTemplate, '###CHECKOPTIONS_SECTION###');
		$hookSectionTemplate = \TYPO3\CMS\Core\Html\HtmlParser::getSubpart($checkOptionsTemplate, '###HOOK_SECTION###');
		$markerArray['statistics_header'] = $this->doc->sectionHeader($GLOBALS['LANG']->getLL('report.statistics.header'));
		$totalCountLabel = $GLOBALS['LANG']->getLL('overviews.nbtotal');
		$totalCountLabel = \TYPO3\CMS\Backend\Utility\BackendUtility::wrapInHelp('linkvalidator', 'checkboxes', $totalCountLabel);
		$markerArray['total_count_label'] = $totalCountLabel;
		if (empty($brokenLinkOverView['brokenlinkCount'])) {
			$markerArray['total_count'] = '0';
		} else {
			$markerArray['total_count'] = $brokenLinkOverView['brokenlinkCount'];
		}
		$linktypes = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->modTS['linktypes'], 1);
		$hookSectionContent = '';
		if (is_array($linktypes)) {
			if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])
				&& is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])) {
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
						$option = '<input type="checkbox" ' . $additionalAttr . ' id="' . $prefix . 'SET_' . $type . '" name="' . $prefix . 'SET[' . $type . ']" value="1"' . ($this->pObj->MOD_SETTINGS[$type] ? ' checked="checked"' : '') . '/>' . '<label for="' . $prefix . 'SET[' . $type . ']">' . htmlspecialchars($translation) . '</label>';
						$hookSectionMarker['option'] = $option;
						$hookSectionContent .= \TYPO3\CMS\Core\Html\HtmlParser::substituteMarkerArray(
							$hookSectionTemplate,
							$hookSectionMarker, '###|###',
							TRUE,
							TRUE
						);
					}
				}
			}
		}
		$checkOptionsTemplate = \TYPO3\CMS\Core\Html\HtmlParser::substituteSubpart(
			$checkOptionsTemplate,
			'###HOOK_SECTION###', $hookSectionContent
		);
		return \TYPO3\CMS\Core\Html\HtmlParser::substituteMarkerArray($checkOptionsTemplate, $markerArray, '###|###', TRUE, TRUE);
	}

	/**
	 * Loads data in the HTML head section (e.g. JavaScript or stylesheet information)
	 *
	 * @return void
	 */
	protected function loadHeaderData() {
		$this->doc->addStyleSheet('linkvalidator', $this->relativePath . 'Resources/Public/Css/linkvalidator.css', 'linkvalidator');
		$this->doc->getPageRenderer()->addJsFile($this->doc->backPath . '../t3lib/js/extjs/ux/Ext.ux.FitToParent.js');
	}

	/**
	 * Gets the buttons that shall be rendered in the docHeader
	 *
	 * @return array Available buttons for the docHeader
	 */
	protected function getDocHeaderButtons() {
		$buttons = array(
			'csh' => \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('_MOD_web_func', '', $GLOBALS['BACK_PATH']),
			'shortcut' => $this->getShortcutButton(),
			'save' => ''
		);
		return $buttons;
	}

	/**
	 * Gets the button to set a new shortcut in the backend (if current user is allowed to).
	 *
	 * @return string HTML representation of the shortcut button
	 */
	protected function getShortcutButton() {
		$result = '';
		if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
			$result = $this->doc->makeShortcutIcon('', 'function', $this->MCONF['name']);
		}
		return $result;
	}

	/**
	 * Gets the filled markers that are used in the HTML template
	 *
	 * @return array The filled marker array
	 */
	protected function getTemplateMarkers() {
		$markers = array(
			'FUNC_TITLE' => $GLOBALS['LANG']->getLL('report.func.title'),
			'CHECKOPTIONS_TITLE' => $GLOBALS['LANG']->getLL('report.statistics.header'),
			'FUNC_MENU' => $this->getLevelSelector(),
			'CONTENT' => $this->content,
			'CHECKALLLINK' => $this->checkAllHtml,
			'CHECKOPTIONS' => $this->checkOptHtml,
			'ID' => '<input type="hidden" name="id" value="' . $this->pObj->id . '" />',
			'REFRESH' => $this->refreshListHtml,
			'UPDATE' => ''
		);
		return $markers;
	}

	/**
	 * Gets the filled markers that are used in the HTML template
	 *
	 * @return array The filled marker array
	 */
	protected function getTemplateMarkersCheck() {
		$markers = array(
			'FUNC_TITLE' => $GLOBALS['LANG']->getLL('checklinks.func.title'),
			'CHECKOPTIONS_TITLE' => $GLOBALS['LANG']->getLL('checklinks.statistics.header'),
			'FUNC_MENU' => $this->getLevelSelector(),
			'CONTENT' => '',
			'CHECKALLLINK' => $this->checkAllHtml,
			'CHECKOPTIONS' => $this->checkOptHtmlCheck,
			'ID' => '<input type="hidden" name="id" value="' . $this->pObj->id . '" />',
			'REFRESH' => '',
			'UPDATE' => $this->updateListHtml
		);
		return $markers;
	}

	/**
	 * Determines whether the current user is an admin
	 *
	 * @return boolean Whether the current user is admin
	 */
	protected function isCurrentUserAdmin() {
		return (bool) $GLOBALS['BE_USER']->user['admin'];
	}

}

?>