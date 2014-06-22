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
/**
 * Context Menu plugin for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
class ContextMenu extends \TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi {

	protected $extensionKey = 'rtehtmlarea';

	// The key of the extension that is extending htmlArea RTE
	protected $pluginName = 'ContextMenu';

	// The name of the plugin registered by the extension
	protected $relativePathToLocallangFile = '';

	// Path to this main locallang file of the extension relative to the extension dir.
	protected $relativePathToSkin = '';

	// Path to the skin (css) file relative to the extension dir.
	protected $htmlAreaRTE;

	// Reference to the invoking object
	protected $thisConfig;

	// Reference to RTE PageTSConfig
	protected $toolbar;

	// Reference to RTE toolbar array
	protected $LOCAL_LANG;

	// Frontend language array
	protected $pluginButtons;

	protected $convertToolbarForHtmlAreaArray = array();

	public function main($parentObject) {
		$enabled = parent::main($parentObject) && !($this->htmlAreaRTE->client['browser'] == 'opera' || $this->thisConfig['contextMenu.']['disabled']);
		return $enabled;
	}

	/**
	 * Return JS configuration of the htmlArea plugins registered by the extension
	 *
	 * @param 	integer		Relative id of the RTE editing area in the form
	 * @return string		JS configuration for registered plugins
	 */
	public function buildJavascriptConfiguration($editorId) {
		$registerRTEinJavascriptString = '';
		if (is_array($this->thisConfig['contextMenu.'])) {
			$registerRTEinJavascriptString .= '
	RTEarea[' . $editorId . '].contextMenu =  ' . $this->htmlAreaRTE->buildNestedJSArray($this->thisConfig['contextMenu.']) . ';';
			if ($this->thisConfig['contextMenu.']['showButtons']) {
				$registerRTEinJavascriptString .= '
	RTEarea[' . $editorId . '].contextMenu.showButtons = ' . json_encode(\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->htmlAreaRTE->cleanList(\TYPO3\CMS\Core\Utility\GeneralUtility::strtolower($this->thisConfig['contextMenu.']['showButtons'])), TRUE)) . ';';
			}
			if ($this->thisConfig['contextMenu.']['hideButtons']) {
				$registerRTEinJavascriptString .= '
	RTEarea[' . $editorId . '].contextMenu.hideButtons = ' . json_encode(\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->htmlAreaRTE->cleanList(\TYPO3\CMS\Core\Utility\GeneralUtility::strtolower($this->thisConfig['contextMenu.']['hideButtons'])), TRUE)) . ';';
			}
		}
		return $registerRTEinJavascriptString;
	}

}
