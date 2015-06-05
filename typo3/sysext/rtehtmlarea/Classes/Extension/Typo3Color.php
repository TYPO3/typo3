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
 * TYPO3 Color plugin for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
class Typo3Color extends RteHtmlAreaApi {

	/**
	 * The name of the plugin registered by the extension
	 *
	 * @var string
	 */
	protected $pluginName = 'TYPO3Color';

	/**
	 * Path to this main locallang file of the extension relative to the extension directory
	 *
	 * @var string
	 */
	protected $relativePathToLocallangFile = 'extensions/TYPO3Color/locallang.xlf';

	/**
	 * Path to the skin file relative to the extension directory
	 *
	 * @var string
	 */
	protected $relativePathToSkin = 'Resources/Public/Css/Skin/Plugins/typo3-color.css';

	/**
	 * The comma-separated list of button names that the registered plugin is adding to the htmlArea RTE toolbar
	 *
	 * @var string
	 */
	protected $pluginButtons = 'textcolor,bgcolor';

	/**
	 * The name-converting array, converting the button names used in the RTE PageTSConfing to the button id's used by the JS scripts
	 *
	 * @var array
	 */
	protected $convertToolbarForHtmlAreaArray = array(
		'textcolor' => 'ForeColor',
		'bgcolor' => 'HiliteColor'
	);

	/**
	 * Returns TRUE if the plugin is available and correctly initialized
	 *
	 * @param RteHtmlAreaBase $parentObject parent object
	 * @return bool TRUE if this plugin object should be made available in the current environment and is correctly initialized
	 */
	public function main($parentObject) {
		return parent::main($parentObject) && $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['allowStyleAttribute'];
	}

	/**
	 * Return JS configuration of the htmlArea plugins registered by the extension
	 *
	 * @param string $rteNumberPlaceholder A dummy string for JS arrays
	 * @return string JS configuration for registered plugins
	 */
	public function buildJavascriptConfiguration($rteNumberPlaceholder) {
		// Process colors configuration
		return $this->buildJSColorsConfig($rteNumberPlaceholder);
	}

	/**
	 * Return Javascript configuration of colors
	 *
	 * @param string $rteNumberPlaceholder A dummy string for JS arrays
	 * @return string Javascript configuration of colors
	 */
	public function buildJSColorsConfig($rteNumberPlaceholder) {
		if ($this->htmlAreaRTE->is_FE()) {
			$RTEProperties = $this->htmlAreaRTE->RTEsetup;
		} else {
			$RTEProperties = $this->htmlAreaRTE->RTEsetup['properties'];
		}
		$configureRTEInJavascriptString = '';
		$configureRTEInJavascriptString .= '
			RTEarea[' . $rteNumberPlaceholder . '].disableColorPicker = ' . (trim($this->thisConfig['disableColorPicker']) ? 'true' : 'false') . ';';
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
			RTEarea[' . $rteNumberPlaceholder . '].colors = ' . json_encode($HTMLAreaJSColors) . ';';
		}
		return $configureRTEInJavascriptString;
	}

}
