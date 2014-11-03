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
		'gecko' => array('copy', 'cut', 'paste'),
		'webkit' => array('copy', 'cut', 'paste'),
		'opera' => array('copy', 'cut', 'paste')
	);

	public function main($parentObject) {
		$enabled = parent::main($parentObject);
		// Hiding some buttons
		if ($enabled && is_array($this->hideButtonsFromClient[$this->htmlAreaRTE->client['browser']])) {
			$this->pluginButtons = implode(',', array_diff(\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->pluginButtons, TRUE), $this->hideButtonsFromClient[$this->htmlAreaRTE->client['browser']]));
		}
		// Force enabling the plugin even if no button remains in the tool bar, so that hot keys still are enabled
		$this->pluginAddsButtons = FALSE;
		return $enabled;
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
