<?php
namespace TYPO3\CMS\Rtehtmlarea\Extension;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2013 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * TextStyle plugin for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
class TextStyle extends \TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi {

	protected $extensionKey = 'rtehtmlarea';

	// The key of the extension that is extending htmlArea RTE
	protected $pluginName = 'TextStyle';

	// The name of the plugin registered by the extension
	protected $relativePathToLocallangFile = 'extensions/TextStyle/locallang.xml';

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
	protected $pluginButtons = 'textstyle';

	// The comma-seperated list of button names that the extension id adding to the htmlArea RTE tollbar
	protected $pluginLabels = 'textstylelabel';

	// The comma-seperated list of label names that the extension id adding to the htmlArea RTE tollbar
	// The name-converting array, converting the button names used in the RTE PageTSConfing to the button id's used by the JS scripts
	protected $convertToolbarForHtmlAreaArray = array(
		'textstylelabel' => 'I[text_style]',
		'textstyle' => 'TextStyle'
	);

	protected $requiresClassesConfiguration = TRUE;

}


?>