<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2009 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * Default Font plugin for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 *
 * TYPO3 SVN ID: $Id$
 *
 */

require_once(t3lib_extMgm::extPath('rtehtmlarea').'class.tx_rtehtmlareaapi.php');

class tx_rtehtmlarea_defaultfont extends tx_rtehtmlareaapi {

	protected $extensionKey = 'rtehtmlarea';	// The key of the extension that is extending htmlArea RTE
	protected $pluginName = 'DefaultFont';	// The name of the plugin registered by the extension
	protected $relativePathToLocallangFile = 'extensions/DefaultFont/locallang.xml';	// Path to this main locallang file of the extension relative to the extension dir.
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

	protected $defaultFontFaces = array(
		'Arial'			=> 'Arial,sans-serif',
		'Arial Black'		=> 'Arial Black,sans-serif',
		'Verdana'		=> 'Verdana,Arial,sans-serif',
		'Times New Roman'	=> 'Times New Roman,Times,serif',
		'Garamond'		=> 'Garamond',
		'Lucida Handwriting'	=> 'Lucida Handwriting',
		'Courier'		=> 'Courier',
		'Webdings'		=> 'Webdings',
		'Wingdings'		=> 'Wingdings',
		);

	protected $defaultFontSizes = array(
		'1'	=>	'1 (8 pt)',
		'2'	=>	'2 (10 pt)',
		'3'	=>	'3 (12 pt)',
		'4'	=>	'4 (14 pt)',
		'5'	=>	'5 (18 pt)',
		'6'	=>	'6 (24 pt)',
		'7'	=>	'7 (36 pt)',
		);

	protected $defaultFontSizes_safari = array(
		'1'	=>	'x-small (10px)',
		'2'	=>	'small (13px)',
		'3'	=>	'medium (16px)',
		'4'	=>	'large (18px)',
		'5'	=>	'x-large (24px)',
		'6'	=>	'xx-large (32px)',
		'7'	=>	'xxx-large (48px)',
		);

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

			// Process font faces configuration
		if (in_array('fontstyle',$this->toolbar)) {
			$registerRTEinJavascriptString .= $this->buildJSFontFacesConfig($RTEcounter);
		}

			// Process font sizes configuration
		if (in_array('fontsize',$this->toolbar)) {
			$registerRTEinJavascriptString .= $this->buildJSFontSizesConfig($RTEcounter);
		}

		return $registerRTEinJavascriptString;
	}

	/**
	 * Return Javascript configuration of font faces
	 *
	 * @param	integer		$RTEcounter: The index number of the current RTE editing area within the form.
	 *
	 * @return	string		Javascript configuration of font faces
 	 */
	protected function buildJSFontfacesConfig($RTEcounter) {
		global $TSFE, $LANG;

		if ($this->htmlAreaRTE->is_FE()) {
			$RTEProperties = $this->htmlAreaRTE->RTEsetup;
		} else {
			$RTEProperties = $this->htmlAreaRTE->RTEsetup['properties'];
		}

		$configureRTEInJavascriptString = '';

			// Builing JS array of default font faces
			// utf8-encode labels if we are responding to an IRRE ajax call
		$HTMLAreaFontname = array();
		$HTMLAreaFontname['nofont'] = '
				"' . $fontName . '" : "' . $this->htmlAreaRTE->cleanList($fontValue) . '"';
		$defaultFontFacesList = 'nofont,';
		if ($this->htmlAreaRTE->is_FE()) {
			$HTMLAreaFontname['nofont'] = '
				"' . $GLOBALS['TSFE']->getLLL('No font', $this->LOCAL_LANG) . '" : ""';
		} else {
			$HTMLAreaFontname['nofont'] = '
				"' . ($this->htmlAreaRTE->TCEform->inline->isAjaxCall ? $GLOBALS['LANG']->csConvObj->utf8_encode($GLOBALS['LANG']->getLL('No font'), $GLOBALS['LANG']->charSet) : $GLOBALS['LANG']->getLL('No font')) . '" : ""';
		}

		$hideFontFaces = $this->htmlAreaRTE->cleanList($this->thisConfig['hideFontFaces']);
		if ($hideFontFaces != '*') {
			$index = 0;
			foreach ($this->defaultFontFaces as $fontName => $fontValue) {
				if (!t3lib_div::inList($hideFontFaces, $index+1)) {
					$HTMLAreaFontname[$fontName] = '
				"' . ((!$this->htmlAreaRTE->is_FE() && $this->htmlAreaRTE->TCEform->inline->isAjaxCall) ? $GLOBALS['LANG']->csConvObj->utf8_encode($fontName, $GLOBALS['LANG']->charSet) : $fontName) . '" : "' . $this->htmlAreaRTE->cleanList($fontValue) . '"';
					$defaultFontFacesList .= $fontName . ',';
				}
				$index++;
			}
		}

			// Adding configured font faces
		if (is_array($RTEProperties['fonts.'])) {
			foreach ($RTEProperties['fonts.'] as $fontName => $conf) {
				$fontName = substr($fontName,0,-1);
				$fontLabel = $this->htmlAreaRTE->getPageConfigLabel($conf['name'],0);
				$HTMLAreaFontname[$fontName] = '
				"' . ((!$this->htmlAreaRTE->is_FE() && $this->htmlAreaRTE->TCEform->inline->isAjaxCall) ? $GLOBALS['LANG']->csConvObj->utf8_encode($fontLabel, $GLOBALS['LANG']->charSet) : $fontLabel) . '" : "' . $this->htmlAreaRTE->cleanList($conf['value']) . '"';
			}
		}

			// Setting the list of font faces
		$HTMLAreaJSFontface = '{';
		$HTMLAreaFontface = t3lib_div::trimExplode(',' , $this->htmlAreaRTE->cleanList($defaultFontFacesList . ',' . $this->thisConfig['fontFace']));
		$HTMLAreaFontfaceIndex = 0;
		foreach ($HTMLAreaFontface as $fontName) {
			if ($HTMLAreaFontfaceIndex) {
				$HTMLAreaJSFontface .= ',';
			}
			$HTMLAreaJSFontface .= $HTMLAreaFontname[$fontName];
			$HTMLAreaFontfaceIndex++;
		}
		$HTMLAreaJSFontface .= '};';

		$button = 'fontstyle';
		if (!is_array( $this->thisConfig['buttons.']) || !is_array( $this->thisConfig['buttons.'][$button.'.'])) {
			$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.'].buttons.'. $button .' = new Object();';
		}
		$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.'].buttons.'. $button .'.options = '. $HTMLAreaJSFontface;

		return $configureRTEInJavascriptString;
	}

	/**
	 * Return Javascript configuration of font sizes
	 *
	 * @param	integer		$RTEcounter: The index number of the current RTE editing area within the form.
	 *
	 * @return	string		Javascript font sizes configuration
	 */
	protected function buildJSFontSizesConfig($RTEcounter) {
		global $LANG, $TSFE;
		$configureRTEInJavascriptString = '';

			// Builing JS array of default font sizes
		$HTMLAreaFontSizes = array();
		if ($this->htmlAreaRTE->is_FE()) {
			$HTMLAreaFontSizes[0] = $TSFE->getLLL('No size', $this->LOCAL_LANG);
		} else {
			$HTMLAreaFontSizes[0] = $LANG->getLL('No size');
		}

		foreach ($this->defaultFontSizes as $FontSizeItem => $FontSizeLabel) {
			if ($this->htmlAreaRTE->client['BROWSER'] == 'safari') {
				$HTMLAreaFontSizes[$FontSizeItem] = $this->defaultFontSizes_safari[$FontSizeItem];
			} else {
				$HTMLAreaFontSizes[$FontSizeItem] = $FontSizeLabel;
			}
		}
		if ($this->thisConfig['hideFontSizes'] ) {
			$hideFontSizes =  t3lib_div::trimExplode(',', $this->htmlAreaRTE->cleanList($this->thisConfig['hideFontSizes']), 1);
			foreach ($hideFontSizes as $item)  {
				if ($HTMLAreaFontSizes[strtolower($item)]) {
					unset($HTMLAreaFontSizes[strtolower($item)]);
				}
			}
		}

		$HTMLAreaJSFontSize = '{';
		if ($this->htmlAreaRTE->cleanList($this->thisConfig['hideFontSizes']) != '*') {
				// utf8-encode labels if we are responding to an IRRE ajax call
			if (!$this->htmlAreaRTE->is_FE() && $this->htmlAreaRTE->TCEform->inline->isAjaxCall) {
				foreach ($HTMLAreaFontSizes as $FontSizeItem => $FontSizeLabel) {
					$HTMLAreaFontSizes[$FontSizeItem] = $GLOBALS['LANG']->csConvObj->utf8_encode($FontSizeLabel, $GLOBALS['LANG']->charSet);
				}
			}
			$HTMLAreaFontSizeIndex = 0;
			foreach ($HTMLAreaFontSizes as $FontSizeItem => $FontSizeLabel) {
				if($HTMLAreaFontSizeIndex) {
					$HTMLAreaJSFontSize .= ',';
				}
				$HTMLAreaJSFontSize .= '
				"' . $FontSizeLabel . '" : "' . ($FontSizeItem?$FontSizeItem:'') . '"';
				$HTMLAreaFontSizeIndex++;
			}
		}
		$HTMLAreaJSFontSize .= '};';

		$button = 'fontsize';
		if (!is_array( $this->thisConfig['buttons.']) || !is_array( $this->thisConfig['buttons.'][$button.'.'])) {
			$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.'].buttons.'. $button .' = new Object();';
		}
		$configureRTEInJavascriptString .= '
			RTEarea['.$RTEcounter.'].buttons.'. $button .'.options = '. $HTMLAreaJSFontSize;

		return $configureRTEInJavascriptString;
	}

} // end of class

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/extensions/DefaultFont/class.tx_rtehtmlarea_defaultfont.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/extensions/DefaultFont/class.tx_rtehtmlarea_defaultfont.php']);
}

?>
