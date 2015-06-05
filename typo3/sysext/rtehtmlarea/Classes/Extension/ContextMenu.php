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

use TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi;
use TYPO3\CMS\Rtehtmlarea\RteHtmlAreaBase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Context Menu plugin for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
class ContextMenu extends RteHtmlAreaApi {

	/**
	 * The name of the plugin registered by the extension
	 *
	 * @var string
	 */
	protected $pluginName = 'ContextMenu';

	/**
	 * Returns TRUE if the plugin is available and correctly initialized
	 *
	 * @param RteHtmlAreaBase $parentObject parent object
	 * @return bool TRUE if this plugin object should be made available in the current environment and is correctly initialized
	 */
	public function main($parentObject) {
		return parent::main($parentObject) && !($this->htmlAreaRTE->client['browser'] == 'opera' || $this->thisConfig['contextMenu.']['disabled']);
	}

	/**
	 * Return JS configuration of the htmlArea plugins registered by the extension
	 *
	 * @param string $rteNumberPlaceholder A dummy string for JS arrays
	 * @return string JS configuration for registered plugins
	 */
	public function buildJavascriptConfiguration($rteNumberPlaceholder) {
		$registerRTEinJavascriptString = '';
		if (is_array($this->thisConfig['contextMenu.'])) {
			$registerRTEinJavascriptString .= '
	RTEarea[' . $rteNumberPlaceholder . '].contextMenu =  ' . $this->htmlAreaRTE->buildNestedJSArray($this->thisConfig['contextMenu.']) . ';';
			if ($this->thisConfig['contextMenu.']['showButtons']) {
				$registerRTEinJavascriptString .= '
	RTEarea[' . $rteNumberPlaceholder . '].contextMenu.showButtons = ' . json_encode(GeneralUtility::trimExplode(',', $this->htmlAreaRTE->cleanList(GeneralUtility::strtolower($this->thisConfig['contextMenu.']['showButtons'])), TRUE)) . ';';
			}
			if ($this->thisConfig['contextMenu.']['hideButtons']) {
				$registerRTEinJavascriptString .= '
	RTEarea[' . $rteNumberPlaceholder . '].contextMenu.hideButtons = ' . json_encode(GeneralUtility::trimExplode(',', $this->htmlAreaRTE->cleanList(GeneralUtility::strtolower($this->thisConfig['contextMenu.']['hideButtons'])), TRUE)) . ';';
			}
		}
		return $registerRTEinJavascriptString;
	}

}
