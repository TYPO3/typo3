<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2012 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * Render custom attribute data-htmlarea-clickenlarge
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
class tx_rtehtmlarea_pi4 extends tslib_pibase {

		// Default plugin variables:
	var $prefixId = 'tx_rtehtmlarea_pi4';		// Same as class name
	var $scriptRelPath = 'pi4/class.tx_rtehtmlarea_pi4.php';	// Path to this script relative to the extension dir.
	var $extKey = 'rtehtmlarea';		// The extension key.
	var $conf = array();

	/**
	 * cObj object
	 *
	 * @var tslib_cObj
	 */
	var $cObj;

	/**
	 * Rendering the "data-htmlarea-clickenlarge" custom attribute, called from TypoScript
	 *
	 * @param	string		Content input. Not used, ignore.
	 * @param	array		TypoScript configuration
	 * @return	string		HTML output.
	 * @access private
	 */
	public function removeAccessibilityIcon ($content, $conf) {
		// If the link was not rendered
		if (substr($content, 0, 3) !== '<a ' && substr($content, 0, 5) === '<img ') {
			// Let's remove the accessibility icon, if there is one
			$matches = array();
			if (preg_match('/^<img .*>/', $content, $matches) === 1) {
				$attributes = t3lib_div::get_tag_attributes($matches[0]);
				if ($attributes['src']) {
					// Get RTE Configconfiguration
					$pageTSConfig = $GLOBALS['TSFE']->getPagesTSconfig();
					if (is_array($pageTSConfig) && is_array($pageTSConfig['RTE.'])) {
						$classesAnchorConfiguration = $pageTSConfig['RTE.']['classesAnchor.'];
						if (is_array($classesAnchorConfiguration)) {
							// Make the url of the source relative
							$siteUrl = t3lib_div::getIndpEnv('TYPO3_SITE_URL');
							if (t3lib_div::isFirstPartOfStr($attributes['src'], $siteUrl)) {
								$attributes['src'] = substr($attributes['src'], strlen($siteUrl));
							}
							// Lookup the RTE.classesAnchor array
							foreach ($classesAnchorConfiguration as $item => $conf) {
								if ($conf['image']) {
									$imagePath = $this->getFullFileName(trim(str_replace('\'', '', str_replace('"', '', $conf['image']))));
									if ($attributes['src'] === $imagePath) {
										// If found, remove the img tag and break
										$content = substr($content, strlen($matches[0]));
										break;
									}
								}
							}
						}
					}
				}
			}
		}
		return $content;
	}
	/*
	 * Returns the full name of a file referenced in Page TSConfig
	 */
	protected function getFullFileName($filename) {
		if (substr($filename,0,4)=='EXT:') {
			list($extKey,$local) = explode('/',substr($filename,4),2);
			$newFilename = '';
			if (strcmp($extKey,'') &&  t3lib_extMgm::isLoaded($extKey) && strcmp($local,'')) {
				$newFilename = t3lib_extMgm::siteRelPath($extKey) . $local;
			}
		} elseif (substr($filename,0,1) != '/') {
			$newFilename = $filename;
		} else {
			$newFilename = substr($filename, 1);
		}
		return  t3lib_div::resolveBackPath($newFilename);
	}
}
if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/pi4/class.tx_rtehtmlarea_pi4.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/pi4/class.tx_rtehtmlarea_pi4.php']);
}
?>