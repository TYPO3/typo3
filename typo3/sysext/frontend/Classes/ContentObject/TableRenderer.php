<?php
namespace TYPO3\CMS\Frontend\ContentObject;

/***************************************************************
 * Copyright notice
 *
 * (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class TableRenderer {

	// offset, x
	/**
	 * @todo Define visibility
	 */
	public $offX = 0;

	// offset, y
	/**
	 * @todo Define visibility
	 */
	public $offY = 0;

	// top menu
	/**
	 * @todo Define visibility
	 */
	public $tm = '';

	// left menu
	/**
	 * @todo Define visibility
	 */
	public $lm = '';

	// right menu
	/**
	 * @todo Define visibility
	 */
	public $rm = '';

	// bottom menu
	/**
	 * @todo Define visibility
	 */
	public $bm = '';

	// content
	/**
	 * @todo Define visibility
	 */
	public $content = '';

	// top menu TDparams
	/**
	 * @todo Define visibility
	 */
	public $tmTDparams = 'valign="top"';

	// left menu TDparams
	/**
	 * @todo Define visibility
	 */
	public $lmTDparams = 'valign="top"';

	// right menu TDparams
	/**
	 * @todo Define visibility
	 */
	public $rmTDparams = 'valign="top"';

	// bottom menu TDparams
	/**
	 * @todo Define visibility
	 */
	public $bmTDparams = 'valign="top"';

	// content TDparams
	/**
	 * @todo Define visibility
	 */
	public $contentTDparams = 'valign="top"';

	// content margin, left
	/**
	 * @todo Define visibility
	 */
	public $cMl = 1;

	// content margin, right
	/**
	 * @todo Define visibility
	 */
	public $cMr = 1;

	// content margin, top
	/**
	 * @todo Define visibility
	 */
	public $cMt = 0;

	// content margin, bottom
	/**
	 * @todo Define visibility
	 */
	public $cMb = 1;

	// Places a little gif-spacer in the bottom of the content frame
	/**
	 * @todo Define visibility
	 */
	public $contentW = 0;

	/**
	 * @todo Define visibility
	 */
	public $tableParams = 'border="0" cellspacing="0" cellpadding="0"';

	/**
	 * Wrapping internal vars ->tm, ->lm, ->rm, ->bm and ->content in a table where each content part is stored in a cell.
	 * The two arguments to this function defines some offsets and margins to use in the arrangement of the content in the table.
	 *
	 * @param string $offset List of offset parameters; x,y
	 * @param string $cMargins List of margin parameters; left, top, right, bottom
	 * @return string The content strings wrapped in a <table> as the parameters defined
	 * @see tslib_cObj::CTABLE()
	 * @todo Define visibility
	 */
	public function start($offset, $cMargins) {
		$offArr = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $offset);
		$cMargArr = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $cMargins);
		$cols = 0;
		$rows = 0;
		if ($this->lm) {
			$cols++;
		}
		if ($this->rm) {
			$cols++;
		}
		if ($cMargArr[0]) {
			$cols++;
		}
		if ($cMargArr[2]) {
			$cols++;
		}
		if ($cMargArr[1] || $cMargArr[3] || $this->tm || $this->bm || $this->content || $this->contentW) {
			$cols++;
		}
		if ($cMargArr[1]) {
			$rows++;
		}
		if ($cMargArr[3]) {
			$rows++;
		}
		if ($this->tm) {
			$rows++;
		}
		if ($this->bm) {
			$rows++;
		}
		if ($this->content) {
			$rows++;
		}
		if ($this->contentW) {
			$rows++;
		}
		if (!$rows && $cols) {
			// If there are no rows in the middle but still som columns...
			$rows = 1;
		}
		if ($rows && $cols) {
			$res = LF . '<table ' . $this->tableParams . '>';
			// Top offset:
			if ($offArr[1]) {
				$xoff = $offArr[0] ? 1 : 0;
				if ($cols + $xoff > 1) {
					$colspan = ' colspan="' . ($cols + $xoff) . '"';
				}
				$res .= '<tr><td' . $colspan . '><img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" width="1" height="' . $offArr[1] . '" alt="" title="" /></td></tr>';
			}
			// The rows:
			if ($rows > 1) {
				$rowspan = ' rowspan="' . $rows . '"';
			}
			$res .= '<tr>';
			if ($offArr[0]) {
				$res .= '<td' . $rowspan . '><img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" height="1" width="' . $offArr[0] . '" alt="" title="" /></td>';
			}
			if ($this->lm) {
				$res .= '<td' . $rowspan . ' ' . $this->lmTDparams . '>' . $this->lm . '</td>';
			}
			if ($cMargArr[0]) {
				$res .= '<td' . $rowspan . '><img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" height="1" width="' . $cMargArr[0] . '" alt="" title="" /></td>';
			}
			// Content...
			$middle = array();
			if ($this->tm) {
				$middle[] = '<td ' . $this->tmTDparams . '>' . $this->tm . '</td>';
			}
			if ($cMargArr[1]) {
				$middle[] = '<td><img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" width="1" height="' . $cMargArr[1] . '" alt="" title="" /></td>';
			}
			if ($this->content) {
				$middle[] = '<td ' . $this->contentTDparams . '>' . $this->content . '</td>';
			}
			if ($cMargArr[3]) {
				$middle[] = '<td><img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" width="1" height="' . $cMargArr[3] . '" alt="" title="" /></td>';
			}
			if ($this->bm) {
				$middle[] = '<td ' . $this->bmTDparams . '>' . $this->bm . '</td>';
			}
			if ($this->contentW) {
				$middle[] = '<td><img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" height="1" width="' . $this->contentW . '" alt="" title="" /></td>';
			}
			if (isset($middle[0])) {
				$res .= $middle[0];
			}
			// Left of content
			if ($cMargArr[2]) {
				$res .= '<td' . $rowspan . '><img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" height="1" width="' . $cMargArr[2] . '" alt="" title="" /></td>';
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


?>