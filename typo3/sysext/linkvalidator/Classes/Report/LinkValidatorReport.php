<?php
namespace TYPO3\CMS\Linkvalidator\Report;

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

use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\CMS\Linkvalidator\LinkAnalyzer;

/**
 * Module 'Linkvalidator' for the 'linkvalidator' extension
 *
 * @author Michael Miousse <michael.miousse@infoglobe.ca>
 * @author Jochen Rieger <j.rieger@connecta.ag>
 */
class LinkValidatorReport extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule {

	/**
	 * @var DocumentTemplate
	 */
	public $doc;

	/**
	 * Information about the current page record
	 *
	 * @var array
	 */
	protected $pageRecord = array();

	/**
	 * Information, if the module is accessible for the current user or not
	 *
	 * @var bool
	 */
	protected $isAccessibleForCurrentUser = FALSE;

	/**
	 * Depth for the recursive traversal of pages for the link validation
	 *
	 * @var int
	 */
	protected $searchLevel;

	/**
	 * Link validation class
	 *
	 * @var LinkAnalyzer
	 */
	protected $linkAnalyzer;

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
	 * Html for the statistics table with the checkboxes of the link types
	 * and the numbers of broken links for report tab
	 *
	 * @var string
	 */
	protected $checkOptionsHtml;

	/**
	 * Html for the statistics table with the checkboxes of the link types
	 * and the numbers of broken links for check links tab
	 *
	 * @var string
	 */
	protected $checkOptionsHtmlCheck;

	/**
	 * Complete content (html) to be displayed
	 *
	 * @var string
	 */
	protected $content;

	/**
	 * @var \TYPO3\CMS\Linkvalidator\Linktype\LinktypeInterface[]
	 */
	protected $hookObjectsArr = array();

	/**
	 * @var string
	 */
	protected $updateListHtml = '';

	/**
	 * @var string
	 */
	protected $refreshListHtml = '';

	/**
	 * Main method of modfuncreport
	 *
	 * @return string Module content
	 */
	public function main() {
		$this->getLanguageService()->includeLLFile('EXT:linkvalidator/Resources/Private/Language/Module/locallang.xlf');
		$this->searchLevel = GeneralUtility::_GP('search_levels');
		if (isset($this->pObj->id)) {
			$this->modTS = BackendUtility::getModTSconfig($this->pObj->id, 'mod.linkvalidator');
			$this->modTS = $this->modTS['properties'];
		}
		$update = GeneralUtility::_GP('updateLinkList');
		$prefix = '';
		if (!empty($update)) {
			$prefix = 'check';
		}
		$set = GeneralUtility::_GP($prefix . 'SET');
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
		$this->getBackendUser()->pushModuleData('web_info', $this->pObj->MOD_SETTINGS);
		$this->initialize();

		// Localization
		$this->doc->getPageRenderer()->addInlineLanguageLabelFile(
			ExtensionManagementUtility::extPath('linkvalidator', 'Resources/Private/Language/Module/locallang.xlf')
		);

		if ($this->modTS['showCheckLinkTab'] == 1) {
			$this->updateListHtml = '<input type="submit" name="updateLinkList" id="updateLinkList" value="' . $this->getLanguageService()->getLL('label_update') . '"/>';
		}
		$this->refreshListHtml = '<input type="submit" name="refreshLinkList" id="refreshLinkList" value="' . $this->getLanguageService()->getLL('label_refresh') . '"/>';
		$this->linkAnalyzer = GeneralUtility::makeInstance(LinkAnalyzer::class);
		$this->updateBrokenLinks();

		$brokenLinkOverView = $this->linkAnalyzer->getLinkCounts($this->pObj->id);
		$this->checkOptionsHtml = $this->getCheckOptions($brokenLinkOverView);
		$this->checkOptionsHtmlCheck = $this->getCheckOptions($brokenLinkOverView, 'check');
		$this->render();

		$pageTile = '';
		if ($this->pObj->id) {
			$pageRecord = BackendUtility::getRecord('pages', $this->pObj->id);
			$pageTile = '<h1>' . htmlspecialchars(BackendUtility::getRecordTitle('pages', $pageRecord)) . '</h1>';
		}

		return '<div id="linkvalidator-modfuncreport">' . $pageTile . $this->createTabs() . '</div>';
	}

	/**
	 * Create tabs to split the report and the checkLink functions
	 *
	 * @return string
	 */
	protected function createTabs() {
		$menuItems = array(
			0 => array(
				'label' => $this->getLanguageService()->getLL('Report'),
				'content' => $this->flush(TRUE)
			),
		);

		if ((bool)$this->modTS['showCheckLinkTab']) {
			$menuItems[1] = array(
				'label' => $this->getLanguageService()->getLL('CheckLink'),
				'content' => $this->flush()
			);
		}

		return $this->doc->getDynTabMenu($menuItems, 'ident');
	}

	/**
	 * Initializes the Module
	 *
	 * @return void
	 */
	protected function initialize() {
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] as $linkType => $classRef) {
				$this->hookObjectsArr[$linkType] = GeneralUtility::getUserObj($classRef);
			}
		}

		$this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('EXT:linkvalidator/Resources/Private/Templates/mod_template.html');

		$this->pageRecord = BackendUtility::readPageAccess($this->pObj->id, $this->getBackendUser()->getPagePermsClause(1));
		if ($this->pObj->id && is_array($this->pageRecord) || !$this->pObj->id && $this->isCurrentUserAdmin()) {
			$this->isAccessibleForCurrentUser = TRUE;
		}

		$this->doc->addStyleSheet('module', 'sysext/linkvalidator/Resources/Public/Styles/styles.css');
		$this->doc->getPageRenderer()->loadJquery();
		$this->doc->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Linkvalidator/Linkvalidator');

		// Don't access in workspace
		if ($this->getBackendUser()->workspace !== 0) {
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
			$fields = GeneralUtility::trimExplode(',', $fieldList, TRUE);
			foreach ($fields as $field) {
				if (!$searchFields || !is_array($searchFields[$table]) || array_search($field, $searchFields[$table]) === FALSE) {
					$searchFields[$table][] = $field;
				}
			}
		}
		$rootLineHidden = $this->linkAnalyzer->getRootLineIsHidden($this->pObj->pageinfo);
		if (!$rootLineHidden || $this->modTS['checkhidden'] == 1) {
			// Get children pages
			$pageList = $this->linkAnalyzer->extGetTreeList(
				$this->pObj->id,
				$this->searchLevel,
				0,
				$this->getBackendUser()->getPagePermsClause(1),
				$this->modTS['checkhidden']
			);
			if ($this->pObj->pageinfo['hidden'] == 0 || $this->modTS['checkhidden']) {
				$pageList .= $this->pObj->id;
			}

			$this->linkAnalyzer->init($searchFields, $pageList, $this->modTS);

			// Check if button press
			$update = GeneralUtility::_GP('updateLinkList');
			if (!empty($update)) {
				$this->linkAnalyzer->getLinkStatistics($this->checkOpt, $this->modTS['checkhidden']);
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
			/** @var FlashMessage $message */
			$message = GeneralUtility::makeInstance(
				FlashMessage::class,
				$this->getLanguageService()->getLL('no.access'),
				$this->getLanguageService()->getLL('no.access.title'),
				FlashMessage::ERROR
			);
			$this->content .= $message->render();
		}
	}

	/**
	 * Flushes the rendered content to the browser
	 *
	 * @param bool $form
	 * @return string $content
	 */
	protected function flush($form = FALSE) {
		return $this->doc->moduleBody(
			$this->pageRecord,
			$this->getDocHeaderButtons(),
			$form ? $this->getTemplateMarkers() : $this->getTemplateMarkersCheck()
		);
	}

	/**
	 * Builds the selector for the level of pages to search
	 *
	 * @return string Html code of that selector
	 */
	protected function getLevelSelector() {
		// Build level selector
		$options = array();
		$availableOptions = array(
			0 => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_0'),
			1 => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_1'),
			2 => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_2'),
			3 => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_3'),
			999 => $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_infi')
		);
		foreach ($availableOptions as $optionValue => $optionLabel) {
			$options[] = '<option value="' . $optionValue . '"' . ($optionValue === (int)$this->searchLevel ? ' selected="selected"' : '') . '>' . htmlspecialchars($optionLabel) . '</option>';
		}
		return '<select name="search_levels">' . implode('', $options) . '</select>';
	}

	/**
	 * Displays the table of broken links or a note if there were no broken links
	 *
	 * @return string Content of the table or of the note
	 */
	protected function renderBrokenLinksTable() {
		$brokenLinkItems = '';
		$brokenLinksTemplate = HtmlParser::getSubpart($this->doc->moduleTemplate, '###NOBROKENLINKS_CONTENT###');
		$keyOpt = array();
		if (is_array($this->checkOpt)) {
			$keyOpt = array_keys($this->checkOpt);
		}

		// Table header
		$brokenLinksMarker = $this->startTable();

		$rootLineHidden = $this->linkAnalyzer->getRootLineIsHidden($this->pObj->pageinfo);
		if (!$rootLineHidden || (bool)$this->modTS['checkhidden']) {
			$pageList = $this->linkAnalyzer->extGetTreeList(
				$this->pObj->id,
				$this->searchLevel,
				0,
				$this->getBackendUser()->getPagePermsClause(1),
				$this->modTS['checkhidden']
			);
			// Always add the current page, because we are just displaying the results
			$pageList .= $this->pObj->id;

			$records = $this->getDatabaseConnection()->exec_SELECTgetRows(
				'*',
				'tx_linkvalidator_link',
				'record_pid IN (' . $pageList . ') AND link_type IN (\'' . implode('\',\'', $keyOpt) . '\')',
				'',
				'record_uid ASC, uid ASC'
			);
			if (!empty($records)) {
				// Display table with broken links
				$brokenLinksTemplate = HtmlParser::getSubpart($this->doc->moduleTemplate, '###BROKENLINKS_CONTENT###');
				$brokenLinksItemTemplate = HtmlParser::getSubpart($this->doc->moduleTemplate, '###BROKENLINKS_ITEM###');

				// Table rows containing the broken links
				$items = array();
				foreach ($records as $record) {
					$items[] = $this->renderTableRow($record['table_name'], $record, $brokenLinksItemTemplate);
				}
				$brokenLinkItems = implode(LF, $items);
			} else {
				$brokenLinksMarker = $this->getNoBrokenLinkMessage($brokenLinksMarker);
			}
		} else {
			$brokenLinksMarker = $this->getNoBrokenLinkMessage($brokenLinksMarker);
		}
		$brokenLinksTemplate = HtmlParser::substituteMarkerArray(
			$brokenLinksTemplate,
			$brokenLinksMarker, '###|###',
			TRUE
		);
		return HtmlParser::substituteSubpart($brokenLinksTemplate, '###BROKENLINKS_ITEM', $brokenLinkItems);
	}

	/**
	 * Replace $brokenLinksMarker['NO_BROKEN_LINKS] with localized flashmessage
	 *
	 * @param array $brokenLinksMarker
	 * @return array $brokenLinksMarker['NO_BROKEN_LINKS] replaced with flashmessage
	 */
	protected function getNoBrokenLinkMessage(array $brokenLinksMarker) {
		$brokenLinksMarker['LIST_HEADER'] = $this->doc->sectionHeader($this->getLanguageService()->getLL('list.header'));
		/** @var $message FlashMessage */
		$message = GeneralUtility::makeInstance(
			FlashMessage::class,
			$this->getLanguageService()->getLL('list.no.broken.links'),
			$this->getLanguageService()->getLL('list.no.broken.links.title'),
			FlashMessage::OK
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
		$makerTableHead = array(
			'tablehead_path' => $this->getLanguageService()->getLL('list.tableHead.path'),
			'tablehead_element' => $this->getLanguageService()->getLL('list.tableHead.element'),
			'tablehead_headlink' => $this->getLanguageService()->getLL('list.tableHead.headlink'),
			'tablehead_linktarget' => $this->getLanguageService()->getLL('list.tableHead.linktarget'),
			'tablehead_linkmessage' => $this->getLanguageService()->getLL('list.tableHead.linkmessage'),
			'tablehead_lastcheck' => $this->getLanguageService()->getLL('list.tableHead.lastCheck'),
		);

		// Add CSH to the header of each column
		foreach ($makerTableHead as $column => $label) {
			$makerTableHead[$column] = BackendUtility::wrapInHelp('linkvalidator', $column, $label);
		}
		// Add section header
		$makerTableHead['list_header'] = $this->doc->sectionHeader($this->getLanguageService()->getLL('list.header'));
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

		// Construct link to edit the content element
		$requestUri = GeneralUtility::getIndpEnv('REQUEST_URI') .
			'?id=' . $this->pObj->id .
			'&search_levels=' . $this->searchLevel;
		$actionLink = '<a href="#" onclick="';
		$actionLink .= BackendUtility::editOnClick(
			'&edit[' . $table . '][' . $row['record_uid'] . ']=edit',
			$GLOBALS['BACK_PATH'],
			$requestUri
		);
		$actionLink .= '" title="' . $this->getLanguageService()->getLL('list.edit') . '">';
		$actionLink .= IconUtility::getSpriteIcon('actions-document-open');
		$actionLink .= '</a>';
		$elementHeadline = $row['headline'];
		if (empty($elementHeadline)) {
			$elementHeadline = '<i>' . $this->getLanguageService()->getLL('list.no.headline') . '</i>';
		}
		// Get the language label for the field from TCA
		if ($GLOBALS['TCA'][$table]['columns'][$row['field']]['label']) {
			$fieldName = $this->getLanguageService()->sL($GLOBALS['TCA'][$table]['columns'][$row['field']]['label']);
			// Crop colon from end if present
			if (substr($fieldName, '-1', '1') === ':') {
				$fieldName = substr($fieldName, '0', strlen($fieldName) - 1);
			}
		}
		// Fallback, if there is no label
		$fieldName = !empty($fieldName) ? $fieldName : $row['field'];
		// column "Element"
		$element = IconUtility::getSpriteIconForRecord(
			$table,
			$row,
			array('title' => $table . ':' . $row['record_uid'])
		);
		$element .= $elementHeadline;
		$element .= ' ' . sprintf($this->getLanguageService()->getLL('list.field'), $fieldName);
		$markerArray['actionlink'] = $actionLink;
		$markerArray['path'] = BackendUtility::getRecordPath($row['record_pid'], '', 0, 0);
		$markerArray['element'] = $element;
		$markerArray['headlink'] = $row['link_title'];
		$markerArray['linktarget'] = $hookObj->getBrokenUrl($row);
		$response = unserialize($row['url_response']);
		if ($response['valid']) {
			$linkMessage = '<span class="valid">' . $this->getLanguageService()->getLL('list.msg.ok') . '</span>';
		} else {
			$linkMessage = '<span class="error">' . $hookObj->getErrorMessage($response['errorParams']) . '</span>';
		}
		$markerArray['linkmessage'] = $linkMessage;

		$lastRunDate = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], $row['last_check']);
		$lastRunTime = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'], $row['last_check']);
		$markerArray['lastcheck'] = sprintf($this->getLanguageService()->getLL('list.msg.lastRun'), $lastRunDate, $lastRunTime);

		// Return the table html code as string
		return HtmlParser::substituteMarkerArray($brokenLinksItemTemplate, $markerArray, '###|###', TRUE, TRUE);
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
		if (!empty($prefix)) {
			$additionalAttr = ' class="' . $prefix . '"';
		} else {
			$additionalAttr = ' class="refresh"';
		}
		$checkOptionsTemplate = HtmlParser::getSubpart($this->doc->moduleTemplate, '###CHECKOPTIONS_SECTION###');
		$hookSectionTemplate = HtmlParser::getSubpart($checkOptionsTemplate, '###HOOK_SECTION###');
		$markerArray['statistics_header'] = $this->doc->sectionHeader($this->getLanguageService()->getLL('report.statistics.header'));
		$markerArray['total_count_label'] = BackendUtility::wrapInHelp('linkvalidator', 'checkboxes', $this->getLanguageService()->getLL('overviews.nbtotal'));
		$markerArray['total_count'] = $brokenLinkOverView['brokenlinkCount'] ?: '0';

		$linktypes = GeneralUtility::trimExplode(',', $this->modTS['linktypes'], TRUE);
		$hookSectionContent = '';
		if (is_array($linktypes)) {
			if (
				!empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])
				&& is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])
			) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] as $type => $value) {
					if (in_array($type, $linktypes)) {
						$hookSectionMarker = array(
							'count' => $brokenLinkOverView[$type] ?: '0',
						);

						$translation = $this->getLanguageService()->getLL('hooks.' . $type) ?: $type;
						$hookSectionMarker['option'] = '<input type="checkbox"' . $additionalAttr . ' id="' . $prefix . 'SET_' . $type . '" name="' . $prefix
							. 'SET[' . $type . ']" value="1"' . ($this->pObj->MOD_SETTINGS[$type] ? ' checked="checked"' : '') . '/>' . '<label for="'
							. $prefix . 'SET[' . $type . ']">' . htmlspecialchars($translation) . '</label>';

						$hookSectionContent .= HtmlParser::substituteMarkerArray(
							$hookSectionTemplate,
							$hookSectionMarker, '###|###',
							TRUE,
							TRUE
						);
					}
				}
			}
		}
		$checkOptionsTemplate = HtmlParser::substituteSubpart(
			$checkOptionsTemplate,
			'###HOOK_SECTION###',
			$hookSectionContent
		);
		return HtmlParser::substituteMarkerArray($checkOptionsTemplate, $markerArray, '###|###', TRUE, TRUE);
	}

	/**
	 * Gets the buttons that shall be rendered in the docHeader
	 *
	 * @return array Available buttons for the docHeader
	 */
	protected function getDocHeaderButtons() {
		return array(
			'csh' => BackendUtility::cshItem('_MOD_web_func', ''),
			'shortcut' => $this->getShortcutButton(),
			'save' => ''
		);
	}

	/**
	 * Gets the button to set a new shortcut in the backend (if current user is allowed to).
	 *
	 * @return string HTML representation of the shortcut button
	 */
	protected function getShortcutButton() {
		$result = '';
		if ($this->getBackendUser()->mayMakeShortcut()) {
			$result = $this->doc->makeShortcutIcon('', 'function', $this->pObj->MCONF['name']);
		}
		return $result;
	}

	/**
	 * Gets the filled markers that are used in the HTML template
	 *
	 * @return array The filled marker array
	 */
	protected function getTemplateMarkers() {
		return array(
			'FUNC_TITLE' => $this->getLanguageService()->getLL('report.func.title'),
			'CHECKOPTIONS_TITLE' => $this->getLanguageService()->getLL('report.statistics.header'),
			'FUNC_MENU' => $this->getLevelSelector(),
			'CONTENT' => $this->content,
			'CHECKOPTIONS' => $this->checkOptionsHtml,
			'ID' => '<input type="hidden" name="id" value="' . $this->pObj->id . '" />',
			'REFRESH' => '<input type="submit" name="refreshLinkList" id="refreshLinkList" value="' . $this->getLanguageService()->getLL('label_refresh') . '" />',
			'UPDATE' => '',
		);
	}

	/**
	 * Gets the filled markers that are used in the HTML template
	 *
	 * @return array The filled marker array
	 */
	protected function getTemplateMarkersCheck() {
		return array(
			'FUNC_TITLE' => $this->getLanguageService()->getLL('checklinks.func.title'),
			'CHECKOPTIONS_TITLE' => $this->getLanguageService()->getLL('checklinks.statistics.header'),
			'FUNC_MENU' => $this->getLevelSelector(),
			'CONTENT' => '',
			'CHECKOPTIONS' => $this->checkOptionsHtmlCheck,
			'ID' => '<input type="hidden" name="id" value="' . $this->pObj->id . '" />',
			'REFRESH' => '',
			'UPDATE' => '<input type="submit" name="updateLinkList" id="updateLinkList" value="' . $this->getLanguageService()->getLL('label_update') . '"/>',
		);
	}

	/**
	 * Determines whether the current user is an admin
	 *
	 * @return bool Whether the current user is admin
	 */
	protected function isCurrentUserAdmin() {
		return $this->getBackendUser()->isAdmin();
	}

	/**
	 * Returns LanguageService
	 *
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * Returns the current BE user.
	 *
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

}
