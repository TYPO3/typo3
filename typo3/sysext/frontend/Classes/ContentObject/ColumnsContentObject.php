<?php
namespace TYPO3\CMS\Frontend\ContentObject;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Xavier Perseguers <typo3@perseguers.ch>
 *  (c) 2010-2013 Steffen Kamper <steffen@typo3.org>
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
 * Contains COLUMNS class object.
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 */
class ColumnsContentObject extends \TYPO3\CMS\Frontend\ContentObject\AbstractContentObject {

	/**
	 * Rendering the cObject, COLUMNS
	 *
	 * @param array $conf Array of TypoScript properties
	 * @return string Output
	 */
	public function render($conf = array()) {
		$content = '';
		if (is_array($conf) && $this->cObj->checkIf($conf['if.'])) {
			$tdRowCount = 0;
			$tableParams = isset($conf['tableParams.']) ? $this->cObj->stdWrap($conf['tableParams'], $conf['tableParams.']) : $conf['tableParams'];
			$tableParams = $tableParams ? ' ' . $tableParams : ' border="0" cellspacing="0" cellpadding="0"';
			$TDParams = isset($conf['TDParams.']) ? $this->cObj->stdWrap($conf['TDParams'], $conf['TDParams.']) : $conf['TDParams'];
			$TDparams = $TDparams ? ' ' . $TDparams : ' valign="top"';
			$rows = isset($conf['rows.']) ? $this->cObj->stdWrap($conf['rows'], $conf['rows.']) : $conf['rows'];
			$rows = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($rows, 2, 20);
			$totalWidth = isset($conf['totalWidth.']) ? intval($this->cObj->stdWrap($conf['totalWidth'], $conf['totalWidth.'])) : intval($conf['totalWidth']);
			$columnWidth = 0;
			$totalGapWidth = 0;
			$gapData = array(
				'gapWidth' => isset($conf['gapWidth.']) ? $this->cObj->stdWrap($conf['gapWidth'], $conf['gapWidth.']) : $conf['gapWidth'],
				'gapBgCol' => isset($conf['gapBgCol.']) ? $this->cObj->stdWrap($conf['gapBgCol'], $conf['gapBgCol.']) : $conf['gapBgCol'],
				'gapLineThickness' => isset($conf['gapLineThickness.']) ? $this->cObj->stdWrap($conf['gapLineThickness'], $conf['gapLineThickness.']) : $conf['gapLineThickness'],
				'gapLineCol' => isset($conf['gapLineCol.']) ? $this->cObj->stdWrap($conf['gapLineCol'], $conf['gapLineCol.']) : $conf['gapLineCol']
			);
			$gapData = $GLOBALS['TSFE']->tmpl->splitConfArray($gapData, $rows - 1);
			foreach ($gapData as $val) {
				$totalGapWidth += intval($val['gapWidth']);
			}
			if ($totalWidth) {
				$columnWidth = ceil(($totalWidth - $totalGapWidth) / $rows);
				$TDparams .= ' width="' . $columnWidth . '"';
				$tableParams .= ' width="' . $totalWidth . '"';
			} else {
				$TDparams .= ' width="' . floor(100 / $rows) . '%"';
				$tableParams .= ' width="100%"';
			}
			for ($a = 1; $a <= $rows; $a++) {
				$tdRowCount++;
				$content .= '<td' . $TDparams . '>';
				$content .= $this->cObj->cObjGetSingle($conf[$a], $conf[$a . '.'], $a);
				$content .= '</td>';
				if ($a < $rows) {
					$gapConf = $gapData[$a - 1];
					$gapWidth = intval($gapConf['gapWidth']);
					if ($gapWidth) {
						$tdPar = $gapConf['gapBgCol'] ? ' bgcolor="' . $gapConf['gapBgCol'] . '"' : '';
						$gapLine = intval($gapConf['gapLineThickness']);
						if ($gapLine) {
							$gapSurround = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange(($gapWidth - $gapLine) / 2, 1, 1000);
							// right gap
							$content .= '<td' . $tdPar . '><img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" width="' . $gapSurround . '" height="1" alt="" title="" /></td>';
							$tdRowCount++;
							// line:
							$GtdPar = $gapConf['gapLineCol'] ? ' bgcolor="' . $gapConf['gapLineCol'] . '"' : ' bgcolor="black"';
							$content .= '<td' . $GtdPar . '><img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" width="' . $gapLine . '" height="1" alt="" title="" /></td>';
							$tdRowCount++;
							// left gap
							$content .= '<td' . $tdPar . '><img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" width="' . $gapSurround . '" height="1" alt="" title="" /></td>';
							$tdRowCount++;
						} else {
							$content .= '<td' . $tdPar . '><img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" width="' . $gapWidth . '" height="1" alt="" title="" /></td>';
							$tdRowCount++;
						}
					}
				}
			}
			$content = '<tr>' . $content . '</tr>';
			$content = '<table' . $tableParams . '>' . $content . '</table>';
			if ($conf['after'] || isset($conf['after.'])) {
				$content .= $this->cObj->cObjGetSingle($conf['after'], $conf['after.'], 'after');
			}
			if (isset($conf['stdWrap.'])) {
				$content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
			}
		}
		return $content;
	}

}


?>