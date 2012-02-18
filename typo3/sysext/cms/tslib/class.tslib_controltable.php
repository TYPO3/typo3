<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 1999-2012 Kasper Skårhøj (kasperYYYY@typo3.com)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Rendering of tables for content positioning
 *
 * @see tslib_cObj::CTABLE()
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tslib
 */
class tslib_controlTable {
	var $offX = 0; // offset, x
	var $offY = 0; // offset, y


	var $tm = ''; // top menu
	var $lm = ''; // left menu
	var $rm = ''; // right menu
	var $bm = ''; // bottom menu
	var $content = ''; // content


	var $tmTDparams = 'valign="top"'; // top menu TDparams
	var $lmTDparams = 'valign="top"'; // left menu TDparams
	var $rmTDparams = 'valign="top"'; // right menu TDparams
	var $bmTDparams = 'valign="top"'; // bottom menu TDparams
	var $contentTDparams = 'valign="top"'; // content TDparams


	var $cMl = 1; // content margin, left
	var $cMr = 1; // content margin, right
	var $cMt = 0; // content margin, top
	var $cMb = 1; // content margin, bottom


	var $contentW = 0; // Places a little gif-spacer in the bottom of the content frame


	var $tableParams = 'border="0" cellspacing="0" cellpadding="0"';

	/**
	 * Wrapping internal vars ->tm, ->lm, ->rm, ->bm and ->content in a table where each content part is stored in a cell.
	 * The two arguments to this function defines some offsets and margins to use in the arrangement of the content in the table.
	 *
	 * @param	string		List of offset parameters; x,y
	 * @param	string		List of margin parameters; left, top, right, bottom
	 * @return	string		The content strings wrapped in a <table> as the parameters defined
	 * @see tslib_cObj::CTABLE()
	 */
	function start($offset, $cMargins) {
		$offArr = t3lib_div::intExplode(',', $offset);
		$cMargArr = t3lib_div::intExplode(',', $cMargins);

		$cols = 0;
		$rows = 0;

		if ($this->lm)
			$cols++;
		if ($this->rm)
			$cols++;
		if ($cMargArr[0])
			$cols++;
		if ($cMargArr[2])
			$cols++;
		if ($cMargArr[1] || $cMargArr[3] || $this->tm || $this->bm || $this->content || $this->contentW)
			$cols++;

		if ($cMargArr[1])
			$rows++;
		if ($cMargArr[3])
			$rows++;
		if ($this->tm)
			$rows++;
		if ($this->bm)
			$rows++;
		if ($this->content)
			$rows++;
		if ($this->contentW)
			$rows++;
		if (!$rows && $cols)
			$rows = 1; // If there are no rows in the middle but still som columns...


		if ($rows && $cols) {
			$res = LF . '<table ' . $this->tableParams . '>';
				// top offset:
			if ($offArr[1]) {
				$xoff = $offArr[0] ? 1 : 0;
				if ($cols + $xoff > 1) {
					$colspan = ' colspan="' . ($cols + $xoff) . '"';
				}
				$res .= '<tr><td' . $colspan . '><img src="' . $GLOBALS['TSFE']->absRefPrefix .
					'clear.gif" width="1" height="' . $offArr[1] . '" alt="" title="" /></td></tr>';
			}
				// The rows:
			if ($rows > 1) {
				$rowspan = ' rowspan="' . ($rows) . '"';
			}
			$res .= '<tr>';
			if ($offArr[0]) {
				$res .= '<td' . $rowspan . '><img src="' . $GLOBALS['TSFE']->absRefPrefix .
					'clear.gif" height="1" width="' . $offArr[0] . '" alt="" title="" /></td>';
			}
			if ($this->lm) {
				$res .= '<td' . $rowspan . ' ' . $this->lmTDparams . '>' . $this->lm . '</td>';
			}
			if ($cMargArr[0]) {
				$res .= '<td' . $rowspan . '><img src="' . $GLOBALS['TSFE']->absRefPrefix .
					'clear.gif" height="1" width="' . $cMargArr[0] . '" alt="" title="" /></td>';
			}
				// content...


			$middle = array();
			if ($this->tm) {
				$middle[] = '<td ' . $this->tmTDparams . '>' . $this->tm . '</td>';
			}
			if ($cMargArr[1]) {
				$middle[] = '<td><img src="' . $GLOBALS['TSFE']->absRefPrefix .
					'clear.gif" width="1" height="' . $cMargArr[1] . '" alt="" title="" /></td>';
			}
			if ($this->content) {
				$middle[] = '<td ' . $this->contentTDparams . '>' . $this->content . '</td>';
			}
			if ($cMargArr[3]) {
				$middle[] = '<td><img src="' . $GLOBALS['TSFE']->absRefPrefix .
					'clear.gif" width="1" height="' . $cMargArr[3] . '" alt="" title="" /></td>';
			}
			if ($this->bm) {
				$middle[] = '<td ' . $this->bmTDparams . '>' . $this->bm . '</td>';
			}
			if ($this->contentW) {
				$middle[] = '<td><img src="' . $GLOBALS['TSFE']->absRefPrefix .
					'clear.gif" height="1" width="' . $this->contentW . '" alt="" title="" /></td>';
			}
			if (isset($middle[0])) {
				$res .= $middle[0];
			}

				// Left of content
			if ($cMargArr[2]) {
				$res .= '<td' . $rowspan . '><img src="' . $GLOBALS['TSFE']->absRefPrefix .
					'clear.gif" height="1" width="' . $cMargArr[2] . '" alt="" title="" /></td>';
			}
			if ($this->rm) {
				$res .= '<td' . $rowspan . ' ' . $this->rmTDparams . '>' . $this->rm . '</td>';
			}
			$res .= '</tr>';

				// More than the two rows
			$mCount = count($middle);
			for ($a = 1; $a < $mCount; $a++) {
				$res .= '<tr>' . $middle[$a] . '</tr>';
			}
			$res .= '</table>';
			return $res;
		}
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/class.tslib_controltable.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/class.tslib_controltable.php']);
}

?>