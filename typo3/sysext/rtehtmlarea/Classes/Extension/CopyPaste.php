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
 * Copy Paste plugin for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
class CopyPaste extends \TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi {

	protected $extensionKey = 'rtehtmlarea';

	// The key of the extension that is extending htmlArea RTE
	protected $pluginName = 'CopyPaste';

	// The name of the plugin registered by the extension
	protected $relativePathToLocallangFile = '';

	// Path to this main locallang file of the extension relative to the extension dir.
	protected $relativePathToSkin = 'extensions/CopyPaste/skin/htmlarea.css';

	// Path to the skin (css) file relative to the extension dir.
	protected $htmlAreaRTE;

	// Reference to the invoking object
	protected $thisConfig;

	// Reference to RTE PageTSConfig
	protected $toolbar;

	// Reference to RTE toolbar array
	protected $LOCAL_LANG;

	// Frontend language array
	protected $pluginButtons = 'copy, cut, paste';

	protected $convertToolbarForHtmlAreaArray = array(
		'copy' => 'Copy',
		'cut' => 'Cut',
		'paste' => 'Paste'
	);

	// Hide buttons not implemented in client browsers
	protected $hideButtonsFromClient = array(
		'webkit' => array('paste'),
		'opera' => array('copy', 'cut', 'paste')
	);

	public function main($parentObject) {
		$enabled = parent::main($parentObject);
		// Hiding some buttons
		if ($enabled && is_array($this->hideButtonsFromClient[$this->htmlAreaRTE->client['browser']])) {
			$this->pluginButtons = implode(',', array_diff(\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->pluginButtons, 1), $this->hideButtonsFromClient[$this->htmlAreaRTE->client['browser']]));
		}
		// Force enabling the plugin even if no button remains in the tool bar, so that hot keys still are enabled
		$this->pluginAddsButtons = FALSE;
		return $enabled;
	}

	/**
	 * Return JS configuration of the htmlArea plugins registered by the extension
	 *
	 * @param 	integer		Relative id of the RTE editing area in the form
	 * @return string		JS configuration for registered plugins
	 */
	public function buildJavascriptConfiguration($RTEcounter) {
		$registerRTEinJavascriptString = '';
		$button = 'paste';
		if ($this->htmlAreaRTE->client['browser'] == 'gecko') {
			$mozillaAllowClipboardURL = $this->thisConfig['buttons.'][$button . '.']['mozillaAllowClipboardURL'] ? $this->thisConfig['buttons.'][$button . '.']['mozillaAllowClipboardURL'] : $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extensionKey]['plugins']['CopyPaste']['mozillaAllowClipboardURL'];
			if ($mozillaAllowClipboardURL) {
				if (!is_array($this->thisConfig['buttons.']) || !is_array($this->thisConfig['buttons.'][($button . '.')])) {
					$registerRTEinJavascriptString .= '
			RTEarea[' . $RTEcounter . '].buttons.' . $button . ' = new Object();';
				}
				$registerRTEinJavascriptString .= '
			RTEarea[' . $RTEcounter . '].buttons.' . $button . '.mozillaAllowClipboardURL = "' . $mozillaAllowClipboardURL . '";';
			}
		}
		return $registerRTEinJavascriptString;
	}

	/**
	 * Return an updated array of toolbar enabled buttons
	 *
	 * @param 	array		$show: array of toolbar elements that will be enabled, unless modified here
	 * @return 	array		toolbar button array, possibly updated
	 */
	public function applyToolbarConstraints($show) {
		// Remove some buttons
		if (is_array($this->hideButtonsFromClient[$this->htmlAreaRTE->client['browser']])) {
			return array_diff($show, $this->hideButtonsFromClient[$this->htmlAreaRTE->client['browser']]);
		} else {
			return $show;
		}
	}

}


?>