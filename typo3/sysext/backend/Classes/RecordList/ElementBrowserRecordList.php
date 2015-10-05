<?php
namespace TYPO3\CMS\Backend\RecordList;

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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\Browser\ElementBrowser;
use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;

/**
 * Displays the page/file tree for browsing database records or files.
 * Used from TCEFORMS an other elements
 * In other words: This is the ELEMENT BROWSER!
 */
class ElementBrowserRecordList extends DatabaseRecordList {

	/**
	 * Table name of the field pointing to this element browser
	 *
	 * @var string
	 */
	protected $relatingTable;

	/**
	 * Field name of the field pointing to this element browser
	 *
	 * @var string
	 */
	protected $relatingField;

	/**
	 * Back-reference to ElementBrowser class
	 *
	 * @var ElementBrowser
	 */
	protected $elementBrowser;

	/**
	 * Initializes the script path
	 */
	public function __construct() {
		parent::__construct();
		$this->determineScriptUrl();
	}

	/**
	 * @param ElementBrowser $elementBrowser
	 * @return void
	 */
	public function setElementBrowser(ElementBrowser $elementBrowser) {
		$this->elementBrowser = $elementBrowser;
	}

	/**
	 * Creates the URL for links
	 *
	 * @param mixed $altId If not blank string, this is used instead of $this->id as the id value.
	 * @param string $table If this is "-1" then $this->table is used, otherwise the value of the input variable.
	 * @param string $exclList Commalist of fields NOT to pass as parameters (currently "sortField" and "sortRev")
	 * @return string Query-string for URL
	 */
	public function listURL($altId = '', $table = '-1', $exclList = '') {
		return $this->getThisScript() . 'id=' . ($altId !== '' ? $altId : $this->id)
			. '&table=' . rawurlencode((int)$table === -1 ? $this->table : $table)
			. ($this->thumbs ? '&imagemode=' . $this->thumbs : '')
			. ($this->searchString ? '&search_field=' . rawurlencode($this->searchString) : '')
			. ($this->searchLevels ? '&search_levels=' . rawurlencode($this->searchLevels) : '')
			. ((!$exclList || !GeneralUtility::inList($exclList, 'sortField')) && $this->sortField ? '&sortField=' . rawurlencode($this->sortField) : '')
			. ((!$exclList || !GeneralUtility::inList($exclList, 'sortRev')) && $this->sortRev ? '&sortRev=' . rawurlencode($this->sortRev) : '')
			. $this->ext_addP();
	}

	/**
	 * Returns additional, local GET parameters to include in the links of the record list.
	 *
	 * @return string
	 */
	public function ext_addP() {
		return '&act=' . $this->elementBrowser->act . '&mode=' . $this->elementBrowser->mode . '&expandPage=' . $this->elementBrowser->expandPage . '&bparams=' . rawurlencode($this->elementBrowser->bparams);
	}

	/**
	 * Returns the title (based on $code) of a record (from table $table) with the proper link around (that is for "pages"-records a link to the level of that record...)
	 *
	 * @param string $table Table name
	 * @param int $uid UID (not used here)
	 * @param string $code Title string
	 * @param array $row Records array (from table name)
	 * @return string
	 */
	public function linkWrapItems($table, $uid, $code, $row) {
		if (!$code) {
			$code = '<i>[' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.no_title', TRUE) . ']</i>';
		} else {
			$code = BackendUtility::getRecordTitlePrep($code, $this->fixedL);
		}
		$title = BackendUtility::getRecordTitle($table, $row, FALSE, TRUE);
		$ficon = $this->iconFactory->getIconForRecord($table, $row, Icon::SIZE_SMALL)->render();
		$aOnClick = 'return insertElement(' . GeneralUtility::quoteJSvalue($table) . ', ' . GeneralUtility::quoteJSvalue($row['uid']) . ', \'db\', ' . GeneralUtility::quoteJSvalue($title) . ', \'\', \'\', ' . GeneralUtility::quoteJSvalue($ficon) . ');';
		$ATag = '<a href="#" onclick="' . $aOnClick . '" title="' . $this->getLanguageService()->getLL('addToList', TRUE) . '">';
		$ATag_alt = substr($ATag, 0, -4) . ',\'\',1);">';
		$ATag_e = '</a>';
		return $ATag . $this->iconFactory->getIcon('actions-edit-add', Icon::SIZE_SMALL)->render() . $ATag_e . $ATag_alt . $code . $ATag_e;
	}

	/**
	 * Check if all row listing conditions are fulfilled.
	 *
	 * @param string $table String Table name
	 * @param array $row Array Record
	 * @return bool True, if all conditions are fulfilled.
	 */
	protected function isRowListingConditionFulfilled($table, $row) {
		$returnValue = TRUE;
		if ($this->relatingField && $this->relatingTable) {
			$tcaFieldConfig = $GLOBALS['TCA'][$this->relatingTable]['columns'][$this->relatingField]['config'];
			if (is_array($tcaFieldConfig['filter'])) {
				foreach ($tcaFieldConfig['filter'] as $filter) {
					if (!$filter['userFunc']) {
						continue;
					}
					$parameters = $filter['parameters'] ?: array();
					$parameters['values'] = array($table . '_' . $row['uid']);
					$parameters['tcaFieldConfig'] = $tcaFieldConfig;
					$valueArray = GeneralUtility::callUserFunction($filter['userFunc'], $parameters, $this);
					if (empty($valueArray)) {
						$returnValue = FALSE;
					}
				}
			}
		}
		return $returnValue;
	}

	/**
	 * Set which pointing field (in the TCEForm) we are currently rendering the element browser for
	 *
	 * @param string $tableName Table name
	 * @param string $fieldName Field name
	 */
	public function setRelatingTableAndField($tableName, $fieldName) {
		// Check validity of the input data and load TCA
		if (isset($GLOBALS['TCA'][$tableName])) {
			$this->relatingTable = $tableName;
			if ($fieldName && isset($GLOBALS['TCA'][$tableName]['columns'][$fieldName])) {
				$this->relatingField = $fieldName;
			}
		}
	}

	/**
	 * Local version that sets allFields to TRUE to support userFieldSelect
	 *
	 * @return void
	 * @see fieldSelectBox
	 */
	public function generateList() {
		$this->allFields = TRUE;
		parent::generateList();
	}

}
