<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Xavier Perseguers <typo3@perseguers.ch>
 *  (c) 2010 Steffen Kamper <steffen@typo3.org>
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
 * Contains TEXT class object.
 *
 * $Id: class.tslib_content.php 7905 2010-06-13 14:42:33Z ohader $
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 */
class tslib_content_HorizontalRuler extends tslib_content_Abstract {

	/**
	 * Rendering the cObject, HRULER
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 */
	public function render($conf = array()) {

		$lineThickness = isset($conf['lineThickness.'])
			? $this->cObj->stdWrap($conf['lineThickness'], $conf['lineThickness.'])
			: $conf['lineThickness'];
		$lineThickness = t3lib_div::intInRange($lineThickness, 1, 50);

		$lineColor = isset($conf['lineColor.'])
			? $this->cObj->stdWrap($conf['lineColor'], $conf['lineColor.'])
			: $conf['lineColor'];
		if(!$lineColor) {
			$lineColor = 'black';
		}

		$spaceBefore = isset($conf['spaceLeft.'])
			? intval($this->cObj->stdWrap($conf['spaceLeft'], $conf['spaceLeft.']))
			: intval($conf['spaceLeft']);

		$spaceAfter = isset($conf['spaceRight.'])
			? intval($this->cObj->stdWrap($conf['spaceRight'], $conf['spaceRight.']))
			: intval($conf['spaceRight']);

		$tableWidth = isset($conf['tableWidth.'])
			? intval($this->cObj->stdWrap($conf['tableWidth'], $conf['tableWidth.']))
			: intval($conf['tableWidth']);
		if(!$tableWidth) {
			$tableWidth = '99%';
		}

		$theValue = '';

		$theValue .= '<table border="0" cellspacing="0" cellpadding="0"
			width="' . htmlspecialchars($tableWidth) . '"
			summary=""><tr>';
		if ($spaceBefore) {
			$theValue .= '<td width="1">
				<img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif"
				width="' . $spaceBefore . '"
				height="1" alt="" title="" />
			</td>';
		}
		$theValue .= '<td bgcolor="' . $lineColor . '">
			<img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif"
			width="1"
			height="' . $lineThickness . '"
			alt="" title="" />
		</td>';
		if ($spaceAfter) {
			$theValue .= '<td width="1">
				<img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif"
				width="' . $spaceAfter . '"
				height="1" alt="" title="" />
			</td>';
		}
		$theValue .= '</tr></table>';

		if (isset($conf['stdWrap.'])) {
			$theValue = $this->cObj->stdWrap($theValue, $conf['stdWrap.']);
		}

		return $theValue;

	}

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_horizontalruler.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_horizontalruler.php']);
}

?>