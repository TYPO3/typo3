<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * SelectFont extension for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 *
 * TYPO3 SVN ID: $Id: class.tx_rtehtmlarea_selectfont.php $
 *
 */

require_once(t3lib_extMgm::extPath('rtehtmlarea').'class.tx_rtehtmlareaapi.php');

class tx_rtehtmlarea_selectfont extends tx_rtehtmlareaapi {

	protected $extensionKey = 'rtehtmlarea';	// The key of the extension that is extending htmlArea RTE
	protected $pluginName = 'SelectFont';	// The name of the plugin registered by the extension
	protected $relativePathToLocallangFile = 'extensions/SelectFont/locallang.xml';	// Path to this main locallang file of the extension relative to the extension dir.
	protected $relativePathToSkin = '';		// Path to the skin (css) file relative to the extension dir.
	protected $htmlAreaRTE;				// Reference to the invoking object
	protected $thisConfig;				// Reference to RTE PageTSConfig
	protected $toolbar;				// Reference to RTE toolbar array
	protected $LOCAL_LANG; 				// Frontend language array

	protected $pluginButtons = 'fontstyle,fontsize';
	protected $convertToolbarForHtmlAreaArray = array (
		'fontstyle'		=> 'FontName',
		'fontsize'		=> 'FontSize',
		);

	protected $defaultFont = array(
		'fontstyle' => array(
			'Arial'			=> 'Arial,sans-serif',
			'Arial Black'		=> '\'Arial Black\',sans-serif',
			'Verdana'		=> 'Verdana,Arial,sans-serif',
			'Times New Roman'	=> '\'Times New Roman\',Times,serif',
			'Garamond'		=> 'Garamond',
			'Lucida Handwriting'	=> '\'Lucida Handwriting\'',
			'Courier'		=> 'Courier',
			'Webdings'		=> 'Webdings',
			'Wingdings'		=> 'Wingdings',
			),
		'fontsize' => array(
			'Extra small'	=>	'xx-small',
			'Very small'	=>	'x-small',
			'Small'		=>	'small',
			'Medium'	=>	'medium',
			'Large'		=>	'large',
			'Very large'	=>	'x-large',
			'Extra large'	=>	'xx-large',
			),
		);

	protected $RTEProperties;

	public function main($parentObject) {
		$enabled = parent::main($parentObject) && $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['allowStyleAttribute'];
		if ($this->htmlAreaRTE->is_FE()) {
			$this->RTEProperties = $this->htmlAreaRTE->RTEsetup;
		} else {
			$this->RTEProperties = $this->htmlAreaRTE->RTEsetup['properties'];
		}
		return $enabled;
	}

	/**
	 * Return JS configuration of the htmlArea plugins registered by the extension
	 *
	 * @param	integer		Relative id of the RTE editing area in the form
	 *
	 * @return string		JS configuration for registered plugins
	 *
	 * The returned string will be a set of JS instructions defining the configuration that will be provided to the plugin(s)
	 * Each of the instructions should be of the form:
	 * 	RTEarea['.$RTEcounter.']["buttons"]["button-id"]["property"] = "value";
	 */
	public function buildJavascriptConfiguration($RTEcounter) {
		$registerRTEinJavascriptString = '';
		$pluginButtonsArray = t3lib_div::trimExplode(",", $this->pluginButtons);

			// Process Page TSConfig configuration for each button
		foreach ($pluginButtonsArray as $buttonId) {
			if (in_array($buttonId, $this->toolbar)) {
				$registerRTEinJavascriptString .= $this->buildJSFontItemsConfig($RTEcounter, $buttonId);
			}
		}
		return $registerRTEinJavascriptString;
	}

	/**
	 * Return Javascript configuration of font faces
	 *
	 * @param	integer		$RTEcounter: The index number of the current RTE editing area within the form.
	 * @param	string		$buttonId: button id
	 *
	 * @return	string		Javascript configuration of font faces
 	 */
	protected function buildJSFontItemsConfig($RTEcounter, $buttonId) {
		$configureRTEInJavascriptString = '';

			// Getting removal and addition configuration
		$hideItems = $this->htmlAreaRTE->cleanList($this->thisConfig['hideFont' .  (($buttonId == 'fontstyle') ? 'Faces' : 'Sizes')]);
		$addItems = $this->htmlAreaRTE->cleanList($this->thisConfig[($buttonId == 'fontstyle') ? 'fontFace' : 'fontSize']);
		if (is_array($this->thisConfig['buttons.']) && is_array($this->thisConfig['buttons.'][$buttonId])) {
			if ($this->thisConfig['buttons.'][$buttonId]['removeItems']) {
				$hideItems = $this->thisConfig['buttons.'][$buttonId]['removeItems'];
			}
			if ($this->thisConfig['buttons.'][$buttonId]['addItems']) {
				$addItems = $this->thisConfig['buttons.'][$buttonId]['addItems'];
			}
		}
			// Initializing the items array
		$items = array();
		if ($this->htmlAreaRTE->is_FE()) {
			$items['none'] = '
			"' . $GLOBALS['TSFE']->getLLL((($buttonId == 'fontstyle') ? 'No font' : 'No size'), $this->LOCAL_LANG) . '" : ""';
		} else {
			$items['none'] = '
				"' . $GLOBALS['LANG']->getLL(($buttonId == 'fontstyle') ? 'No font' : 'No size') . '" : ""';
		}
		$defaultItems = 'none,';

			// Inserting and localizing default items
		if ($hideItems != '*') {
			$index = 0;
			foreach ($this->defaultFont[$buttonId] as $name => $value) {
				if (!t3lib_div::inList($hideItems, $index+1)) {
					if ($this->htmlAreaRTE->is_FE()) {
						$label = $GLOBALS['TSFE']->getLLL($name,$this->LOCAL_LANG);
					} else {
						$label = $GLOBALS['LANG']->getLL($name);
					}
					$items[$name] = '
				"' . $name . '" : "' . $this->htmlAreaRTE->cleanList($value) . '"';
					$defaultItems .= $name . ',';
				}
				$index++;
			}
		}
			// Adding configured items
		if (is_array($this->RTEProperties[($buttonId == 'fontstyle') ? 'fonts.' : 'fontSizes.'])) {
			foreach ($this->RTEProperties[($buttonId == 'fontstyle') ? 'fonts.' : 'fontSizes.'] as $name => $conf) {
				$name = substr($name,0,-1);
				$label = $this->htmlAreaRTE->getPageConfigLabel($conf['name'],0);
				$items[$name] = '
				"' . $label . '" : "' . $this->htmlAreaRTE->cleanList($conf['value']) . '"';
			}
		}
			// Setting the JS list of options
		$JSOptions = '';
		$configuredItems = t3lib_div::trimExplode(',' , $this->htmlAreaRTE->cleanList($defaultItems . ',' . $addItems));
		$index = 0;
		foreach ($configuredItems as $name) {
			$JSOptions .= ($index ? ',' : '') . $items[$name];
			$index++;
		}
		$JSOptions = '{'
			. $JSOptions . '
		};';

			// Adding to button JS configuration
		if (!is_array( $this->thisConfig['buttons.']) || !is_array( $this->thisConfig['buttons.'][$buttonId.'.'])) {
			$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.'].buttons.'. $buttonId .' = new Object();';
		}
		$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.'].buttons.'. $buttonId .'.options = '. $JSOptions;

		return $configureRTEInJavascriptString;
	}
} // end of class

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/extensions/SelectFont/class.tx_rtehtmlarea_selectfont.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/extensions/SelectFont/class.tx_rtehtmlarea_selectfont.php']);
}
?>