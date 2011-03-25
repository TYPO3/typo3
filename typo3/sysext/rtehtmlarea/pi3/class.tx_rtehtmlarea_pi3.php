<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2011 Stanislas Rolland <typo3(arobas)sjbr.ca>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Render custom attribute clickenlarge
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
class tx_rtehtmlarea_pi3 extends tslib_pibase {

		// Default plugin variables:
	var $prefixId = 'tx_rtehtmlarea_pi3';		// Same as class name
	var $scriptRelPath = 'pi3/class.tx_rtehtmlarea_pi3.php';	// Path to this script relative to the extension dir.
	var $extKey = 'rtehtmlarea';		// The extension key.
	var $conf = array();

	/**
	 * cObj object
	 *
	 * @var tslib_cObj
	 */
	var $cObj;

	/**
	 * Rendering the "clickenlarge" custom attribute, called from TypoScript
	 *
	 * @param	string		Content input. Not used, ignore.
	 * @param	array		TypoScript configuration
	 * @return	string		HTML output.
	 * @access private
	 */
	function render_clickenlarge($content,$conf) {

		$clickenlarge = isset($this->cObj->parameters['clickenlarge']) ? $this->cObj->parameters['clickenlarge'] : 0;
		$path = $this->cObj->parameters['src'];
		$pathPre = $GLOBALS['TYPO3_CONF_VARS']['BE']['RTE_imageStorageDir'] . 'RTEmagicC_';
		if (t3lib_div::isFirstPartOfStr($path,$pathPre)) {
				// Find original file:
			$pI = pathinfo(substr($path,strlen($pathPre)));
			$filename = substr($pI['basename'],0,-strlen('.'.$pI['extension']));
			$file = $GLOBALS['TYPO3_CONF_VARS']['BE']['RTE_imageStorageDir'] . 'RTEmagicP_' . $filename;
		} else {
			$file = $this->cObj->parameters['src'];
		}

		unset($this->cObj->parameters['clickenlarge']);
		unset($this->cObj->parameters['allParams']);
		$content = '<img '. t3lib_div::implodeAttributes($this->cObj->parameters, TRUE, TRUE) . ' />';

		if ($clickenlarge && is_array($conf['imageLinkWrap.'])) {
			$theImage = $file ? $GLOBALS['TSFE']->tmpl->getFileName($file) : '';
			if ($theImage) {
				$this->cObj->parameters['origFile'] = $theImage;
				if ($this->cObj->parameters['title']) {
					$conf['imageLinkWrap.']['title'] = $this->cObj->parameters['title'];
				}
				if ($this->cObj->parameters['alt']) {
					$conf['imageLinkWrap.']['alt'] = $this->cObj->parameters['alt'];
				}
				$content = $this->cObj->imageLinkWrap($content,$theImage,$conf['imageLinkWrap.']);
				$content = $this->cObj->stdWrap($content,$conf['stdWrap.']);
			}
		}
		return $content;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/pi3/class.tx_rtehtmlarea_pi3.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/pi3/class.tx_rtehtmlarea_pi3.php']);
}

?>