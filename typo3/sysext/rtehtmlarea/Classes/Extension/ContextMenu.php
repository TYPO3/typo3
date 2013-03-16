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
	RTEarea[' . $editorId . '].contextMenu.showButtons = ' . json_encode(\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->htmlAreaRTE->cleanList(\TYPO3\CMS\Core\Utility\GeneralUtility::strtolower($this->thisConfig['contextMenu.']['showButtons'])), 1)) . ';';
			}
			if ($this->thisConfig['contextMenu.']['hideButtons']) {
				$registerRTEinJavascriptString .= '
	RTEarea[' . $editorId . '].contextMenu.hideButtons = ' . json_encode(\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->htmlAreaRTE->cleanList(\TYPO3\CMS\Core\Utility\GeneralUtility::strtolower($this->thisConfig['contextMenu.']['hideButtons'])), 1)) . ';';
			}
		}
		return $registerRTEinJavascriptString;
	}

}


?>