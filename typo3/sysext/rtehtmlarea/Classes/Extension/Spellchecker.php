<?php
namespace TYPO3\CMS\Rtehtmlarea\Extension;

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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi;
use TYPO3\CMS\Rtehtmlarea\RteHtmlAreaBase;

/**
 * Spell Checker plugin for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
class Spellchecker extends RteHtmlAreaApi {

	/**
	 * The name of the plugin registered by the extension
	 *
	 * @var string
	 */
	protected $pluginName = 'SpellChecker';

	/**
	 * The comma-separated list of button names that the registered plugin is adding to the htmlArea RTE toolbar
	 *
	 * @var string
	 */
	protected $pluginButtons = 'spellcheck';

	/**
	 * The name-converting array, converting the button names used in the RTE PageTSConfing to the button id's used by the JS scripts
	 *
	 * @var array
	 */
	protected $convertToolbarForHtmlAreaArray = array(
		'spellcheck' => 'SpellCheck'
	);

	/**
	 * Spell checker modes
	 *
	 * @var array
	 */
	protected $spellCheckerModes = array('ultra', 'fast', 'normal', 'bad-spellers');

	/**
	 * Returns TRUE if the plugin is available and correctly initialized
	 *
	 * @param RteHtmlAreaBase $parentObject parent object
	 * @return bool TRUE if this plugin object should be made available in the current environment and is correctly initialized
	 */
	public function main($parentObject) {
		return parent::main($parentObject) && ExtensionManagementUtility::isLoaded('static_info_tables') && !in_array($this->htmlAreaRTE->language, GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->htmlAreaRTE->ID]['plugins'][$pluginName]['noSpellCheckLanguages'])) && ($this->htmlAreaRTE->contentCharset == 'iso-8859-1' || $this->htmlAreaRTE->contentCharset == 'utf-8');
	}

	/**
	 * Return JS configuration of the htmlArea plugins registered by the extension
	 *
	 * @param string $rteNumberPlaceholder A dummy string for JS arrays
	 * @return string JS configuration for registered plugins
	 */
	public function buildJavascriptConfiguration($rteNumberPlaceholder) {
		$button = 'spellcheck';
		// Set the SpellChecker mode
		$spellCheckerMode = isset($GLOBALS['BE_USER']->userTS['options.']['HTMLAreaPspellMode']) ? trim($GLOBALS['BE_USER']->userTS['options.']['HTMLAreaPspellMode']) : 'normal';
		if (!in_array($spellCheckerMode, $this->spellCheckerModes)) {
			$spellCheckerMode = 'normal';
		}
		// Set the use of personal dictionary
		$enablePersonalDicts = $this->thisConfig['buttons.'][$button . '.']['enablePersonalDictionaries'] ? (isset($GLOBALS['BE_USER']->userTS['options.']['enablePersonalDicts']) && $GLOBALS['BE_USER']->userTS['options.']['enablePersonalDicts'] ? TRUE : FALSE) : FALSE;
		if ($this->htmlAreaRTE->is_FE()) {
			$enablePersonalDicts = FALSE;
		}
		$registerRTEinJavascriptString = '';
		if (in_array($button, $this->toolbar)) {
			if (!is_array($this->thisConfig['buttons.']) || !is_array($this->thisConfig['buttons.'][($button . '.')])) {
				$registerRTEinJavascriptString .= '
			RTEarea[' . $rteNumberPlaceholder . '].buttons.' . $button . ' = new Object();';
			}
			$registerRTEinJavascriptString .= '
			RTEarea[' . $rteNumberPlaceholder . '].buttons.' . $button . '.contentTypo3Language = "' . $this->htmlAreaRTE->contentTypo3Language . '";
			RTEarea[' . $rteNumberPlaceholder . '].buttons.' . $button . '.contentISOLanguage = "' . $this->htmlAreaRTE->contentISOLanguage . '";
			RTEarea[' . $rteNumberPlaceholder . '].buttons.' . $button . '.contentCharset = "' . $this->htmlAreaRTE->contentCharset . '";
			RTEarea[' . $rteNumberPlaceholder . '].buttons.' . $button . '.spellCheckerMode = "' . $spellCheckerMode . '";
			RTEarea[' . $rteNumberPlaceholder . '].buttons.' . $button . '.enablePersonalDicts = ' . ($enablePersonalDicts ? 'true' : 'false') . ';';
			$registerRTEinJavascriptString .= '
			RTEarea[' . $rteNumberPlaceholder . '].buttons.' . $button . '.path = "' . ($this->htmlAreaRTE->is_FE() || $this->htmlAreaRTE->isFrontendEditActive() ? ($GLOBALS['TSFE']->absRefPrefix ? $GLOBALS['TSFE']->absRefPrefix : '') . 'index.php?eID=rtehtmlarea_spellchecker' : BackendUtility::getAjaxUrl('rtehtmlarea::spellchecker')) . '";';
		}
		return $registerRTEinJavascriptString;
	}

}
