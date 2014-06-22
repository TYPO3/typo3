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
 * Block Style extension for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
class BlockStyle extends \TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi {

	protected $extensionKey = 'rtehtmlarea';

	// The key of the extension that is extending htmlArea RTE
	protected $pluginName = 'BlockStyle';

	// The name of the plugin registered by the extension
	protected $relativePathToLocallangFile = 'extensions/BlockStyle/locallang.xlf';

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
	protected $pluginButtons = 'blockstyle';

	// The comma-separated list of button names that the extension id adding to the htmlArea RTE tollbar
	protected $pluginLabels = 'blockstylelabel';

	// The comma-separated list of label names that the extension id adding to the htmlArea RTE tollbar
	// The name-converting array, converting the button names used in the RTE PageTSConfing to the button id's used by the JS scripts
	protected $convertToolbarForHtmlAreaArray = array(
		'blockstylelabel' => 'I[Block style label]',
		'blockstyle' => 'BlockStyle'
	);

	protected $requiresClassesConfiguration = TRUE;

}
