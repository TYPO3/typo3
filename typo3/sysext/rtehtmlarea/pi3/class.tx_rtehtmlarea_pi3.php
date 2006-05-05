<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2006 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
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
 * Render custom tag clickenlarge
 *
 * @author Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
 *
 * $Id$  *
 */

require_once(PATH_tslib.'class.tslib_pibase.php');

class tx_rtehtmlarea_pi3 extends tslib_pibase {

		// Default plugin variables:
	var $prefixId = 'tx_rtehtmlarea_pi3';		// Same as class name
	var $scriptRelPath = 'pi3/class.tx_rtehtmlarea_pi3.php';	// Path to this script relative to the extension dir.
	var $extKey = 'rtehtmlarea';		// The extension key.
	var $conf = array();
	var $cObj;

	/**
	 * Rendering the "clickenlarge" custom attribute, called from TypoScript
	 *
	 * @param	string		Content input. Not used, ignore.
	 * @param	array		TypoScript configuration
	 * @return	string		HTML output.
	 * @access private
	 */
	function render_clickenlarge($content,$conf)	{
		global $TYPO3_CONF_VARS;
		
		$clickenlarge = isset($this->cObj->parameters['clickenlarge']) ? $this->cObj->parameters['clickenlarge'] : 0;
		$file = isset($this->cObj->parameters['clickenlargesrc']) ? $this->cObj->parameters['clickenlargesrc'] : '';
		
		unset($this->cObj->parameters['clickenlarge']);
		unset($this->cObj->parameters['clickenlargesrc']);
		unset($this->cObj->parameters['allParams']);
		$content = '<img '. t3lib_div::implodeAttributes($this->cObj->parameters, TRUE, TRUE) . ' />';
		
		if ($TYPO3_CONF_VARS['EXTCONF'][$this->extKey]['enableClickEnlarge'] && $clickenlarge && is_array($conf['imageLinkWrap.'])) {
			$theImage = $file ? $GLOBALS['TSFE']->tmpl->getFileName($file) : '';
			if ($theImage) {
				if ($this->cObj->parameters['title']) $conf['imageLinkWrap.']['title'] = $this->cObj->parameters['title'];
				if ($this->cObj->parameters['alt']) $conf['imageLinkWrap.']['alt'] = $this->cObj->parameters['alt'];
				$content = $this->cObj->imageLinkWrap($content,$theImage,$conf['imageLinkWrap.']);
			}
		}
		return $content;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/pi3/class.tx_rtehtmlarea_pi3.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/pi3/class.tx_rtehtmlarea_pi3.php']);
}

?>