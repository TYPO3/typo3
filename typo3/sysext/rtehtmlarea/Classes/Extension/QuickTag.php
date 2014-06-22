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
 * CharacterMap plugin for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
class QuickTag extends \TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi {

	protected $extensionKey = 'rtehtmlarea';

	// The key of the extension that is extending htmlArea RTE
	protected $pluginName = 'QuickTag';

	// The name of the plugin registered by the extension
	protected $relativePathToLocallangFile = '';

	// Path to this main locallang file of the extension relative to the extension dir.
	protected $relativePathToSkin = 'extensions/QuickTag/skin/htmlarea.css';

	// Path to the skin (css) file relative to the extension dir.
	protected $htmlAreaRTE;

	// Reference to the invoking object
	protected $thisConfig;

	// Reference to RTE PageTSConfig
	protected $toolbar;

	// Reference to RTE toolbar array
	protected $LOCAL_LANG;

	// Frontend language array
	protected $pluginButtons = 'inserttag';

	protected $convertToolbarForHtmlAreaArray = array(
		'inserttag' => 'InsertTag'
	);

	protected $requiredPlugins = 'TYPO3Color';

	// The comma-separated list of names of prerequisite plugins
	public function main($parentObject) {
		$available = parent::main($parentObject);
		if ($this->thisConfig['disableSelectColor'] && $this->htmlAreaRTE->client['browser'] != 'gecko') {
			$this->requiredPlugins = 'DefaultColor';
		}
		return $available;
	}

}
