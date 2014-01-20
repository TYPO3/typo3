<?php
namespace TYPO3\CMS\Frontend\ContentObject;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
/**
 * Contains COLUMNS class object.
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 */
class ColumnsContentObject extends AbstractContentObject {

	/**
	 * Rendering the cObject, COLUMNS
	 *
	 * @param array $conf Array of TypoScript properties
	 * @return string Output
	 */
	public function render($conf = array()) {
		if (empty($conf) || !empty($conf['if.']) && !$this->cObj->checkIf($conf['if.'])) {
			return '';
		}

		$content = '';

		$tdRowCount = 0;
		$tableParams = isset($conf['tableParams.']) ? $this->cObj->stdWrap($conf['tableParams'], $conf['tableParams.']) : $conf['tableParams'];
		$tableParams = $tableParams ? ' ' . $tableParams : ' border="0" cellspacing="0" cellpadding="0"';
		$TDparams = isset($conf['TDParams.']) ? $this->cObj->stdWrap($conf['TDParams'], $conf['TDParams.']) : $conf['TDParams'];
		$TDparams = $TDparams ? ' ' . $TDparams : ' valign="top"';
		$rows = isset($conf['rows.']) ? $this->cObj->stdWrap($conf['rows'], $conf['rows.']) : $conf['rows'];
		$rows = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($rows, 2, 20);
		$totalWidth = isset($conf['totalWidth.']) ? (int)$this->cObj->stdWrap($conf['totalWidth'], $conf['totalWidth.']) : (int)$conf['totalWidth'];
		$totalGapWidth = 0;
		$gapData = array(
			'gapWidth' => isset($conf['gapWidth.']) ? $this->cObj->stdWrap($conf['gapWidth'], $conf['gapWidth.']) : $conf['gapWidth'],
			'gapBgCol' => isset($conf['gapBgCol.']) ? $this->cObj->stdWrap($conf['gapBgCol'], $conf['gapBgCol.']) : $conf['gapBgCol'],
			'gapLineThickness' => isset($conf['gapLineThickness.']) ? $this->cObj->stdWrap($conf['gapLineThickness'], $conf['gapLineThickness.']) : $conf['gapLineThickness'],
			'gapLineCol' => isset($conf['gapLineCol.']) ? $this->cObj->stdWrap($conf['gapLineCol'], $conf['gapLineCol.']) : $conf['gapLineCol']
		);
		$gapData = $GLOBALS['TSFE']->tmpl->splitConfArray($gapData, $rows - 1);
		foreach ($gapData as $val) {
			$totalGapWidth += (int)$val['gapWidth'];
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
				$gapWidth = (int)$gapConf['gapWidth'];
				if ($gapWidth) {
					$tdPar = $gapConf['gapBgCol'] ? ' bgcolor="' . $gapConf['gapBgCol'] . '"' : '';
					$gapLine = (int)$gapConf['gapLineThickness'];
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
		return $content;
	}

}
