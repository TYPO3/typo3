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

/**
 * CharacterMap plugin for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
class QuickTag extends RteHtmlAreaApi {

	/**
	 * The name of the plugin registered by the extension
	 *
	 * @var string
	 */
	protected $pluginName = 'QuickTag';

	/**
	 * The comma-separated list of button names that the registered plugin is adding to the htmlArea RTE toolbar
	 *
	 * @var string
	 */
	protected $pluginButtons = 'inserttag';

	/**
	 * The name-converting array, converting the button names used in the RTE PageTSConfing to the button id's used by the JS scripts
	 *
	 * @var array
	 */
	protected $convertToolbarForHtmlAreaArray = array(
		'inserttag' => 'InsertTag'
	);

	/**
	 * The comma-separated list of names of prerequisite plugins
	 *
	 * @var string
	 */
	protected $requiredPlugins = 'TYPO3Color';

	/**
	 * Returns TRUE if the plugin is available and correctly initialized
	 *
	 * @param RteHtmlAreaBase $parentObject parent object
	 * @return bool TRUE if this plugin object should be made available in the current environment and is correctly initialized
	 */
	public function main($parentObject) {
		$available = parent::main($parentObject);
		if ($this->thisConfig['disableSelectColor'] && $this->htmlAreaRTE->client['browser'] != 'gecko') {
			$this->requiredPlugins = 'DefaultColor';
		}
		return $available;
	}

}
