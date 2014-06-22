<?php
namespace TYPO3\CMS\Rtehtmlarea\Extension;

/**
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

/**
 * Spell Checker plugin for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
class Spellchecker extends \TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi {

	protected $extensionKey = 'rtehtmlarea';

	// The key of the extension that is extending htmlArea RTE
	protected $pluginName = 'SpellChecker';

	// The name of the plugin registered by the extension
	protected $relativePathToLocallangFile = '';

	// Path to this main locallang file of the extension relative to the extension dir.
	protected $relativePathToSkin = 'extensions/SpellChecker/skin/htmlarea.css';

	// Path to the skin (css) file relative to the extension dir.
	protected $htmlAreaRTE;

	// Reference to the invoking object
	protected $thisConfig;

	// Reference to RTE PageTSConfig
	protected $toolbar;

	// Reference to RTE toolbar array
	protected $LOCAL_LANG;

	// Frontend language array
	protected $pluginButtons = 'spellcheck';

	protected $convertToolbarForHtmlAreaArray = array(
		'spellcheck' => 'SpellCheck'
	);

	protected $spellCheckerModes = array('ultra', 'fast', 'normal', 'bad-spellers');

	public function main($parentObject) {
		return parent::main($parentObject) && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('static_info_tables') && !in_array($this->htmlAreaRTE->language, \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->htmlAreaRTE->ID]['plugins'][$pluginName]['noSpellCheckLanguages'])) && ($this->htmlAreaRTE->contentCharset == 'iso-8859-1' || $this->htmlAreaRTE->contentCharset == 'utf-8');
	}

	/**
	 * Return JS configuration of the htmlArea plugins registered by the extension
	 *
	 * @param 	integer		Relative id of the RTE editing area in the form
	 * @return string		JS configuration for registered plugins
	 */
	public function buildJavascriptConfiguration($RTEcounter) {
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
			RTEarea[' . $RTEcounter . '].buttons.' . $button . ' = new Object();';
			}
			$registerRTEinJavascriptString .= '
			RTEarea[' . $RTEcounter . '].buttons.' . $button . '.contentTypo3Language = "' . $this->htmlAreaRTE->contentTypo3Language . '";
			RTEarea[' . $RTEcounter . '].buttons.' . $button . '.contentISOLanguage = "' . $this->htmlAreaRTE->contentISOLanguage . '";
			RTEarea[' . $RTEcounter . '].buttons.' . $button . '.contentCharset = "' . $this->htmlAreaRTE->contentCharset . '";
			RTEarea[' . $RTEcounter . '].buttons.' . $button . '.spellCheckerMode = "' . $spellCheckerMode . '";
			RTEarea[' . $RTEcounter . '].buttons.' . $button . '.enablePersonalDicts = ' . ($enablePersonalDicts ? 'true' : 'false') . ';';
			$registerRTEinJavascriptString .= '
			RTEarea[' . $RTEcounter . '].buttons.' . $button . '.path = "' . ($this->htmlAreaRTE->is_FE() || $this->htmlAreaRTE->isFrontendEditActive() ? ($GLOBALS['TSFE']->absRefPrefix ? $GLOBALS['TSFE']->absRefPrefix : '') . 'index.php?eID=rtehtmlarea_spellchecker' : $this->htmlAreaRTE->backPath . BackendUtility::getAjaxUrl('rtehtmlarea::spellchecker')) . '";';
		}
		return $registerRTEinJavascriptString;
	}

}
