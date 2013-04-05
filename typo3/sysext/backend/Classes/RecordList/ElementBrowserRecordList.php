<?php
namespace TYPO3\CMS\Backend\RecordList;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Displays the page/file tree for browsing database records or files.
 * Used from TCEFORMS an other elements
 * In other words: This is the ELEMENT BROWSER!
 *
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * Local version of the record list.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class ElementBrowserRecordList extends \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList {

	/**
	 * @todo Define visibility
	 */
	public $thisScript = 'browse_links.php';

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
	 * Initializes the script path
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function __construct() {
		parent::__construct();
		$this->thisScript = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('SCRIPT_NAME');
	}

	/**
	 * Creates the URL for links
	 *
	 * @param mixed $altId If not blank string, this is used instead of $this->id as the id value.
	 * @param string $table If this is "-1" then $this->table is used, otherwise the value of the input variable.
	 * @param string $exclList Commalist of fields NOT to pass as parameters (currently "sortField" and "sortRev")
	 * @return string Query-string for URL
	 * @todo Define visibility
	 */
	public function listURL($altId = '', $table = -1, $exclList = '') {
		return $this->thisScript . '?id=' . (strcmp($altId, '') ? $altId : $this->id) . '&table=' . rawurlencode(($table == -1 ? $this->table : $table)) . ($this->thumbs ? '&imagemode=' . $this->thumbs : '') . ($this->searchString ? '&search_field=' . rawurlencode($this->searchString) : '') . ($this->searchLevels ? '&search_levels=' . rawurlencode($this->searchLevels) : '') . ((!$exclList || !\TYPO3\CMS\Core\Utility\GeneralUtility::inList($exclList, 'sortField')) && $this->sortField ? '&sortField=' . rawurlencode($this->sortField) : '') . ((!$exclList || !\TYPO3\CMS\Core\Utility\GeneralUtility::inList($exclList, 'sortRev')) && $this->sortRev ? '&sortRev=' . rawurlencode($this->sortRev) : '') . $this->ext_addP();
	}

	/**
	 * Returns additional, local GET parameters to include in the links of the record list.
	 *
	 * @return string
	 * @todo Define visibility
	 */
	public function ext_addP() {
		$str = '&act=' . $GLOBALS['SOBE']->browser->act . '&mode=' . $GLOBALS['SOBE']->browser->mode . '&expandPage=' . $GLOBALS['SOBE']->browser->expandPage . '&bparams=' . rawurlencode($GLOBALS['SOBE']->browser->bparams);
		return $str;
	}

	/**
	 * Returns the title (based on $code) of a record (from table $table) with the proper link around (that is for "pages"-records a link to the level of that record...)
	 *
	 * @param string $table Table name
	 * @param integer $uid UID (not used here)
	 * @param string $code Title string
	 * @param array $row Records array (from table name)
	 * @return string
	 * @todo Define visibility
	 */
	public function linkWrapItems($table, $uid, $code, $row) {
		if (!$code) {
			$code = '<i>[' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.no_title', 1) . ']</i>';
		} else {
			$code = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitlePrep($code, $this->fixedL);
		}
		$title = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle($table, $row, FALSE, TRUE);
		$ficon = \TYPO3\CMS\Backend\Utility\IconUtility::getIcon($table, $row);
		$aOnClick = 'return insertElement(\'' . $table . '\', \'' . $row['uid'] . '\', \'db\', ' . \TYPO3\CMS\Core\Utility\GeneralUtility::quoteJSvalue($title) . ', \'\', \'\', \'' . $ficon . '\');';
		$ATag = '<a href="#" onclick="' . $aOnClick . '">';
		$ATag_alt = substr($ATag, 0, -4) . ',\'\',1);">';
		$ATag_e = '</a>';
		return $ATag . '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/plusbullet2.gif', 'width="18" height="16"') . ' title="' . $GLOBALS['LANG']->getLL('addToList', 1) . '" alt="" />' . $ATag_e . $ATag_alt . $code . $ATag_e;
	}

	/**
	 * Check if all row listing conditions are fulfilled.
	 *
	 * @param string $table String Table name
	 * @param array $row Array Record
	 * @return boolean True, if all conditions are fulfilled.
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
					$parameters = $filter['parameters'] ? $filter['parameters'] : array();
					$parameters['values'] = array($table . '_' . $row['uid']);
					$parameters['tcaFieldConfig'] = $tcaFieldConfig;
					$valueArray = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($filter['userFunc'], $parameters, $this);
					if (count($valueArray) === 0) {
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
		global $TCA;
		// Check validity of the input data and load TCA
		if (isset($TCA[$tableName])) {
			$this->relatingTable = $tableName;
			if ($fieldName && isset($TCA[$tableName]['columns'][$fieldName])) {
				$this->relatingField = $fieldName;
			}
		}
	}

	/**
	 * Local version that sets allFields to TRUE to support userFieldSelect
	 *
	 * @return void
	 * @see fieldSelectBox
	 * @todo Define visibility
	 */
	public function generateList() {
		$this->allFields = TRUE;
		parent::generateList();
	}

}


?>