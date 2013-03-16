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
 * TYPO3 Color plugin for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
class Typo3Color extends \TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi {

	protected $extensionKey = 'rtehtmlarea';

	// The key of the extension that is extending htmlArea RTE
	protected $pluginName = 'TYPO3Color';

	// The name of the plugin registered by the extension
	protected $relativePathToLocallangFile = 'extensions/TYPO3Color/locallang.xml';

	// Path to this main locallang file of the extension relative to the extension dir.
	protected $relativePathToSkin = 'extensions/TYPO3Color/skin/htmlarea.css';

	// Path to the skin (css) file relative to the extension dir.
	protected $htmlAreaRTE;

	// Reference to the invoking object
	protected $thisConfig;

	// Reference to RTE PageTSConfig
	protected $toolbar;

	// Reference to RTE toolbar array
	protected $LOCAL_LANG;

	// Frontend language array
	protected $pluginButtons = 'textcolor,bgcolor';

	protected $convertToolbarForHtmlAreaArray = array(
		'textcolor' => 'ForeColor',
		'bgcolor' => 'HiliteColor'
	);

	public function main($parentObject) {
		return parent::main($parentObject) && $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['allowStyleAttribute'];
	}

	/**
	 * Return JS configuration of the htmlArea plugins registered by the extension
	 *
	 * @param 	integer		Relative id of the RTE editing area in the form
	 * @return string		JS configuration for registered plugins
	 */
	public function buildJavascriptConfiguration($RTEcounter) {
		// Process colors configuration
		$registerRTEinJavascriptString = $this->buildJSColorsConfig($RTEcounter);
		return $registerRTEinJavascriptString;
	}

	/**
	 * Return Javascript configuration of colors
	 *
	 * @param 	integer		$RTEcounter: The index number of the current RTE editing area within the form.
	 * @return 	string		Javascript configuration of colors
	 * @todo Define visibility
	 */
	public function buildJSColorsConfig($RTEcounter) {
		if ($this->htmlAreaRTE->is_FE()) {
			$RTEProperties = $this->htmlAreaRTE->RTEsetup;
		} else {
			$RTEProperties = $this->htmlAreaRTE->RTEsetup['properties'];
		}
		$configureRTEInJavascriptString = '';
		$configureRTEInJavascriptString .= '
			RTEarea[' . $RTEcounter . '].disableColorPicker = ' . (trim($this->thisConfig['disableColorPicker']) ? 'true' : 'false') . ';';
		// Building the array of configured colors
		if (is_array($RTEProperties['colors.'])) {
			$HTMLAreaColorname = array();
			foreach ($RTEProperties['colors.'] as $colorName => $conf) {
				$colorName = substr($colorName, 0, -1);
				$colorLabel = $this->htmlAreaRTE->getPageConfigLabel($conf['name'], 0);
				$HTMLAreaColorname[$colorName] = array($colorLabel, strtoupper(substr($conf['value'], 1, 6)));
			}
		}
		// Setting the list of colors if specified in the RTE config
		if ($this->thisConfig['colors']) {
			$HTMLAreaColors = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->htmlAreaRTE->cleanList($this->thisConfig['colors']));
			$HTMLAreaJSColors = array();
			foreach ($HTMLAreaColors as $colorName) {
				if ($HTMLAreaColorname[$colorName]) {
					$HTMLAreaJSColors[] = $HTMLAreaColorname[$colorName];
				}
			}
			if ($this->htmlAreaRTE->is_FE()) {
				$GLOBALS['TSFE']->csConvObj->convArray($HTMLAreaJSColors, $this->htmlAreaRTE->OutputCharset, 'utf-8');
			}
			$configureRTEInJavascriptString .= '
			RTEarea[' . $RTEcounter . '].colors = ' . json_encode($HTMLAreaJSColors) . ';';
		}
		return $configureRTEInJavascriptString;
	}

}


?>