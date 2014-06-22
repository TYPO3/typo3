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
 * Default Link extension for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
class DefaultLink extends \TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi {

	protected $extensionKey = 'rtehtmlarea';

	// The key of the extension that is extending htmlArea RTE
	protected $pluginName = 'DefaultLink';

	// The name of the plugin registered by the extension
	protected $relativePathToLocallangFile = '';

	// Path to this main locallang file of the extension relative to the extension dir.
	protected $relativePathToSkin = 'extensions/DefaultLink/skin/htmlarea.css';

	// Path to the skin (css) file relative to the extension dir.
	protected $htmlAreaRTE;

	// Reference to the invoking object
	protected $thisConfig;

	// Reference to RTE PageTSConfig
	protected $toolbar;

	// Reference to RTE toolbar array
	protected $LOCAL_LANG;

	// Frontend language array
	protected $pluginButtons = 'link, unlink';

	protected $convertToolbarForHtmlAreaArray = array(
		'link' => 'CreateLink',
		'unlink' => 'UnLink'
	);

	/**
	 * Return JS configuration of the htmlArea plugins registered by the extension
	 *
	 * @param 	integer		Relative id of the RTE editing area in the form
	 * @return string		JS configuration for registered plugins
	 */
	public function buildJavascriptConfiguration($RTEcounter) {
		$registerRTEinJavascriptString = '';
		$button = 'link';
		if (in_array($button, $this->toolbar) && $this->htmlAreaRTE->is_FE()) {
			if (!is_array($this->thisConfig['buttons.']) || !is_array($this->thisConfig['buttons.'][($button . '.')])) {
				$registerRTEinJavascriptString .= '
			RTEarea[' . $RTEcounter . ']["buttons"]["' . $button . '"] = new Object();';
			}
			$registerRTEinJavascriptString .= '
			RTEarea[' . $RTEcounter . '].buttons.' . $button . '.stripBaseUrl = true;';
		}
		return $registerRTEinJavascriptString;
	}

}
