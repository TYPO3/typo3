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
 * Rendering of tables for offset
 */
class OffsetTableContentObject extends \TYPO3\CMS\Frontend\ContentObject\AbstractContentObject {
	/**
	 * @todo Define visibility
	 */
	public $tableParams = 'border="0" cellspacing="0" cellpadding="0"';

	/**
	 * @todo Define visibility
	 */
	public $default_tableParams = 'border="0" cellspacing="0" cellpadding="0"';

	/**
	 * @todo Define visibility
	 */
	public $tdParams = ' width="99%" valign="top"';

	/**
	 * Override default constructor to make it possible to instantiate this
	 * class for rendering an offset table not in content object context
	 *
	 * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj
	 */
	public function __construct(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj = NULL) {
		if (!is_null($cObj)) {
			$this->cObj = $cObj;
			$this->fileFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();
		}
	}

	/**
	 * Rendering the cObject, OTABLE
	 *
	 * @param array $conf Array of TypoScript properties
	 * @return string Output
	 */
	public function render($conf = array()) {
		$tableParams = isset($conf['tableParams.']) ? $this->cObj->stdWrap($conf['tableParams'], $conf['tableParams.']) : $conf['tableParams'];
		if ($tableParams) {
			$this->tableParams = $tableParams;
		}
		$offset = isset($conf['offset.']) ? $this->cObj->stdWrap($conf['offset'], $conf['offset.']) : $conf['offset'];
		$content = $this->start($this->cObj->cObjGet($conf), $offset);
		if (isset($conf['stdWrap.'])) {
			$content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
		}
		return $content;
	}

	/**
	 * Wrapping the input content string in a table which will space it out from top/left/right/bottom
	 *
	 * @param string $content The HTML content string
	 * @param string $offset List of offset parameters; x,y,r,b,w,h
	 * @return string The HTML content string being wrapped in a <table> offsetting the content as the $offset parameters defined
	 * @todo Define visibility
	 */
	public function start($content, $offset) {
		$valPairs = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $offset . ',,,,,');
		if ($valPairs[0] || $valPairs[1] || $valPairs[2] || $valPairs[3] || $valPairs[4] || $valPairs[5]) {
			// If width is defined AND there has been no change to the default table params,
			// then extend them to a tablewidth of 1
			if ($valPairs[4] && $this->default_tableParams == $this->tableParams) {
				$this->tableParams .= ' width="1"';
			}
			// Init:
			$this->begin = LF . '<table ' . $this->tableParams . '>';
			$this->end = '</table>';
			$rows = array();
			$widthImg = '';
			$heightImg = '';
			// If width is required, set so bottom column will display for sure
			if ($valPairs[4]) {
				if (!$valPairs[3]) {
					$valPairs[3] = 1;
				}
				$widthImg = '<img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" width="' . $valPairs[4] . '" height="1" alt="" title="" />';
			}
			// If height is required, set so right column will display for sure
			if ($valPairs[5]) {
				if (!$valPairs[2]) {
					$valPairs[2] = 1;
				}
				$valPairs[2] = 1;
				$heightImg = '<img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" width="1" height="' . $valPairs[5] . '" alt="" title="" />';
			}
			// First row:
			// top
			if ($valPairs[1]) {
				$rows[1] .= '<tr>';
				$rows[1] .= '<td><img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" width="' . ($valPairs[0] ? $valPairs[0] : 1) . '" height="' . $valPairs[1] . '" alt="" title="" /></td>';
				if ($valPairs[0]) {
					$rows[1] .= '<td></td>';
				}
				if ($valPairs[2]) {
					$rows[1] .= '<td></td>';
				}
				$rows[1] .= '</tr>';
			}
			// Middle row:
			$rows[2] .= '<tr>';
			if ($valPairs[0]) {
				$rows[2] .= $valPairs[1] ? '<td></td>' : '<td><img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" width="' . $valPairs[0] . '" height="1" alt="" title="" /></td>';
			}
			$rows[2] .= '<td' . $this->tdParams . '>' . $content . '</td>';
			if ($valPairs[2]) {
				$rows[2] .= $valPairs[3] ? '<td>' . $heightImg . '</td>' : '<td><img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" width="' . $valPairs[2] . '" height="' . ($valPairs[5] ? $valPairs[5] : 1) . '" alt="" title="" /></td>';
			}
			$rows[2] .= '</tr>';
			// Bottom row:
			if ($valPairs[3]) {
				$rows[3] .= '<tr>';
				if ($valPairs[0]) {
					$rows[3] .= '<td></td>';
				}
				if ($valPairs[2]) {
					$rows[3] .= '<td>' . $widthImg . '</td>';
				}
				$rows[3] .= '<td><img src="' . $GLOBALS['TSFE']->absRefPrefix . 'clear.gif" width="' . ($valPairs[2] ? $valPairs[2] : ($valPairs[4] ? $valPairs[4] : 1)) . '" height="' . $valPairs[3] . '" alt="" title="" /></td>';
				$rows[3] .= '</tr>';
			}
			return $this->begin . implode('', $rows) . $this->end;
		} else {
			return $content;
		}
	}

}


?>