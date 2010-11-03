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
		$lineThickness = t3lib_div::intInRange($this->cObj->stdWrap($conf['lineThickness'], $conf['lineThickness.']), 1, 50);
		$lineColor = $conf['lineColor'] ? $conf['lineColor'] : 'black';
		$spaceBefore = intval($conf['spaceLeft']);
		$spaceAfter = intval($conf['spaceRight']);
		$tableWidth = $conf['tableWidth'] ? $conf['tableWidth'] : '99%';
		$content = '';

		$content .= '<table border="0" cellspacing="0" cellpadding="0" width="' . htmlspecialchars($tableWidth) . '" summary=""><tr>';
		if ($spaceBefore) {
			$content .= '<td width="1"><img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" width="' .
				$spaceBefore . '" height="1" alt="" title="" /></td>';
		}
		$content .= '<td bgcolor="' . $lineColor . '"><img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" width="1" height="' .
			$lineThickness . '" alt="" title="" /></td>';
		if ($spaceAfter) {
			$content .= '<td width="1"><img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" width="' .
				$spaceAfter . '" height="1" alt="" title="" /></td>';
		}
		$content .= '</tr></table>';

		$content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
		return $content;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_horizontalruler.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_horizontalruler.php']);
}

?>