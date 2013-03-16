<?php
namespace TYPO3\CMS\Rtehtmlarea\Extension;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Stanislas Rolland <typo3(arobas)sjbr.ca>
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
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
 * Language plugin for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
class Language extends \TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi {

	protected $extensionKey = 'rtehtmlarea';

	// The key of the extension that is extending htmlArea RTE
	protected $pluginName = 'Language';

	// The name of the plugin registered by the extension
	protected $relativePathToLocallangFile = 'extensions/Language/locallang.xml';

	// Path to this main locallang file of the extension relative to the extension dir.
	protected $relativePathToSkin = 'extensions/Language/skin/htmlarea.css';

	// Path to the skin (css) file relative to the extension dir.
	protected $htmlAreaRTE;

	// Reference to the invoking object
	protected $thisConfig;

	// Reference to RTE PageTSConfig
	protected $toolbar;

	// Reference to RTE toolbar array
	protected $LOCAL_LANG;

	// Frontend language array
	protected $pluginButtons = 'lefttoright,righttoleft,language,showlanguagemarks';

	protected $convertToolbarForHtmlAreaArray = array(
		'lefttoright' => 'LeftToRight',
		'righttoleft' => 'RightToLeft',
		'language' => 'Language',
		'showlanguagemarks' => 'ShowLanguageMarks'
	);

	public function main($parentObject) {
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('static_info_tables') && file_exists(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('static_info_tables') . 'class.tx_staticinfotables_div.php')) {
			require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('static_info_tables') . 'class.tx_staticinfotables_div.php';
		} else {
			$this->pluginButtons = \TYPO3\CMS\Core\Utility\GeneralUtility::rmFromList('language', $this->pluginButtons);
		}
		return parent::main($parentObject);
	}

	/**
	 * Return JS configuration of the htmlArea plugins registered by the extension
	 *
	 * @param 	integer		Relative id of the RTE editing area in the form
	 * @return string		JS configuration for registered plugins
	 */
	public function buildJavascriptConfiguration($RTEcounter) {
		$button = 'language';
		$registerRTEinJavascriptString = '';
		if (!is_array($this->thisConfig['buttons.']) || !is_array($this->thisConfig['buttons.'][($button . '.')])) {
			$registerRTEinJavascriptString .= '
			RTEarea[' . $RTEcounter . '].buttons.' . $button . ' = new Object();';
		}
		if ($this->htmlAreaRTE->is_FE()) {
			$first = $GLOBALS['TSFE']->getLLL('No language mark', $this->LOCAL_LANG);
		} else {
			$first = $GLOBALS['LANG']->getLL('No language mark');
		}
		$languages = array('none' => $first);
		$languages = array_flip(array_merge($languages, $this->getLanguages()));
		$languagesJSArray = array();
		foreach ($languages as $key => $value) {
			$languagesJSArray[] = array('text' => $key, 'value' => $value);
		}
		$languagesJSArray = json_encode(array('options' => $languagesJSArray));
		$registerRTEinJavascriptString .= '
			RTEarea[' . $RTEcounter . '].buttons.' . $button . '.dataUrl = "' . ($this->htmlAreaRTE->is_FE() && $GLOBALS['TSFE']->absRefPrefix ? $GLOBALS['TSFE']->absRefPrefix : '') . $this->htmlAreaRTE->writeTemporaryFile('', ($button . '_' . $this->htmlAreaRTE->contentLanguageUid), 'js', $languagesJSArray) . '";';
		return $registerRTEinJavascriptString;
	}

	/**
	 * Getting all languages into an array
	 * where the key is the ISO alpha-2 code of the language
	 * and where the value are the name of the language in the current language
	 * Note: we exclude sacred and constructed languages
	 *
	 * @return 	array		An array of names of languages
	 * @todo Define visibility
	 */
	public function getLanguages() {
		$nameArray = array();
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('static_info_tables')) {
			$where = '1=1';
			$table = 'static_languages';
			$lang = \tx_staticinfotables_div::getCurrentLanguage();
			$titleFields = \tx_staticinfotables_div::getTCAlabelField($table, TRUE, $lang);
			$prefixedTitleFields = array();
			foreach ($titleFields as $titleField) {
				$prefixedTitleFields[] = $table . '.' . $titleField;
			}
			$labelFields = implode(',', $prefixedTitleFields);
			// Restrict to certain languages
			if (is_array($this->thisConfig['buttons.']) && is_array($this->thisConfig['buttons.']['language.']) && isset($this->thisConfig['buttons.']['language.']['restrictToItems'])) {
				$languageList = implode('\',\'', \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_DB']->fullQuoteStr(strtoupper($this->thisConfig['buttons.']['language.']['restrictToItems']), $table)));
				$where .= ' AND ' . $table . '.lg_iso_2 IN (' . $languageList . ')';
			}
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($table . '.lg_iso_2,' . $table . '.lg_country_iso_2,' . $labelFields, $table, $where . ' AND lg_constructed = 0 ' . ($this->htmlAreaRTE->is_FE() ? $GLOBALS['TSFE']->sys_page->enableFields($table) : \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($table) . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table)));
			$prefixLabelWithCode = !$this->thisConfig['buttons.']['language.']['prefixLabelWithCode'] ? FALSE : TRUE;
			$postfixLabelWithCode = !$this->thisConfig['buttons.']['language.']['postfixLabelWithCode'] ? FALSE : TRUE;
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$code = strtolower($row['lg_iso_2']) . ($row['lg_country_iso_2'] ? '-' . strtoupper($row['lg_country_iso_2']) : '');
				foreach ($titleFields as $titleField) {
					if ($row[$titleField]) {
						$nameArray[$code] = $prefixLabelWithCode ? $code . ' - ' . $row[$titleField] : ($postfixLabelWithCode ? $row[$titleField] . ' - ' . $code : $row[$titleField]);
						break;
					}
				}
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			uasort($nameArray, 'strcoll');
		}
		return $nameArray;
	}

	/**
	 * Return an updated array of toolbar enabled buttons
	 *
	 * @param 	array		$show: array of toolbar elements that will be enabled, unless modified here
	 * @return 	array		toolbar button array, possibly updated
	 */
	public function applyToolbarConstraints($show) {
		if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('static_info_tables')) {
			return array_diff($show, array('language'));
		} else {
			return $show;
		}
	}

}


?>